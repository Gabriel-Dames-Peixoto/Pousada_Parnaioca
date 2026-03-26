<?php
session_start();
include_once './conexao.php';


if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

// --- BLOCO 1: PROCESSA A ATUALIZAÇÃO (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idusuario = $_POST['idusuario'];
    $login = $_POST['login'];
    $senha = $_POST['senha'];
    $perfil = $_POST['perfil'];
    $status = $_POST['status'];
    
    if (!empty($_POST['senha'])) {
        $senha = md5($_POST['senha']);
        $sql = "UPDATE usuarios SET login = ?, senha = ?, perfil = ?, status = ? WHERE idusuario = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ssssi", $login, $senha, $perfil, $status, $idusuario);
    } else {
        $sql = "UPDATE usuarios SET login = ?, perfil = ?, status = ? WHERE idusuario = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("sssi", $login, $perfil, $status, $idusuario);
    }
    
    if ($stmt->execute()) {
        // Usamos um echo e um redirecionamento via JS ou Refresh
        registrarLog("Dados do usuario $login foram atualizados por " . $_SESSION['login'], "UPDATE");
        echo "<script>alert('Usuário atualizado com sucesso!'); window.location.href='usuarios.php';</script>";
        exit(); 
    } else {
        $erro_update = "Erro ao atualizar: " . $stmt->error;
    }
    $stmt->close();
}

// --- BLOCO 2: BUSCA OS DADOS PARA PREENCHER O FORMULÁRIO ---
// Prioridade para o GET (vinda da lista) e depois POST (se deu erro no envio)
$id_busca = $_GET['idusuario'] ?? $_POST['idusuario'] ?? null;
$usuario = null;

if ($id_busca) {
    $sql = "SELECT * FROM usuarios WHERE idusuario = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $id_busca);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="2.css">
        <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
        <title>Pousada Parnoica</title>
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
        <section class="container">
            <h1>Editar Usuário</h1>

            <?php if (isset($erro_update)) echo "<p class='erro'>$erro_update</p>"; ?>

            <?php if ($usuario): ?>
                <form action="editaruso.php" method="POST">
                    <input type="hidden" name="idusuario" value="<?= $usuario['idusuario'] ?>">

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
                <a href="usuarios.php">Voltar para a lista</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>