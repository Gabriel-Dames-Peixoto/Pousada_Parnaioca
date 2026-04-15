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

$perfilUsuario = $usuario['perfil'] ?? '---';

// 📋 Lista de páginas do sistema
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

// 🔎 Busca permissões do perfil
if ($usuario) {
    $stmtPermissoes = $con->prepare("SELECT pagina, permitido FROM permissoes WHERE perfil = ?");

    if (!$stmtPermissoes) {
        die("Erro no prepare: " . $con->error);
    }

    $stmtPermissoes->bind_param("s", $usuario['perfil']);
    $stmtPermissoes->execute();
    $resultadoPermissoes = $stmtPermissoes->get_result();

    while ($linha = $resultadoPermissoes->fetch_assoc()) {
        $permissoesPerfil[$linha['pagina']] = (int)$linha['permitido'];
    }

    $stmtPermissoes->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Permissões</title>
    <link rel="stylesheet" href="2.css">
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

        <h1>Permissões - <?php echo htmlspecialchars($usuario['login'] ?? '---'); ?></h1>

        <?php if ($erro): ?>
            <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Aba</th>
                        <th>Página</th>
                        <th>Status</th>
                        <th>Alterar</th>
                    </tr>

                    <?php foreach ($abasMenu as $aba):

                        if ($aba['publica']) continue;

                        $permitido = $permissoesPerfil[$aba['pagina']] ?? 0;
                    ?>

                        <tr>
                            <td><?php echo htmlspecialchars($aba['nome']); ?></td>
                            <td><?php echo htmlspecialchars($aba['pagina']); ?></td>

                            <td>
                                <span class="<?php echo $permitido ? 'status-permitido' : 'status-bloqueado'; ?>">
                                    <?php echo $permitido ? 'Permitido' : 'Bloqueado'; ?>
                                </span>
                            </td>

                            <td>
                                <label class="switch">
                                    <input type="checkbox"
                                        data-id="<?php echo $idUsuario; ?>"
                                        data-pagina="<?php echo $aba['pagina']; ?>"
                                        <?php echo $permitido ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </td>
                        </tr>


                    <?php endforeach; ?>

                </table>
            </div>

            <?php endif; ?>

    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca</p>
    </footer>

    <script>
        document.querySelectorAll('input[type="checkbox"]').forEach(el => {
            el.addEventListener('change', function() {

                const id = this.dataset.id;
                const pagina = this.dataset.pagina;
                const permitido = this.checked ? 1 : 0;

                fetch('alterar_permissao_ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${id}&pagina=${pagina}&permitido=${permitido}`
                    })
                    .then(res => res.text())
                    .then(res => {
                        console.log(res);
                    })
                    .catch(() => {
                        alert('Erro ao alterar permissão');
                    });
            });
        });
    </script>

</body>

</html>