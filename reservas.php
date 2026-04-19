<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
    <title>Reservas</title>
</head>

<body>

    <header>
        <nav>
            <ul><?php include_once 'menu.php'; ?></ul>
        </nav>
    </header>

    <main>
        <h1>Reservas</h1>

        <?php
        $sql = "SELECT * FROM quartos ORDER BY id ASC";
        $result = mysqli_query($con, $sql);

        while ($row = mysqli_fetch_assoc($result)) {

            $stmt_res = $con->prepare("
        SELECT data_checkin, hora_checkin, data_checkout, hora_checkout
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

            $estilo = ($row['status'] == 0) ? "style='color: #999;'" : "";
            $mensagem = ($row['status'] == 0) ? " - Indisponível" : "";

            $onclick = ($row['status'] == 1) ? "onclick='toggleCalendario(" . $row['id'] . ")'" : "";

            echo "<h2 $onclick $estilo>🏨 " . $row['quarto'] . $mensagem . "</h2>";

            if ($row['status'] == 1) {
                echo "<div id='calendario-" . $row['id'] . "' class='container-calendario'>";
                echo "<div class='calendario' data-id='" . $row['id'] . "' data-reservas='" . $jsonReservas . "'></div>";

                echo "<br>";
                echo '<a href="reservas_cadastrar.php?id=' . $row['id'] . '">Reservar</a>';
                if ($_SESSION['perfil'] == 'adm') {

                    echo ' | <a href="reservas_cancelar.php">Cancelar</a> | ';
                    echo '<a href="reservas_finalizar.php">Finalizar reserva</a>';
                }
                echo "</div>";
            }
            echo "</div>";
        }
        ?>

    </main>

    <script>
        // ABRIR / FECHAR
        function toggleCalendario(id) {
            const el = document.getElementById("calendario-" + id);

            el.style.display = (el.style.display === "block") ? "none" : "block";

            if (!el.dataset.loaded) {
                gerarCalendario(el);
                el.dataset.loaded = true;
            }
        }

        // GERAR CALENDÁRIO
        function gerarCalendario(container) {

            const calendarioDiv = container.querySelector(".calendario");
            calendarioDiv.innerHTML = "";

            const reservas = JSON.parse(calendarioDiv.dataset.reservas);

            let hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            let ano = hoje.getFullYear();
            let mes = hoje.getMonth();

            renderizarCalendario(ano, mes);

            function renderizarCalendario(ano, mes) {

                calendarioDiv.innerHTML = "";

                // HEADER
                const header = document.createElement("div");
                header.classList.add("cal-header");

                const btnPrev = document.createElement("button");
                btnPrev.innerText = "◀";

                const btnNext = document.createElement("button");
                btnNext.innerText = "▶";

                const titulo = document.createElement("span");

                const meses = [
                    "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                    "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
                ];

                titulo.innerText = `${meses[mes]} ${ano}`;

                btnPrev.onclick = () => {
                    mes--;
                    if (mes < 0) {
                        mes = 11;
                        ano--;
                    }
                    renderizarCalendario(ano, mes);
                };

                btnNext.onclick = () => {
                    mes++;
                    if (mes > 11) {
                        mes = 0;
                        ano++;
                    }
                    renderizarCalendario(ano, mes);
                };

                header.append(btnPrev, titulo, btnNext);
                calendarioDiv.appendChild(header);

                // DIAS SEMANA
                ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"].forEach(d => {
                    let el = document.createElement("div");
                    el.classList.add("dia-semana");
                    el.innerText = d;
                    calendarioDiv.appendChild(el);
                });

                let primeiroDia = new Date(ano, mes, 1).getDay();
                let totalDias = new Date(ano, mes + 1, 0).getDate();

                for (let i = 0; i < primeiroDia; i++) {
                    calendarioDiv.appendChild(document.createElement("div"));
                }

                for (let dia = 1; dia <= totalDias; dia++) {

                    let dataAtual = new Date(ano, mes, dia);
                    dataAtual.setHours(0, 0, 0, 0);

                    let ocupado = false;
                    let info = "";

                    reservas.forEach(r => {

                        let inicio = new Date(r.data_checkin + "T00:00:00");
                        let fim = new Date(r.data_checkout + "T00:00:00");

                        inicio.setHours(0, 0, 0, 0);
                        fim.setHours(0, 0, 0, 0);

                        if (dataAtual >= inicio && dataAtual <= fim) {

                            ocupado = true;

                            if (dataAtual.getTime() === inicio.getTime()) {
                                info = `<small>Início: ${r.hora_checkin}</small>`;
                            } else if (dataAtual.getTime() === fim.getTime()) {
                                info = `<small>Até: ${r.hora_checkout}</small>`;
                            } else {
                                info = `<small>Ocupado</small>`;
                            }
                        }

                    });

                    let el = document.createElement("div");
                    el.classList.add("dia");

                    let texto = `<strong>${dia}</strong>`;

                    if (dataAtual < hoje) {
                        el.classList.add("dia-passado");
                    } else if (ocupado) {
                        el.classList.add("dia-ocupado");
                    } else {
                        el.classList.add("dia-livre");

                        el.onclick = () => {
                            let data = `${ano}-${String(mes+1).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
                            let quarto = calendarioDiv.dataset.id;
                            window.location.href = `reservas_cadastrar.php?id=${quarto}&data=${data}`;
                        };
                    }

                    el.innerHTML = texto + info;
                    calendarioDiv.appendChild(el);
                }
            }
        }
    </script>

    <footer>
        <p>&copy; 2026 Pousada Parnaioca. Todos os direitos reservados.</p>
    </footer>

</body>

</html>
