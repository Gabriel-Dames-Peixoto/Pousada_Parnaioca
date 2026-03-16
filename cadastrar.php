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
        
        <h4>Cadastro de Usuário</h4>
        
        <form action="gravar.php" method="post">
            
            Login:<br/>
            <input type="text" name="login" /><br/>
            
            Senha:<br/>
            <input type="password" name="senha"/><br/>
            
            Perfil:<br/>
            <input type="radio" name="perfil" value="adm"/>Administrador
            <input type="radio" name="perfil" value="user"/>Usuário
            <br/>
            
            <input type="submit" value="Enviar" />
            
        </form>
        
    <?php
        session_start();
        include_once './conexao.php';
   
    ?>
    </body>
</html>
