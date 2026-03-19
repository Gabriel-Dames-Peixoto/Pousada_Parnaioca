<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    // Se não houver login na sessão, manda de volta para o index
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
?>