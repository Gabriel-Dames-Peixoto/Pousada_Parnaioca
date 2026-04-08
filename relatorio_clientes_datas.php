<?php
session_start();
include_once './conexao.php';
include_once './validar.php';



$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim']    ?? '';
$resultados  = [];
$total_registros = 0;

if ($data_inicio && $data_fim) {
    $stmt = $con->prepare("
        SELECT 
            c.id, c.nome, c.cpf, c.email, c.telefone,
            c.cidade, c.estado, c.status,
            COUNT(r.id)          AS total_reservas,
            MIN(r.data_checkin)  AS primeira_estadia,
            MAX(r.data_checkin)  AS ultima_estadia
        FROM clientes c
        LEFT JOIN reservas r ON r.cliente_id = c.id
            AND r.data_checkin BETWEEN ? AND ?
        WHERE c.id IN (
            SELECT DISTINCT cliente_id FROM reservas
            WHERE data_checkin BETWEEN ? AND ?
        )
        GROUP BY c.id
        ORDER BY c.nome ASC
    ");
    $stmt->bind_param("ssss", $data_inicio, $data_fim, $data_inicio, $data_fim);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $resultados[] = $row;
    }
    $total_registros = count($resultados);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Relatório — Clientes por Período</title>
    <style>
        .relatorio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 10px;
        }

        .badge-total {
            background: #2c3e50;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .filter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
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

        .status-ativo {
            color: #27ae60;
            font-weight: bold;
        }

        .status-inativo {
            color: #e74c3c;
            font-weight: bold;
        }

        .empty-state {
            padding: 40px;
            color: #999;
            font-size: 1.1rem;
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
        }

        .btn-imprimir:hover {
            background: #219a52;
        }

        .btn-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #2c3e50;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .btn-dashboard:hover {
            background: #1a252f;
            color: white;
            text-decoration: none;
        }

        @media print {

            header,
            .filter-card,
            .btn-imprimir,
            .btn-dashboard,
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
        <div class="relatorio-header">
            <h1>📋 Clientes por Período</h1>
            <?php if ($total_registros > 0): ?>
                <div style="display:flex; gap:10px; align-items:center;">
                    <span class="badge-total"><?= $total_registros ?> cliente(s) encontrado(s)</span>
                    <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botão voltar ao dashboard -->
        <a href="dashboard.php" class="btn-dashboard">← Voltar ao Dashboard</a>

        <div class="filter-card">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="data_inicio">Data de check-in inicial:</label>
                    <input type="date" id="data_inicio" name="data_inicio"
                        value="<?= htmlspecialchars($data_inicio) ?>" required>
                </div>
                <div class="form-group">
                    <label for="data_fim">Data de check-in final:</label>
                    <input type="date" id="data_fim" name="data_fim"
                        value="<?= htmlspecialchars($data_fim) ?>" required>
                </div>
                <button type="submit">🔍 Buscar</button>
            </form>
        </div>

        <?php if ($data_inicio && $data_fim): ?>
            <?php if ($total_registros > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Cidade/UF</th>
                                <th>Estadias no período</th>
                                <th>Primeira estadia</th>
                                <th>Última estadia</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultados as $row): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['nome']) ?></td>
                                    <td><?= htmlspecialchars($row['cpf']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['telefone']) ?></td>
                                    <td><?= htmlspecialchars($row['cidade']) ?>/<?= htmlspecialchars($row['estado']) ?></td>
                                    <td><?= $row['total_reservas'] ?></td>
                                    <td><?= $row['primeira_estadia'] ? date('d/m/Y', strtotime($row['primeira_estadia'])) : '—' ?></td>
                                    <td><?= $row['ultima_estadia']  ? date('d/m/Y', strtotime($row['ultima_estadia']))  : '—' ?></td>
                                    <td class="<?= $row['status'] == 1 ? 'status-ativo' : 'status-inativo' ?>">
                                        <?= $row['status'] == 1 ? '🟢 Ativo' : '🔴 Inativo' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">😕 Nenhum cliente encontrado com estadias entre
                    <strong><?= date('d/m/Y', strtotime($data_inicio)) ?></strong> e
                    <strong><?= date('d/m/Y', strtotime($data_fim)) ?></strong>.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="empty-state">👆 Selecione um período acima para gerar o relatório.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>