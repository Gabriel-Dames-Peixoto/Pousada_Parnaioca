<?php
include_once './conexao.php';

// Obtém o ID da URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // ALTERAÇÃO: Em vez de DELETE, fazemos um UPDATE no status
    $sql = "UPDATE clientes SET status = 0 WHERE id = ?";
    
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Redireciona de volta com uma mensagem de sucesso (opcional)
            header("Location: clientes.php?mensagem=inativado");
        } else {
            echo "Erro ao inativar: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    header("Location: clientes.php");
}

mysqli_close($con);
?>