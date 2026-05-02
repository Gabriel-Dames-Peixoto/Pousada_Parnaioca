<?php

session_start();
include_once './conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    echo json_encode([]);
    exit();
}

$quarto_id = filter_input(INPUT_GET, 'quarto_id', FILTER_VALIDATE_INT);
$status    = $_GET['status'] ?? 'ativa';
$filtro    = $_GET['filtro'] ?? 'todas_ativas';

$status_permitidos = ['ativa', 'finalizada', 'cancelada'];
$filtros_permitidos = ['todas_ativas', 'checkin', 'hospedadas'];

if (!in_array($status, $status_permitidos, true) || !in_array($filtro, $filtros_permitidos, true) || !$quarto_id) {
    echo json_encode([]);
    exit();
}

$condicoes = [
    'todas_ativas' => '',
    'checkin' => " AND r.data_checkin_real IS NULL AND TIMESTAMP(r.data_checkin, r.hora_checkin) <= NOW()",
    'hospedadas' => " AND r.data_checkin_real IS NOT NULL",
];

$stmt = $con->prepare("
    SELECT
        r.id,
        r.quarto_id,
        q.quarto,
        c.nome AS cliente,
        COALESCE(u.login, 'Sem usuario') AS usuario,
        r.data_checkin,
        r.hora_checkin,
        r.data_checkin_real,
        r.data_checkout,
        r.hora_checkout,
        r.data_ultima_extensao,
        r.status
    FROM reservas r
    JOIN quartos q ON q.id = r.quarto_id
    JOIN clientes c ON c.id = r.cliente_id
    LEFT JOIN usuarios u ON u.id = r.usuario_id
    WHERE r.quarto_id = ?
      AND r.status = ?
      {$condicoes[$filtro]}
    ORDER BY r.data_checkin ASC, r.hora_checkin ASC
");

$stmt->bind_param("is", $quarto_id, $status);
$stmt->execute();
$res = $stmt->get_result();

$reservas = [];

while ($row = $res->fetch_assoc()) {
    $checkin  = date('d/m/Y', strtotime($row['data_checkin']));
    $checkout = date('d/m/Y', strtotime($row['data_checkout']));
    $checkinReal = $row['data_checkin_real'] ? date('d/m/Y H:i', strtotime($row['data_checkin_real'])) : null;

    $reservas[] = [
        'id' => $row['id'],
        'quarto_id' => $row['quarto_id'],
        'quarto' => $row['quarto'],
        'cliente' => $row['cliente'],
        'usuario' => $row['usuario'],
        'data_checkin' => $row['data_checkin'],
        'hora_checkin' => $row['hora_checkin'],
        'data_checkout' => $row['data_checkout'],
        'hora_checkout' => $row['hora_checkout'],
        'periodo' => "{$checkin} {$row['hora_checkin']} ate {$checkout} {$row['hora_checkout']}",
        'status' => $row['status'],
        'checkin_realizado' => !empty($row['data_checkin_real']),
        'data_checkin_real' => $checkinReal,
        'data_ultima_extensao' => $row['data_ultima_extensao'],
    ];
}

$stmt->close();
echo json_encode($reservas);
