<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login'])) {
    // Se não houver login na sessão, manda de volta para o index
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Pegando os nomes corretos conforme o 'name' do input no HTML
    $numero = $_POST['Quarto'] ?? ''; 
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? '';

    if ($numero && $tipo && $descricao && $preco) {
        // 2. SQL ajustado
        $sql = "INSERT INTO quartos (quarto, tipo, preco, descricao) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $con->prepare($sql)) {
            // 3. Tipos: s (string) para quarto, s (tipo), d (double) para preco, s (descricao)
            // Ordem deve seguir o SQL: quarto, tipo, preco, descricao
            $stmt->bind_param("ssds", $numero, $tipo, $preco, $descricao);
            
            if ($stmt->execute()) {
                $mensagem = "<div class='sucesso'><p>Quarto cadastrado com sucesso! Redirecionando...</p></div>";
                header("refresh:3;url=quartos.php");
            } else {
                $mensagem = "<div class='erro'><p>Erro ao cadastrar quarto: " . htmlspecialchars($stmt->error) . "</p></div>";
            }
            $stmt->close();
        } else {
            $mensagem = "<div class='erro'><p>Erro na preparação: " . htmlspecialchars($con->error) . "</p></div>";
        }
    } else {
        $mensagem = "<div class='erro'><p>Por favor, preencha todos os campos.</p></div>";
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="2.css">
        <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
        <title>Pousada Parnoica</title>
    </head>
    
    <header>
        <nav>
            <ul>
                <?php
                    include_once 'Menu.php';
                ?>
            </ul>
        </nav>
    </header>
<body>
    <h1>Cadastro de Quartos</h1>
    
    <?php echo $mensagem; ?>

    <form action="" method="post">
        <label for="Quarto">Número do Quarto:</label>
        <input type="text" id="Quarto" name="Quarto" required><br><br>

        <label for="tipo">Tipo:</label>
        <input type="text" id="tipo" name="tipo" required><br><br>

        <label for="descricao">Descrição:</label>
        <textarea id="descricao" name="descricao" required></textarea><br><br>

        <label for="preco">Preço:</label>
        <input type="number" id="preco" name="preco" step="0.01" required><br><br>

        <input type="submit" value="Gravar">
    </form>
    
    <p><a href="quartos.php">Voltar para Quartos</a></p>

    <?php mysqli_close($con); ?>
</body>
</html>