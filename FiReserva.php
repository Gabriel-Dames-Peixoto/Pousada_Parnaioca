<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_reserva'];

    // Verifica status
    $check = $con->prepare("SELECT status FROM reservas WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!$res) {
        die("Reserva não encontrada.");
    }

    if ($res['status'] !== 'ativa') {
        die("Só é possível finalizar reservas ativas.");
    }

    $stmt = $con->prepare("
        UPDATE reservas 
        SET status = 'finalizada', data_finalizacao = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "Reserva finalizada com sucesso!";
}
?>

<form method="POST">
    <input type="number" name="id_reserva" placeholder="ID da reserva" required>
    <button type="submit">Finalizar</button>
</form>