<?php
session_start();
include_once './conexao.php';
include_once './validar.php';


// ✅ Validação de acesso corrigida
if (!isset($_SESSION['login']) || $_SESSION['status'] != 1) {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

// ✅ Pega ID com segurança
$id_quarto = $_GET['id'] ?? null;
$id_quarto = filter_var($id_quarto, FILTER_VALIDATE_INT);

if (!$id_quarto) {
    die("Nenhum quarto selecionado.");
}

// Buscar dados do quarto
$stmt_q = $con->prepare("SELECT quarto, preco, descricao, capacidade FROM quartos WHERE id = ?");
if (!$stmt_q) {
    die("Erro na consulta: " . $con->error);
}
$nome_quarto = $_POST['quarto'] ?? null;
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) {
    die("Quarto não encontrado.");
}

// PROCESSAMENTO
if (isset($_POST['reservar'])) {

    $quarto_id = $id_quarto;

    // ✅ Segurança nos inputs
    $cliente_id = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $checkin = $_POST['checkin'] ?? null;
    $hora_checkin = $_POST['hora_checkin'] ?? null;
    $checkout = $_POST['checkout'] ?? null;
    $hora_checkout = $_POST['hora_checkout'] ?? null;

    $usuario_id = $_SESSION['id'] ?? null;

    if (!$usuario_id) {
        header("Location: index.php?erro=Sessão inválida. Faça login novamente.");
        exit();
    }

    if (!$cliente_id || !$checkin || !$checkout || !$hora_checkin || !$hora_checkout) {
        die("Dados inválidos.");
    }

    $inicio = $checkin . ' ' . $hora_checkin;
    $fim = $checkout . ' ' . $hora_checkout;

    // ✅ Validação correta de datas
    $inicio_dt = new DateTime($inicio);
    $fim_dt = new DateTime($fim);
    $hoje = new DateTime();
    $hoje->setTime(0, 0, 0);

    if ($inicio_dt < $hoje) {
        die("❌ Não é permitido reservar datas passadas.");
    }

    if ($inicio_dt >= $fim_dt) {
        die("Check-out deve ser após o check-in.");
    }

    // ✅ Verificar conflito (mantido funcional)
    $stmt_check = $con->prepare("
        SELECT id FROM reservas 
        WHERE quarto_id = ? 
        AND status = 'ativa'
        AND (
            CONCAT(data_checkin, ' ', hora_checkin) <= ?
            AND 
            CONCAT(data_checkout, ' ', hora_checkout) >= ?
        )
    ");

    if (!$stmt_check) {
        die("Erro na verificação: " . $con->error);
    }

    $stmt_check->bind_param("iss", $quarto_id, $fim, $inicio);
    $stmt_check->execute();
    $reserva_existente = $stmt_check->get_result();

    if ($reserva_existente->num_rows > 0) {
        die("❌ Este quarto já está reservado nesse período.");
    }

    // ✅ Calcular dias
    $dias = $inicio_dt->diff($fim_dt)->days;

    $precoBase = $dados_quarto['preco'];
    $valorFinal = $precoBase;

    if ($dias < 5) {
        $valorFinal *= (1 - (5 - $dias) * 0.10);
    } elseif ($dias > 5) {
        $valorFinal *= (1 + ($dias - 5) * 0.10);
    }

    // ✅ Inserir reserva
    $stmt = $con->prepare("
        INSERT INTO reservas 
        (quarto_id, cliente_id, usuario_id, valor_total, data_checkin, hora_checkin, data_checkout, hora_checkout, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativa')
    ");

    if (!$stmt) {
        die("Erro ao inserir: " . $con->error);
    }

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
    
    registrarLog("A reserva do quarto {$dados_quarto['quarto']} foi realizada com sucesso no período de $checkin até $checkout pelo usuário " 
    . $_SESSION['login'], "INSERT");

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
                $res = mysqli_query($con, "SELECT * FROM clientes where status = 1");
                while ($c = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$c['id']}'>" . htmlspecialchars($c['nome']) . "</option>";
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