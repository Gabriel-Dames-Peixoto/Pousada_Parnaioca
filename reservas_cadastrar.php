<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

$id_quarto = $_GET['id'] ?? null;
$id_quarto = filter_var($id_quarto, FILTER_VALIDATE_INT);

if (!$id_quarto) {
    die("Nenhum quarto selecionado.");
}

$stmt_q = $con->prepare("SELECT quarto, preco, descricao, capacidade, status FROM quartos WHERE id = ?");
if (!$stmt_q) {
    die("Erro na consulta: " . $con->error);
}
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) {
    die("Quarto nao encontrado.");
}

$erro_reserva = '';

if (isset($_POST['reservar'])) {
    $quarto_id     = $id_quarto;
    $cliente_id    = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $checkin       = trim($_POST['checkin'] ?? '');
    $hora_checkin  = trim($_POST['hora_checkin'] ?? '');
    $checkout      = trim($_POST['checkout'] ?? '');
    $hora_checkout = trim($_POST['hora_checkout'] ?? '');
    $usuario_id    = $_SESSION['id'] ?? null;

    if (!$usuario_id) {
        header("Location: login.php?erro=Sessao invalida. Faca login novamente.");
        exit();
    }

    if ($dados_quarto['status'] !== '1') {
        $erro_reserva = "Este quarto esta indisponivel para reserva.";
    } elseif (!$cliente_id || !$checkin || !$checkout || !$hora_checkin || !$hora_checkout) {
        $erro_reserva = "Dados invalidos.";
    } elseif (!clienteAtivoExiste($cliente_id)) {
        $erro_reserva = "Selecione um cliente ativo e valido.";
    } else {
        $inicio_dt = DateTime::createFromFormat('Y-m-d H:i', $checkin . ' ' . $hora_checkin);
        $fim_dt    = DateTime::createFromFormat('Y-m-d H:i', $checkout . ' ' . $hora_checkout);
        $agora     = new DateTime();

        if (!$inicio_dt || !$fim_dt) {
            $erro_reserva = "Data ou horario invalidos.";
        } elseif ($inicio_dt < $agora) {
            $erro_reserva = "Nao e permitido reservar datas passadas.";
        } elseif ($inicio_dt >= $fim_dt) {
            $erro_reserva = "Check-out deve ser apos o check-in.";
        } else {
            $inicio_str = $inicio_dt->format('Y-m-d H:i:s');
            $fim_str    = $fim_dt->format('Y-m-d H:i:s');

            $dias = max(1, (int)$inicio_dt->diff($fim_dt)->days);

            $precoBase  = (float)$dados_quarto['preco'];
            $valorFinal = $precoBase;
            if ($dias < 5) {
                $valorFinal *= (1 - (5 - $dias) * 0.10);
            } elseif ($dias > 5) {
                $valorFinal *= (1 + ($dias - 5) * 0.10);
            }
            $valorFinal = round($valorFinal, 2);

            $con->begin_transaction();

            try {
                $stmt_quarto = $con->prepare("
                    SELECT id, status
                    FROM quartos
                    WHERE id = ?
                    FOR UPDATE
                ");
                $stmt_quarto->bind_param("i", $quarto_id);
                $stmt_quarto->execute();
                $quartoAtual = $stmt_quarto->get_result()->fetch_assoc();
                $stmt_quarto->close();

                if (!$quartoAtual || $quartoAtual['status'] !== '1') {
                    throw new RuntimeException('Quarto indisponivel para reserva.');
                }

                if (!clienteAtivoExiste($cliente_id)) {
                    throw new RuntimeException('O cliente selecionado nao esta mais ativo.');
                }

                $stmt_check = $con->prepare("
                    SELECT id
                    FROM reservas
                    WHERE quarto_id = ?
                      AND status = 'ativa'
                      AND CONCAT(data_checkin, ' ', hora_checkin) < ?
                      AND CONCAT(data_checkout, ' ', hora_checkout) > ?
                    FOR UPDATE
                ");
                $stmt_check->bind_param("iss", $quarto_id, $fim_str, $inicio_str);
                $stmt_check->execute();
                $conflito = $stmt_check->get_result();
                $stmt_check->close();

                if ($conflito->num_rows > 0) {
                    throw new RuntimeException('Este quarto ja esta reservado nesse periodo.');
                }

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
                    "A reserva do quarto {$dados_quarto['quarto']} foi realizada com sucesso no periodo de $checkin ate $checkout pelo usuario " . $_SESSION['login'],
                    "INSERT"
                );

                header("Location: reservas.php?sucesso=1");
                exit();
            } catch (Throwable $e) {
                $con->rollback();
                $erro_reserva = $e->getMessage() ?: "Erro interno ao processar a reserva. Tente novamente.";
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
            <ul><?php include_once 'Menu.php'; ?></ul>
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
                    $selected = ((int)($c['id'] ?? 0) === (int)($_POST['cliente_id'] ?? 0)) ? 'selected' : '';
                    echo "<option value='{$c['id']}' $selected>" . htmlspecialchars($c['nome']) . "</option>";
                }
                ?>
            </select>

            <br><br>

            <label>Check-in:</label><br>
            <input type="date" name="checkin" value="<?= htmlspecialchars($_POST['checkin'] ?? '') ?>" required>
            <input type="time" name="hora_checkin" value="<?= htmlspecialchars($_POST['hora_checkin'] ?? '') ?>" required>

            <br><br>

            <label>Check-out:</label><br>
            <input type="date" name="checkout" value="<?= htmlspecialchars($_POST['checkout'] ?? '') ?>" required>
            <input type="time" name="hora_checkout" value="<?= htmlspecialchars($_POST['hora_checkout'] ?? '') ?>" required>

            <br><br>

            <button type="submit" name="reservar">Reservar</button>
        </form>
    </main>

</body>

</html>
