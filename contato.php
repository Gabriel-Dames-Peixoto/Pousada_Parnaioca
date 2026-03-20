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
    <?php session_start();
    if (session_status() === PHP_SESSION_NONE) session_start(); 
    include_once './conexao.php';

    $exibirMenuCompleto = false;

    // Se houver sessão, vamos validar no banco de dados agora
    if (isset($_SESSION['login'])) {
        $login_atual = $_SESSION['login'];
        $stmt_check = $con->prepare("SELECT status FROM usuarios WHERE login = ?");
        $stmt_check->bind_param("s", $login_atual);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($row_check = $res_check->fetch_assoc()) {
            // Se o status no banco for 0 (Ativo), ele pode ver o menu
            if ($row_check['status'] == 0) {
                $exibirMenuCompleto = true;
            } else {
                // Se foi bloqueado, "limpamos" a sessão mas não destruímos ainda
                unset($_SESSION['login']); 
            }
        }
        $stmt_check->close();
    }
    ?>
    <nav>
        <ul>
            <?php 
            if(isset($_SESSION['login']) && $_SESSION['status'] === '1'):

                include_once 'menu.php';
            else:
                echo '<li><a href="index.php">Início</a></li>';
            ?> 
            <?php endif; ?> 
        </ul>
    </nav>
</header>

    <main>
        <h1>Contato</h1>
        <p>Se você tiver alguma dúvida, precisar de assistência ou precisar reativar o seu usuário,
             não hesite em entrar em contato conosco. Estamos aqui para ajudar!</p>
        
        <h2>Informações de Contato</h2>
        <ul>
            <li><strong>Endereço:</strong> Rua das Flores, 123, Centro, Cidade, Estado</li>
            <li><strong>Telefone:</strong> (11) 1234-5678</li>
            <li><strong>Email:</strong> contato@pousadaparnoica.com.br</li>
        </ul>
    </main>
</body>
</html>