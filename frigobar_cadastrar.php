<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

exigirAdm();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="1.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnaioca — Cadastro de Frigobar</title>
</head>

<body>

    <header>
        <nav>
            <ul><?php include_once 'menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Cadastro de itens do Frigobar</h1>

        <?php
        // Resolve o quarto_id vindo da URL (?id=X ou ?quarto_id=X)
        $quarto_id_url = $_GET['id'] ?? $_GET['quarto_id'] ?? $_SESSION['quarto_id'] ?? null;
        $quarto        = null;

        if ($quarto_id_url) {
            // ── CORREÇÃO: usa prepare em vez de concatenação ──
            $stmt_q = $con->prepare('SELECT id, quarto FROM quartos WHERE id = ?');
            $stmt_q->bind_param('i', $quarto_id_url);
            $stmt_q->execute();
            $quarto = $stmt_q->get_result()->fetch_assoc();
            $stmt_q->close();
        }
        ?>

        <form action="" method="post">

            <?php if ($quarto): ?>
                <label for="quarto_display">Quarto:</label>
                <input type="text" id="quarto_display"
                    value="<?= htmlspecialchars($quarto['quarto']) ?>" disabled><br><br>
                <input type="hidden" name="quarto_id" value="<?= (int)$quarto['id'] ?>">
            <?php else: ?>
                <p class="erro">Quarto não encontrado. <a href="quartos.php">Voltar</a></p>
            <?php endif; ?>

            <label for="nome">Nome do Item:</label>
            <input type="text" id="nome" name="nome" required><br><br>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" required><br><br>

            <label for="valor">Valor (R$):</label>
            <input type="text" id="valor" name="valor" placeholder="0,00"
                pattern="[0-9]+([,\.][0-9]{1,2})?"
                title="Digite um valor numérico (ex: 10,50)" required><br><br>

            <input type="submit" value="Gravar">
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $nome         = trim($_POST['nome']  ?? '');
            $valor_bruto  = trim($_POST['valor'] ?? '');
            $valor_limpo  = preg_replace('/[^0-9,.]/', '', $valor_bruto);
            $valor_final  = (float)str_replace(',', '.', $valor_limpo);
            $quantidade   = (int)($_POST['quantidade'] ?? 0);
            $quarto_id    = (int)($_POST['quarto_id']  ?? 0);

            if (!empty($nome) && $valor_final > 0 && $quarto_id > 0 && $quantidade > 0) {

                // ── CORREÇÃO: prepare/bind_param em vez de concatenação ──
                $stmt = $con->prepare('
                INSERT INTO frigobar (nome, quantidade, valor, quarto_id)
                VALUES (?, ?, ?, ?)
            ');
                $stmt->bind_param('sidi', $nome, $quantidade, $valor_final, $quarto_id);

                if ($stmt->execute()) {
                    $nome_quarto = $quarto['quarto'] ?? "ID $quarto_id";
                    registrarLog(
                        "O item $nome foi cadastrado no frigobar do quarto $nome_quarto por " . $_SESSION['login'],
                        'INSERT'
                    );
                    echo "<p style='color:green;'>Item cadastrado com sucesso!</p>";
                    echo "<input type='button' value='Voltar para o quarto'
                      onclick='window.location.href=\"quartos_detalhes.php?id=" . (int)$quarto_id . "\"'>";
                } else {
                    echo "<p style='color:red;'>Erro ao cadastrar: " . htmlspecialchars($stmt->error) . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p style='color:red;'>Preencha todos os campos corretamente.</p>";
            }
        }
        ?>
    </main>

</body>

</html>
