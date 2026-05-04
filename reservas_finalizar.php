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
$quartos_com_reserva = $stmt_quartos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_quartos->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reserva = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);

    if (!$id_reserva) {
        $mensagem = "<p class='erro'>Selecione uma reserva valida.</p>";
    } else {
        $con->begin_transaction();

        try {
            $check = $con->prepare("
                SELECT
                    r.id,
                    r.status,
                    r.quarto_id,
                    r.data_checkin,
                    r.hora_checkin,
                    r.data_checkin_real,
                    r.data_checkout,
                    r.hora_checkout,
                    q.quarto,
                    q.preco
                FROM reservas r
                INNER JOIN quartos q ON r.quarto_id = q.id
                WHERE r.id = ?
                FOR UPDATE
            ");
            $check->bind_param('i', $id_reserva);
            $check->execute();
            $reserva = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$reserva) {
                throw new RuntimeException('Reserva nao encontrada.');
            }

            if ($reserva['status'] !== 'ativa') {
                throw new RuntimeException('So e possivel finalizar reservas ativas.');
            }

            if (empty($reserva['data_checkin_real'])) {
                throw new RuntimeException('Realize o check-in antes de finalizar a reserva.');
            }

            $inicioReservado = DateTime::createFromFormat('Y-m-d H:i:s', $reserva['data_checkin'] . ' ' . $reserva['hora_checkin']);
            $checkinReal     = new DateTime($reserva['data_checkin_real']);
            $checkoutReal    = DateTime::createFromFormat('Y-m-d H:i:s', $reserva['data_checkout'] . ' ' . $reserva['hora_checkout']);
            $agora           = new DateTime();

            if (!$inicioReservado || !$checkinReal || !$checkoutReal) {
                throw new RuntimeException('A reserva possui datas invalidas e nao pode ser finalizada.');
            }

            $fimEfetivo = $agora < $checkoutReal ? clone $agora : clone $checkoutReal;

            $noitesPacote = max(1, (int)$inicioReservado->diff($checkoutReal)->days);
            $diasCobrados = max(1, (int)$inicioReservado->diff($fimEfetivo)->days);

            $valorDiaria  = round(((float)$reserva['preco']) / $noitesPacote, 2);
            $valorDiarias = round($valorDiaria * $diasCobrados, 2);

            $consumos = [];
            if (!empty($_POST['frigobar_id']) && is_array($_POST['frigobar_id'])) {
                foreach ($_POST['frigobar_id'] as $index => $itemId) {
                    $itemId = (int)$itemId;
                    $qtd    = (int)($_POST['quantidade'][$index] ?? 0);

                    if ($itemId > 0 && $qtd > 0) {
                        if (!isset($consumos[$itemId])) {
                            $consumos[$itemId] = 0;
                        }
                        $consumos[$itemId] += $qtd;
                    }
                }
            }

            $total_consumo = 0.00;
            $erros_estoque = [];

            foreach ($consumos as $itemId => $qtd) {
                $busca = $con->prepare("
                    SELECT id, nome, valor, quantidade, quarto_id, status
                    FROM frigobar
                    WHERE id = ?
                    FOR UPDATE
                ");
                $busca->bind_param('i', $itemId);
                $busca->execute();
                $item = $busca->get_result()->fetch_assoc();
                $busca->close();

                if (!$item || (int)$item['quarto_id'] !== (int)$reserva['quarto_id'] || $item['status'] !== '1') {
                    $erros_estoque[] = "Item de frigobar invalido para esta reserva.";
                    continue;
                }

                if ($qtd > (int)$item['quantidade']) {
                    $erros_estoque[] = "Estoque insuficiente para \"" . $item['nome'] . "\": solicitado $qtd, disponivel {$item['quantidade']}.";
                    continue;
                }

                $subtotal       = round(((float)$item['valor']) * $qtd, 2);
                $total_consumo += $subtotal;

                $insert = $con->prepare("
                    INSERT INTO consumo_frigobar (reserva_id, frigobar_id, quantidade, valor_total)
                    VALUES (?, ?, ?, ?)
                ");
                $insert->bind_param('iiid', $id_reserva, $itemId, $qtd, $subtotal);
                $insert->execute();
                $insert->close();

                if (((int)$item['quantidade'] - $qtd) <= 0) {
                    $baixa = $con->prepare("UPDATE frigobar SET quantidade = 0, status = '0' WHERE id = ?");
                    $baixa->bind_param('i', $itemId);
                } else {
                    $baixa = $con->prepare("UPDATE frigobar SET quantidade = quantidade - ? WHERE id = ?");
                    $baixa->bind_param('ii', $qtd, $itemId);
                }
                $baixa->execute();
                $baixa->close();
            }

            $valor_final = round($valorDiarias + $total_consumo, 2);

            $update = $con->prepare("
                UPDATE reservas
                SET status = 'finalizada',
                    data_finalizacao = NOW(),
                    valor_total = ?
                WHERE id = ?
            ");
            $update->bind_param('di', $valor_final, $id_reserva);
            $update->execute();
            $update->close();

            $con->commit();

            registrarLog(
                "A reserva do quarto {$reserva['quarto']} foi finalizada pelo usuario " . $_SESSION['login'],
                'UPDATE'
            );

            $aviso_estoque = '';
            if (!empty($erros_estoque)) {
                $aviso_estoque = "<p class='aviso'><strong>Atencao:</strong> "
                    . implode('<br>', array_map('htmlspecialchars', $erros_estoque))
                    . '</p>';
            }

            $mensagemCobranca = 'A cobranca considera todo o periodo reservado do quarto.';
            if ($checkinReal > $inicioReservado) {
                $mensagemCobranca = 'A cobranca considera os dias reservados desde o check-in previsto, mesmo com chegada apos essa data.';
            }

            $mensagem = "
                $aviso_estoque
                <p class='sucesso'>
                    Reserva finalizada com sucesso!<br>
                    Pacote: <strong>{$noitesPacote} noite(s)</strong><br>
                    Diaria: <strong>R$ " . number_format($valorDiaria, 2, ',', '.') . "</strong><br>
                    Dias cobrados: <strong>{$diasCobrados}</strong><br>
                    Valor das diarias: <strong>R$ " . number_format($valorDiarias, 2, ',', '.') . "</strong><br>
                    Consumo do frigobar: <strong>R$ " . number_format($total_consumo, 2, ',', '.') . "</strong><br>
                    Total final: <strong>R$ " . number_format($valor_final, 2, ',', '.') . "</strong><br>
                    <small>{$mensagemCobranca}</small>
                </p>
                <p><a href='reservas.php'>Ir para Reservas</a></p>
            ";
        } catch (Throwable $e) {
            $con->rollback();
            $mensagem = "<p class='erro'>" . htmlspecialchars($e->getMessage() ?: 'Erro ao finalizar a reserva.') . "</p>";
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
    <title>Finalizar Reserva - Pousada Parnaioca</title>
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
            <ul><?php include_once 'Menu.php'; ?></ul>
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
            select.innerHTML = '<option value="">Carregando...</option>';

            fetch('reservas_buscar.php?quarto_id=' + quartoId + '&status=ativa&filtro=hospedadas')
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
                        opt.textContent = `#${r.id} - ${r.cliente} - ${r.quarto} - ${r.periodo} - ${r.usuario}`;
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

            frigobar.innerHTML = '<p class="loading">Carregando itens do frigobar...</p>';

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
