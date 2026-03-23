<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    // Se não houver login na sessão, manda de volta para o index
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
$mensagem = "";
$id_quarto = $_GET['id'] ?? null; 
$dados_quarto = [];

if ($id_quarto) {
    // Busca os dados atuais do item selecionado
    $stmt_load = $con->prepare("SELECT * FROM quartos WHERE id = ?");
    $stmt_load->bind_param("i", $id_quarto);
    $stmt_load->execute();
    $result = $stmt_load->get_result();
    
    if ($result->num_rows > 0) {
        $dados_quarto = $result->fetch_assoc();
    } else {
        die("Quarto não encontrado no banco de dados.");
    }
    $stmt_load->close();
} else {
    die("ID do quarto não fornecido.");
}

$resQuarto = $con->query("SELECT quarto FROM quartos WHERE id = " . intval($id_quarto));
$quarto_data = $resQuarto->fetch_assoc();
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
    
    <header>
        <nav>
            <ul>
                <?php
                    include_once 'Menu.php';
                ?>
            </ul>
        </nav>
    </header>
<body>
    <main>
    <h1>Cadastro de Quartos</h1>
    
    <?php echo $mensagem; ?>

    <form action="" method="post">
        <input type="hidden" name="id_quarto" value="<?php echo $id_quarto; ?>">

        <label for="Quarto">Quarto:</label>
        <input type="text" id="Quarto" name="Quarto" value="<?php echo htmlspecialchars($dados_quarto['quarto']); ?>" required><br><br>

        <label for="tipo">Tipo:</label>
        <input type="text" id="tipo" name="tipo" value="<?php echo htmlspecialchars($dados_quarto['tipo']); ?>" required><br><br>

        <label for="descricao">Descrição:</label>
        <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($dados_quarto['descricao']); ?></textarea><br><br>

        <label for="preco">Preço:</label>
        <input type="number" id="preco" name="preco" step="0.01" value="<?php echo htmlspecialchars($dados_quarto['preco']); ?>" required><br><br>

        <input type="submit" value="Gravar">
    
    
    <p><a href="quartos.php">Voltar para Quartos</a></p>
    </form>
    </main>
    
</body>
</html>
<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Pegando os nomes corretos conforme o 'name' do input no HTML
    $id_quarto = $_POST['id_quarto'] ?? null;
    $numero = $_POST['Quarto'] ?? ''; 
    $tipo = $_POST['tipo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $preco = $_POST['preco'] ?? '';

    if ($id_quarto &&$numero && $tipo && $descricao && $preco) {
        // 2. SQL ajustado
        $sql = "UPDATE quartos SET quarto=?, tipo=?, preco=?, descricao=? WHERE id=?";
        
        if ($stmt = $con->prepare($sql)) {

            $stmt->bind_param("ssdsi", $numero, $tipo, $preco, $descricao, $id_quarto);
            
            if ($stmt->execute()) {
                $mensagem = "<div class='sucesso'><p>Quarto atualizado com sucesso! Redirecionando...</p></div>";
                header("refresh:3;url=quartos.php");
            } else {
                $mensagem = "<div class='erro'><p>Erro ao atualizar quarto: " . htmlspecialchars($stmt->error) . "</p></div>";
            }
            $stmt->close();
        } else {
            $mensagem = "<div class='erro'><p>Erro na preparação: " . htmlspecialchars($con->error) . "</p></div>";
        }
    } else {
        $mensagem = "<div class='erro'><p>Por favor, preencha todos os campos.</p></div>";
    }
}
?>
    <footer>
        <p>&copy; 2026 Pousada Parnoica. Todos os direitos reservados.</p>
    </footer>