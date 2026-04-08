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

    <?php
    session_start();
    include_once './conexao.php';
    

    $logado = false;

    if (isset($_SESSION['login'])) {

        $login = $_SESSION['login'];

        // Consulta status no banco
        $stmt = $con->prepare("SELECT status FROM usuarios WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {

            if ($row['status'] == 1) {
                $logado = true;
            } else {
                session_destroy();
            }
        }

        $stmt->close();
    }
    ?>

    <header>
        <nav>
            <ul>
                <?php if ($logado): ?>
                    <?php include_once 'menu.php'; ?>
                <?php else: ?>
                    <li><a href="index.php">Início</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Contato</h1>

        <p>
            Se você tiver alguma dúvida, precisar de assistência ou precisar reativar o seu usuário,
            não hesite em entrar em contato conosco. Estamos aqui para ajudar!
        </p>

        <h2>Informações de Contato</h2>

        <ul>
            <li><strong>Endereço:</strong> Parnaioca, Angra dos Reis - RJ</li>
            <li><strong>Telefone:</strong> (11) 1234-5678</li>
            <li><strong>Email:</strong> contato@pousadaparnoica.com.br</li>
        </ul>
    </main>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>

</body>

</html>