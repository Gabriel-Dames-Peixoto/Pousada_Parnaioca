<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica - Cadastro</title>
</head>

<body>
    <header>
        <nav>
            <ul>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container-login">
            <div class="login-box">
                <h2>Cadastro de Usuário</h2>

                <?php
                $mensagens = ['erro' => '', 'sucesso' => ''];
                foreach ($mensagens as $tipo => $msg) {
                    if (isset($_GET[$tipo])) {
                        $classe = $tipo === 'erro' ? 'alert-danger' : 'alert-success';
                        echo '<div class="alert ' . $classe . '">' . htmlspecialchars($_GET[$tipo]) . '</div>';
                    }
                }
                ?>

                <form method="POST" action="gravar.php" class="form-login">
                    <div class="form-group">
                        <label for="usuario">Usuário:</label>
                        <input type="text" id="usuario" name="usuario" required>
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>

                    <div class="form-group">
                        <label for="perfil">Perfil:</label>
                        <select id="perfil" name="perfil" required>
                            <option value="">Selecione um perfil</option>
                            <option value="user">Usuario</option>
                            <option value="adm">Administrador</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-login">Cadastrar</button>
                    <a href="usuarios.php" class="btn-cancel">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
</body>

</html>