<?php


session_start();
include_once './conexao.php';
include_once './validar.php';

exigirAdm();

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quarto = trim($_POST['Quarto'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = $_POST['preco'] ?? '';
    $capacidade = (int)($_POST['capacidade'] ?? 0);
    $vagas_estacionamento = (int)($_POST['vagas_estacionamento'] ?? 0);

    if ($quarto && $tipo && $descricao && $preco) {

        $stmt = $con->prepare('
            INSERT INTO quartos (quarto, tipo, preco, descricao, capacidade, vagas_estacionamento)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('ssdsii', $quarto, $tipo, $preco, $descricao, $capacidade, $vagas_estacionamento);

        if ($stmt->execute()) {
            registrarLog("O quarto $quarto foi cadastrado por " . $_SESSION['login'], 'INSERT');
            $mensagem = "<div class='sucesso'><p>Quarto cadastrado com sucesso! Redirecionando...</p></div>";
            header('refresh:3;url=quartos.php');
        } else {
            $mensagem = "<div class='erro'><p>Erro ao cadastrar quarto: " . htmlspecialchars($stmt->error) . "</p></div>";
        }
        $stmt->close();
    } else {
        $mensagem = "<div class='erro'><p>Por favor, preencha todos os campos.</p></div>";
    }
}

// Busca tipos ativos para o select
$tipos_result = $con->query("SELECT nome FROM tipos_acomodacao WHERE status = 1 ORDER BY nome ASC");
$tipos        = $tipos_result ? $tipos_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Cadastrar Quarto — Pousada Parnaioca</title>
</head>


<body>
    <header>
        <nav>
            <ul>
                <?php
                include_once 'Menu.php';
                ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Cadastro de Quartos</h1>

        <?= $mensagem ?>

        <form action="" method="post">
            <label for="Quarto">Quarto:</label>
            <input type="text" id="Quarto" name="Quarto" required><br><br>

            <label for="tipo">Tipo de acomodação:</label>
            <select id="tipo" name="tipo" required>
                <option value="">-- Selecione --</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= htmlspecialchars($t['nome']) ?>"><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            <small> <a href="tipos_acomodacao.php" target="_blank">Gerenciar tipos</a></small>
            <br><br>

            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" required></textarea><br><br>

            <label for="capacidade">Capacidade:</label>
            <input type="number" id="capacidade" name="capacidade" min="1" required><br><br>

            <label for="vagas_estacionamento">Vagas de Estacionamento:</label>
            <input type="number" id="vagas_estacionamento" name="vagas_estacionamento" min="0" required><br><br>

            <label for="preco">Preço (base 5 noites):</label>
            <input type="number" id="preco" name="preco" step="0.01" min="0" required><br><br>

            <input type="submit" value="Gravar">

            <p><a href="quartos.php">Voltar para Quartos</a></p>
        </form>
    </main>
    <?php $con->close(); ?>
</body>

</html>