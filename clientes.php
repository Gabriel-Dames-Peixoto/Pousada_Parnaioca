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

        <h1>Clientes</h1>
        <form method="GET" action="clientes.php">
            <select name="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" <?php if (isset($_GET['status']) && $_GET['status'] === '1') echo 'selected'; ?>>Ativos</option>
                <option value="0" <?php if (isset($_GET['status']) && $_GET['status'] === '0') echo 'selected'; ?>>Inativos</option>
            </select>

            <input type="text" name="search" placeholder="Pesquisar cliente" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Pesquisar</button>
            <button type="button" onclick="window.location.href='cadastrar.php'">Novo Cliente</button>
        </form>

       <?php
        include_once './conexao.php';
                
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // 1. Base da Query: 1=1 permite adicionar ANDs livremente
        $sql = "SELECT id, nome, data_nascimento, cpf, email, status FROM clientes WHERE 1=1";
        
        // 2. Lógica de filtros dinâmicos
        $params = [];
        $types = "";

        if ($status !== '') {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "i";
        }

        if ($search !== '') {
            $sql .= " AND (nome LIKE ? OR email LIKE ? OR cpf LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        $sql .= " ORDER BY nome";

        $stmt = $con->prepare($sql);

        // 3. Bind dinâmico de parâmetros (se houver filtros)
        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Data de Nascimento</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>";
            while ($row = $result->fetch_assoc()) {
                $estilo = ($row['status'] == 0) ? "style='color: #ff3d3d;'" : "";
                $textoStatus = ($row['status'] == 1) ? "Ativo" : "Inativo";

                echo "<tr $estilo>
                        <td>".htmlspecialchars($row['id'])."</td>
                        <td>".htmlspecialchars($row['nome'])."</td>
                        <td>".htmlspecialchars($row['data_nascimento'])."</td>
                        <td>".htmlspecialchars($row['cpf'])."</td>
                        <td>".htmlspecialchars($row['email'])."</td>
                        <td>".$textoStatus."</td>
                        <td>
                            <a href='editar.php?id=".htmlspecialchars($row['id'])."'>Editar</a>
                            
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
    </main>
    </body>
</html>