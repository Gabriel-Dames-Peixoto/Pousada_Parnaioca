<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
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
        <?php
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $sql = "SELECT quarto FROM quartos WHERE id = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $nome_quarto = $row['quarto'];
            } else {
                $nome_quarto = "Quarto não encontrado";
            }
            $stmt->close();
        } else {
            $nome_quarto = "Nenhum quarto selecionado";
        }
        ?>
        <h1>Informações do Quarto <br><?php echo htmlspecialchars($nome_quarto); ?></h1>
        <p><?php
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $sql = "SELECT * FROM quartos WHERE id = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo "<p>Preço: R$ " . number_format($row["preco"], 2, ',', '.') . "</p>";
                    echo "<p>Descrição: " . $row["descricao"] . "</p>";
                } else {
                    echo "<p>Quarto não encontrado.</p>";
                }
                $stmt->close();
            } else {
                echo "<p>Nenhum quarto selecionado.</p>";
            }

            mysqli_close($con);
            
        ?></p>
        <h2>Frigobar</h2>
        <p><?php
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $sql = "SELECT nome, valor FROM frigobar WHERE quarto_id = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<p>" . htmlspecialchars($row["nome"]) . " - R$ " . number_format($row["valor"], 2, ',', '.') . "</p>";
                    }
                } else {
                    echo "<p>Frigobar vazio.</p>";
                }
                $stmt->close();
            } else {
                echo "<p>Nenhum quarto selecionado.</p>";
            }
        ?></p>
    </main>
</body>
</html>