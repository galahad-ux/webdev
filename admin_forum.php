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
        $tid    = (int)($_POST['thread_id'] ?? 0);
        $rid    = (int)($_POST['reply_id']  ?? 0);

        try {
            if ($action === 'delete_thread' && $tid > 0) {
                $pdo->prepare("DELETE FROM forum_thread WHERE thread_id = ?")->execute([$tid]);
                $success = 'Sujet supprimé.';
            } elseif ($action === 'toggle_close' && $tid > 0) {
                $pdo->prepare("UPDATE forum_thread SET is_closed = 1 - is_closed WHERE thread_id = ?")->execute([$tid]);
                $success = 'Statut modifié.';
            } elseif ($action === 'toggle_pin' && $tid > 0) {
                $pdo->prepare("UPDATE forum_thread SET is_pinned = 1 - is_pinned WHERE thread_id = ?")->execute([$tid]);
                $success = 'Épinglage modifié.';
            } elseif ($action === 'delete_reply' && $rid > 0) {
                $pdo->prepare("DELETE FROM forum_reply WHERE reply_id = ?")->execute([$rid]);
                $success = 'Réponse supprimée.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

$threads = [];
$replies = [];
try {
    $threads = $pdo->query("
        SELECT ft.*, u.name AS author_name,
               COUNT(fr.reply_id) AS reply_count
        FROM forum_thread ft
        JOIN `user` u ON ft.user_id = u.user_id
        LEFT JOIN forum_reply fr ON ft.thread_id = fr.thread_id
        GROUP BY ft.thread_id, u.name
        ORDER BY ft.is_pinned DESC, ft.created_at DESC
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);

    $replies = $pdo->query("
        SELECT fr.*, u.name AS author_name, ft.title AS thread_title
        FROM forum_reply fr
        JOIN `user` u ON fr.user_id = u.user_id
        JOIN forum_thread ft ON fr.thread_id = ft.thread_id
        ORDER BY fr.created_at DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'La table forum_thread n\'existe pas encore. Exécutez les migrations SQL.';
}

$page_title = 'Modération forum — Admin Momo';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">Modération du forum</h1>

        <?php if ($success): ?><div class="admin-alert admin-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="admin-alert admin-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <section class="admin-form-section" aria-labelledby="threads-title">
            <h2 id="threads-title">Sujets (<?= count($threads) ?>)</h2>
            <?php if (empty($threads)): ?>
                <p>Aucun sujet pour le moment.</p>
            <?php else: ?>
            <div class="admin-table-wrap" tabindex="0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Titre</th>
                        <th scope="col">Auteur</th>
                        <th scope="col">Réponses</th>
                        <th scope="col">Date</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($threads as $t): ?>
                <tr>
                    <td>
                        <?php if ($t['is_pinned']): ?><span class="badge badge-pinned" title="Épinglé">📌</span> <?php endif; ?>
                        <a href="forum_thread?id=<?= (int)$t['thread_id'] ?>" target="_blank">
                            <?= htmlspecialchars(mb_strimwidth($t['title'], 0, 60, '…'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($t['author_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$t['reply_count'] ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($t['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge <?= $t['is_closed'] ? 'badge-inactive' : 'badge-active' ?>">
                            <?= $t['is_closed'] ? 'Fermé' : 'Ouvert' ?>
                        </span>
                    </td>
                    <td class="admin-actions-cell">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="thread_id" value="<?= (int)$t['thread_id'] ?>">
                            <input type="hidden" name="action" value="toggle_pin">
                            <button type="submit" class="btn-small" title="<?= $t['is_pinned'] ? 'Désépingler' : 'Épingler' ?>">
                                <?= $t['is_pinned'] ? '📌 Désépingler' : '📌 Épingler' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="thread_id" value="<?= (int)$t['thread_id'] ?>">
                            <input type="hidden" name="action" value="toggle_close">
                            <button type="submit" class="btn-small">
                                <?= $t['is_closed'] ? 'Rouvrir' : 'Fermer' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce sujet et toutes ses réponses ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="thread_id" value="<?= (int)$t['thread_id'] ?>">
                            <input type="hidden" name="action" value="delete_thread">
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

        <section class="admin-form-section" aria-labelledby="replies-title">
            <h2 id="replies-title">Dernières réponses (50)</h2>
            <?php if (empty($replies)): ?>
                <p>Aucune réponse pour le moment.</p>
            <?php else: ?>
            <div class="admin-table-wrap" tabindex="0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Auteur</th>
                        <th scope="col">Sujet</th>
                        <th scope="col">Extrait</th>
                        <th scope="col">Date</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($replies as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['author_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['thread_title'], 0, 40, '…'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($r['content'], 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette réponse ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="reply_id" value="<?= (int)$r['reply_id'] ?>">
                            <input type="hidden" name="action" value="delete_reply">
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
