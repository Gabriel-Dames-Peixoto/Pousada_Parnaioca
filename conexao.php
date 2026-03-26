<?php
$con = mysqli_connect("localhost", "root", "", "parnoicagabriel")
    or die("Erro ao conectar com o banco de dados: " . mysqli_connect_error());

function registrarLog($mensagem, $acao)
{
    global $con;
    $msg = mysqli_real_escape_string($con, $mensagem);
    $tipo = mysqli_real_escape_string($con, $acao);

    // Agora o INSERT inclui a nova coluna 'acao'
    $sql = "INSERT INTO logs_sistema (mensagem, acao) VALUES ('$msg', '$tipo')";
    mysqli_query($con, $sql);
}
