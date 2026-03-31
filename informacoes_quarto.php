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


$stmt_q = $con->prepare("SELECT quarto, preco, descricao, capacidade, vagas_estacionamento FROM quartos WHERE id = ?");
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) {
    die("Quarto não encontrado.");
}


$stmt_frig = $con->prepare("SELECT status_frigobar FROM frigobar WHERE quarto_id = ? LIMIT 1");
$stmt_frig->bind_param("i", $id_quarto);
$stmt_frig->execute();
$dados_frig = $stmt_frig->get_result()->fetch_assoc();
$stmt_frig->close();

$status_frigobar = $dados_frig['status_frigobar'] ?? 1;


if (isset($_POST['toggle_frigobar']) && $_SESSION['perfil'] === 'adm') {

    $novo_status = ($status_frigobar == 1) ? 0 : 1;

    $stmt_update = $con->prepare("UPDATE frigobar SET status_frigobar = ? WHERE quarto_id = ?");
    $stmt_update->bind_param("ii", $novo_status, $id_quarto);
    $stmt_update->execute();
    $stmt_update->close();


    header("Location: informacoes_quarto.php?id=" . $id_quarto);
    exit();
}


$stmt_res = $con->prepare("SELECT data_checkin, data_checkout FROM reservas WHERE quarto_id = ? AND status = 'ativa' LIMIT 1");
$stmt_res->bind_param("i", $id_quarto);
$stmt_res->execute();
$dados_reserva = $stmt_res->get_result()->fetch_assoc();
$stmt_res->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Pousada Parnaioca - Detalhes</title>
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
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

            <p><strong>Preço:</strong> R$ <?= number_format($dados_quarto['preco'], 2, ',', '.') ?></p>

            <p><strong>Descrição:</strong><br>
                <?= nl2br(htmlspecialchars($dados_quarto['descricao'])) ?>
            </p>

            <p><strong>Capacidade:</strong> <?= $dados_quarto['capacidade'] ?> pessoas</p>
            <p><strong>Vagas:</strong> <?= $dados_quarto['vagas_estacionamento'] ?></p>

            <?php if ($dados_reserva): ?>
                <p style="background:#fff3cd;padding:10px;">
                    <strong>Reservado:</strong>
                    <?= date('d/m/Y', strtotime($dados_reserva['data_checkin'])) ?>
                    até
                    <?= date('d/m/Y', strtotime($dados_reserva['data_checkout'])) ?>
                </p>
            <?php else: ?>
                <p style="color:green;">✅ Disponível</p>
            <?php endif; ?>
        </section>

        <hr>

        <!-- ==========================
     FILTRO (GET)
========================== -->
        <section>

            <form method="GET">
                <input type="hidden" name="id" value="<?= $id_quarto ?>">

                <?php if ($_SESSION['perfil'] === 'adm'): ?>
                    <select name="item_status" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inativos</option>
                    </select>
                <?php endif; ?>

                <input type="text" name="busca_item" placeholder="Buscar item..."
                    value="<?= htmlspecialchars($_GET['busca_item'] ?? '') ?>">

                <button type="submit">🔍</button>

                <?php if ($_SESSION['perfil'] === 'adm'): ?>
                    <button type="button"
                        onclick="location.href='cFrigobar.php?id=<?= $id_quarto ?>'">
                        Cadastrar Item
                    </button>
                <?php endif; ?>
            </form>

            <?php if ($_SESSION['perfil'] === 'adm'): ?>
                <form method="POST" style="margin-top:10px;">
                    <button type="submit" name="toggle_frigobar">
                        <?= $status_frigobar == 1 ? 'Inativar Frigobar' : 'Ativar Frigobar' ?>
                    </button>
                </form>
            <?php endif; ?>

        </section>

        <section style="margin-top:20px;">

            <h3>Itens do Frigobar</h3>

            <?php if ($status_frigobar == 0): ?>
                <p style="color:red;">⚠️ Frigobar indisponível</p>
            <?php else: ?>

                <?php
                $sql = "SELECT id, nome, quantidade, valor, status 
        FROM frigobar 
        WHERE quarto_id = ? AND nome LIKE ?";

                if ($status_filter !== '') {
                    $sql .= " AND status = ?";
                }

                $stmt = $con->prepare($sql);

                if ($status_filter !== '') {
                    $stmt->bind_param("isi", $id_quarto, $busca, $status_filter);
                } else {
                    $stmt->bind_param("is", $id_quarto, $busca);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                ?>

                <?php if ($result->num_rows > 0): ?>
                    <table border="1" cellpadding="5" width="100%">
                        <tr>
                            <th>Item</th>
                            <th>Valor</th>
                            <th>Qtd</th>

                            <?php if ($_SESSION['perfil'] === 'adm'): ?>
                                <th>Status</th>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>

                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nome']) ?></td>
                                <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
                                <td><?= $row['quantidade'] ?></td>

                                <?php if ($_SESSION['perfil'] === 'adm'): ?>
                                    <td style="color:<?= $row['status'] ? 'green' : 'red' ?>">
                                        <?= $row['status'] ? 'Ativo' : 'Inativo' ?>
                                    </td>
                                    <td>
                                        <a href="edFrigobar.php?id=<?= $row['id'] ?>">Editar</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </table>

                <?php else: ?>
                    <p>Nenhum item encontrado.</p>
                <?php endif; ?>

                <?php $stmt->close(); ?>

            <?php endif; ?>

        </section>

        <!-- ==========================
     AÇÕES
========================== -->
        <div style="margin-top:20px;">
            <button onclick="location.href='quartos.php'">Voltar</button>
            <button onclick="location.href='Requarto.php?id=<?= $id_quarto ?>'">Reservar</button>
        </div>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

</body>

</html>