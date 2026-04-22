<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

exigirAdm();

$busca      = trim($_GET['busca']  ?? '');
$acao       = trim($_GET['acao']   ?? '');
$data_ini   = trim($_GET['data_ini'] ?? '');
$data_fim   = trim($_GET['data_fim'] ?? '');
$por_pagina = 50;
$pagina     = max(1, (int)($_GET['pag'] ?? 1));
$offset     = ($pagina - 1) * $por_pagina;

// Monta query com filtros opcionais
$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($busca !== '') {
    $where .= " AND mensagem LIKE ?";
    $params[] = "%{$busca}%";
    $types   .= 's';
}

if ($acao !== '') {
    $where .= " AND acao = ?";
    $params[] = $acao;
    $types   .= 's';
}

if ($data_ini !== '') {
    $where .= " AND DATE(data_hora) >= ?";
    $params[] = $data_ini;
    $types   .= 's';
}

if ($data_fim !== '') {
    $where .= " AND DATE(data_hora) <= ?";
    $params[] = $data_fim;
    $types   .= 's';
}

// Total de registros para paginação
$stmt_count = $con->prepare("SELECT COUNT(*) FROM logs_sistema $where");
if (!empty($params)) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$stmt_count->bind_result($total_registros);
$stmt_count->fetch();
$stmt_count->close();

$total_paginas = (int)ceil($total_registros / $por_pagina);

// Registros da página atual
$params_pag   = $params;
$types_pag    = $types . 'ii';
$params_pag[] = $por_pagina;
$params_pag[] = $offset;

$stmt = $con->prepare("SELECT id, data_hora, acao, mensagem FROM logs_sistema $where ORDER BY data_hora DESC LIMIT ? OFFSET ?");
if (!empty($params_pag)) $stmt->bind_param($types_pag, ...$params_pag);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lista de ações distintas para o filtro
$acoes_res = $con->query("SELECT DISTINCT acao FROM logs_sistema WHERE acao IS NOT NULL ORDER BY acao ASC");
$acoes_lista = $acoes_res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Logs do Sistema — Pousada Parnaioca</title>
    <style>
        .filter-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 25px;
        }

        .filter-card form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin: 0;
            max-width: 100%;
            text-align: left;
        }

        .filter-card .form-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-card label {
            font-size: 0.82rem;
            font-weight: bold;
            color: #555;
        }

        .filter-card input,
        .filter-card select {
            width: auto;
            padding: 7px 10px;
            margin: 0;
            font-size: 0.88rem;
        }

        .filter-card button {
            width: auto;
            padding: 8px 18px;
            font-size: 0.88rem;
        }

        .badge-total {
            background: #2c3e50;
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }

        .acao-insert {
            background: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .acao-update {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .acao-delete {
            background: #f8d7da;
            color: #721c24;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .acao-outros {
            background: #e2e3e5;
            color: #383d41;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .paginacao {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .paginacao a,
        .paginacao span {
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.85rem;
            text-decoration: none;
            color: #2c3e50;
        }

        .paginacao .atual {
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }

        .paginacao a:hover {
            background: #f0f0f0;
            text-decoration: none;
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
            .btn-dashboard,
            .paginacao,
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
            <ul><?php include_once 'menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>📋 Logs do Sistema</h1>

        <a href="dashboard.php" class="btn-dashboard">← Voltar ao Dashboard</a>

        <div class="filter-card">
            <form method="GET">
                <div class="form-group">
                    <label>Buscar mensagem:</label>
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="ex: admin, quarto...">
                </div>
                <div class="form-group">
                    <label>Ação:</label>
                    <select name="acao">
                        <option value="">Todas</option>
                        <?php foreach ($acoes_lista as $a): ?>
                            <option value="<?= htmlspecialchars($a['acao']) ?>" <?= $acao === $a['acao'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['acao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>De:</label>
                    <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>">
                </div>
                <div class="form-group">
                    <label>Até:</label>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
                </div>
                <button type="submit">🔍 Filtrar</button>
                <a href="logs_sistema.php" style="font-size:0.85rem; padding: 8px 12px; background:#e0e0e0; border-radius:6px; color:#555; text-decoration:none;">✕ Limpar</a>
            </form>
        </div>

        <span class="badge-total"><?= number_format($total_registros, 0, ',', '.') ?> registro(s) encontrado(s)</span>

        <?php if (!empty($logs)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data/Hora</th>
                            <th>Ação</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
                            $acao_lower = strtolower($log['acao'] ?? '');
                            if ($acao_lower === 'insert') {
                                $classe_acao = 'acao-insert';
                            } elseif ($acao_lower === 'update') {
                                $classe_acao = 'acao-update';
                            } elseif ($acao_lower === 'delete') {
                                $classe_acao = 'acao-delete';
                            } else {
                                $classe_acao = 'acao-outros';
                            }
                        ?>
                            <tr>
                                <td><?= $log['id'] ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                                <td><span class="<?= $classe_acao ?>"><?= htmlspecialchars($log['acao'] ?? '—') ?></span></td>
                                <td style="text-align:left;"><?= htmlspecialchars($log['mensagem']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1):
                // Monta query string sem pag
                $qs = http_build_query(array_filter([
                    'busca'    => $busca,
                    'acao'     => $acao,
                    'data_ini' => $data_ini,
                    'data_fim' => $data_fim,
                ]));
                $qs = $qs ? $qs . '&' : '';
            ?>
                <div class="paginacao">
                    <?php if ($pagina > 1): ?>
                        <a href="?<?= $qs ?>pag=<?= $pagina - 1 ?>">‹ Anterior</a>
                    <?php endif; ?>

                    <?php for ($p = max(1, $pagina - 3); $p <= min($total_paginas, $pagina + 3); $p++): ?>
                        <?php if ($p === $pagina): ?>
                            <span class="atual"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?<?= $qs ?>pag=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?<?= $qs ?>pag=<?= $pagina + 1 ?>">Próxima ›</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p style="color:#999; padding: 30px 0;">Nenhum log encontrado para os filtros informados.</p>
        <?php endif; ?>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>