<?php
include_once './conexao.php';

$quarto_id = filter_input(INPUT_GET, 'quarto_id', FILTER_VALIDATE_INT);

if (!$quarto_id) {
    exit;
}

$stmt = $con->prepare("
    SELECT id, nome, valor, quantidade
    FROM frigobar
    WHERE quarto_id = ? AND status = '1'
    ORDER BY nome ASC
");

$stmt->bind_param("i", $quarto_id);
$stmt->execute();
$res = $stmt->get_result();

echo "<h3>Consumo do Frigobar</h3>";

while ($item = $res->fetch_assoc()) {
    $nome = htmlspecialchars($item['nome']);
    $valor = number_format((float)$item['valor'], 2, ',', '.');
    $maximo = max(0, (int)$item['quantidade']);

    echo "
    <div style='margin-bottom:10px'>
        <strong>{$nome} (R$ {$valor})</strong><br>
        <small>Disponivel: {$maximo}</small><br>

        <input type='hidden' name='frigobar_id[]' value='{$item['id']}'>
        <input type='number' name='quantidade[]' min='0' max='{$maximo}' value='0'>
    </div>
    ";
}

$stmt->close();
?>
