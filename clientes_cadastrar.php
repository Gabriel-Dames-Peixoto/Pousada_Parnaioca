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

        <h1>Cadastro de Cliente</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome            = trim($_POST['nome']            ?? '');
            $data_nascimento = trim($_POST['data_nascimento'] ?? '');
            $cpf             = trim($_POST['cpf']             ?? '');
            $email           = trim($_POST['email']           ?? '');
            $telefone        = trim($_POST['telefone']        ?? '');
            $estado          = trim($_POST['estado']          ?? '');
            $cidade          = trim($_POST['cidade']          ?? '');

            if ($nome && $data_nascimento && $cpf && $email && $telefone && $estado && $cidade) {

                $stmt_check = $con->prepare("SELECT id FROM clientes WHERE cpf = ?");
                $stmt_check->bind_param("s", $cpf);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    echo "<div class='erro'><p>Cliente com este CPF já está cadastrado. <a href='clientes.php'>Ver clientes</a></p></div>";
                } else {
                    $sql = "INSERT INTO clientes (nome, data_nascimento, cpf, email, telefone, estado, cidade)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";

                    if ($stmt = $con->prepare($sql)) {
                        $stmt->bind_param("sssssss", $nome, $data_nascimento, $cpf, $email, $telefone, $estado, $cidade);

                        if ($stmt->execute()) {
                            registrarLog("O cliente $nome foi cadastrado por " . $_SESSION['login'], "INSERT");
                            echo "<div class='sucesso'><p>Cliente cadastrado com sucesso! Redirecionando...</p></div>";
                            header("refresh:3;url=clientes.php");
                        } else {
                            echo "<div class='erro'><p>Erro ao cadastrar cliente: " . htmlspecialchars($stmt->error) . "</p></div>";
                        }
                        $stmt->close();
                    } else {
                        echo "<div class='erro'><p>Erro na preparação da consulta: " . htmlspecialchars($con->error) . "</p></div>";
                    }
                }
                $stmt_check->close();
            } else {
                echo "<div class='erro'><p>Preencha todos os campos.</p></div>";
            }
        }
        ?>

        <form action="clientes_cadastrar.php" method="POST">
            <div>
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div>
                <label for="data_nascimento">Data de Nascimento:</label>
                <input type="date" id="data_nascimento" name="data_nascimento"
                    min="1900-01-01" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div>
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" maxlength="14"
                    placeholder="000.000.000-00" required>
            </div>
            <div>
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" maxlength="15" required>
            </div>
            <div>
                <label for="estado">Estado:</label>
                <input type="text" id="estado" name="estado" required>
            </div>
            <div>
                <label for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
            <input type="submit" value="Cadastrar">
        </form>

    </main>
    <script>
        document.getElementById('cpf').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            this.value = value;
        });
    </script>

</body>

</html>
