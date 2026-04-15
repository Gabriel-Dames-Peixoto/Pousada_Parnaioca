<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

exigirAdm();

$idUsuario = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$usuario = null;
$erro = '';
if ($idUsuario <= 0) {
    $erro = 'Usuário não informado.';
} else {
    $stmtUsuario = $con->prepare("SELECT id, login, perfil, status FROM usuarios WHERE id = ?");

    if (!$stmtUsuario) {
        die("Erro no prepare: " . $con->error);
    }

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();
    $resultadoUsuario = $stmtUsuario->get_result();
    $usuario = $resultadoUsuario->fetch_assoc();
    $stmtUsuario->close();

    if (!$usuario) {
        $erro = 'Usuário não encontrado.';
    }
}
$permissoesPerfil = [];
if ($usuario) {
    $stmtPermissoes = $con->prepare("SELECT pagina, permitido FROM permissoes WHERE perfil = ?");

    if (!$stmtPermissoes) {
        die("Erro no prepare: " . $con->error);
    }

    $stmtPermissoes->bind_param("s", $usuario['perfil']);
    $stmtPermissoes->execute();
    $resultadoPermissoes = $stmtPermissoes->get_result();

    while ($linhaPermissao = $resultadoPermissoes->fetch_assoc()) {
        $permissoesPerfil[$linhaPermissao['pagina']] = (int) $linhaPermissao['permitido'];
    }
}
$stmtPermissoes->close();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica - Alterar Permissões</title>
</head>

<body>
    <header>
        <nav>
            <ul>
                <?php ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Alterar Permissões - <?php echo htmlspecialchars($usuario['login']); ?></h1>
        <?php if ($erro): ?>
            <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
        <?php else: ?>
            <table>
