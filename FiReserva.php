<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}


$mensagem = "";

// Busca a lista para o formulário (Removido o bind_param que causava erro)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = $_POST['id_reserva']; // ID da reserva vindo do formulário

    // Busca os dados da reserva e o NOME DO QUARTO (essencial para o seu log)
    $check = $con->prepare("
        SELECT r.status, r.valor_total, q.quarto 
        FROM reservas r 
        INNER JOIN quartos q ON r.quarto_id = q.id 
        WHERE r.id = ?
    ");
    $check->bind_param("i", $id_reserva);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!$res) {
        $mensagem = "<p class='erro'>Reserva não encontrada.</p>";
    } elseif ($res['status'] !== 'ativa') {
        $mensagem = "<p class='erro'>Só pode finalizar reservas ativas.</p>";
    } else {
        $total_consumo = 0.00;

        // Processamento do Frigobar
        if (!empty($_POST['frigobar_id']) && is_array($_POST['frigobar_id'])) {
            foreach ($_POST['frigobar_id'] as $index => $item_id) {
                $qtd = (int)($_POST['quantidade'][$index] ?? 0);

                if ($qtd > 0) {
                    $busca = $con->prepare("SELECT valor, quantidade FROM frigobar WHERE id = ?");
                    $busca->bind_param("i", $item_id);
                    $busca->execute();
                    $item = $busca->get_result()->fetch_assoc();

                    if ($item && $qtd <= $item['quantidade']) {
                        $subtotal = $item['valor'] * $qtd;
                        $total_consumo += $subtotal;

                        // Insere consumo
                        $insert = $con->prepare("INSERT INTO consumo_frigobar (reserva_id, frigobar_id, quantidade, valor_total) VALUES (?, ?, ?, ?)");
                        $insert->bind_param("iiid", $id_reserva, $item_id, $qtd, $subtotal);
                        $insert->execute();

                        // ATUALIZAÇÃO IMPORTANTE: Baixa no estoque do frigobar
                        $baixa = $con->prepare("UPDATE frigobar SET quantidade = quantidade - ? WHERE id = ?");
                        $baixa->bind_param("ii", $qtd, $item_id);
                        $baixa->execute();
                    }
                }
            }
        }

        // Finaliza a reserva
        $update = $con->prepare("
            UPDATE reservas 
            SET status = 'finalizada',
                data_finalizacao = NOW(),
                valor_total = valor_total + ?
            WHERE id = ?
        ");
        $update->bind_param("di", $total_consumo, $id_reserva);
        $update->execute();

        $valor_final = $res['valor_total'] + $total_consumo;
        $mensagem = "<p class='sucesso'>Reserva finalizada! Consumo: R$ " 
        . number_format($total_consumo, 2, ',', '.') . " | Total Final: R$ " 
        . number_format($valor_final, 2, ',', '.') . "</p> . <p><a href='reservas.php'>Ir para Reservas</a></p>";

        registrarLog("A reserva do quarto {$res['quarto']} foi finalizada pelo usuário " . $_SESSION['login'], "UPDATE");
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

        #reservas-container {
            margin-top: 5px;
        }

        #reservas-container select {
            margin-top: 8px;
        }

        #reservas-container .aviso {
            color: #888;
            font-style: italic;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        .loading {
            color: #3498db;
            font-size: 0.9rem;
            margin-top: 8px;
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

        <h1>Finalizar Reserva</h1>

        <?= $mensagem ?>

        <form method="POST" id="formFinalizar">

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

            <!-- PASSO 2: Reservas do quarto selecionado -->
            <div class="step-block" id="reservas-container" style="display:none;">
                <span class="step-label">2. Selecione a reserva:</span>
                <select name="id_reserva" id="reservaSelect" required onchange="carregarFrigobar()">
                    <option value="">-- Selecione --</option>
                </select>
            </div>

            <!-- PASSO 3: Frigobar -->
            <div id="frigobar-container"></div>

            <br>
            <button type="submit" id="btnFinalizar" style="display:none;">Finalizar Reserva</button>

        </form>

    </main>

    <script>
        function carregarReservas() {
            const quartoId = document.getElementById('quartoSelect').value;
            const container = document.getElementById('reservas-container');
            const select = document.getElementById('reservaSelect');
            const frigobar = document.getElementById('frigobar-container');
            const btnFin = document.getElementById('btnFinalizar');

            // Limpa
            select.innerHTML = '<option value="">-- Selecione --</option>';
            frigobar.innerHTML = '';
            btnFin.style.display = 'none';

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
                        opt.dataset.quarto = r.quarto_id;
                        opt.textContent = `#${r.id} — ${r.cliente} — ${r.quarto} — ${r.periodo} — ${r.usuario}`;
                        select.appendChild(opt);
                    });
                })
                .catch(err => {
                    select.innerHTML = '<option value="">Erro ao carregar reservas</option>';
                    console.error(err);
                });
        }

        function carregarFrigobar() {
            const quartoId = document.getElementById('quartoSelect').value;
            const reservaId = document.getElementById('reservaSelect').value;
            const frigobar = document.getElementById('frigobar-container');
            const btnFin = document.getElementById('btnFinalizar');

            frigobar.innerHTML = '';
            btnFin.style.display = 'none';

            if (!quartoId || !reservaId) return;

            frigobar.innerHTML = '<p class="loading">⏳ Carregando itens do frigobar...</p>';

            fetch('buscar_frigobar.php?quarto_id=' + quartoId)
                .then(r => r.text())
                .then(html => {
                    frigobar.innerHTML = html;
                    btnFin.style.display = 'block';
                })
                .catch(err => {
                    frigobar.innerHTML = '<p style="color:red;">Erro ao carregar frigobar.</p>';
                    console.error(err);
                });
        }
    </script>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

</body>

</html>