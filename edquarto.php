<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

exigirAdm();

$mensagem   = '';
$id_quarto  = $_GET['id'] ?? null;
$dados_quarto = [];

// Processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_quarto  = (int)($_POST['id_quarto'] ?? 0);
    $quarto     = trim($_POST['Quarto']     ?? '');
    $tipo       = trim($_POST['tipo']       ?? '');
    $descricao  = trim($_POST['descricao']  ?? '');
    $preco      = $_POST['preco']           ?? '';
    $capacidade = (int)($_POST['capacidade'] ?? 0);
    $vagas      = (int)($_POST['vagas_estacionamento'] ?? 0);
    $status_raw = $_POST['Status'] ?? '';
    $status     = ($status_raw === 'disponivel') ? '1' : '0';

    if ($id_quarto && $quarto && $tipo && $descricao && $preco) {

        $sql = 'UPDATE quartos
                SET quarto=?, tipo=?, preco=?, descricao=?, capacidade=?, vagas_estacionamento=?, status=?
                WHERE id=?';

        $stmt = $con->prepare($sql);
        $stmt->bind_param('ssdsissi', $quarto, $tipo, $preco, $descricao, $capacidade, $vagas, $status, $id_quarto);

        if ($stmt->execute()) {
            registrarLog("Dados do quarto $quarto foram atualizados por " . $_SESSION['login'], 'UPDATE');
            $mensagem = "<div style='color:green;'>✅ Quarto atualizado com sucesso! Redirecionando...</div>";
            header('refresh:2;url=quartos.php');
        } else {
            $mensagem = "<div style='color:red;'>Erro: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    } else {
        $mensagem = "<div style='color:red;'>Preencha todos os campos.</div>";
    }
}

// Carregar dados do quarto
if ($id_quarto) {
    $stmt = $con->prepare('SELECT * FROM quartos WHERE id = ?');
    $stmt->bind_param('i', $id_quarto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $dados_quarto = $result->fetch_assoc();
    } else {
        die('Quarto não encontrado.');
    }
    $stmt->close();
} else {
    die('ID não informado.');
}

// Tipos ativos para o select
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
    <title>Pousada Parnaióca</title>

</head>

<body>
    <header>
        <nav>
            <ul><?php include_once 'menu.php'; ?></ul>
        </nav>
    </header>
    <main>
        <h1>Editar Quarto <?= htmlspecialchars($dados_quarto['quarto']) ?></h1>

        <?= $mensagem ?>

        <form method="post">
            <input type="hidden" name="id_quarto" value="<?= $id_quarto ?>">

            <label>Quarto:</label>
            <input type="text" name="Quarto" value="<?= htmlspecialchars($dados_quarto['quarto']) ?>" required><br><br>

            <label>Tipo de acomodação:</label>
            <select name="tipo" required>
                <option value="">-- Selecione --</option>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= htmlspecialchars($t['nome']) ?>"
                        <?= $dados_quarto['tipo'] === $t['nome'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nome']) ?>
                    </option>
                <?php endforeach; ?>
                <?php
                // Se o tipo salvo não está na lista ativa, exibe como opção extra
                $nomes = array_column($tipos, 'nome');
                if ($dados_quarto['tipo'] && !in_array($dados_quarto['tipo'], $nomes)):
                ?>
                    <option value="<?= htmlspecialchars($dados_quarto['tipo']) ?>" selected>
                        <?= htmlspecialchars($dados_quarto['tipo']) ?> (tipo inativo)
                    </option>
                <?php endif; ?>
            </select>
            <small> <a href="tipos_acomodacao.php" target="_blank">Gerenciar tipos</a></small>
            <br><br>

            <label>Descrição:</label>
            <textarea name="descricao" required><?= htmlspecialchars($dados_quarto['descricao']) ?></textarea><br><br>

            <label>Capacidade:</label>
            <input type="number" name="capacidade" value="<?= (int)$dados_quarto['capacidade'] ?>" required><br><br>

            <label>Vagas estacionamento:</label>
            <input type="number" name="vagas_estacionamento" value="<?= (int)$dados_quarto['vagas_estacionamento'] ?>" required><br><br>

            <label>Preço:</label>
            <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($dados_quarto['preco']) ?>" required><br><br>

            <label>Status:</label>
            <select name="Status" required>
                <option value="disponivel" <?= $dados_quarto['status'] == '1' ? 'selected' : '' ?>>Disponível</option>
                <option value="indisponivel" <?= $dados_quarto['status'] == '0' ? 'selected' : '' ?>>Indisponível</option>
            </select><br><br>

            <input type="submit" value="Gravar">
        </form>

        <br><a href="quartos.php">Voltar</a>
    </main>
    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>
</body>

</html>