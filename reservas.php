<?php
session_start();
include_once './conexao.php';
if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

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
            <input type="text" name="search" placeholder="Pesquisar quarto" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Pesquisar</button>
        </form> 
        <?php
            $sql = "SELECT * FROM quartos ORDER BY id ASC";
            $result = mysqli_query($con, $sql);

            if ($result && mysqli_num_rows($result) > 0) {

                while ($row = mysqli_fetch_assoc($result)) {

                    
                    $stmt_res = $con->prepare("
                        SELECT data_checkin, data_checkout 
                        FROM reservas 
                        WHERE quarto_id = ? AND status = 'ativa'
                    ");
                    $stmt_res->bind_param("i", $row['id']);
                    $stmt_res->execute();
                    $res = $stmt_res->get_result();

                    $reservas = [];
                    while ($r = $res->fetch_assoc()) {
                        $reservas[] = $r;
                    }

                    $jsonReservas = json_encode($reservas);

                    echo "<div class='quarto-box'>";
                    echo "<h2 onclick='toggleCalendario(".$row['id'].")' style='cursor:pointer;'>
                             ".htmlspecialchars($row['quarto'])."
                        </h2>";

                    echo "<div id='calendario-".$row['id']."' style='display:none;'>";

                    echo "<div class='calendario' data-reservas='".htmlspecialchars($jsonReservas)."'></div>";

                    echo "<br><a href='Requarto.php?id=".$row['id']."'>Reservar</a>";

                    echo "</div>";
                    echo "<hr>";
                }

            } else {
                echo "Nenhum quarto encontrado.";
            }
        ?>

    </main>
    <footer>
        <p>&copy; 2026 Pousada Parnoica. Todos os direitos reservados.</p>
    </footer>
    <script>
        function toggleCalendario(id) {
            let el = document.getElementById("calendario-" + id);
            el.style.display = el.style.display === "none" ? "block" : "none";
            
            if (!el.dataset.loaded) {
                gerarCalendario(el);
                el.dataset.loaded = true;
            }
        }
        
        function gerarCalendario(container) {
            const calendarioDiv = container.querySelector(".calendario");
            const reservas = JSON.parse(calendarioDiv.dataset.reservas);

            let hoje = new Date();

            for (let i = 0; i < 30; i++) {
                let dia = new Date();
                dia.setDate(hoje.getDate() + i);

                let ocupado = false;

                reservas.forEach(r => {
                    let inicio = new Date(r.data_checkin);
                    let fim = new Date(r.data_checkout);

                    if (dia >= inicio && dia <= fim) {
                        ocupado = true;
                    }
                });
                
                let span = document.createElement("span");
                span.innerText = dia.getDate();

                span.style.display = "inline-block";
                span.style.width = "30px";
                span.style.margin = "2px";
                span.style.textAlign = "center";
                span.style.padding = "5px";
                
                if (ocupado) {
                    span.style.background = "#ff4d4d"; 
                    span.style.color = "#fff";
                } else {
                    span.style.background = "#4CAF50"; 
                    span.style.color = "#fff";
                }
                
                calendarioDiv.appendChild(span);
            }
        }

    </script>
    </body>
</html>
