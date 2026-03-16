<?php
    session_start();
    include_once './conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Verificar se o usuário existe
    $verificar = $con->prepare("SELECT id FROM clientes WHERE id = ?");
    if (!$verificar) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao preparar a consulta']);
        exit;
    }
    
    $verificar->bind_param("i", $id);
    $verificar->execute();
    
    if ($verificar->get_result()->num_rows > 0) {
        // Deletar o usuário
        $deletar = $con->prepare("DELETE FROM clientes WHERE id = ?");
        if (!$deletar) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao preparar DELETE']);
            $verificar->close();
            exit;
        }
        
        $deletar->bind_param("i", $id);
        
        if ($deletar->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Cliente deletado com sucesso']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao deletar: ' . $deletar->error]);
        }
        $deletar->close();
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Cliente não encontrado']);
    }
    
    $verificar->close();
    exit;
}

?>