<?php
// Detecta a página atual para destacar o item ativo no menu
$pagina_atual = basename($_SERVER['PHP_SELF']);

function menu_link(string $href, string $label, string $pagina_atual): string
{
    $ativo  = ($pagina_atual === $href) ? ' style="color:#3498db; border-bottom: 2px solid #3498db; padding-bottom:2px;"' : '';
    return "<li><a href=\"{$href}\"{$ativo}>{$label}</a></li>";
}
?>
<?= menu_link('inicio.php',   'Início',   $pagina_atual) ?>
<?= menu_link('quartos.php',  'Quartos',  $pagina_atual) ?>
<?= menu_link('reservas.php', 'Reservas', $pagina_atual) ?>
<?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'adm'): ?>
    <?= menu_link('clientes.php',           'Clientes',   $pagina_atual) ?>
    <?= menu_link('usuarios.php',           'Usuários',   $pagina_atual) ?>
    <?= menu_link('dashboard.php',          'Dashboard',  $pagina_atual) ?>
    <?= menu_link('logs_sistema.php',       'Logs',       $pagina_atual) ?>
<?php endif; ?>
<?= menu_link('contato.php', 'Contato', $pagina_atual) ?>
<?= menu_link('logout.php',  'Sair',    $pagina_atual) ?>