<?php
session_start();
include_once './conexao.php';

$login = $_POST['login'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($login) || empty($senha)) {
    header('Location: index.php?erro=' . urlencode('Preencha todos os campos.'));
    exit();
}

// Busca login + nivel (novo campo) além dos campos já existentes
$sql  = 'SELECT id, login, senha, perfil, status, nivel FROM usuarios WHERE login = ?';
$stmt = mysqli_prepare($con, $sql);

if (!$stmt) {
    die('Erro interno. Contate o administrador.');
}

mysqli_stmt_bind_param($stmt, 's', $login);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    if (md5($senha) === $row['senha']) {

        if ($row['status'] == 1) {

            session_regenerate_id(true);

            // Guarda nivel na sessão (2=adm, 1=user)
            $_SESSION['id']     = $row['id'];
            $_SESSION['login']  = $row['login'];
            $_SESSION['perfil'] = $row['perfil'];
            $_SESSION['status'] = $row['status'];
            $_SESSION['nivel']  = (int)($row['nivel'] ?? ($row['perfil'] === 'adm' ? 2 : 1));
            $_SESSION['tempo']  = time();

            // Log de acesso
            $dataHora    = date('d-m-Y H:i:s');
            $arquivolog  = fopen('Login.log', 'a');
            fwrite($arquivolog, "$dataHora - login realizado: $login" . PHP_EOL);
            fclose($arquivolog);

            header('Location: inicio.php');
            exit();
        } else {
            header('Location: index.php?erro=' . urlencode('Usuário bloqueado. Contate o administrador.'));
            exit();
        }
    } else {
        header('Location: index.php?erro=' . urlencode('Senha inválida.'));
        exit();
    }
} else {
    header('Location: index.php?erro=' . urlencode('Usuário não encontrado.'));
    exit();
}
