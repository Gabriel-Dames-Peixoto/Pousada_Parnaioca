<?php
<<<<<<< HEAD
    @$con = mysqli_connect("localhost","root","","parnoica") // alterar para parnoicagabriel
or die("Erro ao conectar com o banco de dados: " . mysqli_connect_error());
=======
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
>>>>>>> 5dc1ef35964e29e7e4c1168bd60c9529d6671faf
