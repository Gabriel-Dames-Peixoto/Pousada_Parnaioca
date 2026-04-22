<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

exigirAdm();

$mensagem = '';

$stmt_quartos = $con->prepare('
    SELECT DISTINCT q.id, q.quarto
    FROM quartos q
    INNER JOIN reservas r ON r.quarto_id = q.id
    WHERE r.status = \'ativa\'
    ORDER BY q.quarto ASC
');
$stmt_quartos->execute();
$quartos_com_reserva = $stmt_quartos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quartos->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_reserva = (int)($_POST['id_reserva'] ?? 0);

    if (!$id_reserva) {
        $mensagem = "<p class='erro'>Selecione uma reserva válida.</p>";
    } else {

        $check = $con->prepare('
            SELECT r.status, r.data_checkin, r.data_checkout, q.quarto, q.preco
            FROM reservas r
            INNER JOIN quartos q ON r.quarto_id = q.id
            WHERE r.id = ?
        ');
        $check->bind_param('i', $id_reserva);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$res) {
            $mensagem = "<p class='erro'>Reserva não encontrada.</p>";
        } elseif ($res['status'] !== 'ativa') {
            $mensagem = "<p class='erro'>Só é possível finalizar reservas ativas.</p>";
        } else {

            // =========================
            // 🧠 DATAS DA RESERVA
            // =========================
            $data_checkin  = new DateTime($res['data_checkin']);
            $data_checkout = new DateTime($res['data_checkout']);

            // Noites contratadas no pacote
            $noites_pacote = (int)$data_checkin->diff($data_checkout)->days;
            if ($noites_pacote <= 0) $noites_pacote = 1;

            // Dias efetivamente utilizados até a data de checkout da reserva
            // Usa o menor entre: data de hoje e data de checkout contratada
            $hoje = new DateTime();
            $hoje->setTime(0, 0, 0);
            $data_saida_efetiva = $hoje < $data_checkout ? $hoje : $data_checkout;

            $dias_usados = (int)$data_checkin->diff($data_saida_efetiva)->days;
            if ($dias_usados <= 0) $dias_usados = 1;

            // Valor da diária = preço base ÷ noites do pacote
            $valor_diaria  = $res['preco'] / $noites_pacote;
            $valor_diarias = round($valor_diaria * $dias_usados, 2);

            // =========================
            // 🍫 CONSUMO DO FRIGOBAR
            // =========================
            $total_consumo = 0.00;
            $erros_estoque = [];

            if (!empty($_POST['frigobar_id']) && is_array($_POST['frigobar_id'])) {

                foreach ($_POST['frigobar_id'] as $index => $item_id) {
                    $item_id = (int)$item_id;
                    $qtd     = (int)($_POST['quantidade'][$index] ?? 0);

                    if ($qtd <= 0) continue;

                    $busca = $con->prepare('SELECT nome, valor, quantidade FROM frigobar WHERE id = ?');
                    $busca->bind_param('i', $item_id);
                    $busca->execute();
                    $item = $busca->get_result()->fetch_assoc();
                    $busca->close();

                    if (!$item) continue;

                    if ($qtd > $item['quantidade']) {
                        $erros_estoque[] = "Estoque insuficiente para \"{$item['nome']}\": solicitado $qtd, disponível {$item['quantidade']}.";
                        continue;
                    }

                    $subtotal       = round($item['valor'] * $qtd, 2);
                    $total_consumo += $subtotal;

                    $insert = $con->prepare('
                        INSERT INTO consumo_frigobar (reserva_id, frigobar_id, quantidade, valor_total)
                        VALUES (?, ?, ?, ?)
                    ');
                    $insert->bind_param('iiid', $id_reserva, $item_id, $qtd, $subtotal);
                    $insert->execute();
                    $insert->close();

                    // Atualizar estoque
                    if ($item['quantidade'] - $qtd <= 0) {
                        $baixa = $con->prepare('UPDATE frigobar SET quantidade = 0, status = 0 WHERE id = ?');
                        $baixa->bind_param('i', $item_id);
                    } else {
                        $baixa = $con->prepare('UPDATE frigobar SET quantidade = quantidade - ? WHERE id = ?');
                        $baixa->bind_param('ii', $qtd, $item_id);
                    }
                    $baixa->execute();
                    $baixa->close();
                }
            }

            // =========================
            // 💰 TOTAL FINAL
            // =========================
            $valor_final = round($valor_diarias + $total_consumo, 2);

            $update = $con->prepare('
                UPDATE reservas
                SET status = \'finalizada\',
                    data_finalizacao = NOW(),
                    valor_total = ?
                WHERE id = ?
            ');
            $update->bind_param('di', $valor_final, $id_reserva);
            $update->execute();
            $update->close();

            registrarLog(
                "A reserva do quarto {$res['quarto']} foi finalizada pelo usuário " . $_SESSION['login'],
                'UPDATE'
            );

            $aviso_estoque = '';
            if (!empty($erros_estoque)) {
                $aviso_estoque = "<p class='aviso'><strong>Atenção:</strong> "
                    . implode('<br>', array_map('htmlspecialchars', $erros_estoque))
                    . '</p>';
            }

            $mensagem = "
                $aviso_estoque
                <p class='sucesso'>
                    Reserva finalizada com sucesso!<br>
                    Pacote: <strong>{$noites_pacote} noite(s)</strong><br>
                    Diária: <strong>R$ " . number_format($valor_diaria, 2, ',', '.') . "</strong><br>
                    Dias utilizados: <strong>{$dias_usados}</strong><br>
                    Valor das diárias: <strong>R$ " . number_format($valor_diarias, 2, ',', '.') . "</strong><br>
                    Consumo do frigobar: <strong>R$ " . number_format($total_consumo, 2, ',', '.') . "</strong><br>
                    Total final: <strong>R$ " . number_format($valor_final, 2, ',', '.') . "</strong>
                </p>
                <p><a href='reservas.php'>Ir para Reservas</a></p>
            ";
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
    <title>Finalizar Reserva — Pousada Parnaioca</title>
    <style>
        .step-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 6px;
            display: block;
            font-size: .95rem;
        }

        .step-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }

        .aviso {
            background: #fff8e1;
            color: #795600;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 5px solid #f9c74f;
        }

        .loading {
            color: #3498db;
            font-size: .9rem;
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

            <div class="step-block">
                <span class="step-label">1. Selecione o quarto:</span>
                <select id="quartoSelect" onchange="carregarReservas()">
                    <option value="">-- Selecione um quarto --</option>
                    <?php foreach ($quartos_com_reserva as $q): ?>
                        <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['quarto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="step-block" id="reservas-container" style="display:none;">
                <span class="step-label">2. Selecione a reserva:</span>
                <select name="id_reserva" id="reservaSelect" required onchange="carregarFrigobar()">
                    <option value="">-- Selecione --</option>
                </select>
            </div>

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

            select.innerHTML = '<option value="">-- Selecione --</option>';
            frigobar.innerHTML = '';
            btnFin.style.display = 'none';

            if (!quartoId) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            select.innerHTML = '<option value="">⏳ Carregando...</option>';

            fetch('reservas_buscar.php?quarto_id=' + quartoId + '&status=ativa')
                .then(r => r.json())
                .then(data => {
                    select.innerHTML = '<option value="">-- Selecione a reserva --</option>';
                    if (!data.length) {
                        select.innerHTML = '<option disabled>Nenhuma reserva ativa para este quarto</option>';
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
                .catch(() => {
                    select.innerHTML = '<option>Erro ao carregar reservas</option>';
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

            fetch('frigobar_buscar.php?quarto_id=' + quartoId)
                .then(r => r.text())
                .then(html => {
                    frigobar.innerHTML = html;
                    btnFin.style.display = 'block';
                })
                .catch(() => {
                    frigobar.innerHTML = '<p style="color:red">Erro ao carregar frigobar.</p>';
                });
        }
    </script>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>