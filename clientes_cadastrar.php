<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

// Valida CPF matematicamente
function validarCPF(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += $cpf[$i] * ($t + 1 - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ($cpf[$t] != $digito) return false;
    }

    return true;
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

            $erros = [];

            if (!$nome || !$data_nascimento || !$cpf || !$email || !$telefone || !$estado || !$cidade) {
                $erros[] = "Preencha todos os campos.";
            }

            // Validação de data de nascimento
            if ($data_nascimento) {
                $dt = DateTime::createFromFormat('Y-m-d', $data_nascimento);
                $hoje = new DateTime();
                if (!$dt || $dt > $hoje) {
                    $erros[] = "Data de nascimento inválida ou no futuro.";
                }
            }

            // Validação matemática do CPF
            if (!validarCPF($cpf)) {
                $erros[] = "CPF inválido. Verifique os dígitos informados.";
            }

            // Validação de e-mail
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros[] = "E-mail inválido.";
            }

            if (empty($erros)) {
                // Verifica CPF duplicado
                $stmt_cpf = $con->prepare("SELECT id FROM clientes WHERE cpf = ?");
                $stmt_cpf->bind_param("s", $cpf);
                $stmt_cpf->execute();
                $stmt_cpf->store_result();
                if ($stmt_cpf->num_rows > 0) {
                    $erros[] = "Já existe um cliente cadastrado com este CPF.";
                }
                $stmt_cpf->close();

                // Verifica e-mail duplicado
                $stmt_email = $con->prepare("SELECT id FROM clientes WHERE email = ?");
                $stmt_email->bind_param("s", $email);
                $stmt_email->execute();
                $stmt_email->store_result();
                if ($stmt_email->num_rows > 0) {
                    $erros[] = "Já existe um cliente cadastrado com este e-mail.";
                }
                $stmt_email->close();
            }

            if (!empty($erros)) {
                foreach ($erros as $erro) {
                    echo "<div class='erro'><p>" . htmlspecialchars($erro) . "</p></div>";
                }
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
        // Máscara CPF
        document.getElementById('cpf').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            this.value = value;
        });

        // Validação CPF no front antes de submeter
        function validarCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
            let soma = 0,
                resto;
            for (let i = 1; i <= 9; i++) soma += parseInt(cpf[i - 1]) * (11 - i);
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf[9])) return false;
            soma = 0;
            for (let i = 1; i <= 10; i++) soma += parseInt(cpf[i - 1]) * (12 - i);
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            return resto === parseInt(cpf[10]);
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const cpf = document.getElementById('cpf').value;
            if (!validarCPF(cpf)) {
                e.preventDefault();
                alert('CPF inválido. Verifique os dígitos informados.');
            }
        });
    </script>

</body>

</html>