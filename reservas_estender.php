<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

$mensagem = '';

$stmt_quartos = $con->prepare("
    SELECT DISTINCT q.id, q.quarto
    FROM quartos q
    INNER JOIN reservas r ON r.quarto_id = q.id
    WHERE r.status = 'ativa'
      AND r.data_checkin_real IS NOT NULL
    ORDER BY q.quarto ASC
");
$stmt_quartos->execute();
$quartos_hospedados = $stmt_quartos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quartos->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
    $nova_data_checkout = trim($_POST['data_checkout'] ?? '');
    $nova_hora_checkout = trim($_POST['hora_checkout'] ?? '');

    if (!$id_reserva || !$nova_data_checkout || !$nova_hora_checkout) {
        $mensagem = "<p class='erro'>Preencha todos os campos para estender a reserva.</p>";
    } else {
        $con->begin_transaction();

        try {
            $stmt = $con->prepare("
                SELECT r.*, q.quarto, q.preco
                FROM reservas r
                INNER JOIN quartos q ON q.id = r.quarto_id
                WHERE r.id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $id_reserva);
            $stmt->execute();
            $reserva = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$reserva) {
                throw new RuntimeException('Reserva nao encontrada.');
            }

            if ($reserva['status'] !== 'ativa' || empty($reserva['data_checkin_real'])) {
                throw new RuntimeException('So e possivel estender reservas ativas com check-in realizado.');
            }

            $inicioReserva = obterInicioReserva($reserva['data_checkin'], $reserva['hora_checkin']);
            $fimAtual = obterInicioReserva($reserva['data_checkout'], $reserva['hora_checkout']);
            $novoFim = obterInicioReserva($nova_data_checkout, $nova_hora_checkout);

            if ($novoFim <= $fimAtual) {
                throw new RuntimeException('A nova saida deve ser posterior ao checkout atual.');
            }

            $stmtConflito = $con->prepare("
                SELECT id
                FROM reservas
                WHERE quarto_id = ?
                  AND status = 'ativa'
                  AND id <> ?
                  AND CONCAT(data_checkin, ' ', hora_checkin) < ?
                  AND CONCAT(data_checkout, ' ', hora_checkout) > ?
                FOR UPDATE
            ");
            $novoFimStr = $novoFim->format('Y-m-d H:i:s');
            $fimAtualStr = $fimAtual->format('Y-m-d H:i:s');
            $stmtConflito->bind_param("iiss", $reserva['quarto_id'], $id_reserva, $novoFimStr, $fimAtualStr);
            $stmtConflito->execute();
            $conflito = $stmtConflito->get_result()->fetch_assoc();
            $stmtConflito->close();

            if ($conflito) {
                throw new RuntimeException('Nao e possivel estender: existe outra reserva para este quarto no periodo solicitado.');
            }

            $valorAtualizado = calcularValorReserva((float)$reserva['preco'], $inicioReserva, $novoFim);

            $update = $con->prepare("
                UPDATE reservas
                SET data_checkout = ?,
                    hora_checkout = ?,
                    valor_total = ?,
                    data_ultima_extensao = NOW()
                WHERE id = ?
            ");
            $update->bind_param("ssdi", $nova_data_checkout, $nova_hora_checkout, $valorAtualizado, $id_reserva);
            $update->execute();
            $update->close();

            $con->commit();
            registrarLog("A reserva {$id_reserva} do quarto {$reserva['quarto']} foi estendida por " . $_SESSION['login'], 'UPDATE');
            $mensagem = "<p class='sucesso'>Reserva estendida com sucesso!</p>";
        } catch (Throwable $e) {
            $con->rollback();
            $mensagem = "<p class='erro'>" . htmlspecialchars($e->getMessage()) . "</p>";
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
    <title>Estender Reserva</title>
</head>
<body>
    <header>
        <nav>
            <ul><?php include_once 'Menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Estender Reserva</h1>
        <?= $mensagem ?>

        <form method="POST">
            <label>Quarto:</label><br>
            <select id="quartoSelect" onchange="carregarReservas()">
                <option value="">-- Selecione um quarto --</option>
                <?php foreach ($quartos_hospedados as $q): ?>
                    <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['quarto']) ?></option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <div id="reservaBox" style="display:none;">
                <label>Reserva:</label><br>
                <select name="id_reserva" id="reservaSelect" required onchange="mostrarFormulario()">
                    <option value="">-- Selecione --</option>
                </select>
            </div>

            <div id="extensaoBox" style="display:none;">
                <br>
                <p id="reservaAtual" style="font-weight:bold;"></p>
                <label>Novo check-out:</label><br>
                <input type="date" name="data_checkout" id="data_checkout" required>
                <input type="time" name="hora_checkout" id="hora_checkout" required>
            </div>

            <br>
            <button type="submit" id="btnExtender" style="display:none;">Estender Reserva</button>
        </form>
    </main>

    <script>
        let reservasCarregadas = [];

        function carregarReservas() {
            const quartoId = document.getElementById('quartoSelect').value;
            const box = document.getElementById('reservaBox');
            const select = document.getElementById('reservaSelect');
            const extensaoBox = document.getElementById('extensaoBox');
            const button = document.getElementById('btnExtender');

            select.innerHTML = '<option value="">-- Selecione --</option>';
            extensaoBox.style.display = 'none';
            button.style.display = 'none';
            reservasCarregadas = [];

            if (!quartoId) {
                box.style.display = 'none';
                return;
            }

            box.style.display = 'block';
            select.innerHTML = '<option value="">Carregando...</option>';

            fetch('reservas_buscar.php?quarto_id=' + quartoId + '&status=ativa&filtro=hospedadas')
                .then(r => r.json())
                .then(data => {
                    reservasCarregadas = data;
                    select.innerHTML = '<option value="">-- Selecione a reserva --</option>';

                    if (!data.length) {
                        select.innerHTML = '<option value="" disabled>Nenhuma reserva em hospedagem neste quarto</option>';
                        return;
                    }

                    data.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.id;
                        opt.textContent = `#${r.id} - ${r.cliente} - ${r.periodo}`;
                        select.appendChild(opt);
                    });
                })
                .catch(() => {
                    select.innerHTML = '<option value="">Erro ao carregar reservas</option>';
                });
        }

        function mostrarFormulario() {
            const idReserva = document.getElementById('reservaSelect').value;
            const reserva = reservasCarregadas.find(r => String(r.id) === idReserva);
            const extensaoBox = document.getElementById('extensaoBox');
            const button = document.getElementById('btnExtender');

            if (!reserva) {
                extensaoBox.style.display = 'none';
                button.style.display = 'none';
                return;
            }

            document.getElementById('reservaAtual').textContent = 'Checkout atual: ' + reserva.data_checkout + ' ' + reserva.hora_checkout;
            document.getElementById('data_checkout').value = reserva.data_checkout;
            document.getElementById('hora_checkout').value = reserva.hora_checkout;
            extensaoBox.style.display = 'block';
            button.style.display = 'inline-block';
        }
    </script>
</body>
</html>
