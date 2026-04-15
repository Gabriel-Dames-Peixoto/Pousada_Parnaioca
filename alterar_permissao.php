<?php
session_start();
include_once './conexao.php';

$id = (int) $_POST['id'];
$pagina = $_POST['pagina'];
$permitido = (int) $_POST['permitido'];

$stmtPerfil = $con->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$stmtPerfil->bind_param("i", $id);
$stmtPerfil->execute();
$res = $stmtPerfil->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    die("Usuário inválido");
}

$perfil = $user['perfil'];

$stmt = $con->prepare("
    INSERT INTO permissoes (perfil, pagina, permitido)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE permitido = ?
");

$stmt->bind_param("ssii", $perfil, $pagina, $permitido, $permitido);
$stmt->execute();

echo "ok";
