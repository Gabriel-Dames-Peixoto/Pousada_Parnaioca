<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] != 'adm') {
    header("Location: index.php");
    exit();
}

$filtro_nome = $_GET['nome'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$mensagem = "";

// 🔍 CONSULTA CORRIGIDA
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

// FILTRO CLIENTE
if (!empty($filtro_nome)) {
    $sql .= " AND c.nome LIKE ?";
    $params[] = "%$filtro_nome%";
    $types .= "s";
}

// FILTRO USUÁRIO
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

// 🔥 CANCELAR RESERVA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_reserva'];
    $motivo = trim($_POST['motivo']);

    if (empty($motivo)) {
        $mensagem = "<p class='erro'>Informe o motivo do cancelamento.</p>";
    } else {

        $check = $con->prepare("SELECT status FROM reservas WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();

        if (!$res) {
            $mensagem = "<p class='erro'>Reserva não encontrada.</p>";
        } elseif ($res['status'] === 'finalizada') {
            $mensagem = "<p class='erro'>Não é possível cancelar reserva finalizada.</p>";
        } elseif ($res['status'] === 'cancelada') {
            $mensagem = "<p class='erro'>Reserva já está cancelada.</p>";
        } else {

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

            $mensagem = "<p class='sucesso'>Reserva cancelada com sucesso!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Cancelar Reserva</title>
</head>

<body>

<header>
    <nav>
        <ul><?php include_once 'menu.php'; ?></ul>
    </nav>
</header>

<main>
    <h1>Cancelar Reserva</h1>

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

    <!-- 📋 LISTA -->
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

        <label>Motivo do cancelamento:</label>
        <textarea name="motivo" required></textarea>

        <br><br>

        <button type="submit">Cancelar Reserva</button>
    </form>

</main>

<footer>
    <p>&copy; 2026 Pousada Parnaioca</p>
</footer>

</body>
</html>
