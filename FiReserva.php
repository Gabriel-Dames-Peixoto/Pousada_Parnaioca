<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}

$filtro_nome = $_GET['nome'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$mensagem = "";

// 🔍 CONSULTA CORRIGIDA (LEFT JOIN + COALESCE)
$sql = "
SELECT 
    r.id, 
    c.nome AS cliente, 
    COALESCE(u.login, 'Sem usuário') AS usuario, 
    r.status
FROM reservas r
JOIN clientes c ON r.cliente_id = c.id
LEFT JOIN usuarios u ON r.usuario_id = u.id
WHERE r.status = 'ativa'
";

$params = [];
$types = "";

// 🔍 FILTRO CLIENTE
if (!empty($filtro_nome)) {
    $sql .= " AND c.nome LIKE ?";
    $params[] = "%$filtro_nome%";
    $types .= "s";
}

// 🔍 FILTRO USUÁRIO
if (!empty($filtro_usuario)) {
    $sql .= " AND u.login LIKE ?";
    $params[] = "%$filtro_usuario%";
    $types .= "s";
}

$stmt = $con->prepare($sql);

if (!$stmt) {
    die("Erro SQL: " . $con->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$reservas = $stmt->get_result();

// 🔥 FINALIZAR RESERVA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_reserva'];

    // 🔎 Verificar reserva
    $check = $con->prepare("SELECT status, valor_total FROM reservas WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!$res) {
        $mensagem = "<p class='erro'>Reserva não encontrada.</p>";
    } elseif ($res['status'] !== 'ativa') {
        $mensagem = "<p class='erro'>Só é possível finalizar reservas ativas.</p>";
    } else {

        // 🍺 SOMAR FRIGOBAR
        $consumo = $con->prepare("
            SELECT SUM(valor_total) as total 
            FROM consumo_frigobar 
            WHERE reserva_id = ?
        ");

        $consumo->bind_param("i", $id);
        $consumo->execute();
        $total_consumo = $consumo->get_result()->fetch_assoc()['total'] ?? 0;

        // 🔥 ATUALIZAR RESERVA
        $update = $con->prepare("
            UPDATE reservas 
            SET 
                status = 'finalizada',
                data_finalizacao = NOW(),
                valor_total = valor_total + ?
            WHERE id = ?
        ");

        $update->bind_param("di", $total_consumo, $id);
        $update->execute();

        $mensagem = "<p class='sucesso'>Reserva finalizada com sucesso! (Frigobar: R$ " 
            . number_format($total_consumo, 2, ',', '.') . ")</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Finalizar Reserva</title>
</head>

<body>

<header>
    <nav>
        <ul>
            <?php include_once 'menu.php'; ?>
        </ul>
    </nav>
</header>

<main>
    <h1>Finalizar Reserva</h1>

    <?= $mensagem ?>

    <!-- 🔍 FILTROS -->
    <form method="GET">
        <label>Nome do cliente:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($filtro_nome) ?>">

        <label>Usuário:</label>
        <input type="text" name="usuario" value="<?= htmlspecialchars($filtro_usuario) ?>">

        <button type="submit">Filtrar</button>
    </form>

    <br>

    <!-- 📋 LISTA DE RESERVAS -->
    <form method="POST">
        <label>Selecione a reserva:</label>

        <select name="id_reserva" required>
            <option value="">-- Selecione --</option>

            <?php while ($r = $reservas->fetch_assoc()) { ?>
                <option value="<?= $r['id'] ?>">
                    #<?= $r['id'] ?> - <?= $r['cliente'] ?> 
                    (<?= $r['usuario'] ?>) - <?= $r['status'] ?>
                </option>
            <?php } ?>
        </select>

        <br><br>

        <button type="submit">Finalizar Reserva</button>
    </form>

</main>

<footer>
    <p>&copy; 2026 Pousada Parnaioca</p>
</footer>

</body>
</html>
