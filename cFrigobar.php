<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] !== 1 || $_SESSION['perfil'] !== 'adm') {
    // Se não houver login na sessão, manda de volta para o index
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="1.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Pousada Parnoica</title>
</head>
<body>
    <header>
        <nav>
            <ul>
                <?php
                    include_once 'Menu.php';
                ?>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Cadastro de itens do Frigobar</h1>
            <form action="" method="post">
                <?php
                    // Ajustado para aceitar tanto 'id' (vindo do botão) quanto 'quarto_id'
                    $quarto_id_url = $_GET['id'] ?? $_GET['quarto_id'] ?? $_SESSION['quarto_id'] ?? null;
                            
                if ($quarto_id_url) {
                    // Query corrigida: removido 'quarto_id' que não existe na tabela quartos
                    $resQuarto = $con->query("SELECT id, quarto FROM quartos WHERE id = " . intval($quarto_id_url));
                    $quarto = $resQuarto->fetch_assoc();
                    
                    echo "<label for='quarto_id'>Quarto:</label>";
                    echo "<input type='text' id='quarto_id' value='" . $quarto['quarto'] . "' disabled><br><br>";
                    echo "<input type='hidden' name='quarto_id' value='" . $quarto['id'] . "'>";
                }
                ?>

                <label for="nome">Nome do Item:</label>
                <input type="text" id="nome" name="nome" required><br><br>

                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" min="1" required><br><br>

                <label for="valor">Valor (R$):</label>
                <input type="text" id="valor" name="valor" placeholder="0,00" 
                    pattern="[0-9]+([,\.][0-9]{1,2})?" 
                    title="Digite um valor numérico (ex: 10,50)" required><br><br>

                <input type="submit" value="Gravar">
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nome = trim($_POST['nome'] ?? '');
                $valor_bruto = trim($_POST['valor'] ?? '');
                $valor_limpo = preg_replace('/[^0-9,.]/', '', $valor_bruto); 
                $valor_formatado = str_replace(',', '.', $valor_limpo);
                $valor_final = floatval($valor_formatado);
                $quantidade = intval($_POST['quantidade'] ?? 0);
                $quarto_id = $_POST['quarto_id'] ?? null; 

                if (!empty($nome) && $valor_final > 0 && !empty($quarto_id)) {
                    // Removi o 'id' do INSERT para deixar o banco usar o Auto_Increment
                    $query = "INSERT INTO frigobar (id, nome, quantidade, valor, quarto_id) VALUES (NULL, ?, ?, ?, ?)";
                    $stmt = $con->prepare($query);
                    
                    // s = string, d = double (valor), i = integer (quarto_id)
                    $stmt->bind_param("sidi", $nome, $quantidade, $valor_final, $quarto_id);

                    if ($stmt->execute()) {
                        echo "<p style='color: green;'>Item cadastrado com sucesso!</p>";
                        echo "<input type='button' value='Voltar para informações do quarto' 
                        onclick='window.location.href=\"informacoes_quarto.php?id=" . htmlspecialchars($quarto_id) . "\"'>";
                    } else {
                        echo "<p style='color: red;'>Erro ao cadastrar: " . $stmt->error . "</p>";
                    }
                    $stmt->close();
                } else {
                    echo "<p style='color: red;'>Preencha todos os campos corretamente.</p>";
                }
            }
            ?>
    </main>
</body>
</html>
