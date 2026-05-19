<?php
if (!defined('ADMIN_CONTEXT')) {
    header('Location: admin');
    exit();
}
$_current = basename($_SERVER['PHP_SELF'], '.php');
function admin_nav_link(string $href, string $label, string $current): string {
    $active = $current === $href ? ' class="active" aria-current="page"' : '';
    return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $active . '>' . $label . '</a>';
}
?>
<aside class="admin-sidebar" aria-label="Navigation administration">
    <nav class="admin-nav" role="navigation" aria-label="Menu administration">
        <div class="admin-nav-brand"><a href="admin">⚙ Admin</a></div>
        <?= admin_nav_link('admin',           '📊 Tableau de bord', $_current) ?>
        <?= admin_nav_link('admin_users',     '👥 Utilisateurs',    $_current) ?>
        <?= admin_nav_link('admin_faq',       '❓ FAQ',             $_current) ?>
        <?= admin_nav_link('admin_legal',     '📄 Pages légales',   $_current) ?>
        <?= admin_nav_link('admin_theme',     '🎨 Rendu visuel',    $_current) ?>
        <?= admin_nav_link('admin_forum',     '💬 Forum',           $_current) ?>
        <?= admin_nav_link('admin_messages',  '✉️ Messagerie',      $_current) ?>
        <div class="admin-nav-sep"></div>
        <a href="index">← Retour au site</a>
    </nav>
</aside>
