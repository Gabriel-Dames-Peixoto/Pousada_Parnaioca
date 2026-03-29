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

<form method="POST">
    <input type="number" name="id_reserva" placeholder="ID da reserva" required>

    <br><br>

    <textarea name="motivo" placeholder="Motivo do cancelamento" required></textarea>

    <br><br>

    <button type="submit">Cancelar</button>
</form>