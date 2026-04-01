<?php
session_start();
include_once './conexao.php';


if (!isset($_SESSION['login']) || $_SESSION['status'] != 1 || $_SESSION['perfil'] != 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

// ── 1. Quem está hospedado AGORA ────────────────────────────
$stmt = $con->prepare("
    SELECT
        r.id,
        q.quarto,
        c.nome       AS cliente,
        c.telefone,
        r.data_checkin,
        r.hora_checkin,
        r.data_checkout,
        r.hora_checkout,
        DATEDIFF(r.data_checkout, CURDATE()) AS dias_restantes
    FROM reservas r
    JOIN quartos  q ON q.id = r.quarto_id
    JOIN clientes c ON c.id = r.cliente_id
    WHERE r.status = 'ativa'
      AND CURDATE() BETWEEN r.data_checkin AND r.data_checkout
    ORDER BY r.data_checkout ASC
");
$stmt->execute();
$hospedados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── 2. Item do frigobar com maior saída (todos os tempos) ───
$stmt = $con->prepare("
    SELECT
        f.nome,
        SUM(cf.quantidade)  AS total_vendido,
        SUM(cf.valor_total) AS receita
    FROM consumo_frigobar cf
    JOIN frigobar f ON f.id = cf.frigobar_id
    GROUP BY f.id
    ORDER BY total_vendido DESC
    LIMIT 5
");
$stmt->execute();
$top_itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── 3. Quarto com maior rentabilidade (todos os tempos) ─────
$stmt = $con->prepare("
    SELECT
        q.quarto,
        COUNT(r.id)        AS total_reservas,
        SUM(r.valor_total) AS receita_total,
        AVG(r.valor_total) AS ticket_medio
    FROM reservas r
    JOIN quartos q ON q.id = r.quarto_id
    WHERE r.status = 'finalizada'
    GROUP BY q.id
    ORDER BY receita_total DESC
    LIMIT 5
");
$stmt->execute();
$top_quartos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── 4. Resumo rápido ────────────────────────────────────────
$stmt = $con->prepare("
    SELECT
        (SELECT COUNT(*) FROM clientes WHERE status = 1)           AS clientes_ativos,
        (SELECT COUNT(*) FROM quartos  WHERE status = '1')         AS quartos_ativos,
        (SELECT COUNT(*) FROM reservas WHERE status = 'ativa')     AS reservas_abertas,
        (SELECT COALESCE(SUM(valor_total),0) FROM reservas
            WHERE status = 'finalizada'
            AND MONTH(data_checkin) = MONTH(CURDATE())
            AND YEAR(data_checkin)  = YEAR(CURDATE()))             AS receita_mes
");
$stmt->execute();
$resumo = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Dashboard — Pousada Parnaioca</title>
    <style>
        /* ── Resumo rápido ── */
        .dash-cards {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 35px;
        }

        .dash-card {
            flex: 1;
            min-width: 160px;
            max-width: 220px;
            padding: 22px 15px;
            border-radius: 12px;
            text-align: center;
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .15);
        }

        .dash-card .numero {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }

        .dash-card .label {
            font-size: 0.8rem;
            opacity: .9;
            margin-top: 5px;
            display: block;
        }

        .dc-hospedados {
            background: linear-gradient(135deg, #2c3e50, #3d5a80);
        }

        .dc-reservas {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .dc-clientes {
            background: linear-gradient(135deg, #27ae60, #1e8449);
        }

        .dc-receita {
            background: linear-gradient(135deg, #8e44ad, #6c3483);
        }

        /* ── Grid de seções ── */
        .dash-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media(max-width:700px) {
            .dash-grid {
                grid-template-columns: 1fr;
            }
        }

        .dash-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .07);
        }

        .dash-section h2 {
            font-size: 1rem;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-align: left;
        }

        /* ── Hospedados ── */
        .hospedado-card {
            background: #f0f9f4;
            border-left: 4px solid #27ae60;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
            text-align: left;
        }

        .hospedado-card .nome {
            font-weight: bold;
            font-size: 1rem;
        }

        .hospedado-card .info {
            font-size: 0.85rem;
            color: #555;
            margin-top: 4px;
        }

        .hospedado-card .dias {
            float: right;
            background: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* ── Ranking bars ── */
        .rank-item {
            margin-bottom: 14px;
            text-align: left;
        }

        .rank-item .rank-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            margin-bottom: 4px;
        }

        .rank-item .rank-label .nome {
            font-weight: bold;
        }

        .rank-item .rank-label .valor {
            color: #888;
        }

        .rank-bar-bg {
            background: #eee;
            border-radius: 4px;
            height: 10px;
            overflow: hidden;
        }

        .rank-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #2c3e50, #3498db);
            transition: width .6s ease;
        }

        .rank-bar-fill.gold {
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }

        .rank-bar-fill.purple {
            background: linear-gradient(90deg, #8e44ad, #6c3483);
        }

        .empty-dash {
            color: #aaa;
            font-style: italic;
            font-size: 0.9rem;
            padding: 10px 0;
        }

        /* ── Seção hospedados (full width) ── */
        .dash-full {
            margin-bottom: 30px;
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
        <h1>📊 Dashboard</h1>
        <p style="color:#777; margin-bottom:25px;">
            Atualizado em <?= date('d/m/Y H:i:s'); ?>
        </p>

        <!-- Cards de resumo -->
        <div class="dash-cards">
            <div class="dash-card dc-hospedados">
                <span class="numero"><?= count($hospedados) ?></span>
                <span class="label">Hospedados agora</span>
            </div>
            <div class="dash-card dc-reservas">
                <span class="numero"><?= $resumo['reservas_abertas'] ?></span>
                <span class="label">Reservas em aberto</span>
            </div>
            <div class="dash-card dc-clientes">
                <span class="numero"><?= $resumo['clientes_ativos'] ?></span>
                <span class="label">Clientes ativos</span>
            </div>
            <div class="dash-card dc-receita">
                <span class="numero">R$ <?= number_format($resumo['receita_mes'], 0, ',', '.') ?></span>
                <span class="label">Receita no mês</span>
            </div>
        </div>

        <!-- Hospedados agora (full width) -->
        <div class="dash-section dash-full">
            <h2>🛏️ Quem está hospedado agora</h2>
            <?php if (!empty($hospedados)): ?>
                <?php foreach ($hospedados as $h): ?>
                    <div class="hospedado-card">
                        <span class="dias"><?= $h['dias_restantes'] > 0 ? $h['dias_restantes'] . ' dia(s)' : 'Sai hoje' ?></span>
                        <div class="nome">
                            <?= htmlspecialchars($h['cliente']) ?>
                            — <em><?= htmlspecialchars($h['quarto']) ?></em>
                        </div>
                        <div class="info">
                            📅 Check-in: <?= date('d/m/Y', strtotime($h['data_checkin'])) ?>
                            às <?= substr($h['hora_checkin'], 0, 5) ?>
                            &nbsp;|&nbsp;
                            Check-out: <?= date('d/m/Y', strtotime($h['data_checkout'])) ?>
                            às <?= substr($h['hora_checkout'], 0, 5) ?>
                            &nbsp;|&nbsp;
                            📞 <?= htmlspecialchars($h['telefone']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty-dash">Nenhum hóspede no momento.</p>
            <?php endif; ?>
        </div>

        <!-- Grid: Top itens + Top quartos -->
        <div class="dash-grid">

            <!-- Item com maior saída -->
            <div class="dash-section">
                <h2>🍺 Itens com maior saída</h2>
                <?php if (!empty($top_itens)):
                    $max = $top_itens[0]['total_vendido'];
                    foreach ($top_itens as $i => $item):
                        $pct = $max > 0 ? round(($item['total_vendido'] / $max) * 100) : 0;
                ?>
                        <div class="rank-item">
                            <div class="rank-label">
                                <span class="nome"><?= ($i + 1) ?>. <?= htmlspecialchars($item['nome']) ?></span>
                                <span class="valor"><?= $item['total_vendido'] ?> un. · R$ <?= number_format($item['receita'], 2, ',', '.') ?></span>
                            </div>
                            <div class="rank-bar-bg">
                                <div class="rank-bar-fill gold" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p class="empty-dash">Nenhum consumo registrado ainda.</p>
                <?php endif; ?>
            </div>

            <!-- Quarto com maior rentabilidade -->
            <div class="dash-section">
                <h2>🏆 Quartos mais rentáveis</h2>
                <?php if (!empty($top_quartos)):
                    $max = $top_quartos[0]['receita_total'];
                    foreach ($top_quartos as $i => $q):
                        $pct = $max > 0 ? round(($q['receita_total'] / $max) * 100) : 0;
                ?>
                        <div class="rank-item">
                            <div class="rank-label">
                                <span class="nome"><?= ($i + 1) ?>. <?= htmlspecialchars($q['quarto']) ?></span>
                                <span class="valor"><?= $q['total_reservas'] ?> res. · R$ <?= number_format($q['receita_total'], 2, ',', '.') ?></span>
                            </div>
                            <div class="rank-bar-bg">
                                <div class="rank-bar-fill purple" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p class="empty-dash">Nenhuma reserva finalizada ainda.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- Links rápidos para relatórios -->
        <div class="dash-section">
            <h2>🔗 Relatórios</h2>
            <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center; padding-top:8px;">
                <a href="relatorio_financeiro.php">
                    <button style="width:auto; padding:10px 20px;">💰 Relatório Financeiro</button>
                </a>
                <a href="relatorio_clientes_status.php">
                    <button style="width:auto; padding:10px 20px;">👥 Clientes Ativos/Inativos</button>
                </a>
                <a href="relatorio_clientes_datas.php">
                    <button style="width:auto; padding:10px 20px;">📋 Clientes por Período</button>
                </a>
            </div>
        </div>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>