<?php
$con = mysqli_connect("localhost", "root", "", "parnaiocagabriel")
    or die("Erro ao conectar com o banco de dados: " . mysqli_connect_error());

function registrarLog($mensagem, $acao)
{
    global $con;
    $msg = mysqli_real_escape_string($con, $mensagem);
    $tipo = mysqli_real_escape_string($con, $acao);

    $sql = "INSERT INTO logs_sistema (mensagem, acao) VALUES ('$msg', '$tipo')";
    mysqli_query($con, $sql);
}

date_default_timezone_set('America/Sao_Paulo');
