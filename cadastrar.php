<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica</title>
</head>

<header>
    <nav>
        <ul>
            <?php
            include_once 'Menu.php';
            ?>
        </ul>
    </nav>
</header>
<MAIN>

    <h1>Cadastro de Cliente</h1>

    <form action="cadastrar.php" method="POST">
        <div>
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
        </div>
        <div>
            <label for="data_nascimento">Data de Nascimento:</label>
            <input type="date" id="data_nascimento" name="data_nascimento" min="1900-01-01" max="<?= date('Y-m-d'); ?>" required><br>
        </div>
        <div>
            <label for="cpf">CPF:</label>
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
            <input type="text" id="cpf" maxlength="14" placeholder="000.000.000-00" required name="cpf"><br>
        </div>
        <div>
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required><br>
        </div>
        <div>
            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone" maxlength="15" required><br>
        </div>
        <div>
            <label for="estado">Estado:</label>
            <input type="text" id="estado" name="estado" required><br>
        </div>
        <div>
            <label for="cidade">Cidade:</label>
            <input type="text" id="cidade" name="cidade" required><br>
        </div>
        <input type="submit" value="Cadastrar">
    </form>
</MAIN>
<MAIN>
    <?php

    session_start();
    include_once './conexao.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = $_POST['nome'] ?? '';
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $cidade = $_POST['cidade'] ?? '';

        if ($nome && $data_nascimento && $cpf && $email && $telefone && $estado && $cidade) {
            $sql_check = "SELECT cpf, telefone FROM clientes WHERE cpf = ? and telefone = ?";

            if ($stmt_check = $con->prepare($sql_check)) {
                $stmt_check->bind_param("ss", $cpf, $telefone);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    echo "<p>Cliente já cadastrado.</p>";
                } else {
                    $sql = "INSERT INTO clientes (nome, data_nascimento, cpf, email, telefone, estado, cidade) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)";

                    if ($stmt = $con->prepare($sql)) {
                        $stmt->bind_param("sssssss", $nome, $data_nascimento, $cpf, $email, $telefone, $estado, $cidade);

                        if ($stmt->execute()) {
                            echo "<div class='sucesso'><p>Cliente cadastrado com sucesso! Redirecionando...</p></div>";
                            registrarLog("O cliente $nome foi cadastrado por " . $_SESSION['login'], "INSERT");

                            // ALTERAÇÃO: Redireciona para clientes.php após 3 segundos
                            header("refresh:3;url=clientes.php");
                            // -------------------------------------------------------

                        } else {
                            echo "<div class='erro'><p>Erro ao cadastrar cliente: " . $stmt->error . "</p></div>";
                        }
                        $stmt->close();
                    } else {
                        echo "<div class='erro'><p>Erro na preparação da consulta: " . $con->error . "</p></div>";
                    }
                }
                $stmt_check->close();
            }
        } else {
            echo "<div class='aviso'><p>Preencha todos os campos.</p></div>";
        }
    }
    mysqli_close($con);

    ?>
</MAIN>
</body>

</html>