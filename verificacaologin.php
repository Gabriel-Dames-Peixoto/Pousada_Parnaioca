<?php
session_start();
include_once './conexao.php';

$login = $_POST["login"] ?? '';
$senha = $_POST["senha"] ?? '';

if (empty($login) || empty($senha)) {
    header("Location: index.php?erro=Preencha todos os campos.");
    exit();
}

// 🔥 IMPORTANTE: agora busca o ID
$sql = "SELECT id, login, senha, perfil, status FROM usuarios WHERE login = ?";
$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    die("Erro no prepare: " . mysqli_error($con));
}

mysqli_stmt_bind_param($stmt, "s", $login);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    if (md5($senha) === $row["senha"]) {

        if ($row["status"] == 1) {

            session_regenerate_id(true);

            $_SESSION['id'] = $row['id'];
            $_SESSION['login'] = $row['login'];
            $_SESSION['perfil'] = $row['perfil'];
            $_SESSION['status'] = $row['status'];
            $_SESSION['tempo'] = time();
            $usuario = $_SESSION['login'];
            $dataHora = date("d-m-Y H:i:s");


            $arquivolog = fopen("Login.log", "a");

            fwrite($arquivolog, "$dataHora - login realizado: $usuario" . PHP_EOL);

            fclose($arquivolog);

            header("Location: inicio.php");
            exit();

        } else {
            header("Location: index.php?erro=Usuário bloqueado");
            exit();
        }

    } else {
        header("Location: index.php?erro=Senha inválida");
        exit();
    }

} else {
    header("Location: index.php?erro=Usuário não encontrado");
    exit();
}
