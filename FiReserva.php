<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}


$reservas = $con->query("
    SELECT r.id, c.nome, q.quarto
    FROM reservas r
    JOIN clientes c ON c.id = r.cliente_id
    JOIN quartos q ON q.id = r.quarto_id
    WHERE r.status = 'ativa'
");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="2.css">
    <title>Finalizar Reserva</title>
</head>

<body>
    <main>

        <h1>Selecionar Reserva</h1>

        <form method="GET">
            <label>Reserva:</label>

            <select name="id" required onchange="this.form.submit()">
                <option value="">Selecione...</option>

                <?php while ($r = $reservas->fetch_assoc()): ?>
                    <option value="<?= $r['id'] ?>">
                        ID <?= $r['id'] ?> - <?= $r['nome'] ?> (<?= $r['quarto'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <hr>
        <?php
        $id_reserva = $_GET['id'] ?? null;

        if ($id_reserva):

            $stmt = $con->prepare("
    SELECT r.*, c.nome, q.quarto, q.id as quarto_id
    FROM reservas r
    JOIN clientes c ON c.id = r.cliente_id
    JOIN quartos q ON q.id = r.quarto_id
    WHERE r.id = ?
");
            $stmt->bind_param("i", $id_reserva);
            $stmt->execute();
            $reserva = $stmt->get_result()->fetch_assoc();

            $stmt_frigobar = $con->prepare("
    SELECT * FROM frigobar WHERE quarto_id = ? AND status = '1'
");
            $stmt_frigobar->bind_param("i", $reserva['quarto_id']);
            $stmt_frigobar->execute();
            $itens = $stmt_frigobar->get_result();
        ?>

            <h2>Cliente: <?= $reserva['nome'] ?></h2>
            <p>Quarto: <?= $reserva['quarto'] ?></p>

            <p><strong>Valor base:</strong>
                R$ <span id="valorBase"><?= number_format($reserva['valor_total'], 2, '.', '') ?></span>
            </p>

            <form method="POST">

                <input type="hidden" name="id_reserva" value="<?= $id_reserva ?>">

                <h3>Frigobar</h3>

                <?php while ($item = $itens->fetch_assoc()): ?>
                    <div>
                        <?= $item['nome'] ?> (R$ <?= $item['valor'] ?>)

                        <input type="number"
                            class="item"
                            data-preco="<?= $item['valor'] ?>"
                            name="item[<?= $item['id'] ?>]"
                            value="0"
                            min="0">
                    </div>
                <?php endwhile; ?>

                <br>

                <h2>Total: R$ <span id="total">0.00</span></h2>

                <button type="submit">Finalizar</button>

            </form>

            <script>
                function calcularTotal() {

                    let base = parseFloat(document.getElementById("valorBase").innerText);
                    let total = base;

                    document.querySelectorAll(".item").forEach(input => {

                        let preco = parseFloat(input.dataset.preco);
                        let qtd = parseInt(input.value) || 0;

                        total += preco * qtd;
                    });

                    document.getElementById("total").innerText = total.toFixed(2);
                }

                document.querySelectorAll(".item").forEach(input => {
                    input.addEventListener("input", calcularTotal);
                });

                calcularTotal();
            </script>

        <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $id_reserva = $_POST['id_reserva'];

                $stmt = $con->prepare("SELECT valor_total FROM reservas WHERE id=?");
                $stmt->bind_param("i", $id_reserva);
                $stmt->execute();
                $reserva = $stmt->get_result()->fetch_assoc();

                $valor_final = $reserva['valor_total'];

                foreach ($_POST['item'] as $id_item => $qtd) {

                    if ($qtd > 0) {

                        $stmt_item = $con->prepare("SELECT valor FROM frigobar WHERE id=?");
                        $stmt_item->bind_param("i", $id_item);
                        $stmt_item->execute();
                        $item = $stmt_item->get_result()->fetch_assoc();

                        $subtotal = $item['valor'] * $qtd;
                        $valor_final += $subtotal;

                        $stmt_insert = $con->prepare("
                INSERT INTO consumo_frigobar 
                (reserva_id, frigobar_id, quantidade, valor_total)
                VALUES (?, ?, ?, ?)
            ");
                        $stmt_insert->bind_param("iiid", $id_reserva, $id_item, $qtd, $subtotal);
                        $stmt_insert->execute();
                    }
                }

                $stmt_up = $con->prepare("
        UPDATE reservas 
        SET valor_total=?, status='finalizada'
        WHERE id=?
    ");
                $stmt_up->bind_param("di", $valor_final, $id_reserva);
                $stmt_up->execute();

                echo "<script>
        alert('Finalizado! Total: R$ " . number_format($valor_final, 2, ',', '.') . "');
        window.location='reservas.php';
    </script>";
            }
        endif;
        ?>