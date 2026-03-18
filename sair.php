<?php
session_start(); // Inicia a sessão para poder manipulá-la
session_unset(); // Limpa todas as variáveis de sessão
session_destroy(); // Destrói a sessão logicamente

// Redireciona para a tela inicial/login
header("Location: index.php");
exit();
?>