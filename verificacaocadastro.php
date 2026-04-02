<?php
// Verificação de Cadastro - Registration Verification

session_start();
include_once './conexao.php';
include_once './validar.php';

// Função para verificar cadastro
function verificarCadastro($login, $con)
{
    try {
        $stmt = $con->prepare("SELECT id, login, senha, perfil FROM usuarios WHERE login = ?");


        if ($stmt) {
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Processamento da requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($login) || empty($senha)) {
        $erro = "Login e senha são obrigatórios";
    } else {
        $usuario = verificarCadastro($login, $con);

        if ($usuario && md5($senha) === $usuario['senha']) {
            $sucesso = "Login bem-sucedido! Bem-vindo, " . htmlspecialchars($usuario['login']);
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['login'] = $usuario['login'];
            $_SESSION['senha'] = $usuario['senha'];
            $_SESSION['perfil'] = $usuario['perfil'];
            header("Location: inicio.php");
            exit();
        } else {
            $erro = "Login ou senha inválidos";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Cadastro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .sucesso {
            color: green;
            margin-top: 15px;
        }

        .erro {
            color: red;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <h1>Verificação de Cadastro</h1>

    <form method="POST">
        <div class="form-group">
            <label for="login">Login:</label>
            <input type="text" id="login" name="login" required>
        </div>
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit">Verificar</button>
    </form>

    <?php if (isset($sucesso)): ?>
        <p class="sucesso"><?php echo $sucesso; ?></p>
    <?php elseif (isset($erro)): ?>
        <p class="erro"><?php echo $erro; ?></p>
    <?php endif; ?>
</body>

</html>