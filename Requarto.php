<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

$id_quarto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_quarto) {
    die("Nenhum quarto selecionado.");
}

// Buscar dados do quarto
$stmt_q = $con->prepare("SELECT quarto, preco, descricao, capacidade FROM quartos WHERE id = ?");
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) {
    die("Quarto não encontrado.");
}

// PROCESSAMENTO
if (isset($_POST['reservar'])) {

    $quarto_id = $_POST['quarto_id'];
    $cliente_id = $_POST['cliente_id'];
    $checkin = $_POST['checkin'];
    $hora_checkin = $_POST['hora_checkin'];
    $checkout = $_POST['checkout'];
    $hora_checkout = $_POST['hora_checkout'];
    $usuario_id = $_SESSION['id'];


    if (!$cliente_id || !$checkin || !$checkout || !$hora_checkin || !$hora_checkout) {
        die("Dados inválidos.");
    }

    $inicio = $checkin . ' ' . $hora_checkin;
    $fim = $checkout . ' ' . $hora_checkout;

    $hoje = date('Y-m-d H:i:s');

    if ($inicio < $hoje) {
        die("❌ Não é permitido reservar datas passadas.");
    }

    if ($inicio >= $fim) {
        die("Check-out deve ser após o check-in.");
    }

    // VERIFICAR CONFLITO
    $stmt_check = $con->prepare("
        SELECT * FROM reservas 
        WHERE quarto_id = ? 
        AND status = 'ativa'
        AND (
            CONCAT(data_checkin, ' ', hora_checkin) <= ?
            AND 
            CONCAT(data_checkout, ' ', hora_checkout) >= ?
        )
    ");

    $stmt_check->bind_param("iss", $quarto_id, $fim, $inicio);
    $stmt_check->execute();
    $reserva_existente = $stmt_check->get_result();

    if ($reserva_existente->num_rows > 0) {
        die("❌ Este quarto já está reservado nesse período.");
    }

    // CALCULAR DIAS
    $data1 = new DateTime($inicio);
    $data2 = new DateTime($fim);
    $dias = $data1->diff($data2)->days;

    $precoBase = $dados_quarto['preco'];
    $valorFinal = $precoBase;

    if ($dias < 5) {
        $valorFinal *= (1 - (5 - $dias) * 0.10);
    } elseif ($dias > 5) {
        $valorFinal *= (1 + ($dias - 5) * 0.10);
    }

    // INSERIR
    $stmt = $con->prepare("
    INSERT INTO reservas 
    (quarto_id, cliente_id, usuario_id, valor_total, data_checkin, hora_checkin, data_checkout, hora_checkout, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativa')
");

    $stmt->bind_param(
        "iiidssss",
        $quarto_id,
        $cliente_id,
        $usuario_id,
        $valorFinal,
        $checkin,
        $hora_checkin,
        $checkout,
        $hora_checkout
    );


    $stmt->execute();

    header("Location: reservas.php?sucesso=1");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Reserva de Quarto</title>
</head>

<body>

    <header>
        <nav>
            <ul><?php include_once 'menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Reservar quarto <?= htmlspecialchars($dados_quarto['quarto']) ?></h1>

        <form method="POST">
            <input type="hidden" name="quarto_id" value="<?= $id_quarto ?>">

            <label>Cliente:</label><br>
            <select name="cliente_id" required>
                <?php
                $res = mysqli_query($con, "SELECT * FROM clientes");
                while ($c = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$c['id']}'>{$c['nome']}</option>";
                }
                ?>
            </select>

            <br><br>

            <label>Check-in:</label><br>
            <input type="date" name="checkin" required>
            <input type="time" name="hora_checkin" required>

            <br><br>

            <label>Check-out:</label><br>
            <input type="date" name="checkout" required>
            <input type="time" name="hora_checkout" required>

            <br><br>

            <button type="submit" name="reservar">Reservar</button>
        </form>
    </main>

</body>

</html>