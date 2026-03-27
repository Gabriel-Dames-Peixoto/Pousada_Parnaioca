<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['status'] === 1 || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}

$id_reserva = $_GET['id'] ?? null;

// 🔥 Buscar reserva + quarto
$stmt = $con->prepare("
    SELECT r.*, q.id as quarto_id, q.quarto
    FROM reservas r
    JOIN quartos q ON q.id = r.quarto_id
    WHERE r.id = ?
");
$stmt->bind_param("i", $id_reserva);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();

if (!$reserva) {
    die("Reserva não encontrada.");
}

// 🔥 Buscar itens do frigobar daquele quarto
$stmt_frigobar = $con->prepare("
    SELECT * FROM frigobar 
    WHERE quarto_id = ? AND status = '1'
");
$stmt_frigobar->bind_param("i", $reserva['quarto_id']);
$stmt_frigobar->execute();
$itens = $stmt_frigobar->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Reserva</title>
    <link rel="stylesheet" href="2.css">
</head>

<body>

<main>
    <h1>Finalizar Reserva - Quarto <?= htmlspecialchars($reserva['quarto']) ?></h1>

    <p><strong>Valor atual:</strong> 
        R$ <?= number_format($reserva['valor_total'],2,',','.') ?>
    </p>

    <form method="POST">

        <input type="hidden" name="id_reserva" value="<?= $id_reserva ?>">

        <h2>Consumo do Frigobar</h2>

        <?php while ($item = $itens->fetch_assoc()): ?>
            <label>
                <?= htmlspecialchars($item['nome']) ?> 
                (R$ <?= number_format($item['valor'],2,',','.') ?>)
            </label>

            <input type="number" 
                   name="item[<?= $item['id'] ?>]" 
                   min="0" 
                   value="0">

            <br><br>
        <?php endwhile; ?>

        <button type="submit">Finalizar Reserva</button>
    </form>
</main>

</body>
</html>

<?php
// 🔥 PROCESSAMENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_reserva = $_POST['id_reserva'];

    // 🔥 Buscar reserva
    $stmt = $con->prepare("SELECT valor_total FROM reservas WHERE id = ?");
    $stmt->bind_param("i", $id_reserva);
    $stmt->execute();
    $reserva = $stmt->get_result()->fetch_assoc();

    $valor_final = $reserva['valor_total'];

    if (!empty($_POST['item'])) {

        // 🔥 PEGAR TODOS IDS DE UMA VEZ
        $ids = array_keys($_POST['item']);
        $ids_str = implode(",", array_map('intval', $ids));

        $result = $con->query("SELECT id, valor FROM frigobar WHERE id IN ($ids_str)");

        $valores = [];
        while ($row = $result->fetch_assoc()) {
            $valores[$row['id']] = $row['valor'];
        }

        foreach ($_POST['item'] as $id_item => $qtd) {

            if ($qtd > 0 && isset($valores[$id_item])) {

                $subtotal = $valores[$id_item] * $qtd;
                $valor_final += $subtotal;

                // 🔥 SALVAR CONSUMO NO BANCO
                $stmt_insert = $con->prepare("
                    INSERT INTO consumo_frigobar 
                    (reserva_id, frigobar_id, quantidade, valor_total)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt_insert->bind_param(
                    "iiid",
                    $id_reserva,
                    $id_item,
                    $qtd,
                    $subtotal
                );

                $stmt_insert->execute();
            }
        }
    }

    // 🔥 FINALIZAR RESERVA
    $stmt_up = $con->prepare("
        UPDATE reservas 
        SET valor_total = ?, status = 'finalizada'
        WHERE id = ?
    ");
    $stmt_up->bind_param("di", $valor_final, $id_reserva);

    if ($stmt_up->execute()) {

        registrarLog(
            "Reserva $id_reserva finalizada com consumo por " . $_SESSION['login'],
            "UPDATE"
        );

        echo "<script>
            alert('Reserva finalizada! Total: R$ " . number_format($valor_final,2,',','.') . "');
            window.location='reservas.php';
        </script>";
    }
}
?>
