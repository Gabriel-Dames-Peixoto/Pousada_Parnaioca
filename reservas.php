<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    // Se não houver login na sessão, manda de volta para o index
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
    <title>Pousada Parnoica - Cadastro</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <?php 
                    include_once 'menu.php'; 
                ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Reservas</h1>
            <p>Aqui você pode realizar ou gerenciar suas reservas. Se você tiver alguma dúvida ou precisar de assistência,
            não hesite em entrar em contato conosco.</p>
            <p><a href="contato.php">Entre em contato</a></p>

        <form method="GET" action="reservas.php">
             <select name="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" <?php if (isset($_GET['status']) && $_GET['status'] === '1') echo 'selected'; ?>>disponível</option>
                <option value="0" <?php if (isset($_GET['status']) && $_GET['status'] === '0') echo 'selected'; ?>>reservados</option>
            </select>
            <input type="text" name="search" placeholder="Pesquisar usuário" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Pesquisar</button>
            <button type="button" onclick="window.location.href='cadastrouso2.php'">Novo Usuário</button>
        </form> 
        <?php
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            $sql = "SELECT id, quarto, tipo, preco, status FROM quartos WHERE 1=1";

            $params = [];
            $types = "";

            $status = isset($_GET['status']) ? $_GET['status'] : '';
            if ($status !== '') {
                $sql .= " AND status = ?";
                $params[] = $status;
                $types .= "i";
            }

            if ($search !== '') {
                $sql .= " AND (id LIKE ? OR quarto LIKE ? OR tipo LIKE ? OR status LIKE ?)";
                $term = "%$search%";
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
                $types .= "ssss";
            }

            $sql .= " ORDER BY id ASC";

            $stmt = $con->prepare($sql);

            if ($types !== "") {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                echo "<table border='1' cellpadding='5'>
                    <tr>
                    <th>ID</th>
                    <th>Quarto</th>
                    <th>Tipo</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Ações</th>
                    </tr>";
                while ($row = $result->fetch_assoc()) {
                $estilo = ($row['status'] == 0) ? "style='color: #ff3d3d;'" : "";
                $textoStatus = ($row['status'] == 1) ? "Disponível" : "Reservado";

                echo "<tr $estilo>
                    <td>".htmlspecialchars($row['id'])."</td>
                    <td>".htmlspecialchars($row['quarto'])."</td>
                    <td>".htmlspecialchars($row['tipo'])."</td>
                    <td>".htmlspecialchars($row['preco'])."</td>
                    <td>". $textoStatus ."</td>
                    <td>
                        <a href='Requarto.php?id=".htmlspecialchars($row['id'])."'>Reservar</a>
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
    </main>
    <footer>
        <p>&copy; 2026 Pousada Parnoica. Todos os direitos reservados.</p>
    </footer>
    </body>
</html>
