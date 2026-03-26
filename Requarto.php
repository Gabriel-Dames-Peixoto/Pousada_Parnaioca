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
    $checkout = $_POST['checkout'];
    $stmt_nome = $con->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmt_nome->bind_param("i", $cliente_id);
    $stmt_nome->execute();
    $res_nome = $stmt_nome->get_result()->fetch_assoc();
    $nome_cliente_log = $res_nome['nome'] ?? "ID $cliente_id"; // Fallback caso não encontre
    $stmt_nome->close();

    if (!$cliente_id || !$checkin || !$checkout) {
        die("Dados inválidos.");
    }

    // VALIDAR DATA PASSADA
    $hoje = date('Y-m-d');
    if ($checkin < $hoje) {
        die("❌ Não é permitido reservar datas passadas.");
    }

    // VERIFICAR CONFLITO
    $stmt_check = $con->prepare("
        SELECT * FROM reservas 
        WHERE quarto_id = ? 
        AND status = 'ativa'
        AND (
            data_checkin <= ? AND data_checkout >= ?
        )
    ");

    $stmt_check->bind_param("iss", $quarto_id, $checkout, $checkin);
    $stmt_check->execute();
    $reserva_existente = $stmt_check->get_result();

    if ($reserva_existente->num_rows > 0) {
        die("❌ Este quarto já está reservado nesse período.");
    }

    // CALCULAR DIAS
    $data1 = new DateTime($checkin);
    $data2 = new DateTime($checkout);
    $dias = $data1->diff($data2)->days;

    if ($dias <= 0) {
        die("Check-out deve ser após o check-in.");
    }

    // CALCULAR VALOR
    $precoBase = $dados_quarto['preco'];
    $valorFinal = $precoBase;

    if ($dias < 5) {
        $valorFinal *= (1 - (5 - $dias) * 0.10);
    } elseif ($dias > 5) {
        $valorFinal *= (1 + ($dias - 5) * 0.10);
    }

    // INSERIR RESERVA
    $stmt = $con->prepare("
        INSERT INTO reservas 
        (quarto_id, cliente_id, valor_total, data_checkin, data_checkout, status) 
        VALUES (?, ?, ?, ?, ?, 'ativa')
    ");
    $dataInicio = $data1->format('d/m/Y');
    $dataFim = $data2->format('d/m/Y');
    $nomeQuarto = $dados_quarto['quarto'];
    $stmt->bind_param("iidss", $quarto_id, $cliente_id, $valorFinal, $checkin, $checkout);
    $stmt->execute();
    registrarLog("A reserva do quarto $nomeQuarto foi realizada para o cliente $nome_cliente_log no período de 
    $dataInicio à $dataFim pelo usuário " . $_SESSION['login'], "INSERT");

    header("Location: reservas.php?sucesso=1");
    exit();
}

$busca = filter_input(INPUT_GET, 'busca_cliente', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <title>Reserva de Quarto</title>
</head>

<body>

<header>
    <nav>
        <ul>
            <?php include_once 'menu.php'; ?>
        </ul>
    </nav>
</header>

<main>
    <h1>Reservar quarto <?= htmlspecialchars($dados_quarto['quarto']) ?></h1>

    <p>
        <strong>Preço base (5 noites):</strong>
        R$ <?= number_format($dados_quarto['preco'], 2, ',', '.') ?>
    </p>

    <hr>

    <!-- BUSCA -->
    <form method="GET">
        <input type="hidden" name="id" value="<?= $id_quarto ?>">
        <input type="text" name="busca_cliente" placeholder="Buscar cliente" value="<?= htmlspecialchars($busca) ?>">
        <button type="submit">Buscar</button>
    </form>

    <br>

    <!-- RESERVA -->
    <form method="POST">

        <input type="hidden" name="quarto_id" value="<?= $id_quarto ?>">

        <label>Cliente:</label><br>
        <select name="cliente_id" required>
            <?php
            $sql_clientes = "SELECT * FROM clientes WHERE nome LIKE ? OR cpf LIKE ?";
            $stmt_c = $con->prepare($sql_clientes);

            $term = "%$busca%";
            $stmt_c->bind_param("ss", $term, $term);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result();

            while ($cliente = $res_c->fetch_assoc()) {
                echo "<option value='{$cliente['id']}'>
                        {$cliente['nome']} - {$cliente['cpf']}
                      </option>";
            }
            ?>
        </select>

        <br><br>

        <label>Quantidade de pessoas:</label>
        <input type="number" name="quantidade_pessoas" min="1" max="<?= $dados_quarto['capacidade'] ?>" required>

        <br><br>

        <label>Check-in:</label><br>
        <input type="date" name="checkin" required>

        <br><br>

        <label>Check-out:</label><br>
        <input type="date" name="checkout" required>

        <br><br>

        <label>Valor calculado:</label><br>
        <input type="text" id="valor_final" readonly>

        <br><br>

        <button type="submit" name="reservar">Reservar</button>

    </form>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const precoBase = <?= $dados_quarto['preco'] ?>;

    const formatarMoeda = (valor) => {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    };

    const checkin = document.querySelector('input[name="checkin"]');
    const checkout = document.querySelector('input[name="checkout"]');
    const campoValor = document.getElementById('valor_final');

    function calcularValor() {

        if (!checkin.value || !checkout.value) {
            campoValor.value = "";
            return;
        }

        let data1 = new Date(checkin.value);
        let data2 = new Date(checkout.value);
        let hoje = new Date();
        hoje.setHours(0,0,0,0);

        let dias = (data2 - data1) / (1000 * 60 * 60 * 24);

        if (dias <= 0) {
            campoValor.value = "Datas inválidas";
            return;
        }

        if (data1 < hoje) {
            campoValor.value = "Data inválida (passado)";
            return;
        }

        let valor = precoBase;

        if (dias < 5) {
            valor *= (1 - (5 - dias) * 0.10);
        } else if (dias > 5) {
            valor *= (1 + (dias - 5) * 0.10);
        }

        campoValor.value = formatarMoeda(valor);
    }

    checkin.addEventListener("change", calcularValor);
    checkout.addEventListener("change", calcularValor);

});
</script>

</body>
</html>
