<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

// 1. CAPTURA O ID DO ITEM QUE VEM DA URL (vindo do informações_quarto.php)
$item_id = $_GET['id'] ?? null;
$dados_item = null;

if ($item_id) {
    // Busca os dados atuais do item selecionado
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

// Busca o nome do quarto para exibição
$quarto_id = $dados_item['quarto_id'];
$resQuarto = $con->query("SELECT quarto FROM quartos WHERE id = " . intval($quarto_id));
$quarto_data = $resQuarto->fetch_assoc();
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
            <ul><?php include_once 'Menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Editar Item do Frigobar</h1>

        <form action="" method="post">
            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
            <input type="hidden" name="quarto_id" value="<?php echo $quarto_id; ?>">

            <label>Quarto:</label>
            <input type="text" value="<?php echo htmlspecialchars($quarto_data['quarto']); ?>" disabled><br><br>

            <label for="nome">Nome do Item:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($dados_item['nome']); ?>" required><br><br>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" value="<?php echo $dados_item['quantidade']; ?>" required><br><br>

            <label for="valor">Valor (R$):</label>
            <input type="text" id="valor" name="valor" value="<?php echo number_format($dados_item['valor'], 2, ',', ''); ?>" required><br><br>

            <label for="status">Status do Item:</label>
            <select name="status" id="status">
                <option value="1" <?php echo ($dados_item['status'] == 1) ? 'selected' : ''; ?>>Ativo (Disponível)</option>
                <option value="0" <?php echo ($dados_item['status'] == 0) ? 'selected' : ''; ?>>Inativo (Indisponível)</option>
            </select><br><br>

            <input type="submit" value="Salvar Alterações">
            <input type="button" value="Cancelar" onclick="window.location.href='informacoes_quarto.php?id=<?php echo $quarto_id; ?>'">
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_edit = $_POST['item_id'];
            $nome = trim($_POST['nome']);
            $quantidade = intval($_POST['quantidade']);

            // Trata o valor (converte virgula em ponto)
            $valor_bruto = str_replace(',', '.', trim($_POST['valor']));
            $valor_final = floatval($valor_bruto);

            if (!empty($nome) && $valor_final > 0) {
                // LÓGICA DE UPDATE
                $query = "UPDATE frigobar SET nome=?, quantidade=?, valor=?, status=? WHERE id=?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("sidii", $nome, $quantidade, $valor_final, $_POST['status'], $id_edit);

                if ($stmt->execute()) {
                    registrarLog("O item $nome do frigobar do quarto $resquarto alterado por " . $_SESSION['login'], "UPDATE");
                    echo "<p style='color: green;'>Item atualizado com sucesso!</p>";
                    // Redireciona de volta após 2 segundos
                    echo "<script>setTimeout(function(){ window.location.href='informacoes_quarto.php?id=$quarto_id'; }, 2000);</script>";
                } else {
                    echo "<p style='color: red;'>Erro ao atualizar: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
        }
        ?>
    </main>
</body>

</html>