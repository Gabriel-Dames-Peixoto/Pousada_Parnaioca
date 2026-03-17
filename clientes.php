<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="2.css">
        <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
        <title>Pousada Parnoica</title>
    </head>
    <header>
        <nav>
            <ul>
                <?php
                    include_once 'Menu.php';
                ?>
            </ul>
        </nav>
    </header>
    <MAIN>

        <h1>Clientes</h1>
        <form method="GET" action="">
            <select name="status" onchange="this.form.submit()">
                
                <option value="1" <?php if (isset($_GET['status']) && $_GET['status'] === '1') echo 'selected'; ?>>Ativos</option>
                <option value="0" <?php if (isset($_GET['status']) && $_GET['status'] === '0') echo 'selected'; ?>>Inativos</option>
            </select>

            <input type="text" name="search" placeholder="Pesquisar cliente" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Pesquisar</button>
            <button type="button" onclick="window.location.href='cadastrar.php'">Novo Cliente</button>
        </form>

       <?php
        include_once './conexao.php';
        
        $status = (isset($_GET['status']) ? $_GET['status'] : '');
        $search = (isset($_GET['search']) ? $con->real_escape_string($_GET['search']) : '');
        $sql = "SELECT id, nome, data_nascimento, cpf, email FROM clientes WHERE status = ?";
        if ($status !== '') {
            $sql .= " AND status = ?";
        }
        $sql .= " ORDER BY nome";

        $stmt = $con->prepare($sql);
        if ($status !== '') {
            $stmt->bind_param("ii", $status, $status);
        } else {
            $stmt->bind_param("i", $status);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Nome</th><th>Data de Nascimento</th><th>CPF</th><th>Email</th><th>Ações</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>".htmlspecialchars($row['id'])."</td>
                        <td>".htmlspecialchars($row['nome'])."</td>
                        <td>".htmlspecialchars($row['data_nascimento'])."</td>
                        <td>".htmlspecialchars($row['cpf'])."</td>
                        <td>".htmlspecialchars($row['email'])."</td>
                        <td>
                            <a href='editar.php?id=".htmlspecialchars($row['id'])."'>Editar</a> |
                            <a href='inativar.php?id=".htmlspecialchars($row['id'])."' onclick='return confirm(\"Tem certeza?\");'>Inativar</a>
                        </td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum cliente encontrado.";
        }

        $con->close();
        ?>
      
    </body>
</html>
