<?php
$con = mysqli_connect("localhost", "root", "", "parnaiocagabriel")
    or die("Erro ao conectar com o banco de dados: " . mysqli_connect_error());

mysqli_set_charset($con, 'utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function colunaExiste(mysqli $con, string $tabela, string $coluna): bool
{
    $stmt = $con->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->bind_param("ss", $tabela, $coluna);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($resultado['total'] ?? 0) > 0;
}

function garantirEstruturaReservas(mysqli $con): void
{
    $ajustes = [
        'data_checkin_real' => "ALTER TABLE reservas ADD COLUMN data_checkin_real DATETIME DEFAULT NULL AFTER hora_checkin",
        'data_ultima_extensao' => "ALTER TABLE reservas ADD COLUMN data_ultima_extensao DATETIME DEFAULT NULL AFTER hora_checkout",
    ];

    foreach ($ajustes as $coluna => $sql) {
        if (!colunaExiste($con, 'reservas', $coluna)) {
            $con->query($sql);
        }
    }
}

garantirEstruturaReservas($con);

function registrarLog($mensagem, $acao)
{
    global $con;
    $msg = mysqli_real_escape_string($con, $mensagem);
    $tipo = mysqli_real_escape_string($con, $acao);

    $sql = "INSERT INTO logs_sistema (mensagem, acao, data_hora) VALUES ('$msg', '$tipo', Now())";
    
    Return mysqli_query($con, $sql);
}

function normalizarCPF(?string $cpf): string
{
    return preg_replace('/\D/', '', (string)$cpf);
}

function formatarCPF(?string $cpf): string
{
    $cpf = normalizarCPF($cpf);

    if (strlen($cpf) !== 11) {
        return (string)$cpf;
    }

    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function clienteAtivoExiste(int $clienteId): bool
{
    global $con;

    $stmt = $con->prepare("SELECT id FROM clientes WHERE id = ? AND status = '1' LIMIT 1");
    $stmt->bind_param("i", $clienteId);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (bool)$cliente;
}

function obterInicioReserva(string $data, string $hora): DateTime
{
    return new DateTime(trim($data) . ' ' . trim($hora));
}

function calcularValorReserva(float $precoBase, DateTime $inicio, DateTime $fim): float
{
    $dias = max(1, (int)$inicio->diff($fim)->days);
    $valorFinal = $precoBase;

    if ($dias < 5) {
        $valorFinal *= (1 - (5 - $dias) * 0.10);
    } elseif ($dias > 5) {
        $valorFinal *= (1 + ($dias - 5) * 0.10);
    }

    return round($valorFinal, 2);
}

date_default_timezone_set('America/Sao_Paulo');
