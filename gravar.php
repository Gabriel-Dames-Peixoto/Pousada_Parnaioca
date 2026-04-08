<?php
session_start();
include_once './validar.php';

// Recebe os dados do formulário
$login = $_POST["usuario"];
$senha = md5($_POST["senha"]);
$perfil = $_POST["perfil"];
$status = 1; // Definindo como Ativo por padrão no cadastro

include_once './conexao.php';

// Verifica se o login já existe
$check = $con->prepare("SELECT id FROM usuarios WHERE login = ?");
$check->bind_param("s", $login);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    header("Location: cadastrouso.php?erro=" . urlencode("Usuário '$login' já existe."));
    exit();
}
$check->close();

$sql = "INSERT INTO usuarios (login, senha, perfil, status) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($con, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sssi", $login, $senha, $perfil, $status);

    if (mysqli_stmt_execute($stmt)) {
        registrarLog("Usuario $login foi cadastrado por " . $_SESSION['login'], "INSERT");
        header("Location: usuarios.php?sucesso=" . urlencode("Cadastro realizado com sucesso!"));
        exit();
    } else {
        header("Location: cadastrouso.php?erro=" . urlencode("Erro ao gravar no banco."));
        exit();
    }
} else {
    echo "Erro na preparação da consulta: " . mysqli_error($con);
}

mysqli_close($con);
