<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($con)) {
    include_once __DIR__ . '/conexao.php';
}

if (!isset($_SESSION['login'])) {
    session_destroy();
    header('Location: index.php?msg=' . urlencode('Faça login para continuar.'));
    exit();
}

$timeout = 10 * 60; // segundos

if (!isset($_SESSION['tempo']) || ($_SESSION['tempo'] + $timeout) < time()) {
    $usuario  = $_SESSION['login'] ?? '';
    $dataHora = date('d-m-Y H:i:s');

    $log = fopen(__DIR__ . '/Login.log', 'a');
    fwrite($log, "$dataHora - Sessão expirada: $usuario" . PHP_EOL);
    fwrite($log, str_repeat('-', 52) . PHP_EOL);
    fclose($log);

    session_destroy();
    header('Location: index.php?msg=' . urlencode('Sessão expirada. Faça login novamente.'));
    exit();
}

$_SESSION['tempo'] = time();

$perfil_sessao = $_SESSION['perfil'] ?? 'user';
$nivel_sessao  = (int)($_SESSION['nivel'] ?? 1);

$pagina_atual = basename($_SERVER['PHP_SELF']);

$paginas_publicas = ['index.php', 'contato.php', 'sair.php', 'verificacaologin.php'];

if (!in_array($pagina_atual, $paginas_publicas)) {

    $stmt_perm = $con->prepare(
        "SELECT permitido FROM permissoes WHERE perfil = ? AND pagina = ?"
    );

    if ($stmt_perm) {
        $stmt_perm->bind_param('ss', $perfil_sessao, $pagina_atual);
        $stmt_perm->execute();
        $result_perm = $stmt_perm->get_result();
        $perm_row    = $result_perm->fetch_assoc();
        $stmt_perm->close();

        if (!$perm_row || $perm_row['permitido'] == 0) {
            header('Location: inicio.php?erro=' . urlencode('Acesso negado: permissão insuficiente.'));
            exit();
        }
    }
}


function isAdm(): bool
{
    return isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm';
}


function temNivel(int $nivel_minimo): bool
{
    return ((int)($_SESSION['nivel'] ?? 1)) >= $nivel_minimo;
}


function exigirAdm(): void
{
    if (!isAdm()) {
        header('Location: inicio.php?erro=' . urlencode('Acesso restrito a administradores.'));
        exit();
    }
}
