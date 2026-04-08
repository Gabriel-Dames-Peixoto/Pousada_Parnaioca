<?php
session_start();
include_once './conexao.php';
include_once './validar.php';

exigirAdm();

$mensagem = '';

// Ativar / Inativar
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id_toggle  = (int)$_GET['id'];
    $novo_status = $_GET['toggle'] === 'ativar' ? 1 : 0;

    $stmt_t = $con->prepare('UPDATE tipos_acomodacao SET status = ? WHERE id = ?');
    $stmt_t->bind_param('ii', $novo_status, $id_toggle);
    $stmt_t->execute();
    $stmt_t->close();

    $acao = $novo_status ? 'ativado' : 'inativado';
    registrarLog("Tipo de acomodação ID $id_toggle foi $acao por " . $_SESSION['login'], 'UPDATE');
    header('Location: tipos_acomodacao.php?ok=' . urlencode("Tipo $acao com sucesso."));
    exit();
}

// Cadastrar novo tipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome'])) {
    $nome = trim($_POST['nome']);

    $stmt_c = $con->prepare('INSERT INTO tipos_acomodacao (nome) VALUES (?)');
    $stmt_c->bind_param('s', $nome);

    if ($stmt_c->execute()) {
        registrarLog("Tipo de acomodação \"$nome\" cadastrado por " . $_SESSION['login'], 'INSERT');
        header('Location: tipos_acomodacao.php?ok=' . urlencode("Tipo \"$nome\" cadastrado."));
        exit();
    } else {
        if ($con->errno === 1062) {
            $mensagem = "<p class='erro'>Já existe um tipo com esse nome.</p>";
        } else {
            $mensagem = "<p class='erro'>Erro ao cadastrar: " . htmlspecialchars($con->error) . "</p>";
        }
    }
    $stmt_c->close();
}

// Editar nome
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['editar_id'])) {
    $editar_id   = (int)$_POST['editar_id'];
    $editar_nome = trim($_POST['editar_nome'] ?? '');

    if ($editar_nome) {
        $stmt_e = $con->prepare('UPDATE tipos_acomodacao SET nome = ? WHERE id = ?');
        $stmt_e->bind_param('si', $editar_nome, $editar_id);
        $stmt_e->execute();
        $stmt_e->close();
        registrarLog("Tipo de acomodação ID $editar_id renomeado para \"$editar_nome\" por " . $_SESSION['login'], 'UPDATE');
        header('Location: tipos_acomodacao.php?ok=' . urlencode('Tipo atualizado.'));
        exit();
    }
}

// Listar todos
$tipos = $con->query('SELECT * FROM tipos_acomodacao ORDER BY nome ASC')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Tipos de Acomodação — Pousada Parnaioca</title>
</head>

<body>

    <header>
        <nav>
            <ul><?php include_once 'Menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Tipos de Acomodação</h1>

        <?php if (isset($_GET['ok'])): ?>
            <p class="sucesso"><?= htmlspecialchars($_GET['ok']) ?></p>
        <?php endif; ?>

        <?= $mensagem ?>

        <!-- Cadastrar novo tipo -->
        <form method="POST" style="margin-bottom:30px;">
            <label for="nome">Novo tipo:</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: Chalé, Bangalô..." required>
            <button type="submit">Cadastrar</button>
        </form>

        <!-- Tabela de tipos -->
        <?php if (!empty($tipos)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tipos as $t): ?>
                            <tr>
                                <td><?= $t['id'] ?></td>
                                <td>
                                    <!-- Edição inline do nome -->
                                    <form method="POST" style="display:inline;margin:0;">
                                        <input type="hidden" name="editar_id" value="<?= $t['id'] ?>">
                                        <input type="text" name="editar_nome"
                                            value="<?= htmlspecialchars($t['nome']) ?>"
                                            style="width:140px;padding:4px 8px;margin:0;">
                                        <button type="submit" style="width:auto;padding:4px 10px;">Salvar</button>
                                    </form>
                                </td>
                                <td><?= $t['status'] ? '🟢 Ativo' : '🔴 Inativo' ?></td>
                                <td>
                                    <?php if ($t['status']): ?>
                                        <a href="tipos_acomodacao.php?toggle=inativar&id=<?= $t['id'] ?>">Inativar</a>
                                    <?php else: ?>
                                        <a href="tipos_acomodacao.php?toggle=ativar&id=<?= $t['id'] ?>">Ativar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Nenhum tipo cadastrado ainda.</p>
        <?php endif; ?>

        <br>
        <a href="quartos.php">← Voltar para Quartos</a>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>