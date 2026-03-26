<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    // Se não houver login na sessão, manda de volta para o index
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
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
                <?php include_once 'Menu.php'; ?>
            </ul>
        </nav>
    </header>
    <main>
        <section class="container">
            <h1>Editar Cadastro</h1>

            <?php
            include_once './conexao.php';

            // --- BLOCO 1: PROCESSA A ATUALIZAÇÃO (POST) ---
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $id = $_POST['id'];
                $nome = $_POST['nome'];
                $email = $_POST['email'];
                $telefone = $_POST['telefone'];
                $estado = $_POST['estado'];
                $cidade = $_POST['cidade'];
                $status = $_POST['status'];

                // Corrigido bind_param: você tem 7 variáveis (6 strings e 1 integer no final)
                // Portanto deve ser "ssssssi"
                $sql = "UPDATE clientes SET nome = ?, email = ?, telefone = ?, estado = ?, cidade = ?, status = ? WHERE id = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ssssssi", $nome, $email, $telefone, $estado, $cidade, $status, $id);
                
                if ($stmt->execute()) {
                    registrarLog("Dados do cliente $nome foram atualizados por " . $_SESSION['login'], "UPDATE");
                    echo "<p class='sucesso'>Cadastro atualizado com sucesso! Redirecionando...</p>";
                    header("refresh:3;url=clientes.php");
                } else {
                    echo "<p class='erro'>Erro ao atualizar cadastro: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }

            // --- BLOCO 2: BUSCA OS DADOS PARA PREENCHER O FORMULÁRIO (GET) ---
            $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
            $cliente = [];

            if ($id) {
                $sql = "SELECT * FROM clientes WHERE id = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $cliente = $result->fetch_assoc();
                $stmt->close();
            }

            // Se o cliente não for encontrado, você pode exibir um aviso
            if (!$cliente && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo "<p class='erro'>Cliente não encontrado!</p>";
            }
            ?>

            <form action="editar.php" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($cliente['id'] ?? '') ?>">

                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" 
                           value="<?= htmlspecialchars($cliente['data_nascimento'] ?? '') ?>" required>
                </div>
                <div>
                    <p><label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cliente['cpf'] ?? '') ?>" readonly><br>
                    <small>(CPF não pode ser alterado)</small></p>
                </div>
                <div>
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="estado">Estado:</label>
                    <input type="text" id="estado" name="estado" value="<?= htmlspecialchars($cliente['estado'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($cliente['cidade'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="1" <?= (isset($cliente['status']) && $cliente['status'] == 1) ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= (isset($cliente['status']) && $cliente['status'] == 0) ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                
                <button type="submit">Atualizar</button>
            </form>
        </section>
    </main>
    <?php $con->close(); ?>
</body>
</html>