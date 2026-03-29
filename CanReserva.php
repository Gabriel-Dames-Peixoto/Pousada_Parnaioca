<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_reserva'];
    $motivo = $_POST['motivo'];

    $check = $con->prepare("SELECT status FROM reservas WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!$res) {
        die("Reserva não encontrada.");
    }

    if ($res['status'] === 'finalizada') {
        die("Não é possível cancelar reserva finalizada.");
    }

    if ($res['status'] === 'cancelada') {
        die("Reserva já está cancelada.");
    }

    $stmt = $con->prepare("
        UPDATE reservas 
        SET 
            status = 'cancelada',
            data_cancelamento = NOW(),
            motivo_cancelamento = ?
        WHERE id = ?
    ");

    $stmt->bind_param("si", $motivo, $id);
    $stmt->execute();

    echo "Reserva cancelada com sucesso!";
}
?>

<<<<<<< HEAD
<form method="POST">
    <input type="number" name="id_reserva" placeholder="ID da reserva" required>
=======
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
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
        <h1>Cancelar Reserva</h1>
        <form method="POST" action="CanReserva.php">
            <label for="id_reserva">ID da Reserva:</label>
            <input type="number" id="id_reserva" name="id_reserva" required>
            <button type="submit">Cancelar Reserva</button>
        </form>
    </main>
</body>

</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = filter_input(INPUT_POST, 'id_reserva', FILTER_SANITIZE_NUMBER_INT);
>>>>>>> 5dc1ef35964e29e7e4c1168bd60c9529d6671faf

    <br><br>

<<<<<<< HEAD
    <textarea name="motivo" placeholder="Motivo do cancelamento" required></textarea>

    <br><br>

    <button type="submit">Cancelar</button>
</form>
=======
    if ($result->num_rows === 0) {
        echo "<p style='color:red;'>Reserva não encontrada.</p>";
        exit();
    }

    // Atualizar o status da reserva para 'cancelada'
    $update_stmt = $con->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
    $update_stmt->bind_param("i", $id_reserva);
    if ($update_stmt->execute()) {
        registrarLog("Reserva $id_reserva foi cancelada por " . $_SESSION['login'], "UPDATE");
        echo "<p style='color:green;'>Reserva cancelada com sucesso!</p>";
        header("Refresh: 2; URL=reservas.php");
    } else {
        echo "<p style='color:red;'>Erro ao cancelar a reserva. Tente novamente.</p>";
    }
}
>>>>>>> 5dc1ef35964e29e7e4c1168bd60c9529d6671faf
