<?php
session_start();
include_once './conexao.php';
<<<<<<< HEAD
if (!isset($_SESSION['login']) || $_SESSION['status'] !== 1 || $_SESSION['perfil'] !== 'adm') {
    // Se não houver login na sessão, manda de volta para o index
=======

// 🔒 Proteção de acesso
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
>>>>>>> 5dc1ef35964e29e7e4c1168bd60c9529d6671faf
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}

// 🔎 Filtros
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png">
    <title>Pousada Parnoica - Clientes</title>
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

        <h1>Clientes</h1>

        <form method="GET" action="clientes.php">
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Ativos</option>
                <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>Inativos</option>
            </select>

            <input type="text" name="search" placeholder="Pesquisar cliente"
                value="<?= htmlspecialchars($search) ?>">

            <button type="submit">Pesquisar</button>

            <button type="button" onclick="window.location.href='cadastrar.php'">
                Novo Cliente
            </button>
        </form>

        <br>

        <div class="table-container">

            <?php
            $sql = "SELECT id, nome, data_nascimento, cpf, email, status FROM clientes WHERE 1=1";

            $params = [];
            $types = "";

            // Filtro por status
            if ($status !== '') {
                $sql .= " AND status = ?";
                $params[] = (int)$status;
                $types .= "i";
            }

            // Filtro de busca
            if ($search !== '') {
                $sql .= " AND (nome LIKE ? OR email LIKE ? OR cpf LIKE ?)";
                $term = "%$search%";
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
                $types .= "sss";
            }

            $sql .= " ORDER BY nome ASC";

            $stmt = $con->prepare($sql);


            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {

                echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Nascimento</th>
                    <th>CPF</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>";

                while ($row = $result->fetch_assoc()) {

                    $ativo = $row['status'] == 1;

                    $classe = $ativo ? "ativo" : "inativo";
                    $textoStatus = $ativo ? "🟢 Ativo" : "🔴 Inativo";

                    echo "<tr class='$classe'>
                <td>" . htmlspecialchars($row['id']) . "</td>
                <td>" . htmlspecialchars($row['nome']) . "</td>
                <td>" . date('d/m/Y', strtotime($row['data_nascimento'])) . "</td>
                <td>" . htmlspecialchars($row['cpf']) . "</td>
                <td>" . htmlspecialchars($row['email']) . "</td>
                <td>$textoStatus</td>
                <td>
                    <a href='editar.php?id=" . $row['id'] . "'>Editar</a>
                </td>
              </tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Nenhum cliente encontrado.</p>";
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