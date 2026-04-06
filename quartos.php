<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

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
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <link rel="stylesheet" href="2.css">
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
            <a href="gravarquartos.php"><button>Cadastrar novo quarto</button></a>
            <a href="reservas.php"><button>Gerenciar reservas</button></a>
        <?php endif; ?>

        <br><br>

        <?php
        $sql = "SELECT * FROM quartos ORDER BY id ASC";
        $result = mysqli_query($con, $sql);

        if ($result && mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

                echo "<div class='quarto'>";

                // Buscar reserva ATUAL incluindo horas
                $stmt_atual = $con->prepare("
                    SELECT data_checkout, hora_checkout
                    FROM reservas
                    WHERE quarto_id = ?
                    AND status = 'ativa'
                    AND CURDATE() BETWEEN data_checkin AND data_checkout
                    LIMIT 1
                ");
                $stmt_atual->bind_param("i", $row["id"]);
                $stmt_atual->execute();
                $res_atual = $stmt_atual->get_result();

                // Buscar próxima reserva futura incluindo horas
                $stmt_futuro = $con->prepare("
                    SELECT data_checkin, hora_checkin
                    FROM reservas
                    WHERE quarto_id = ?
                    AND status = 'ativa'
                    AND data_checkin > CURDATE()
                    ORDER BY data_checkin ASC
                    LIMIT 1
                ");
                $stmt_futuro->bind_param("i", $row["id"]);
                $stmt_futuro->execute();
                $res_futuro = $stmt_futuro->get_result();

                $ocupado = $res_atual->num_rows > 0;

                if ($row['status'] == 0) {

                    $statusTexto = "<span style='color:gray;'>⚫ Indisponível (bloqueado)</span>";
                    $ocupado = true;

                } else {

                    if ($res_atual->num_rows > 0) {

                        $dados = $res_atual->fetch_assoc();
                        $dataSaida  = date("d/m/Y", strtotime($dados['data_checkout']));
                        $horaSaida  = substr($dados['hora_checkout'], 0, 5); // HH:MM

                        $statusTexto = "<span style='color:red;'>🔴 Ocupado até $dataSaida às $horaSaida</span>";
                        $ocupado = true;

                    } else {

                        if ($res_futuro->num_rows > 0) {

                            $dadosFuturo  = $res_futuro->fetch_assoc();
                            $dataEntrada  = date("d/m/Y", strtotime($dadosFuturo['data_checkin']));
                            $horaEntrada  = substr($dadosFuturo['hora_checkin'], 0, 5); // HH:MM

                            $statusTexto = "<span style='color:orange;'>🟡 Disponível hoje (reservado a partir de $dataEntrada às $horaEntrada)</span>";
                        } else {

                            $statusTexto = "<span style='color:green;'>🟢 Livre</span>";
                        }

                        $ocupado = false;
                    }
                }

                echo "<h2>" . htmlspecialchars($row["quarto"]) . " - $statusTexto</h2>";

                echo "Preço base (5 noites): R$ " . number_format($row["preco"], 2, ',', '.') . "<br>";
                echo "<small>(Valor varia conforme quantidade de dias a mais ou a menos)</small><br><br>";

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

                $stmt_atual->close();
                $stmt_futuro->close();
            }
        } else {
            echo "<p>Nenhum quarto disponível.</p>";
        }

        mysqli_close($con);
        ?>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

</body>

</html>