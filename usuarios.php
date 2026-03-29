<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
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

        <h1>Usuários</h1>
        <form method="GET" action="usuarios.php">
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" <?php if (isset($_GET['status']) && $_GET['status'] === '1') echo 'selected'; ?>>Ativos</option>
                <option value="0" <?php if (isset($_GET['status']) && $_GET['status'] === '0') echo 'selected'; ?>>Inativos</option>
            </select>
            <input type="text" name="search" placeholder="Pesquisar usuário" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Pesquisar</button>
            <button type="button" onclick="window.location.href='cadastrouso.php'">Novo Usuário</button>
        </form>

        <div class="table-container">

            <?php
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            $sql = "SELECT idusuario, login, perfil, status FROM usuarios WHERE 1=1";

            $params = [];
            $types = "";

            $status = isset($_GET['status']) ? $_GET['status'] : '';
            if ($status !== '') {
                $sql .= " AND status = ?";
                $params[] = $status;
                $types .= "i";
            }

            if ($search !== '') {
                $sql .= " AND (login LIKE ? OR perfil LIKE ? or status LIKE ?)";
                $term = "%$search%";
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
                $types .= "sss";
            }

            $sql .= " ORDER BY login ASC";

            $stmt = $con->prepare($sql);

            if ($types !== "") {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Login</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>";

                while ($row = $result->fetch_assoc()) {

                    $ativo = $row['status'] == 1;

                    $classe = $ativo ? "ativo" : "inativo";
                    $textoStatus = $ativo ? "🟢 Ativo" : "🔴 Inativo";

                    echo "<tr class='$classe'>
                <td>" . htmlspecialchars($row['idusuario']) . "</td>
                <td>" . htmlspecialchars($row['login']) . "</td>
                <td>" . htmlspecialchars($row['perfil']) . "</td>
                <td>" . $textoStatus . "</td>
                <td>
                    <a href='editaruso.php?idusuario=" . htmlspecialchars($row['idusuario']) . "'>Editar</a>
                </td>
                  </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Nenhum usuário encontrado.</p>";
            }

            $stmt->close();
            $con->close();
            ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>
</body>

</html>