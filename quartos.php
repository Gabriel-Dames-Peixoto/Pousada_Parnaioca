<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login'])) {
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
        <h1>Quartos disponiveis</h1>
        
            <?php include_once 'conexao.php'; 
                // Verifica se o usuário logado é Administrador
                if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm'): ?>
                
                
                <a href="gravarquartos.php">
                    <button type="button">Cadastrar novo quarto</button>
                </a>
                

            <?php endif; ?>
        
        <p><?php
            include_once 'conexao.php';

            $sql = "SELECT * FROM quartos";
            $result = mysqli_query($con, $sql);

                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<div class='quarto'>";
                                echo "<h2>" . $row["quarto"] . "</h2>";
                                echo "Preço: R$ " . number_format($row["preco"], 2, ',', '.') . "<br>";
                                echo "<p><a href='informacoes_quarto.php?id=" . $row["id"] . "'>Informações adicionais</a></p>";
                                echo "</div>";
                            }
            } else {
                echo "Nenhum quarto disponível.";
            }

            mysqli_close($con);
        ?></p>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnoica. Todos os direitos reservados.</p>
    </footer>


</body>
</html>