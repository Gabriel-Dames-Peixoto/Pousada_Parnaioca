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
                   
                ?>
            </ul>
        </nav>
    </header>
<body>
    <main>
    <div class="container-login">
        <div class="login-box">
            <h2>Login</h2>
            
            <?php
            if (isset($_GET['erro'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['erro']) . '</div>';
            }
            if (isset($_GET['sucesso'])) {
                echo '<div class="alert alert-success">' . htmlspecialchars($_GET['sucesso']) . '</div>';
            }
            ?>
            
            <form method="POST" action="verificacaologin.php" class="form-login">
                <div class="form-group">
                    <label for="text">Usuario:</label>
                    <input type="text" id="text" name="text" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <button type="submit" class="btn-login">Entrar</button>
            </form>
            
            <p class="link-cadastrouso">
                Não tem conta? <a href="cadastrouso.php">Cadastre-se aqui</a>
            </p>
        </div>
    </div>
</main>
</body>
</html>