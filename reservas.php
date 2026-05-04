<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

$dataSelecionada = $_GET['data'] ?? date('Y-m-d');
$dataObj = DateTimeImmutable::createFromFormat('Y-m-d', $dataSelecionada) ?: new DateTimeImmutable('today');
$dataSelecionada = $dataObj->format('Y-m-d');

$mesAtual = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)$dataObj->format('n');
$anoAtual = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)$dataObj->format('Y');
if ($mesAtual < 1 || $mesAtual > 12) {
    $mesAtual = (int)$dataObj->format('n');
}
if ($anoAtual < 2000 || $anoAtual > 2100) {
    $anoAtual = (int)$dataObj->format('Y');
}

$primeiroDiaMes = new DateTimeImmutable(sprintf('%04d-%02d-01', $anoAtual, $mesAtual));
$ultimoDiaMes = $primeiroDiaMes->modify('last day of this month');
$inicioCalendario = $primeiroDiaMes->modify('-' . $primeiroDiaMes->format('w') . ' days');
$fimCalendario = $ultimoDiaMes->modify('+' . (6 - (int)$ultimoDiaMes->format('w')) . ' days');

$mesAnterior = $primeiroDiaMes->modify('-1 month');
$mesSeguinte = $primeiroDiaMes->modify('+1 month');

