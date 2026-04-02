<?php
session_start();
include_once './conexao.php';
include_once './validar.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] != 1) {
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
    <title>Pousada Parnaioca</title>
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
        <h1>Bem-vindo à Pousada Parnaioca!</h1>
        <p>Desfrute de uma estadia confortável e acolhedora em nossa pousada.
            Oferecemos quartos aconchegantes, atendimento personalizado e uma localização privilegiada.</p>
        <img src="imagens\pousadap.png" alt="Imagem da Pousada Parnaioca" style="width:50%; height:auto;">
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>

</body>

</html>