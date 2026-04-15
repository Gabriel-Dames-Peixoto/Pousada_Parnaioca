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

$abasMenu = [
    ['pagina' => 'inicio.php', 'nome' => 'Início', 'publica' => false],
    ['pagina' => 'quartos.php', 'nome' => 'Quartos', 'publica' => false],
    ['pagina' => 'reservas.php', 'nome' => 'Reservas', 'publica' => false],
    ['pagina' => 'clientes.php', 'nome' => 'Clientes', 'publica' => false],
    ['pagina' => 'usuarios.php', 'nome' => 'Usuários', 'publica' => false],
    ['pagina' => 'tipos_acomodacao.php', 'nome' => 'Tipos', 'publica' => false],
    ['pagina' => 'dashboard.php', 'nome' => 'Dashboard', 'publica' => false],
    ['pagina' => 'relatorio_financeiro.php', 'nome' => 'Financeiro', 'publica' => false],
    ['pagina' => 'contato.php', 'nome' => 'Contato', 'publica' => true],
    ['pagina' => 'sair.php', 'nome' => 'Sair', 'publica' => true],
];

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

    $stmtPermissoes->close();
}

$abasPermitidas = [];
$abasBloqueadas = [];

foreach ($abasMenu as $aba) {
    if ($aba['publica']) {
        $aba['status_texto'] = 'Sempre liberada';
        $aba['status_classe'] = 'status-publico';
        $aba['observacao'] = 'Página pública do sistema.';
        $abasPermitidas[] = $aba;
        continue;
    }

    $permitido = $permissoesPerfil[$aba['pagina']] ?? 0;

    if ($permitido === 1) {
        $aba['status_texto'] = 'Permitido';
        $aba['status_classe'] = 'status-permitido';
        $aba['observacao'] = 'Liberada para o perfil ' . $usuario['perfil'] . '.';
        $abasPermitidas[] = $aba;
    } else {
        $aba['status_texto'] = 'Bloqueado';
        $aba['status_classe'] = 'status-bloqueado';
        $aba['observacao'] = 'Sem liberação cadastrada para o perfil ' . $usuario['perfil'] . '.';
        $abasBloqueadas[] = $aba;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica - Permissões</title>
</head>

<body>
    <header>
        <nav>
            <ul>
                <?php include_once 'Menu.php'; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Permissões do Usuário</h1>

        <?php if ($erro !== ''): ?>
            <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
            <p><a href="usuarios.php">Voltar para usuários</a></p>
        <?php else: ?>
            <section class="permission-summary">
                <p><strong>Login:</strong> <?php echo htmlspecialchars($usuario['login']); ?></p>
                <p><strong>Perfil:</strong> <?php echo htmlspecialchars($usuario['perfil']); ?></p>
                <p><strong>Status:</strong> <?php echo (int) $usuario['status'] === 1 ? 'Ativo' : 'Inativo'; ?></p>
                <p><strong>Abas liberadas:</strong> <?php echo count($abasPermitidas); ?></p>
            </section>

            <div class="table-container">
                <table>
                    <tr>
                        <th>Aba</th>
                        <th>Página</th>
                        <th>Permissão</th>
                        <th>Observação</th>
                    </tr>
                    <?php foreach ($abasPermitidas as $aba): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($aba['nome']); ?></td>
                            <td><?php echo htmlspecialchars($aba['pagina']); ?></td>
                            <td><span class="<?php echo htmlspecialchars($aba['status_classe']); ?>"><?php echo htmlspecialchars($aba['status_texto']); ?></span></td>
                            <td><?php echo htmlspecialchars($aba['observacao']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php if (!empty($abasBloqueadas)): ?>
                <h2>Abas sem permissão</h2>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Aba</th>
                            <th>Página</th>
                            <th>Permissão</th>
                            <th>Observação</th>
                        </tr>
                        <?php foreach ($abasBloqueadas as $aba): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($aba['nome']); ?></td>
                                <td><?php echo htmlspecialchars($aba['pagina']); ?></td>
                                <td><span class="<?php echo htmlspecialchars($aba['status_classe']); ?>"><?php echo htmlspecialchars($aba['status_texto']); ?></span></td>
                                <td><?php echo htmlspecialchars($aba['observacao']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <p><a href="usuarios.php">Voltar para usuários</a></p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>