<?php
session_start();
include_once './conexao.php';

// 1. Recebe os dados e limpa espaços em branco acidentais
$login = isset($_POST["login"]) ? trim($_POST["login"]) : '';
$senha = isset($_POST["senha"]) ? $_POST["senha"] : ''; 

if (empty($login) || empty($senha)) {
    $msg = "Preencha todos os campos.";
    header("Location: index.php?msg=" . urlencode($msg));
    exit();
}

// 2. Prepara a consulta
$sql = "SELECT login, senha, perfil FROM usuarios WHERE login = ?";
$stmt = mysqli_prepare($con, $sql);

if ($stmt) {
    // Vincula o parâmetro e executa
    mysqli_stmt_bind_param($stmt, "s", $login);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Verifica se o usuário existe
    if ($row = mysqli_fetch_assoc($result)) {
        
        // 3. Verificação de senha (comparando MD5)
        if (md5($senha) === $row["senha"]) {
            
            // Regenera o ID da sessão por segurança após o login
            session_regenerate_id(true);

            $_SESSION["login"] = $row["login"];
            $_SESSION["perfil"] = $row["perfil"];
            $_SESSION["tempo"] = time();

            header("Location: inicio.php");
            exit(); 
            
        } else {
            $msg = "Login ou Senha inválidos";
            header("Location: index.php?msg=" . urlencode($msg));
            exit();
        }
    } else {
        // Usuário não encontrado
        $msg = "Login ou Senha inválidos";
        header("Location: index.php?msg=" . urlencode($msg));
        exit();
    }
    
    mysqli_stmt_close($stmt); // Fecha o statement
} else {
    // Log de erro interno (evite mostrar detalhes do erro SQL para o usuário final)
    error_log("Erro no MySQL: " . mysqli_error($con));
    die("Ocorreu um erro interno. Tente novamente mais tarde.");
}

mysqli_close($con);
?>