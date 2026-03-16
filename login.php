<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/2.css">
        <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
        <title>Pousada Parnoica</title>
    </head>
    <body>

        <h4>Login/cadastrar</h4>
        
        <form action="verificacaologin.php" method="post">
            
            Login:<br/>
            <input type="text" name="login" /><br/>
            
            Senha:<br/>
            <input type="password" name="senha"/><br/>
            <div class="container-botoes">
            <input type="submit" value="Login"/> </form> 
            <input type="submit" value='Cadastrar' onclick="window.location.href='cadastrar.php'" />
            </div>
            
         
        <?php
            if(!empty($_GET["msg"])){
            $msg = $_GET["msg"];            
            echo $msg;
            }
        ?>
    </body>
</html>
