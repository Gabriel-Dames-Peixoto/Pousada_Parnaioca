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
        <h1>Quartos disponiveis</h1>
        <?php
            include_once 'conexao.php';

            $sql = "SELECT * FROM quartos";
            $result = mysqli_query($con, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='quarto'>";
                    echo "<h2>" . $row["tipo"] . "</h2>";
                    echo "<p>Preço: R$ " . $row["preco"] . "</p>";
                    echo "<p>" . $row["descricao"] . "</p>";
                    echo "</div>";
                }
            } else {
                echo "Nenhum quarto disponível.";
            }

            mysqli_close($con);
        ?>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnoica. Todos os direitos reservados.</p>
    </footer>


</body>
</html>