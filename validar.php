<?php
session_start();

if (!isset($_SESSION["login"])) {

    session_destroy();
    $msg = "Usuário sem acesso!";
    header("location:index.php?msg=" . $msg);
}


if ($_SESSION["tempo"] + 10 * 60 < time()) {
    session_destroy();
    $msg = "Sessão expirada";
    header("location:index.php?msg=" . $msg);
} else {
    $_SESSION["tempo"] = time();
}
