<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] === 1) {
    header("Location: index.php?erro=" . urlencode("Acesso negado. Faça login."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png">
    <title>Reservas</title>
</head>

<body>

<header>
    <nav>
        <ul>
            <?php include_once 'menu.php'; ?>
        </ul>
    </nav>
</header>

<main>
    <h1>Reservas</h1>

    <?php
    $sql = "SELECT * FROM quartos ORDER BY id ASC";
    $result = mysqli_query($con, $sql);

    if ($result && mysqli_num_rows($result) > 0) {

        while ($row = mysqli_fetch_assoc($result)) {

            // Buscar reservas
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

            $jsonReservas = htmlspecialchars(json_encode($reservas), ENT_QUOTES, 'UTF-8');

            echo "<div class='quarto-box'>";
            echo "<h2 onclick='toggleCalendario(".$row['id'].")'>
                    🏨 ".htmlspecialchars($row['quarto'])."
                  </h2>";

            echo "<div id='calendario-".$row['id']."' class='container-calendario'>";

            echo "<div class='calendario' data-id='".$row['id']."' data-reservas='".$jsonReservas."'></div>";

            echo "<br><a href='Requarto.php?id=".$row['id']."'>Reservar </a>";
            if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm') {
                echo "<a href='CanReserva.php?id=" . $row["id"] . "'>| Cancelar Reserva</a>";
            }
            echo "</div>";
            echo "</div>";
        }

    } else {
        echo "Nenhum quarto encontrado.";
    }
    ?>

</main>

<footer>
    <p>&copy; 2026 Pousada Parnoica</p>
</footer>

<script>
function toggleCalendario(id) {
    const el = document.getElementById("calendario-" + id);

    el.style.display = (el.style.display === "none" || el.style.display === "") ? "block" : "none";

    if (!el.dataset.loaded) {
        gerarCalendario(el);
        el.dataset.loaded = true;
    }
}

function gerarCalendario(container) {

    const calendarioDiv = container.querySelector(".calendario");

    // 🔥 limpa antes de gerar
    calendarioDiv.innerHTML = "";

    const reservas = JSON.parse(calendarioDiv.dataset.reservas);

    const diasSemana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];

    // Cabeçalho
    diasSemana.forEach(d => {
        let el = document.createElement("div");
        el.classList.add("dia-semana");
        el.innerText = d;
        calendarioDiv.appendChild(el);
    });

    let hoje = new Date();
    let ano = hoje.getFullYear();
    let mes = hoje.getMonth();

    let primeiroDia = new Date(ano, mes, 1).getDay();
    let totalDias = new Date(ano, mes + 1, 0).getDate();

    // Espaços vazios
    for (let i = 0; i < primeiroDia; i++) {
        let vazio = document.createElement("div");
        calendarioDiv.appendChild(vazio);
    }

    for (let dia = 1; dia <= totalDias; dia++) {

        let dataAtual = new Date(ano, mes, dia);
        let ocupado = false;

        reservas.forEach(r => {
            let inicio = new Date(r.data_checkin);
            let fim = new Date(r.data_checkout);

            if (dataAtual >= inicio && dataAtual <= fim) {
                ocupado = true;
            }
        });

        let el = document.createElement("div");
        el.classList.add("dia");
        el.innerText = dia;

        if (ocupado) {
            el.classList.add("dia-ocupado");
        } else {
            el.classList.add("dia-livre");

            el.onclick = () => {
                let data = `${ano}-${String(mes+1).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
                let quarto = calendarioDiv.dataset.id;

                window.location.href = `Requarto.php?id=${quarto}&data=${data}`;
            };
        }

        calendarioDiv.appendChild(el);
    }
}
</script>

</body>
</html>
