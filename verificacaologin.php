<?php
session_start();
include_once './conexao.php';


$login = isset($_POST["login"]) ? trim($_POST["login"]) : '';
$senha = isset($_POST["senha"]) ? $_POST["senha"] : ''; 

if (empty($login) || empty($senha)) {
    $msg = "Preencha todos os campos.";
    header("Location: index.php?msg=" . urlencode($msg));
    exit();
}

$sql = "SELECT login, senha, perfil, status FROM usuarios WHERE login = ?";
$stmt = mysqli_prepare($con, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        
        if (md5($senha) === $row["senha"]) {
            date_default_timezone_set('America/Sao_Paulo');
            if ($row["status"] == 1) {
                session_regenerate_id(true);
                $_SESSION["login"] = $row["login"];
                $_SESSION["perfil"] = $row["perfil"];
                $_SESSION["status"] = $row["status"];
                $_SESSION["tempo"] = time();
                $arquivolog = fopen("Login.log", "a");
                fwrite($arquivolog, date("d-m-Y H:i:s") . " - Login realizado: " . $row["login"] . "\n");
                fclose($arquivolog);
                header("Location: inicio.php");
                exit();
            } else {
                $_SESSION["msg"] = "Usuário bloqueado";
                header("Location: index.php?erro=Usuário bloqueado");
                exit();
            }
        } else {
            $_SESSION["msg"] = "Login ou Senha inválidos";
            header("Location: index.php?erro=Login ou Senha inválidos");
            exit();
        }
    } else {
        $_SESSION["msg"] = "Login ou Senha inválidos";
        header("Location: index.php?erro=Login ou Senha inválidos");
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Erro no MySQL: " . mysqli_error($con));
    die("Ocorreu um erro interno. Tente novamente mais tarde.");
}

mysqli_close($con);
?>
