<?php
session_start();
include_once './conexao.php';
include_once './sessao_validar.php';

exigirAdm();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt_busca = $con->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmt_busca->bind_param("i", $id);
    $stmt_busca->execute();
    $resultado = $stmt_busca->get_result();
    $dados_cliente = $resultado->fetch_assoc();
    $nome_cliente = $dados_cliente['nome'] ?? "Desconhecido";
    $stmt_busca->close();

    $sql = "UPDATE clientes SET status = 0 WHERE id = ?";

    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            registrarLog("O cliente $nome_cliente (ID $id) foi inativado por " . $_SESSION['login'], "UPDATE");
            header("Location: clientes.php?mensagem=inativado");
            exit();
        } else {
            echo "Erro ao inativar: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    header("Location: clientes.php");
}

mysqli_close($con);