$stmtQuartos = $con->prepare("
    SELECT id, quarto, tipo, status
    FROM quartos
    ORDER BY quarto ASC
");
$stmtQuartos->execute();
$quartos = $stmtQuartos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtQuartos->close();

$stmtReservas = $con->prepare("
    SELECT
        r.id,
        r.quarto_id,
        r.cliente_id,
        r.usuario_id,
        r.data_checkin,
        r.hora_checkin,
        r.data_checkin_real,
        r.data_checkout,
        r.hora_checkout,
        r.status,
        q.quarto,
        c.nome AS cliente,
        COALESCE(u.login, 'Sem usuario') AS usuario
    FROM reservas r
    INNER JOIN quartos q ON q.id = r.quarto_id
    INNER JOIN clientes c ON c.id = r.cliente_id
    LEFT JOIN usuarios u ON u.id = r.usuario_id
    WHERE r.status = 'ativa'
    ORDER BY q.quarto ASC, r.data_checkin ASC, r.hora_checkin ASC
");
$stmtReservas->execute();
$reservasAtivas = $stmtReservas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtReservas->close();

$ocupacaoPorDia = [];
$reservasPorQuartoNoDia = [];
$checkinsNoDia = 0;
$checkoutsNoDia = 0;

foreach ($reservasAtivas as $reserva) {
    $inicio = new DateTimeImmutable($reserva['data_checkin']);
    $fim = new DateTimeImmutable($reserva['data_checkout']);
    $cursor = $inicio;

    while ($cursor <= $fim) {
        $chaveDia = $cursor->format('Y-m-d');
        if (!isset($ocupacaoPorDia[$chaveDia])) {
            $ocupacaoPorDia[$chaveDia] = [];
        }
        $ocupacaoPorDia[$chaveDia][$reserva['quarto_id']] = true;
        $cursor = $cursor->modify('+1 day');
    }

    if ($dataSelecionada >= $reserva['data_checkin'] && $dataSelecionada <= $reserva['data_checkout']) {
        $reservasPorQuartoNoDia[$reserva['quarto_id']][] = $reserva;
    }

    if ($reserva['data_checkin'] === $dataSelecionada) {
        $checkinsNoDia++;
    }
    if ($reserva['data_checkout'] === $dataSelecionada) {
        $checkoutsNoDia++;
    }
}

$totalQuartos = count($quartos);
$quartosOcupadosNoDia = count($ocupacaoPorDia[$dataSelecionada] ?? []);
$quartosLivresNoDia = max(0, $totalQuartos - $quartosOcupadosNoDia);
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Reservas</title>
    <style>
        .reservas-shell {
            display: grid;
            gap: 24px;
        }

        .reservas-toolbar,
        .agenda-card,
        .calendario-unico {
            background: #f8fafc;
            border: 1px solid #d9e2ec;
            border-radius: 14px;
            padding: 18px 20px;
            text-align: left;
        }

        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .toolbar-actions a {
            background: #2c3e50;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
        }

        .toolbar-actions a:hover {
            background: #1f2d3a;
            text-decoration: none;
        }

        .filtro-data-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: end;
            max-width: 420px;
            margin: 0;
        }

        .filtro-data-form button {
            width: auto;
            min-width: 120px;
        }

        .resumo-dia {
            display: grid;
            grid-template-columns: repeat(4, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .resumo-item {
            background: #fff;
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #d9e2ec;
        }

        .resumo-item strong {
            display: block;
            font-size: 1.4rem;
            color: #2c3e50;
        }

        .calendario-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .cal-nav {
            display: flex;
            gap: 8px;
        }

        .cal-nav a {
            background: #3498db;
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
        }

        .cal-nav a:hover {
            background: #2980b9;
            text-decoration: none;
        }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .cal-weekday,
        .cal-day {
            border-radius: 10px;
            padding: 10px;
            min-height: 88px;
        }

        .cal-weekday {
            background: #2c3e50;
            color: #fff;
            font-weight: bold;
            text-align: center;
            min-height: auto;
        }

        .cal-day {
            background: #fff;
            border: 1px solid #d9e2ec;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }

        .cal-day.outside {
            opacity: .45;
        }

        .cal-day.selected {
            border: 2px solid #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, .12);
        }

        .cal-day.busy {
            background: linear-gradient(180deg, #ffe0d2 0%, #fff5f0 100%);
            border-color: #f4b39a;
        }

        .cal-day.free {
            background: linear-gradient(180deg, #dbf7e8 0%, #f7fffb 100%);
            border-color: #9ed8b5;
        }

        .cal-day.past {
            background: linear-gradient(180deg, #e3e7eb 0%, #f3f4f6 100%);
            border-color: #c7d0d9;
        }

        .cal-day.past .cal-day-number,
        .cal-day.past .cal-day-meta {
            color: #7b8794;
        }

        .cal-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(44, 62, 80, .08);
        }

        .cal-day a {
            color: inherit;
            text-decoration: none;
            display: block;
            height: 100%;
        }

        .cal-day-number {
            font-weight: bold;
            color: #2c3e50;
        }

        .cal-day-meta {
            margin-top: 8px;
            font-size: .85rem;
            color: #52606d;
        }

        .agenda-lista {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .agenda-quarto {
            border: 1px solid #d9e2ec;
            background: #fff;
            border-radius: 12px;
            padding: 16px;
        }

        .agenda-topo {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 10px;
        }

        .quarto-status {
            font-size: .9rem;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 999px;
        }

        .status-livre {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-ocupado {
            background: #ffebee;
            color: #c62828;
        }

        .reserva-item {
            border-top: 1px solid #eef2f7;
            padding-top: 10px;
            margin-top: 10px;
        }

        .reserva-item:first-of-type {
            border-top: none;
            padding-top: 0;
            margin-top: 0;
        }

        .reserva-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 8px;
            font-size: .92rem;
            color: #52606d;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: bold;
        }

        .badge-pendente {
            background: #fff8e1;
            color: #8d6e00;
        }

        .badge-hospedado {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-checkout {
            background: #fce4ec;
            color: #ad1457;
        }

        .quarto-acoes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .quarto-acoes a {
            font-size: .92rem;
        }

        @media (max-width: 700px) {
            .resumo-dia {
                grid-template-columns: repeat(2, 1fr);
            }

            .cal-grid {
                gap: 6px;
            }

            .cal-weekday,
            .cal-day {
                min-height: 74px;
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <header>
        <nav>
            <ul><?php include_once 'Menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Reservas</h1>

        <div class="reservas-shell">
            <section class="reservas-toolbar">
                <form method="GET" class="filtro-data-form">
                    <div>
                        <label for="data">Ver reservas do dia</label>
                        <input type="date" id="data" name="data" value="<?= htmlspecialchars($dataSelecionada) ?>" required>
                        <input type="hidden" name="mes" value="<?= $mesAtual ?>">
                        <input type="hidden" name="ano" value="<?= $anoAtual ?>">
                    </div>
                    <button type="submit">Filtrar</button>
                </form>

                <div class="toolbar-actions">
                    <a href="reservas_checkin.php">Check-in</a>
                    <a href="reservas_estender.php">Estender</a>
                    <a href="reservas_cancelar.php">Cancelar</a>
                    <a href="reservas_finalizar.php">Finalizar</a>
                </div>

                <div class="resumo-dia">
                    <div class="resumo-item">
                        <strong><?= date('d/m/Y', strtotime($dataSelecionada)) ?></strong>
                        <span>Dia selecionado</span>
                    </div>
                    <div class="resumo-item">
                        <strong><?= $quartosOcupadosNoDia ?></strong>
                        <span>Quartos com reserva</span>
                    </div>
                    <div class="resumo-item">
                        <strong><?= $checkinsNoDia ?></strong>
                        <span>Check-ins previstos</span>
                    </div>
                    <div class="resumo-item">
                        <strong><?= $checkoutsNoDia ?></strong>
                        <span>Check-outs previstos</span>
                    </div>
                </div>
            </section>

            <section class="calendario-unico">
                <div class="calendario-header">
                    <div>
                        <h2><?= $meses[$mesAtual] . ' ' . $anoAtual ?></h2>
                        <p>Selecione um dia para ver a agenda organizada por quarto.</p>
                    </div>
                    <div class="cal-nav">
                        <a href="reservas.php?data=<?= htmlspecialchars($dataSelecionada) ?>&mes=<?= (int)$mesAnterior->format('n') ?>&ano=<?= (int)$mesAnterior->format('Y') ?>">Anterior</a>
                        <a href="reservas.php?data=<?= htmlspecialchars($dataSelecionada) ?>&mes=<?= (int)$mesSeguinte->format('n') ?>&ano=<?= (int)$mesSeguinte->format('Y') ?>">Próximo</a>
                    </div>
                </div>

                <div class="cal-grid">
                    <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'] as $diaSemana): ?>
                        <div class="cal-weekday"><?= $diaSemana ?></div>
                    <?php endforeach; ?>

                    <?php
                    $cursor = $inicioCalendario;
                    while ($cursor <= $fimCalendario):
                        $chaveDia = $cursor->format('Y-m-d');
                        $foraDoMes = $cursor->format('n') !== $primeiroDiaMes->format('n');
                        $selecionado = $chaveDia === $dataSelecionada;
                        $passado = $chaveDia < date('Y-m-d');
                        $ocupados = count($ocupacaoPorDia[$chaveDia] ?? []);
                        $classe = $ocupados > 0 ? 'busy' : 'free';
                        if ($passado) {
                            $classe .= ' past';
                        }
                        if ($foraDoMes) {
                            $classe .= ' outside';
                        }
                        if ($selecionado) {
                            $classe .= ' selected';
                        }
                    ?>
                        <div class="cal-day <?= $classe ?>">
                            <a href="reservas.php?data=<?= $chaveDia ?>&mes=<?= $mesAtual ?>&ano=<?= $anoAtual ?>">
                                <div class="cal-day-number"><?= $cursor->format('d') ?></div>
                                <div class="cal-day-meta">
                                    <?= $ocupados ?> reservado(s)<br>
                                    <?= max(0, $totalQuartos - $ocupados) ?> livre(s)
                                </div>
                            </a>
                        </div>
                    <?php
                        $cursor = $cursor->modify('+1 day');
                    endwhile;
                    ?>
                </div>
            </section>

            <section class="agenda-card">
                <h2>Agenda de <?= date('d/m/Y', strtotime($dataSelecionada)) ?></h2>
                <p>Reservas ativas organizadas por quarto para o dia selecionado.</p>

                <div class="agenda-lista">
                    <?php foreach ($quartos as $quarto): ?>
                        <?php
                        $reservasDoQuarto = $reservasPorQuartoNoDia[$quarto['id']] ?? [];
                        $ocupado = !empty($reservasDoQuarto);
                        ?>
                        <article class="agenda-quarto">
                            <div class="agenda-topo">
                                <div>
                                    <h3><?= htmlspecialchars($quarto['quarto']) ?></h3>
                                    <p><?= htmlspecialchars($quarto['tipo'] ?: 'Sem tipo definido') ?></p>
                                </div>
                                <span class="quarto-status <?= $ocupado ? 'status-ocupado' : 'status-livre' ?>">
                                    <?= $ocupado ? 'Reservado no dia' : 'Disponível no dia' ?>
                                </span>
                            </div>

                            <?php if ($ocupado): ?>
                                <?php foreach ($reservasDoQuarto as $reserva): ?>
                                    <?php
                                    $badges = [];
                                    if (empty($reserva['data_checkin_real']) && $reserva['data_checkin'] === $dataSelecionada) {
                                        $badges[] = '<span class="status-badge badge-pendente">Check-in pendente</span>';
                                    }
                                    if (!empty($reserva['data_checkin_real'])) {
                                        $badges[] = '<span class="status-badge badge-hospedado">Hospede hospedado</span>';
                                    }
                                    if ($reserva['data_checkout'] === $dataSelecionada) {
                                        $badges[] = '<span class="status-badge badge-checkout">Checkout no dia</span>';
                                    }
                                    ?>
                                    <div class="reserva-item">
                                        <strong>Reserva #<?= (int)$reserva['id'] ?> - <?= htmlspecialchars($reserva['cliente']) ?></strong>
                                        <?php if (!empty($badges)): ?>
                                            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;">
                                                <?= implode('', $badges) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="reserva-meta">
                                            <span>Período: <?= htmlspecialchars(date('d/m/Y', strtotime($reserva['data_checkin']))) ?> <?= htmlspecialchars($reserva['hora_checkin']) ?> até <?= htmlspecialchars(date('d/m/Y', strtotime($reserva['data_checkout']))) ?> <?= htmlspecialchars($reserva['hora_checkout']) ?></span>
                                            <span>Responsável: <?= htmlspecialchars($reserva['usuario']) ?></span>
                                            <span><?= !empty($reserva['data_checkin_real']) ? 'Check-in realizado em ' . htmlspecialchars($reserva['data_checkin_real']) : 'Check-in pendente' ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Nenhuma reserva ativa neste quarto para a data selecionada.</p>
                            <?php endif; ?>

                            <div class="quarto-acoes">
                                <?php if ($quarto['status'] === '1'): ?>
                                    <a href="reservas_cadastrar.php?id=<?= (int)$quarto['id'] ?>&data=<?= htmlspecialchars($dataSelecionada) ?>">Nova reserva neste quarto</a>
                                <?php else: ?>
                                    <span style="color:#999;">Quarto indisponível para novas reservas.</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>

</body>

</html>
