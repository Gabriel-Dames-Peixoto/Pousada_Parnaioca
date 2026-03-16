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
                    <input type="cpf" id="cpf" maxlength="14" placeholder="000.000.000-00" required name="cpf"><br>
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
                // Verifica se o cliente já existe
                $sql_check = "SELECT cpf FROM clientes WHERE cpf = ?";
                
                if ($stmt_check = $con->prepare($sql_check)) {
                    $stmt_check->bind_param("s", $cpf);
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
                                echo "<p>Cliente cadastrado com sucesso!</p>";
                            } else {
                                echo "<p>Erro ao cadastrar cliente. . $stmt->error . </p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p>Erro na preparação da consulta. . $con->error . </p>";
                        }
                    }
                    $stmt_check->close();
                }
            } else {
                echo "<p>Preencha todos os campos.</p>";
            }
        }
        mysqli_close($con);
        ?>
        
    
    </body>
</html>
