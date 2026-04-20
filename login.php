<?php
session_start();

if (isset($_SESSION['login'])) {
    header('Location: inicio.php');
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
            </ul>
        </nav>
    </header>
    <main>
        <div class="container-login">
            <div class="login-box">
                <h2>Login</h2>

                <?php
                if (isset($_GET['erro'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['erro']) . '</div>';
                }
                if (isset($_GET['msg'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['msg']) . '</div>';
                }
                if (isset($_GET['sucesso'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['sucesso']) . '</div>';
                }
                ?>

                <form method="POST" action="login_processar.php" class="form-login">
                    <div class="form-group">
                        <label for="login">Usuario:</label>
                        <input type="text" id="login" name="login" required>
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>

                    <button type="submit" class="btn-login">Entrar</button>
                </form>

                <br><br>
                <p class="link-contato">
                    Caso o login não esteja funcionando! <a href="contato.php">Entre em contato</a>
                </p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>

</body>

</html>
