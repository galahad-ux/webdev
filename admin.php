<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: auth');
    exit();
}

define('ADMIN_CONTEXT', true);

$stats = ['users' => 0, 'bookings' => 0, 'threads' => 0, 'unread' => 0, 'tickets_open' => 0];
try {
    $stats['users']        = (int)$pdo->query("SELECT COUNT(*) FROM `user`")->fetchColumn();
    $stats['bookings']     = (int)$pdo->query("SELECT COUNT(*) FROM trip_booking WHERE status = 'confirmed'")->fetchColumn();
    $stats['tickets_open'] = (int)$pdo->query("SELECT COUNT(*) FROM ticket WHERE status = 'open'")->fetchColumn();
} catch (PDOException $e) {}
try {
    $stats['threads'] = (int)$pdo->query("SELECT COUNT(*) FROM forum_thread")->fetchColumn();
} catch (PDOException $e) {}
try {
    $stats['unread'] = (int)$pdo->query("SELECT COUNT(*) FROM internal_message WHERE is_read = 0")->fetchColumn();
} catch (PDOException $e) {}

$page_title = 'Administration — Momo Travel';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">Tableau de bord</h1>

        <div class="admin-stats-grid" role="list" aria-label="Statistiques générales">
            <div class="admin-stat-card" role="listitem">
                <span class="stat-value" aria-label="<?= $stats['users'] ?> utilisateurs"><?= $stats['users'] ?></span>
                <span class="stat-label">Utilisateurs</span>
            </div>
            <div class="admin-stat-card" role="listitem">
                <span class="stat-value" aria-label="<?= $stats['bookings'] ?> réservations"><?= $stats['bookings'] ?></span>
                <span class="stat-label">Réservations confirmées</span>
            </div>
            <div class="admin-stat-card" role="listitem">
                <span class="stat-value" aria-label="<?= $stats['tickets_open'] ?> tickets"><?= $stats['tickets_open'] ?></span>
                <span class="stat-label">Tickets ouverts</span>
            </div>
            <div class="admin-stat-card" role="listitem">
                <span class="stat-value" aria-label="<?= $stats['threads'] ?> sujets"><?= $stats['threads'] ?></span>
                <span class="stat-label">Sujets forum</span>
            </div>
            <div class="admin-stat-card" role="listitem">
                <span class="stat-value" aria-label="<?= $stats['unread'] ?> messages"><?= $stats['unread'] ?></span>
                <span class="stat-label">Messages non lus</span>
            </div>
        </div>

        <nav class="admin-cards-grid" aria-label="Sections d'administration">
            <a href="admin_users" class="admin-action-card" aria-label="Gérer les utilisateurs">
                <span class="admin-card-icon" aria-hidden="true">👥</span>
                <h2>Utilisateurs</h2>
                <p>Gérer comptes, rôles et accès</p>
            </a>
            <a href="admin_faq" class="admin-action-card" aria-label="Gérer la FAQ">
                <span class="admin-card-icon" aria-hidden="true">❓</span>
                <h2>FAQ</h2>
                <p>Créer et modifier les questions fréquentes</p>
            </a>
            <a href="admin_legal" class="admin-action-card" aria-label="Gérer les pages légales">
                <span class="admin-card-icon" aria-hidden="true">📄</span>
                <h2>Pages légales</h2>
                <p>CGU et mentions légales</p>
            </a>
            <a href="admin_theme" class="admin-action-card" aria-label="Gérer le rendu visuel">
                <span class="admin-card-icon" aria-hidden="true">🎨</span>
                <h2>Rendu visuel</h2>
                <p>Paramètres d'apparence du site</p>
            </a>
            <a href="admin_forum" class="admin-action-card" aria-label="Modérer le forum">
                <span class="admin-card-icon" aria-hidden="true">💬</span>
                <h2>Forum</h2>
                <p>Modérer sujets et réponses</p>
            </a>
            <a href="admin_messages" class="admin-action-card" aria-label="Superviser la messagerie">
                <span class="admin-card-icon" aria-hidden="true">✉️</span>
                <h2>Messagerie</h2>
                <p>Superviser la messagerie interne</p>
            </a>
        </nav>
    </div>
</div>
</main>

<?php include 'footer.php'; ?>
