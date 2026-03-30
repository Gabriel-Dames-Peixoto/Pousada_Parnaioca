<?php
session_start();
include_once './conexao.php';

if (!isset($_SESSION['login']) || $_SESSION['perfil'] !== 'adm') {
    header("Location: index.php");
    exit();
}

$filtro_nome = $_GET['nome'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$mensagem = "";

// 🔍 LISTAR RESERVAS ATIVAS
$sql = "
SELECT 
    r.id, 
    r.quarto_id,
    c.nome AS cliente, 
    COALESCE(u.login, 'Sem usuário') AS usuario
FROM reservas r
JOIN clientes c ON r.cliente_id = c.id
LEFT JOIN usuarios u ON r.usuario_id = u.id
WHERE r.status = 'ativa'
";

$stmt = $con->prepare($sql);
$stmt->execute();
$reservas = $stmt->get_result();

// 🔥 FINALIZAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id_reserva'];

    // 🔎 Verifica reserva
    $check = $con->prepare("SELECT status, valor_total FROM reservas WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!$res) {
        $mensagem = "<p class='erro'>Reserva não encontrada.</p>";
    } elseif ($res['status'] !== 'ativa') {
        $mensagem = "<p class='erro'>Só pode finalizar reservas ativas.</p>";
    } else {

        // 🔥 INSERIR CONSUMO (se houver)
        $total_consumo = 0;

        if (!empty($_POST['frigobar_id']) && is_array($_POST['frigobar_id'])) {
            foreach ($_POST['frigobar_id'] as $index => $item_id) {

                $qtd = $_POST['quantidade'][$index] ?? 0;

                if ($qtd > 0) {

                    // buscar valor e quantidade disponível do item
                    $busca = $con->prepare("SELECT valor, quantidade FROM frigobar WHERE id = ?");
                    $busca->bind_param("i", $item_id);
                    $busca->execute();
                    $item = $busca->get_result()->fetch_assoc();

                    if ($item && $qtd <= $item['quantidade']) {
                        $valor = $item['valor'];
                        $subtotal = $valor * $qtd;
                        $total_consumo += $subtotal;

                        $insert = $con->prepare("
                            INSERT INTO consumo_frigobar (reserva_id, frigobar_id, quantidade, valor_total)
                            VALUES (?, ?, ?, ?)
                        ");

                        $insert->bind_param("iiid", $id, $item_id, $qtd, $subtotal);
                        $insert->execute();
                    }
                }
            }
        }

        // 🔥 FINALIZAR
        $update = $con->prepare("
            UPDATE reservas 
            SET status = 'finalizada',
                data_finalizacao = NOW(),
                valor_total = valor_total + ?
            WHERE id = ?
        ");

        $update->bind_param("di", $total_consumo, $id);
        $update->execute();

        $valor_final = $res['valor_total'] + $total_consumo;
        $mensagem = "<p class='sucesso'>
            Reserva finalizada! Consumo: R$ " . number_format($total_consumo, 2, ',', '.') . " | Total Final: R$ " . number_format($valor_final, 2, ',', '.') . "
        </p>";
        header("Refresh: 3; URL=reservas.php"); 
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="2.css">
<link rel="shortcut icon" href="./imagens/ipousada.png" type="image/x-icon">
<title>Finalizar Reserva</title>
</head>

<body>

<header>
<nav><ul><?php include_once 'menu.php'; ?></ul></nav>
</header>

<main>

<h1>Finalizar Reserva</h1>

<?= $mensagem ?>

<form method="POST">

<label>Reserva:</label>
<select name="id_reserva" id="reservaSelect" required>
    <option value="">-- Selecione --</option>

    <?php while ($r = $reservas->fetch_assoc()) { ?>
        <option value="<?= $r['id'] ?>" data-quarto="<?= $r['quarto_id'] ?>">
            #<?= $r['id'] ?> - <?= $r['cliente'] ?> (<?= $r['usuario'] ?>)
        </option>
    <?php } ?>
</select>

<br><br>

<div id="frigobar-container"></div>

<br>
<button type="submit">Finalizar</button>

</form>

</main>

<script>

document.getElementById('reservaSelect').addEventListener('change', function() {
    let quartoId = this.options[this.selectedIndex].dataset.quarto;

    if (!quartoId) {
        document.getElementById('frigobar-container').innerHTML = '';
        return;
    }

    fetch('buscar_frigobar.php?quarto_id=' + quartoId)
    .then(res => res.text())
    .then(html => {
        document.getElementById('frigobar-container').innerHTML = html;
    })
    .catch(err => console.error('Erro ao buscar frigobar:', err));
});

</script>

</body>
</html>
