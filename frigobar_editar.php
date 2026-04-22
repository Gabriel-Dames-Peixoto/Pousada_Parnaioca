<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] != 1 || $_SESSION['perfil'] != 'adm') {
    header("Location: login.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

$item_id    = $_GET['id'] ?? null;
$dados_item = null;

if ($item_id) {
    $stmt_load = $con->prepare("SELECT * FROM frigobar WHERE id = ?");
    $stmt_load->bind_param("i", $item_id);
    $stmt_load->execute();
    $result = $stmt_load->get_result();

    if ($result->num_rows > 0) {
        $dados_item = $result->fetch_assoc();
    } else {
        die("Item não encontrado no banco de dados.");
    }
    $stmt_load->close();
} else {
    die("ID do item não fornecido.");
}

$quarto_id = $dados_item['quarto_id'];

// Busca nome do quarto — variável nomeada corretamente
$stmt_quarto = $con->prepare("SELECT quarto FROM quartos WHERE id = ?");
$stmt_quarto->bind_param("i", $quarto_id);
$stmt_quarto->execute();
$quarto_data = $stmt_quarto->get_result()->fetch_assoc();
$stmt_quarto->close();

$nome_quarto = $quarto_data['quarto'] ?? "ID $quarto_id";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Pousada Parnoica - Editar Item</title>
    <link rel="stylesheet" href="1.css">
</head>

<body>
    <header>
        <nav>
            <ul><?php include_once 'menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Editar Item do Frigobar</h1>

        <form action="" method="post">
            <input type="hidden" name="item_id" value="<?= $item_id ?>">
            <input type="hidden" name="quarto_id" value="<?= $quarto_id ?>">

            <label>Quarto:</label>
            <input type="text" value="<?= htmlspecialchars($nome_quarto) ?>" disabled><br><br>

            <label for="nome">Nome do Item:</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($dados_item['nome']) ?>" required><br><br>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" value="<?= $dados_item['quantidade'] ?>" required><br><br>

            <label for="valor">Valor (R$):</label>
            <input type="text" id="valor" name="valor" value="<?= number_format($dados_item['valor'], 2, ',', '') ?>" required><br><br>

            <label for="status">Status do Item:</label>
            <select name="status" id="status">
                <option value="1" <?= ($dados_item['status'] == 1) ? 'selected' : '' ?>>Ativo (Disponível)</option>
                <option value="0" <?= ($dados_item['status'] == 0) ? 'selected' : '' ?>>Inativo (Indisponível)</option>
            </select><br><br>

            <input type="submit" value="Salvar Alterações">
            <input type="button" value="Cancelar" onclick="window.location.href='quartos_detalhes.php?id=<?= $quarto_id ?>'">
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_edit    = (int)$_POST['item_id'];
            $nome       = trim($_POST['nome']);
            $quantidade = intval($_POST['quantidade']);
            $status_item = (int)$_POST['status'];

            $valor_bruto = str_replace(',', '.', trim($_POST['valor']));
            $valor_final = floatval($valor_bruto);

            if (!empty($nome) && $valor_final > 0) {
                $query = "UPDATE frigobar SET nome=?, quantidade=?, valor=?, status=? WHERE id=?";
                $stmt  = $con->prepare($query);
                $stmt->bind_param("sidii", $nome, $quantidade, $valor_final, $status_item, $id_edit);

                if ($stmt->execute()) {
                    // $nome_quarto já está definido corretamente acima
                    registrarLog(
                        "O item $nome do frigobar do quarto $nome_quarto foi alterado por " . $_SESSION['login'],
                        "UPDATE"
                    );
                    echo "<p style='color: green;'>Item atualizado com sucesso!</p>";
                    echo "<script>setTimeout(function(){ window.location.href='quartos_detalhes.php?id=$quarto_id'; }, 2000);</script>";
                } else {
                    echo "<p style='color: red;'>Erro ao atualizar: " . $stmt->error . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p style='color: red;'>Preencha todos os campos corretamente.</p>";
            }
        }
        ?>
    </main>
</body>

</html>