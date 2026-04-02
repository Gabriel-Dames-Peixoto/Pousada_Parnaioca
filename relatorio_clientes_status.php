<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] != 1 || $_SESSION['perfil'] != 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

$filtro_status = $_GET['status'] ?? 'todos';
$busca         = trim($_GET['busca'] ?? '');

$sql = "
    SELECT 
        c.id, c.nome, c.cpf, c.email, c.telefone,
        c.cidade, c.estado, c.status,
        COUNT(r.id) AS total_reservas
    FROM clientes c
    LEFT JOIN reservas r ON r.cliente_id = c.id
    WHERE 1=1
";

$params = [];
$types  = '';

if ($filtro_status === 'ativos') {
    $sql .= " AND c.status = 1";
} elseif ($filtro_status === 'inativos') {
    $sql .= " AND c.status = 0";
}

if (!empty($busca)) {
    $sql .= " AND (c.nome LIKE ? OR c.cpf LIKE ? OR c.email LIKE ?)";
    $term = "%{$busca}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'sss';
}

$sql .= " GROUP BY c.id ORDER BY c.status DESC, c.nome ASC";

$stmt = $con->prepare($sql);
if (!$stmt) die("Erro na query: " . $con->error);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$clientes = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$res_totais = $con->query("SELECT COUNT(*) as total, SUM(status = 1) as ativos, SUM(status = 0) as inativos FROM clientes");
$totais = $res_totais->fetch_assoc();
$total_geral    = $totais['total']   ?? 0;
$total_ativos   = $totais['ativos']  ?? 0;
$total_inativos = $totais['inativos'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Relatório — Clientes Ativos/Inativos</title>

    <style>
        .cards-resumo {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .card-stat {
            flex: 1;
            min-width: 140px;
            max-width: 200px;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
            font-weight: bold;
        }

        .card-stat .numero {
            font-size: 2.2rem;
            display: block;
        }

        .card-stat .label {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .card-total {
            background: #2c3e50;
        }

        .card-ativo {
            background: #27ae60;
        }

        .card-inativo {
            background: #e74c3c;
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
        }

        .status-ativo {
            color: #27ae60;
            font-weight: bold;
        }

        .status-inativo {
            color: #e74c3c;
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
            float: right;
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
        <h1>👥 Clientes Ativos e Inativos</h1>

        <!-- Botão voltar ao dashboard -->
        <a href="dashboard.php" class="btn-dashboard">← Voltar ao Dashboard</a>

        <div class="cards-resumo">
            <div class="card-stat card-total">
                <span class="numero"><?= $total_geral ?></span>
                <span class="label">Total de Clientes</span>
            </div>
            <div class="card-stat card-ativo">
                <span class="numero"><?= $total_ativos ?></span>
                <span class="label">Ativos</span>
            </div>
            <div class="card-stat card-inativo">
                <span class="numero"><?= $total_inativos ?></span>
                <span class="label">Inativos</span>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET">
                <select name="status">
                    <option value="todos" <?= $filtro_status === 'todos'    ? 'selected' : '' ?>>Todos</option>
                    <option value="ativos" <?= $filtro_status === 'ativos'   ? 'selected' : '' ?>>Ativos</option>
                    <option value="inativos" <?= $filtro_status === 'inativos' ? 'selected' : '' ?>>Inativos</option>
                </select>
                <input type="text" name="busca"
                    placeholder="Nome, CPF ou e-mail"
                    value="<?= htmlspecialchars($busca) ?>">
                <button type="submit">Filtrar</button>
            </form>
        </div>

        <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir</button>

        <?php if (!empty($clientes)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Cidade/UF</th>
                        <th>Reservas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['nome']) ?></td>
                            <td><?= htmlspecialchars($c['cpf']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['telefone']) ?></td>
                            <td><?= htmlspecialchars($c['cidade']) ?>/<?= htmlspecialchars($c['estado']) ?></td>
                            <td><?= $c['total_reservas'] ?></td>
                            <td class="<?= $c['status'] == 1 ? 'status-ativo' : 'status-inativo' ?>">
                                <?= $c['status'] == 1 ? '🟢 Ativo' : '🔴 Inativo' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>😕 Nenhum cliente encontrado.</p>
        <?php endif; ?>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

</body>

</html>