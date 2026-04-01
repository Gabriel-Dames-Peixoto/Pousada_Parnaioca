<?php
session_start();
include_once './conexao.php';

// 🔒 Validação de acesso
if (
    !isset($_SESSION['login']) ||
    $_SESSION['status'] != 1 ||
    $_SESSION['perfil'] != 'adm'
) {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

// 📅 Filtros de data (com validação)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim    = $_GET['data_fim']    ?? date('Y-m-d');

// Garante formato válido
$data_inicio = date('Y-m-d', strtotime($data_inicio));
$data_fim    = date('Y-m-d', strtotime($data_fim));


// ── 1. Totais gerais no período ──────────────────────────────
$stmt = $con->prepare("
    SELECT
        COUNT(*) AS total_reservas,
        SUM(status = 'finalizada') AS finalizadas,
        SUM(status = 'cancelada')  AS canceladas,
        SUM(status = 'ativa')      AS ativas,
        COALESCE(SUM(CASE WHEN status = 'finalizada' THEN valor_total END), 0) AS receita_total,
        COALESCE(AVG(CASE WHEN status = 'finalizada' THEN valor_total END), 0) AS ticket_medio
    FROM reservas
    WHERE DATE(data_checkin) BETWEEN ? AND ?
");

$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$totais = $stmt->get_result()->fetch_assoc();
$stmt->close();


// ── 2. Receita por quarto ────────────────────────────────────
$stmt = $con->prepare("
    SELECT
        q.quarto,
        COUNT(r.id) AS reservas,
        COALESCE(SUM(r.valor_total), 0) AS receita,
        COALESCE(AVG(r.valor_total), 0) AS ticket_medio,
        COALESCE(SUM(DATEDIFF(r.data_checkout, r.data_checkin)), 0) AS total_diarias
    FROM reservas r
    INNER JOIN quartos q ON q.id = r.quarto_id
    WHERE r.status = 'finalizada'
      AND DATE(r.data_checkin) BETWEEN ? AND ?
    GROUP BY q.id, q.quarto
    ORDER BY receita DESC
");

$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$por_quarto = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// ── 3. Receita por mês (últimos 6 meses) ────────────────────
$stmt = $con->prepare("
    SELECT
        DATE_FORMAT(data_checkin, '%Y-%m') AS mes,
        DATE_FORMAT(data_checkin, '%m/%Y') AS mes_label,
        COUNT(*) AS reservas,
        COALESCE(SUM(valor_total), 0) AS receita
    FROM reservas
    WHERE status = 'finalizada'
      AND data_checkin >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes, mes_label
    ORDER BY mes ASC
");

$stmt->execute();
$por_mes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// ── 4. Top 5 consumo de frigobar no período ──────────────────
$stmt = $con->prepare("
    SELECT
        f.nome,
        SUM(cf.quantidade)   AS qtd_vendida,
        SUM(cf.valor_total)  AS receita_frigobar
    FROM consumo_frigobar cf
    JOIN frigobar f ON f.id = cf.frigobar_id
    JOIN reservas r ON r.id = cf.reserva_id
    WHERE r.data_checkin BETWEEN ? AND ?
    GROUP BY f.id
    ORDER BY qtd_vendida DESC
    LIMIT 5
");
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$top_frigobar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── 5. Reservas individuais do período ──────────────────────
$stmt = $con->prepare("
    SELECT
        r.id,
        q.quarto,
        c.nome      AS cliente,
        r.data_checkin,
        r.data_checkout,
        DATEDIFF(r.data_checkout, r.data_checkin) AS diarias,
        r.valor_total,
        r.status
    FROM reservas r
    JOIN quartos  q ON q.id = r.quarto_id
    JOIN clientes c ON c.id = r.cliente_id
    WHERE r.data_checkin BETWEEN ? AND ?
    ORDER BY r.data_checkin DESC
");
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$reservas_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Relatório Financeiro</title>
    <style>
        .cards-financeiros {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .card-fin {
            flex: 1;
            min-width: 150px;
            max-width: 210px;
            padding: 20px 15px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }

        .card-fin .valor {
            font-size: 1.7rem;
            font-weight: bold;
            display: block;
        }

        .card-fin .label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 4px;
            display: block;
        }

        .cf-receita {
            background: #27ae60;
        }

        .cf-ticket {
            background: #2980b9;
        }

        .cf-total {
            background: #2c3e50;
        }

        .cf-final {
            background: #8e44ad;
        }

        .cf-cancel {
            background: #e74c3c;
        }

        .cf-ativa {
            background: #f39c12;
        }

        .filter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 25px;
        }

        .filter-card form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin: 0;
            max-width: 100%;
            text-align: left;
        }

        .filter-card .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-card label {
            font-weight: bold;
            font-size: 0.85rem;
            color: #555;
        }

        .filter-card input[type="date"] {
            width: auto;
            padding: 8px 12px;
            margin: 0;
        }

        .filter-card button {
            width: auto;
            padding: 8px 20px;
        }

        .secao {
            margin-bottom: 35px;
        }

        .secao h2 {
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 6px;
            margin-bottom: 15px;
            text-align: left;
        }

        .status-finalizada {
            color: #27ae60;
            font-weight: bold;
        }

        .status-cancelada {
            color: #e74c3c;
            font-weight: bold;
        }

        .status-ativa {
            color: #f39c12;
            font-weight: bold;
        }

        .btn-imprimir {
            background: #27ae60;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
            float: right;
        }

        .btn-imprimir:hover {
            background: #219a52;
        }

        @media print {

            header,
            .filter-card,
            .btn-imprimir,
            footer {
                display: none;
            }

            main {
                box-shadow: none;
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
        <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir</button>
        <h1>💰 Relatório Financeiro</h1>
        <p style="color:#777; margin-bottom:20px;">
            Período: <strong><?= date('d/m/Y', strtotime($data_inicio)) ?></strong>
            até <strong><?= date('d/m/Y', strtotime($data_fim)) ?></strong>
        </p>

        <!-- Filtro -->
        <div class="filter-card">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Data inicial:</label>
                    <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" required>
                </div>
                <div class="form-group">
                    <label>Data final:</label>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" required>
                </div>
                <button type="submit">🔍 Gerar</button>
            </form>
        </div>

        <!-- Cards de resumo -->
        <div class="cards-financeiros">
            <div class="card-fin cf-receita">
                <span class="valor">R$ <?= number_format($totais['receita_total'], 2, ',', '.') ?></span>
                <span class="label">Receita Total</span>
            </div>
            <div class="card-fin cf-ticket">
                <span class="valor">R$ <?= number_format($totais['ticket_medio'], 2, ',', '.') ?></span>
                <span class="label">Ticket Médio</span>
            </div>
            <div class="card-fin cf-total">
                <span class="valor"><?= $totais['total_reservas'] ?></span>
                <span class="label">Total de Reservas</span>
            </div>
            <div class="card-fin cf-final">
                <span class="valor"><?= $totais['finalizadas'] ?></span>
                <span class="label">Finalizadas</span>
            </div>
            <div class="card-fin cf-cancel">
                <span class="valor"><?= $totais['canceladas'] ?></span>
                <span class="label">Canceladas</span>
            </div>
            <div class="card-fin cf-ativa">
                <span class="valor"><?= $totais['ativas'] ?></span>
                <span class="label">Em aberto</span>
            </div>
        </div>

        <!-- Receita por quarto -->
        <?php if (!empty($por_quarto)): ?>
            <div class="secao">
                <h2>🏨 Receita por Quarto</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Quarto</th>
                                <th>Reservas</th>
                                <th>Total de diárias</th>
                                <th>Ticket Médio</th>
                                <th>Receita Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($por_quarto as $q): ?>
                                <tr>
                                    <td><?= htmlspecialchars($q['quarto']) ?></td>
                                    <td><?= $q['reservas'] ?></td>
                                    <td><?= $q['total_diarias'] ?> diária(s)</td>
                                    <td>R$ <?= number_format($q['ticket_medio'], 2, ',', '.') ?></td>
                                    <td><strong>R$ <?= number_format($q['receita'], 2, ',', '.') ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Receita por mês -->
        <?php if (!empty($por_mes)): ?>
            <div class="secao">
                <h2>📅 Receita dos Últimos 6 Meses</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Reservas finalizadas</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($por_mes as $m): ?>
                                <tr>
                                    <td><?= $m['mes_label'] ?></td>
                                    <td><?= $m['reservas'] ?></td>
                                    <td><strong>R$ <?= number_format($m['receita'], 2, ',', '.') ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Top Frigobar -->
        <?php if (!empty($top_frigobar)): ?>
            <div class="secao">
                <h2>🍺 Top 5 Itens do Frigobar</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qtd. vendida</th>
                                <th>Receita gerada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_frigobar as $i => $f): ?>
                                <tr>
                                    <td><?= ($i + 1) ?>. <?= htmlspecialchars($f['nome']) ?></td>
                                    <td><?= $f['qtd_vendida'] ?></td>
                                    <td>R$ <?= number_format($f['receita_frigobar'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reservas individuais -->
        <?php if (!empty($reservas_lista)): ?>
            <div class="secao">
                <h2>📋 Detalhamento de Reservas no Período</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Quarto</th>
                                <th>Cliente</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Diárias</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas_lista as $r): ?>
                                <tr>
                                    <td><?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['quarto']) ?></td>
                                    <td><?= htmlspecialchars($r['cliente']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['data_checkin'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($r['data_checkout'])) ?></td>
                                    <td><?= $r['diarias'] ?></td>
                                    <td>R$ <?= number_format($r['valor_total'], 2, ',', '.') ?></td>
                                    <td class="status-<?= $r['status'] ?>">
                                        <?php
                                        $icones = ['finalizada' => '✅', 'cancelada' => '❌', 'ativa' => '🟡'];
                                        echo ($icones[$r['status']] ?? '') . ' ' . ucfirst($r['status']);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($reservas_lista)): ?>
            <p style="padding:30px; color:#999;">😕 Nenhuma reserva encontrada para o período selecionado.</p>
        <?php endif; ?>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>