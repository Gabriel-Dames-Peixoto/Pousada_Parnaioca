<?php
session_start();
include_once './conexao.php';



date_default_timezone_set('America/Sao_Paulo');


if (isset($_SESSION['login'])) {
    $usuario = $_SESSION['login'];
    $dataHora = date("d-m-Y H:i:s");
    

    $arquivolog = fopen("Login.log", "a");
    

    fwrite($arquivolog, "$dataHora - Logout realizado: $usuario" . PHP_EOL);
    fwrite($arquivolog, "----------------------------------------------------" . PHP_EOL);
    
    fclose($arquivolog);
}


session_unset();
session_destroy();


header("Location: index.php");
exit();
?>