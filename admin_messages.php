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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        $mid    = (int)($_POST['message_id'] ?? 0);
        try {
            if ($action === 'delete_message' && $mid > 0) {
                $pdo->prepare("DELETE FROM internal_message WHERE message_id = ?")->execute([$mid]);
                $success = 'Message supprimé.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

$stats    = ['total' => 0, 'unread' => 0];
$messages = [];
try {
    $stats['total']  = (int)$pdo->query("SELECT COUNT(*) FROM internal_message")->fetchColumn();
    $stats['unread'] = (int)$pdo->query("SELECT COUNT(*) FROM internal_message WHERE is_read = 0")->fetchColumn();

    $messages = $pdo->query("
        SELECT im.*,
               u_from.name AS from_name,
               u_to.name   AS to_name
        FROM internal_message im
        JOIN `user` u_from ON im.from_user_id = u_from.user_id
        JOIN `user` u_to   ON im.to_user_id   = u_to.user_id
        ORDER BY im.created_at DESC
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'La table internal_message n\'existe pas encore. Exécutez les migrations SQL.';
}

$page_title = 'Messagerie — Admin Momo';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">Supervision de la messagerie</h1>

        <?php if ($success): ?><div class="admin-alert admin-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="admin-alert admin-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <div class="admin-stats-grid" style="grid-template-columns:repeat(2,1fr);max-width:400px;margin-bottom:2rem;">
            <div class="admin-stat-card">
                <span class="stat-value"><?= $stats['total'] ?></span>
                <span class="stat-label">Messages total</span>
            </div>
            <div class="admin-stat-card">
                <span class="stat-value"><?= $stats['unread'] ?></span>
                <span class="stat-label">Non lus</span>
            </div>
        </div>

        <section class="admin-form-section" aria-labelledby="msg-list-title">
            <h2 id="msg-list-title">Derniers messages (100)</h2>
            <?php if (empty($messages)): ?>
                <p>Aucun message pour le moment.</p>
            <?php else: ?>
            <div class="admin-table-wrap" tabindex="0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">De</th>
                        <th scope="col">À</th>
                        <th scope="col">Message</th>
                        <th scope="col">Lu</th>
                        <th scope="col">Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($messages as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['from_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($m['to_name'],   ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($m['content'], 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge <?= $m['is_read'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $m['is_read'] ? 'Lu' : 'Non lu' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce message ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="message_id" value="<?= (int)$m['message_id'] ?>">
                            <input type="hidden" name="action" value="delete_message">
                            <button type="submit" class="btn-small btn-small-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
</main>

<?php include 'footer.php'; ?>
