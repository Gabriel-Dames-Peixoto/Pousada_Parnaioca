<?php
session_start();
require_once './conexao.php'; 

if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    header("Location: index.php?erro=" . urlencode("Acesso negado."));
    exit();
}

$id_quarto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$busca = isset($_GET['busca_item']) ? "%" . $_GET['busca_item'] . "%" : "%";
$status_filter = $_GET['item_status'] ?? ''; 

if (!$id_quarto) {
    die("Nenhum quarto selecionado.");
}

$stmt_q = $con->prepare("SELECT quarto, preco, descricao FROM quartos WHERE id = ?");
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) {
    die("Quarto não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="1.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica - Detalhes</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <?php include_once 'Menu.php'; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="quarto-info">
            <h1>Quarto: <?= htmlspecialchars($dados_quarto['quarto']) ?></h1>
            <p><strong>Preço a partir de 5 noites:</strong> R$ <?= number_format($dados_quarto['preco'], 2, ',', '.') 
            . "<br>(Aberto a negociação dependendo da quantidade de dias, da temporada e da disponibilidade)<br>"?></p>
            <p><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($dados_quarto['descricao'])) ?></p>
        </section>

        <hr>

        <section class="frigobar-section">
            <h3>Itens do Frigobar</h3>
            
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="id" value="<?= $id_quarto ?>">
                
                <select name="item_status" onchange="this.form.submit()">
                    <option value="" <?= $status_filter === '' ? 'selected' : '' ?>>Todos os Status</option>
                    <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Ativos</option>
                    <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inativos</option>
                </select>

                <input type="text" name="busca_item" placeholder="Buscar item..." value="<?= htmlspecialchars($_GET['busca_item'] ?? '') ?>">
                <button type="submit">🔍</button>
                <?php if ($_SESSION['perfil'] === 'adm'): ?>
                    <button type="button" onclick="window.location.href='cFrigobar.php?id=<?= $id_quarto ?>'">Cadastrar Item</button>
                <?php endif; ?>
            </form>

            <?php
            $sql_f = "SELECT id, nome, quantidade, valor, status FROM frigobar WHERE quarto_id = ? AND nome LIKE ?";
            if ($status_filter !== '') { $sql_f .= " AND status = ?"; }

            $stmt_f = $con->prepare($sql_f);
            if ($status_filter !== '') {
                $stmt_f->bind_param("isi", $id_quarto, $busca, $status_filter);
            } else {
                $stmt_f->bind_param("is", $id_quarto, $busca);
            }
            $stmt_f->execute();
            $result = $stmt_f->get_result();

            if ($result->num_rows > 0): ?>
                <table border="1" cellpadding="5" style="width:100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Valor</th>
                            <th>Qtd</th>
                            <th>Status</th>
                            <?php if ($_SESSION['perfil'] === 'adm'): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $corStatus = ($row['status'] == 1) ? "green" : "#ff3d3d";
                            $textoStatus = ($row['status'] == 1) ? "Ativo" : "Inativo";
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nome']) ?></td>
                                <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['quantidade']) ?></td>
                                <td style="color: <?= $corStatus ?>; font-weight: bold;"><?= $textoStatus ?></td>
                                <?php if ($_SESSION['perfil'] === 'adm'): ?>
                                    <td>
                                        <a href="edFrigobar.php?id=<?= $row['id'] ?>">Editar</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum item encontrado no frigobar.</p>
            <?php endif; 
            $stmt_f->close(); ?>
        </section>

        <div class="actions-footer" style="margin-top: 20px;">
            <input type="button" value="Voltar para Quartos" onclick="window.location.href='quartos.php'">
        </div>
    </main>
</body>
</html>