<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login'])) {
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
    <link rel="shortcut icon" href="./imagens/ipousada.png">
    <title>Pousada Parnoica</title>
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
        <h1>Quartos disponíveis</h1>

        <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm'): ?>
            <a href="gravarquartos.php">
                <button type="button">Cadastrar novo quarto</button>
            </a>

            <a href="reservas.php">
                <button type="button">Gerenciar reservas</button>
            </a>
        <?php endif; ?>

        <br><br>

        <?php
        $sql = "SELECT * FROM quartos ORDER BY id ASC";
        $result = mysqli_query($con, $sql);

        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

                echo "<div class='quarto'>";

                $stmt_status = $con->prepare("
                SELECT COUNT(*) as total 
                FROM reservas
                WHERE quarto_id = ?
                AND status = 'ativa'
                AND CURDATE() BETWEEN data_checkin AND data_checkout
            ");

                $stmt_status->bind_param("i", $row["id"]);
                $stmt_status->execute();
                $result_status = $stmt_status->get_result()->fetch_assoc();

                $ocupado = $result_status['total'] > 0;


                $statusTexto = $ocupado
                    ? "<span style='color:red;'>🔴 Reservado</span>"
                    : "<span style='color:green;'>🟢 Disponível</span>";

                echo "<h2>" . htmlspecialchars($row["quarto"]) . " - " . $statusTexto . "</h2>";

                echo "Preço base (5 noites): R$ " . number_format($row["preco"], 2, ',', '.') . "<br>";
                echo "(Valor varia conforme quantidade de dias)<br><br>";

                echo "<a href='informacoes_quarto.php?id=" . $row["id"] . "'>Informações adicionais</a><br>";

                if (!$ocupado) {
                    echo "<a href='Requarto.php?id=" . $row["id"] . "'>Reservar</a><br>";
                } else {
                    echo "<span style='color:gray;'>Indisponível no momento</span><br>";
                }

                if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm') {
                    echo "<a href='edquarto.php?id=" . $row["id"] . "'>Editar</a>";
                }

                echo "</div><hr>";
            }
        } else {
            echo "<p>Nenhum quarto disponível.</p>";
        }

        mysqli_close($con);
        ?>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnoica. Todos os direitos reservados.</p>
    </footer>

</body>

</html>