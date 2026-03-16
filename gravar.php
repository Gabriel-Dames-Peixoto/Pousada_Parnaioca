<?php
    
    $login = $_POST["login"];
    $senha = md5($_POST["senha"]);
    $perfil = $_POST["perfil"];

    include_once './conexao.php';
    
    $sql = "insert into usuarios values(null,
            '".$login."','".$senha."','".$perfil."')";
    
    //echo $sql;    
    if(mysqli_query($con, $sql)){
        echo "Gravado com sucesso!";
    }else{
        echo "Erro ao gravar!";
    }
    
    mysqli_close($con);
?>
<p><a href="index.php">Inicio</a></p>