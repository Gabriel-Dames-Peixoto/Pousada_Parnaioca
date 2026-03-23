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
$stmt_q = $con->prepare("SELECT quarto, preco, descricao FROM quartos WHERE id = ?");
$stmt_q->bind_param("i", $id_quarto);
$stmt_q->execute();
$dados_quarto = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

if (!$dados_quarto) {
    die("Quarto não encontrado.");
}

// PROCESSAMENTO DO POST
if (isset($_POST['reservar'])) {

    $quarto_id = $_POST['quarto_id'];
    $cliente_id = $_POST['cliente_id'];
    $dias = (int) $_POST['dias'];

    $precoBase = $dados_quarto['preco'];
    $valorFinal = $precoBase;

    if ($dias < 5) {
        $desconto = (5 - $dias) * 0.10;
        $valorFinal = $precoBase * (1 - $desconto);
    } elseif ($dias > 5) {
        $acrescimo = ($dias - 5) * 0.10;
        $valorFinal = $precoBase * (1 + $acrescimo);
    }

    // Inserir reserva
    $stmt = $con->prepare("INSERT INTO reservas (quarto_id, cliente_id, valor_total) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $quarto_id, $cliente_id, $valorFinal);
    $stmt->execute();

    // Atualizar status do quarto
    $stmt2 = $con->prepare("UPDATE quartos SET status = 0 WHERE id = ?");
    $stmt2->bind_param("i", $quarto_id);
    $stmt2->execute();

    // REDIRECT (evita duplicação)
    header("Location: reservas.php?id=$quarto_id&sucesso=1");
    exit();
}

// BUSCA DE CLIENTE
$busca = filter_input(INPUT_GET, 'busca_cliente', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png">
    <title>Pousada Parnoica - Cadastro</title>
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
        R$ <?= number_format($dados_quarto['preco'], 2, ',', '.') ?><br>
        (Valor varia conforme quantidade de dias)
    </p>

    <!-- MENSAGEM -->
    <?php if (isset($_GET['sucesso'])): ?>
        <p style="color:green;">Reserva realizada com sucesso!</p>
    <?php endif; ?>

    <form method="GET">
        <input type="hidden" name="id" value="<?= $id_quarto ?>">
        <input type="text" name="busca_cliente" placeholder="Buscar por nome ou CPF" value="<?= htmlspecialchars($busca) ?>">
        <button type="submit">Buscar</button>
        <input type="hidden" name="quarto_id" value="<?= $id_quarto ?>">
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

        <label>Quantidade de dias:</label><br>
        <input type="number" name="dias" min="1" required>

        <br><br>

        <label>Valor calculado:</label><br>
        <input type="text" id="valor_final" readonly>

        <br><br>

        <button type="submit" name="reservar">Reservar</button>
        <?php if (isset($_GET['sucesso'])): ?>
            <button type="button" onclick="window.location.href='quartos.php'">Voltar para Quartos</button>
        <?php endif; ?>
    </form>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const precoBase = <?= $dados_quarto['preco'] ?>;

    const inputDias = document.querySelector('input[name="dias"]');
    const campoValor = document.getElementById('valor_final');

    inputDias.addEventListener('input', function() {
        let dias = parseInt(this.value);
        let valor = precoBase;

        if (!dias || dias <= 0) {
            campoValor.value = "";
            return;
        }

        if (dias < 5) {
            let desconto = (5 - dias) * 0.10;
            valor = precoBase * (1 - desconto);
        } else if (dias > 5) {
            let acrescimo = (dias - 5) * 0.10;
            valor = precoBase * (1 + acrescimo);
        }

        campoValor.value = "R$ " + valor.toFixed(2);
    });
});
</script>

</body>
</html>
