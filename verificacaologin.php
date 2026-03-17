<?php
    session_start();
    include_once './conexao.php';
    //session -> espaço de memória no BROWSER
    $login = $_POST["login"];
    $senha = md5($_POST["senha"]);
    
    //http://php.net/manual/pt_BR/function.mysql-real-escape-string.php
    
    

   echo $sql = "select * from usuarios where 
            login = '$login' AND senha = '$senha'";
    
    $result = mysqli_query($con, $sql);
    
    if(mysqli_num_rows($result) >= 1){
        $row = mysqli_fetch_array($result);

        $_SESSION["login"] = $row["login"]; //guardando no navegador(sessao) o valor do login
        $_SESSION["perfil"] = $row["perfil"];
        $_SESSION["tempo"] = time();

        header("location: inicio.php");
        
        
    }else{
        $msg = "Login/Senha invalido(s)";
        header("location: index.php?msg=".$msg);
        
    }
    
