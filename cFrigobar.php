<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
    // Se não houver login na sessão, manda de volta para o index
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="1.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <?php
                    include_once 'Menu.php';
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Cadastro de itens do Frigobar</h1>
        <form action="" method="post">
            <label for="nome">Nome do Item:</label>
            <input type="text" id="nome" name="nome" required><br><br>

            <label for="valor">Valor (R$):</label>
            <input type="text" id="valor" name="valor" placeholder="0,00" required><br><br>

            <input type="submit" value="Gravar">
        </form>

        <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nome = trim($_POST['nome'] ?? '');
                $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));

                if (!empty($nome) && !empty($valor) && is_numeric($valor)) {
                    $query = "INSERT INTO frigobar (nome, valor) VALUES (?, ?)";
                    $stmt = $conexao->prepare($query);
                    $stmt->bind_param("sd", $nome, (float)$valor);

                    if ($stmt->execute()) {
                        echo "<p style='color: green;'>Item cadastrado com sucesso!</p>";
                    } else {
                        echo "<p style='color: red;'>Erro ao cadastrar item.</p>";
                    }
                    $stmt->close();
                } else {
                    echo "<p style='color: red;'>Dados inválidos.</p>";
                }
            }
        ?>
    </main>
</body>
</html>
