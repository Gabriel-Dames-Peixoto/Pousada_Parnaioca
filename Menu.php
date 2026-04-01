<li><a href="inicio.php">Início</a></li>
<li><a href="quartos.php">Quartos</a></li>
<li><a href="reservas.php">Reservas</a></li>
<?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm'): ?>
    <li><a href="clientes.php">Clientes</a></li>
    <li><a href="usuarios.php">Usuários</a></li>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="relatorio_financeiro.php">Financeiro</a></li>
<?php endif; ?>
<li><a href="contato.php">Contato</a></li>
<li><a href="sair.php">Sair</a></li>