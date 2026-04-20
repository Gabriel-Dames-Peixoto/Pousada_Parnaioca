<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $idusuario = $_POST['id'] ?? null;
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? '';
    $status = $_POST['status'] ?? '';

    if (!$idusuario) {
        die("ID inválido.");
    }


    if (!empty($senha)) {
        $senha = md5($senha);

        $sql = "UPDATE usuarios SET login = ?, senha = ?, perfil = ?, status = ? WHERE id = ?";
        $stmt = $con->prepare($sql);

        if (!$stmt) {
            die("Erro no prepare: " . $con->error);
        }

        $stmt->bind_param("ssssi", $login, $senha, $perfil, $status, $idusuario);
    } else {
        // 🔐 Mantém senha atual
        $sql = "UPDATE usuarios SET login = ?, perfil = ?, status = ? WHERE id = ?";
        $stmt = $con->prepare($sql);

        if (!$stmt) {
            die("Erro no prepare: " . $con->error);
        }

        $stmt->bind_param("sssi", $login, $perfil, $status, $idusuario);
    }

    if ($stmt->execute()) {
        registrarLog("Dados do usuário $login foram atualizados por " . $_SESSION['login'], "UPDATE");
        echo "<script>alert('Usuário atualizado com sucesso!'); window.location.href='usuarios.php';</script>";
        exit();
    } else {
        $erro_update = "Erro ao atualizar: " . $stmt->error;
    }

    $stmt->close();
}


$id_busca = $_GET['id'] ?? $_POST['id'] ?? null;
$usuario = null;

if ($id_busca) {

    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $con->prepare($sql);

    if (!$stmt) {
        die("Erro no prepare: " . $con->error);
    }

    $stmt->bind_param("i", $id_busca);
    $stmt->execute();

    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnaióca</title>

</head>

<body>

    <header>
        <nav>
            <ul>
                <?php include_once 'menu.php'; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="container">

            <h1>Editar Usuário</h1>

            <?php if (isset($erro_update)): ?>
                <p class="erro"><?= $erro_update ?></p>
            <?php endif; ?>

            <?php if ($usuario): ?>

                <form action="usuarios_editar.php" method="POST">

                    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

                    <div>
                        <label>Login:</label>
                        <input type="text" name="login" value="<?= htmlspecialchars($usuario['login']) ?>" required>
                    </div>

                    <div>
                        <label>Senha:</label>
                        <input type="password" name="senha" placeholder="Deixe em branco para manter a atual">
                    </div>

                    <div>
                        <label>Perfil:</label>
                        <select name="perfil" required>
                            <option value="adm" <?= $usuario['perfil'] == 'adm' ? 'selected' : '' ?>>Administrador</option>
                            <option value="user" <?= $usuario['perfil'] == 'user' ? 'selected' : '' ?>>Usuário</option>
                        </select>
                    </div>

                    <div>
                        <label>Status:</label>
                        <select name="status" required>
                            <option value="1" <?= $usuario['status'] == 1 ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= $usuario['status'] == 0 ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>

                    <button type="submit">Atualizar</button>

                </form>

            <?php else: ?>

                <p class="erro">Usuário não encontrado ou ID não informado!</p>
                <a href="usuarios.php">Voltar</a>

            <?php endif; ?>

        </section>
    </main>

</body>

</html>

