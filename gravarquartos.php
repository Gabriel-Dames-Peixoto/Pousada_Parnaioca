<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gravar Quartos</title>
</head>
<body>
    <h1>Cadastro de Quartos</h1>
    <form action="gravarquartos.php" method="post">
        <label for="numero">Número do Quarto:</label>
        <input type="text" id="numero" name="numero" required><br><br>

        <label for="tipo">Tipo:</label>
        <input type="text" id="tipo" name="tipo" required><br><br>

        <label for="preco">Preço:</label>
        <input type="number" id="preco" name="preco" step="0.01" required><br><br>

        <input type="submit" value="Gravar">
    </form>
</body>
</html>