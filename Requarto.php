<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

$id_quarto = $_GET['id'] ?? null;
$id_quarto = filter_var($id_quarto, FILTER_VALIDATE_INT);

if (!$id_quarto) {
    die("Nenhum quarto selecionado.");
}

$stmt_q = $con->prepare("SELECT quarto, preco, descricao, capacidade FROM quartos WHERE id = ?");
if (!$stmt_q) die("Erro na consulta: " . $con->error);
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) die("Quarto não encontrado.");

$erro_reserva = '';

if (isset($_POST['reservar'])) {

    $quarto_id     = $id_quarto;
    $cliente_id    = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $checkin       = $_POST['checkin']       ?? null;
    $hora_checkin  = $_POST['hora_checkin']  ?? null;
    $checkout      = $_POST['checkout']      ?? null;
    $hora_checkout = $_POST['hora_checkout'] ?? null;
    $usuario_id    = $_SESSION['id']         ?? null;

    if (!$usuario_id) {
        header("Location: index.php?erro=Sessão inválida. Faça login novamente.");
        exit();
    }

    if (!$cliente_id || !$checkin || !$checkout || !$hora_checkin || !$hora_checkout) {
        $erro_reserva = "Dados inválidos.";
    } else {

        $inicio_dt = new DateTime($checkin . ' ' . $hora_checkin);
        $fim_dt    = new DateTime($checkout . ' ' . $hora_checkout);
        $hoje      = new DateTime();
        $hoje->setTime(0, 0, 0);

        if ($inicio_dt < $hoje) {
            $erro_reserva = "❌ Não é permitido reservar datas passadas.";
        } elseif ($inicio_dt >= $fim_dt) {
            $erro_reserva = "Check-out deve ser após o check-in.";
        } else {

            $inicio_str = $checkin . ' ' . $hora_checkin;
            $fim_str    = $checkout . ' ' . $hora_checkout;

            $dias = $inicio_dt->diff($fim_dt)->days;

            $precoBase  = $dados_quarto['preco'];
            $valorFinal = $precoBase;
            if ($dias < 5) {
                $valorFinal *= (1 - (5 - $dias) * 0.10);
            } elseif ($dias > 5) {
                $valorFinal *= (1 + ($dias - 5) * 0.10);
            }
            $valorFinal = round($valorFinal, 2);

            $con->begin_transaction();

            try {
                $stmt_check = $con->prepare("
                    SELECT id FROM reservas
                    WHERE quarto_id = ?
                      AND status = 'ativa'
                      AND (
                          CONCAT(data_checkin,  ' ', hora_checkin)  < ?
                          AND
                          CONCAT(data_checkout, ' ', hora_checkout) > ?
                      )
                    FOR UPDATE
                ");
                $stmt_check->bind_param("iss", $quarto_id, $fim_str, $inicio_str);
                $stmt_check->execute();
                $conflito = $stmt_check->get_result();
                $stmt_check->close();

                if ($conflito->num_rows > 0) {
                    $con->rollback();
                    $erro_reserva = "❌ Este quarto já está reservado nesse período.";
                } else {
                    $stmt = $con->prepare("
                        INSERT INTO reservas
                            (quarto_id, cliente_id, usuario_id, valor_total,
                             data_checkin, hora_checkin, data_checkout, hora_checkout, status)
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
                    $stmt->close();

                    $con->commit();

                    registrarLog(
                        "A reserva do quarto {$dados_quarto['quarto']} foi realizada com sucesso " .
                            "no período de $checkin até $checkout pelo usuário " . $_SESSION['login'],
                        "INSERT"
                    );

                    header("Location: reservas.php?sucesso=1");
                    exit();
                }
            } catch (Exception $e) {
                $con->rollback();
                $erro_reserva = "Erro interno ao processar a reserva. Tente novamente.";
            }
        }
    }
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

        <?php if ($erro_reserva): ?>
            <p class="erro"><?= htmlspecialchars($erro_reserva) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="quarto_id" value="<?= $id_quarto ?>">

            <label>Cliente:</label><br>
            <select name="cliente_id" required>
                <?php
                $res = mysqli_query($con, "SELECT * FROM clientes WHERE status = 1 ORDER BY nome ASC");
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