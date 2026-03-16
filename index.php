<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>

        <h4>Área restrita / Home</h4>

        <form action="verificacaologin.php" method="post">
            
            Login:<br/>
            <input type="text" name="login" /><br/>
            
            Senha:<br/>
            <input type="password" name="senha"/><br/>
            
            <input type="submit" value="Login"/>
            
        </form>
        
        <?php
            if(!empty($_GET["msg"])){
            $msg = $_GET["msg"];            
            echo $msg;
            }
        ?>
    </body>
</html>
