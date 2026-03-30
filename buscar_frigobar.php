<?php
include_once './conexao.php';

$quarto_id = $_GET['quarto_id'];

$stmt = $con->prepare("
    SELECT id, nome, valor 
    FROM frigobar 
    WHERE quarto_id = ? AND status = '1'
");

$stmt->bind_param("i", $quarto_id);
$stmt->execute();
$res = $stmt->get_result();

echo "<h3>Consumo do Frigobar</h3>";

while ($item = $res->fetch_assoc()) {

    echo "
    <div style='margin-bottom:10px'>
        <strong>{$item['nome']} (R$ {$item['valor']})</strong><br>

        <input type='hidden' name='frigobar_id[]' value='{$item['id']}'>
        <input type='number' name='quantidade[]' min='0' value='0'>
    </div>
    ";
}
?>
