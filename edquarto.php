<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] != 1 || $_SESSION['perfil'] != 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado."));
    exit();
}

$mensagem = "";
$id_quarto = $_GET['id'] ?? null;
$dados_quarto = [];

// 🔥 PROCESSAR POST PRIMEIRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_quarto = $_POST['id_quarto'] ?? null;
    $quarto = $_POST['Quarto'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? '';
    $capacidade = $_POST['capacidade'] ?? '';
    $vagas = $_POST['vagas_estacionamento'] ?? '';
    $status = $_POST['Status'] ?? '';

    // 🔥 converter status corretamente
    if ($status === 'disponivel') {
        $status = '1';
    } else {
        $status = '0';
    }

    if ($id_quarto && $quarto && $tipo && $descricao && $preco && $status !== '') {

        $sql = "UPDATE quartos 
                SET quarto=?, tipo=?, preco=?, descricao=?, capacidade=?, vagas_estacionamento=?, status=? 
                WHERE id=?";

        $stmt = $con->prepare($sql);

        // 🔥 tipos corrigidos
        $stmt->bind_param(
            "ssdsissi",
            $quarto,
            $tipo,
            $preco,
            $descricao,
            $capacidade,
            $vagas,
            $status,
            $id_quarto
        );

        if ($stmt->execute()) {
            registrarLog("Dados do quarto $quarto foram atualizados por " . $_SESSION['login'], "UPDATE");  
            $mensagem = "<div style='color:green;'>✅ Quarto atualizado com sucesso!</div>";
            header("refresh:2;url=quartos.php");
        } else {
            $mensagem = "<div style='color:red;'>Erro: " . $stmt->error . "</div>";
        }

        $stmt->close();
    } else {
        $mensagem = "<div style='color:red;'>Preencha todos os campos.</div>";
    }
}

// 🔥 CARREGAR DADOS
if ($id_quarto) {

    $stmt = $con->prepare("SELECT * FROM quartos WHERE id = ?");
    $stmt->bind_param("i", $id_quarto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $dados_quarto = $result->fetch_assoc();
    } else {
        die("Quarto não encontrado.");
    }

    $stmt->close();
} else {
    die("ID não informado.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <title>Editar Quarto</title>
</head>

<body>

    <header>
        <nav>
            <ul>
                <?php include_once 'menu.php'; ?>
            </ul>
        </nav>
    </header>

    <main>

        <h1>Editar Quarto <?= htmlspecialchars($dados_quarto['quarto']) ?></h1>

        <?= $mensagem ?>

        <form method="post">

            <input type="hidden" name="id_quarto" value="<?= $id_quarto ?>">

            <label>Quarto:</label>
            <input type="text" name="Quarto" value="<?= htmlspecialchars($dados_quarto['quarto']) ?>" required><br><br>

            <label>Tipo:</label>
            <input type="text" name="tipo" value="<?= htmlspecialchars($dados_quarto['tipo']) ?>" required><br><br>

            <label>Descrição:</label>
            <textarea name="descricao" required><?= htmlspecialchars($dados_quarto['descricao']) ?></textarea><br><br>

            <label>Capacidade:</label>
            <input type="number" name="capacidade" value="<?= htmlspecialchars($dados_quarto['capacidade']) ?>" required><br><br>

            <label>Vagas estacionamento:</label>
            <input type="number" name="vagas_estacionamento" value="<?= htmlspecialchars($dados_quarto['vagas_estacionamento']) ?>" required><br><br>

            <label>Preço:</label>
            <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($dados_quarto['preco']) ?>" required><br><br>

            <label>Status:</label>
            <select name="Status" required>
                <option value="disponivel" <?= $dados_quarto['status'] == '1' ? 'selected' : '' ?>>Disponível</option>
                <option value="indisponivel" <?= $dados_quarto['status'] == '0' ? 'selected' : '' ?>>Indisponível</option>
            </select><br><br>

            <input type="submit" value="Gravar">

        </form>

        <br>
        <a href="quartos.php">Voltar</a>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

</body>

</html>