<?php
    session_start();
    include_once './conexao.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id_usuario = intval($_POST['id']);
    
    // Verificar se o usuário existe
    $verificar = $con->prepare("SELECT id FROM clientes WHERE id = ?");
    $verificar->bind_param("i", $id_usuario);
    $verificar->execute();
    
    if ($verificar->get_result()->num_rows > 0) {
        // Deletar o usuário
        $deletar = $con->prepare("DELETE FROM clientes WHERE id = ?");
        $deletar->bind_param("i", $id_usuario);
        
        if ($deletar->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Cliente deletado com sucesso']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao deletar']);
        }
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Cliente não encontrado']);
    }
    
    $verificar->close();
    $deletar->close();
    exit;
}
?>