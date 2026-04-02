<?php
/**
 * buscar_reservas.php
 * Retorna reservas filtradas por quarto_id em formato JSON.
 * Usado via fetch() em FiReserva.php e CanReserva.php.
 */

session_start();
include_once './conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode([]);
    exit();
}

$quarto_id = filter_input(INPUT_GET, 'quarto_id', FILTER_VALIDATE_INT);
$status    = $_GET['status'] ?? 'ativa';

// Valida status permitido
$status_permitidos = ['ativa', 'finalizada', 'cancelada'];
if (!in_array($status, $status_permitidos)) {
    echo json_encode([]);
    exit();
}

if (!$quarto_id) {
    echo json_encode([]);
    exit();
}

$stmt = $con->prepare("
    SELECT
        r.id,
        r.quarto_id,
        q.quarto,
        c.nome        AS cliente,
        COALESCE(u.login, 'Sem usuário') AS usuario,
        r.data_checkin,
        r.data_checkout,
        r.status
    FROM reservas r
    JOIN quartos  q ON q.id = r.quarto_id
    JOIN clientes c ON c.id = r.cliente_id
    LEFT JOIN usuarios u ON u.id = r.usuario_id
    WHERE r.quarto_id = ?
      AND r.status    = ?
    ORDER BY r.data_checkin ASC
");

$stmt->bind_param("is", $quarto_id, $status);
$stmt->execute();
$res = $stmt->get_result();

$reservas = [];

while ($row = $res->fetch_assoc()) {
    $checkin  = date('d/m/Y', strtotime($row['data_checkin']));
    $checkout = date('d/m/Y', strtotime($row['data_checkout']));

    $reservas[] = [
        'id'        => $row['id'],
        'quarto_id' => $row['quarto_id'],
        'quarto'    => $row['quarto'],
        'cliente'   => $row['cliente'],
        'usuario'   => $row['usuario'],
        'periodo'   => "$checkin até $checkout",
        'status'    => $row['status'],
    ];
}

$stmt->close();
echo json_encode($reservas);