<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica</title>
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
        <section class="container">
            <h1>Editar Cadastro</h1>

            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $id       = (int)$_POST['id'];
                $nome     = trim($_POST['nome']            ?? '');
                $dn       = trim($_POST['data_nascimento'] ?? '');
                $email    = trim($_POST['email']           ?? '');
                $telefone = trim($_POST['telefone']        ?? '');
                $estado   = trim($_POST['estado']          ?? '');
                $cidade   = trim($_POST['cidade']          ?? '');

                $erros = [];

                // Valida data de nascimento
                if ($dn) {
                    $dt   = DateTime::createFromFormat('Y-m-d', $dn);
                    $hoje = new DateTime();
                    $min  = new DateTime('1900-01-01');

                    if (!$dt || $dt > $hoje) {
                        $erros[] = "Data de nascimento inválida ou no futuro.";
                    } elseif ($dt < $min) {
                        $erros[] = "Data de nascimento anterior a 1900 não é permitida.";
                    }
                } else {
                    $erros[] = "Data de nascimento é obrigatória.";
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erros[] = "E-mail inválido.";
                }

                if (!empty($erros)) {
                    foreach ($erros as $e) {
                        echo "<p class='erro'>" . htmlspecialchars($e) . "</p>";
                    }
                } else {
                    if (isAdm()) {
                        $status = (int)($_POST['status'] ?? 1);
                    } else {
                        $stmt_s = $con->prepare("SELECT status FROM clientes WHERE id = ?");
                        $stmt_s->bind_param("i", $id);
                        $stmt_s->execute();
                        $row_s  = $stmt_s->get_result()->fetch_assoc();
                        $status = (int)($row_s['status'] ?? 1);
                        $stmt_s->close();
                    }

                    $sql  = "UPDATE clientes SET nome=?, data_nascimento=?, email=?, telefone=?, estado=?, cidade=?, status=? WHERE id=?";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("ssssssii", $nome, $dn, $email, $telefone, $estado, $cidade, $status, $id);

                    if ($stmt->execute()) {
                        registrarLog("Dados do cliente $nome foram atualizados por " . $_SESSION['login'], "UPDATE");
                        echo "<p class='sucesso'>Cadastro atualizado com sucesso! Redirecionando...</p>";
                        header("refresh:3;url=clientes.php");
                    } else {
                        echo "<p class='erro'>Erro ao atualizar cadastro: " . htmlspecialchars($stmt->error) . "</p>";
                    }
                    $stmt->close();
                }
            }

            $id      = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : null);
            $cliente = [];

            if ($id) {
                $stmt = $con->prepare("SELECT * FROM clientes WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $cliente = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            if (!$cliente && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo "<p class='erro'>Cliente não encontrado!</p>";
            }
            ?>

            <form action="clientes_editar.php" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($cliente['id'] ?? '') ?>">

                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome"
                        value="<?= htmlspecialchars($cliente['nome'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento"
                        min="1900-01-01"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= htmlspecialchars($cliente['data_nascimento'] ?? '') ?>" required>
                </div>
                <div>
                    <p>
                        <label for="cpf">CPF:</label>
                        <input type="text" id="cpf" name="cpf"
                            value="<?= htmlspecialchars($cliente['cpf'] ?? '') ?>" readonly>
                        <small>(CPF não pode ser alterado)</small>
                    </p>
                </div>
                <div>
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email"
                        value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone"
                        value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="estado">Estado:</label>
                    <input type="text" id="estado" name="estado"
                        value="<?= htmlspecialchars($cliente['estado'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade"
                        value="<?= htmlspecialchars($cliente['cidade'] ?? '') ?>" required>
                </div>

                <?php if (isAdm()): ?>
                    <div>
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="1" <?= (isset($cliente['status']) && $cliente['status'] == 1) ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= (isset($cliente['status']) && $cliente['status'] == 0) ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                <?php else: ?>
                    <div>
                        <label>Status:</label>
                        <span><?= (isset($cliente['status']) && $cliente['status'] == 1) ? '🟢 Ativo' : '🔴 Inativo' ?></span>
                        <small>(apenas administradores podem alterar o status)</small>
                    </div>
                <?php endif; ?>

                <button type="submit">Atualizar</button>
            </form>
        </section>
    </main>
    <?php $con->close(); ?>
</body>

</html>