<?php
session_start();
include_once './conexao.php';
include_once './validar.php';   


$mensagem = "";

// Buscar QUARTOS que têm reservas ativas
$stmt_quartos = $con->prepare("
    SELECT DISTINCT q.id, q.quarto
    FROM quartos q
    INNER JOIN reservas r ON r.quarto_id = q.id
    WHERE r.status = 'ativa'
    ORDER BY q.quarto ASC
");
$stmt_quartos->execute();
$quartos_com_reserva = $stmt_quartos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quartos->close();

// 🔥 CANCELAR RESERVA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id     = $_POST['id_reserva'];
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
    <style>
        .step-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 6px;
            display: block;
            font-size: 0.95rem;
        }

        .step-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .loading {
            color: #3498db;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        textarea {
            min-height: 80px;
        }

        #cancelar-container {
            display: none;
        }
    </style>
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

        <form method="POST" id="formCancelar">

            <!-- PASSO 1: Selecionar quarto -->
            <div class="step-block">
                <span class="step-label">1. Selecione o quarto:</span>
                <select id="quartoSelect" onchange="carregarReservas()">
                    <option value="">-- Selecione um quarto --</option>
                    <?php foreach ($quartos_com_reserva as $q): ?>
                        <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['quarto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- PASSO 2: Reservas do quarto -->
            <div class="step-block" id="reservas-container" style="display:none;">
                <span class="step-label">2. Selecione a reserva:</span>
                <select name="id_reserva" id="reservaSelect" required onchange="mostrarMotivo()">
                    <option value="">-- Selecione --</option>
                </select>
            </div>

            <!-- PASSO 3: Motivo + botão -->
            <div class="step-block" id="cancelar-container">
                <span class="step-label">3. Motivo do cancelamento:</span>
                <textarea name="motivo" required placeholder="Descreva o motivo do cancelamento..."></textarea>
                <br><br>
                <button type="submit" style="background:#e74c3c;">Cancelar Reserva</button>
            </div>

        </form>

    </main>

    <script>
        function carregarReservas() {
            const quartoId = document.getElementById('quartoSelect').value;
            const container = document.getElementById('reservas-container');
            const select = document.getElementById('reservaSelect');
            const cancelDiv = document.getElementById('cancelar-container');

            select.innerHTML = '<option value="">-- Selecione --</option>';
            cancelDiv.style.display = 'none';

            if (!quartoId) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            select.innerHTML = '<option value="">⏳ Carregando...</option>';

            fetch('buscar_reservas.php?quarto_id=' + quartoId + '&status=ativa')
                .then(r => r.json())
                .then(data => {
                    select.innerHTML = '<option value="">-- Selecione a reserva --</option>';

                    if (data.length === 0) {
                        select.innerHTML = '<option value="" disabled>Nenhuma reserva ativa para este quarto</option>';
                        return;
                    }

                    data.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.id;
                        opt.textContent = `#${r.id} — ${r.cliente} — ${r.quarto} — ${r.periodo} — ${r.usuario}`;
                        select.appendChild(opt);
                    });
                })
                .catch(err => {
                    select.innerHTML = '<option value="">Erro ao carregar reservas</option>';
                    console.error(err);
                });
        }

        function mostrarMotivo() {
            const val = document.getElementById('reservaSelect').value;
            document.getElementById('cancelar-container').style.display = val ? 'block' : 'none';
        }
    </script>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

</body>

</html>