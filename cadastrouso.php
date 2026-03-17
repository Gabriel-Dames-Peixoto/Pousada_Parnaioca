<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="2.css">
        <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
        <title>Pousada Parnoica - Cadastro</title>
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
            <h2>Cadastro de Usuário</h2>
            
            <?php
            if (isset($_GET['erro'])) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['erro']) . '</div>';
            }
            if (isset($_GET['sucesso'])) {
                echo '<div class="alert alert-success">' . htmlspecialchars($_GET['sucesso']) . '</div>';
            }
            ?>
            
            <form method="POST" action="verificacaocadastro.php" class="form-login">
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
                        <option value="cliente">Cliente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-login">Cadastrar</button>
            </form>
            
            
            <p class="link-cadastrouso">
                Já tem conta? <a href="index.php">Faça login aqui</a>
            </p>
        </div>
    </div>
</main>
</body>
</html>