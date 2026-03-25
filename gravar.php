<?php
// Recebe os dados do formulário
$login = $_POST["usuario"];
$senha = md5($_POST["senha"]);
$perfil = $_POST["perfil"];
$status = 1; // Definindo como Ativo por padrão no cadastro

include_once './conexao.php';

// Ajustado para a tabela 'usuarios' e usando Prepared Statements
$sql = "INSERT INTO usuarios (login, senha, perfil, status) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($con, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sssi", $login, $senha, $perfil, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        // Redireciona com mensagem de sucesso
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
?>