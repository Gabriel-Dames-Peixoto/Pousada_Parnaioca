<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gravar Quartos</title>
</head>
<body>
    <h1>Cadastro de Quartos</h1>
    <form action="gravarquartos.php" method="post">
        <label for="numero">Quarto:</label>
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
    <?php
        include_once './conexao.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numero = $_POST['numero'] ?? '';
            $tipo = $_POST['tipo'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $preco = $_POST['preco'] ?? '';
            
            if ($numero && $tipo && $descricao && $preco) {
                $sql = "INSERT INTO quartos (quarto, tipo, preco, descricao ) VALUES (?, ?, ?, ?)";
                                
                if ($stmt = $con->prepare($sql)) {
                    $stmt->bind_param("sssd", $quarto, $tipo, $preco, $descricao);
                    
                    if ($stmt->execute()) {
                        echo "<div class='sucesso'><p>Quarto cadastrado com sucesso! Redirecionando...</p></div>";
                        header("refresh:3;url=quartos.php");
                    } else {
                        echo "<div class='erro'><p>Erro ao cadastrar quarto: " . htmlspecialchars($stmt->error) . "</p></div>";
                    }
                } else {
                    echo "<div class='erro'><p>Erro na preparação da consulta: " . htmlspecialchars($con->error) . "</p></div>";
                }
             } else {
                echo "<div class='erro'><p>Por favor, preencha todos os campos.</p></div>";
             }
        }
        
        mysqli_close($con);
    ?>
</body>
</html>