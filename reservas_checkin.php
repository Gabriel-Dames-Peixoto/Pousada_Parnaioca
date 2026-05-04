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
      AND r.data_checkin_real IS NULL
      AND TIMESTAMP(r.data_checkin, r.hora_checkin) <= NOW()
    ORDER BY q.quarto ASC
");
$stmt_quartos->execute();
$quartos_disponiveis = $stmt_quartos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quartos->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);

    if (!$id_reserva) {
        $mensagem = "<p class='erro'>Selecione uma reserva valida.</p>";
    } else {
        $con->begin_transaction();

        try {
            $stmt = $con->prepare("
                SELECT r.id, r.status, r.data_checkin, r.hora_checkin, r.data_checkin_real, q.quarto
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

            if ($reserva['status'] !== 'ativa') {
                throw new RuntimeException('So e possivel fazer check-in em reservas ativas.');
            }

            if (!empty($reserva['data_checkin_real'])) {
                throw new RuntimeException('O check-in desta reserva ja foi realizado.');
            }

            $inicioPrevisto = obterInicioReserva($reserva['data_checkin'], $reserva['hora_checkin']);
            $agora = new DateTime();
            if ($agora < $inicioPrevisto) {
                throw new RuntimeException('Esta reserva ainda nao iniciou e nao pode receber check-in.');
            }

            $update = $con->prepare("UPDATE reservas SET data_checkin_real = NOW() WHERE id = ?");
            $update->bind_param("i", $id_reserva);
            $update->execute();
            $update->close();

            $con->commit();

            registrarLog("Check-in realizado para a reserva {$id_reserva} do quarto {$reserva['quarto']} por " . $_SESSION['login'], 'UPDATE');
            $mensagem = "<p class='sucesso'>Check-in realizado com sucesso!</p>";
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
    <title>Check-in de Reserva</title>
</head>
<body>
    <header>
        <nav>
            <ul><?php include_once 'Menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Check-in de Reserva</h1>
        <?= $mensagem ?>

        <div class="aviso" style="background:#fff8e1; color:#795600; padding:12px; border-radius:6px; margin-bottom:20px; border-left:5px solid #f9c74f;">
            A cobranca da hospedagem considera o periodo reservado do quarto. Mesmo que o hospede chegue depois, os dias reservados continuam sendo cobrados.
        </div>

        <form method="POST">
            <label>Quarto:</label><br>
            <select id="quartoSelect" onchange="carregarReservas()">
                <option value="">-- Selecione um quarto --</option>
                <?php foreach ($quartos_disponiveis as $q): ?>
                    <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['quarto']) ?></option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <div id="reservaBox" style="display:none;">
                <label>Reserva:</label><br>
                <select name="id_reserva" id="reservaSelect" required onchange="mostrarAvisoReserva()">
                    <option value="">-- Selecione --</option>
                </select>
            </div>

            <div id="avisoReserva" style="display:none; margin-top:15px;" class="aviso"></div>

            <br>
            <button type="submit" id="btnCheckin" style="display:none;">Realizar Check-in</button>
        </form>
    </main>

    <script>
        let reservasCarregadas = [];

        function carregarReservas() {
            const quartoId = document.getElementById('quartoSelect').value;
            const box = document.getElementById('reservaBox');
            const select = document.getElementById('reservaSelect');
            const button = document.getElementById('btnCheckin');
            const aviso = document.getElementById('avisoReserva');

            select.innerHTML = '<option value="">-- Selecione --</option>';
            button.style.display = 'none';
            aviso.style.display = 'none';
            aviso.textContent = '';
            reservasCarregadas = [];

            if (!quartoId) {
                box.style.display = 'none';
                return;
            }

            box.style.display = 'block';
            select.innerHTML = '<option value="">Carregando...</option>';

            fetch('reservas_buscar.php?quarto_id=' + quartoId + '&status=ativa&filtro=checkin')
                .then(r => r.json())
                .then(data => {
                    reservasCarregadas = data;
                    select.innerHTML = '<option value="">-- Selecione a reserva --</option>';

                    if (!data.length) {
                        select.innerHTML = '<option value="" disabled>Nenhuma reserva aguardando check-in</option>';
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

        function mostrarAvisoReserva() {
            const idReserva = document.getElementById('reservaSelect').value;
            const aviso = document.getElementById('avisoReserva');
            const button = document.getElementById('btnCheckin');
            const reserva = reservasCarregadas.find(r => String(r.id) === idReserva);

            if (!reserva) {
                aviso.style.display = 'none';
                aviso.textContent = '';
                button.style.display = 'none';
                return;
            }

            aviso.textContent = 'Ao confirmar o check-in, o cliente sera cobrado pelos dias reservados desde ' + reserva.data_checkin + ' ' + reserva.hora_checkin + '.';
            aviso.style.display = 'block';
            button.style.display = 'inline-block';
        }
    </script>
</body>
</html>
