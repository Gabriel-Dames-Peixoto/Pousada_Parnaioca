<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Finalizar Reservas</title>
</head>

<body>

    <header>
        <nav>
            <ul>
                <?php
                include_once 'menu.php';
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Finalizar Reservas</h1>
        <form method="POST" action="FiReserva.php">
            <label for="id_reserva">ID da Reserva:</label>
            <input type="number" id="id_reserva" name="id_reserva" required>
            <button type="submit">Finalizar Reserva</button>
        </form>
    </main>
</body>

</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = filter_input(INPUT_POST, 'id_reserva', FILTER_SANITIZE_NUMBER_INT);

    // Verificar se a reserva existe
    $stmt = $con->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt->bind_param("i", $id_reserva);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>Reserva não encontrada.</p>";
        exit();
    }

    // Atualizar o status da reserva para 'cancelada'
    $update_stmt = $con->prepare("UPDATE reservas SET status = 'finalizada' WHERE id = ?");
    $update_stmt->bind_param("i", $id_reserva);
    if ($update_stmt->execute()) {
        registrarLog("Reserva $id_reserva foi finalizada por " . $_SESSION['login'], "UPDATE");
        echo "<p style='color:green;'>Reserva finalizada com sucesso!</p>";
        header("Refresh: 2; URL=reservas.php");
    } else {
        echo "<p style='color:red;'>Erro ao finalizar a reserva. Tente novamente.</p>";
    }
}
