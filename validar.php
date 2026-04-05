<?php
include_once './conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["login"])) {

    session_destroy();
    $msg = "Usuário sem acesso!";
    header("location:index.php?msg=" . $msg);
}


if ($_SESSION["tempo"] + 10 * 60 < time()) {
    $usuario = $_SESSION['login'];
    $dataHora = date("d-m-Y H:i:s");


    $arquivolog = fopen("Login.log", "a");


    fwrite($arquivolog, "$dataHora - sessão expirada: $usuario" . PHP_EOL);
    fwrite($arquivolog, "----------------------------------------------------" . PHP_EOL);

    fclose($arquivolog);
    session_destroy();

    $msg = "Sessão expirada";
    header("location:index.php?msg=" . $msg);
} else {
    $_SESSION["tempo"] = time();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

