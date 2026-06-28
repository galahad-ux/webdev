<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$lang    = $_SESSION['language'] ?? 'fr';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$role    = $_SESSION['user_role'] ?? 'user';

$thread_id = (int)($_GET['id'] ?? 0);
if ($thread_id <= 0) {
    header('Location: forum');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error   = '';

// Post a reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user_id) {
        header('Location: auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = $lang === 'en' ? 'Invalid security token.' : 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_reply') {
            $content = trim($_POST['content'] ?? '');
            if (mb_strlen($content) < 2) {
                $error = $lang === 'en' ? 'Reply cannot be empty.' : 'La réponse ne peut pas être vide.';
            } else {
                try {
                    $check = $pdo->prepare("SELECT is_closed FROM forum_thread WHERE thread_id = ?");
                    $check->execute([$thread_id]);
                    $row = $check->fetch(PDO::FETCH_ASSOC);
                    if (!$row) {
                        header('Location: forum');
                        exit();
                    }
                    if ($row['is_closed']) {
                        $error = $lang === 'en' ? 'This topic is closed.' : 'Ce sujet est fermé.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO forum_reply (thread_id, user_id, content) VALUES (?, ?, ?)");
                        $stmt->execute([$thread_id, $user_id, $content]);
                        header('Location: forum_thread?id=' . $thread_id . '#replies');
                        exit();
                    }
                } catch (PDOException $e) {
                    $error = $lang === 'en' ? 'Database error.' : 'Erreur base de données.';
                }
            }
        } elseif ($action === 'delete_reply' && in_array($role, ['admin'], true)) {
            $rid = (int)($_POST['reply_id'] ?? 0);
            if ($rid > 0) {
                $pdo->prepare("DELETE FROM forum_reply WHERE reply_id = ? AND thread_id = ?")->execute([$rid, $thread_id]);
                $success = $lang === 'en' ? 'Reply deleted.' : 'Réponse supprimée.';
            }
        }
    }
}

// Load thread
$thread  = null;
$replies = [];
try {
    $stmt = $pdo->prepare("
        SELECT ft.*, u.name AS author_name
        FROM forum_thread ft
        JOIN `user` u ON ft.user_id = u.user_id
        WHERE ft.thread_id = ?
    ");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thread) {
        header('Location: forum');
        exit();
    }

    $rStmt = $pdo->prepare("
        SELECT fr.*, u.name AS author_name
        FROM forum_reply fr
        JOIN `user` u ON fr.user_id = u.user_id
        WHERE fr.thread_id = ?
        ORDER BY fr.created_at ASC
    ");
    $rStmt->execute([$thread_id]);
    $replies = $rStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: forum');
    exit();
}

$t = [
    'fr' => [
        'back'        => '← Retour au forum',
        'by'          => 'par',
        'replies'     => 'Réponses',
        'no_replies'  => 'Aucune réponse pour le moment. Soyez le premier à répondre !',
        'closed_msg'  => 'Ce sujet est fermé. Vous ne pouvez plus y répondre.',
        'reply_title' => 'Votre réponse',
        'reply_ph'    => 'Rédigez votre réponse…',
        'submit'      => 'Répondre',
        'login_reply' => 'Connectez-vous pour répondre.',
        'delete'      => 'Supprimer',
        'pinned'      => 'Épinglé',
        'closed'      => 'Fermé',
    ],
    'en' => [
        'back'        => '← Back to forum',
        'by'          => 'by',
        'replies'     => 'Replies',
        'no_replies'  => 'No replies yet. Be the first to reply!',
        'closed_msg'  => 'This topic is closed. No more replies allowed.',
        'reply_title' => 'Your reply',
        'reply_ph'    => 'Write your reply…',
        'submit'      => 'Reply',
        'login_reply' => 'Log in to reply.',
        'delete'      => 'Delete',
        'pinned'      => 'Pinned',
        'closed'      => 'Closed',
    ],
][$lang] ?? [];

$page_title = htmlspecialchars($thread['title'], ENT_QUOTES, 'UTF-8') . ' — Forum Momo';
include 'header.php';
?>

<main id="main-content">
<div class="forum-container">
    <a href="forum" class="forum-back-link" aria-label="<?= htmlspecialchars($t['back'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($t['back'], ENT_QUOTES, 'UTF-8') ?>
    </a>

    <?php if ($error):   ?><div class="forum-alert forum-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($success): ?><div class="forum-alert forum-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <!-- Original post -->
    <article class="thread-original" aria-labelledby="thread-heading">
        <header class="thread-original-header">
            <div class="thread-badges">
                <?php if ($thread['is_pinned']): ?>
                    <span class="badge badge-pinned">📌 <?= htmlspecialchars($t['pinned'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($thread['is_closed']): ?>
                    <span class="badge badge-inactive">🔒 <?= htmlspecialchars($t['closed'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <h1 id="thread-heading"><?= htmlspecialchars($thread['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="thread-meta">
                <span><?= htmlspecialchars($t['by'], ENT_QUOTES, 'UTF-8') ?>
                    <strong><?= htmlspecialchars($thread['author_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
                <span>·</span>
                <time datetime="<?= htmlspecialchars($thread['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(date($lang === 'en' ? 'F j, Y \a\t g:i a' : 'd/m/Y à H:i', strtotime($thread['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </time>
            </div>
        </header>
        <div class="thread-body">
            <?= nl2br(htmlspecialchars($thread['content'], ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </article>

    <!-- Replies -->
    <section id="replies" aria-labelledby="replies-heading">
        <h2 id="replies-heading" class="forum-section-title">
            <?= htmlspecialchars($t['replies'], ENT_QUOTES, 'UTF-8') ?> (<?= count($replies) ?>)
        </h2>

        <?php if (empty($replies)): ?>
            <p class="forum-empty"><?= htmlspecialchars($t['no_replies'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <div class="reply-list" role="list">
            <?php foreach ($replies as $i => $reply): ?>
                <article class="reply-item" role="listitem" id="reply-<?= (int)$reply['reply_id'] ?>">
                    <div class="reply-meta">
                        <strong><?= htmlspecialchars($reply['author_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span>·</span>
                        <time datetime="<?= htmlspecialchars($reply['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(date($lang === 'en' ? 'F j, Y \a\t g:i a' : 'd/m/Y à H:i', strtotime($reply['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                        </time>
                        <span class="reply-number">#<?= $i + 1 ?></span>
                    </div>
                    <div class="reply-body">
                        <?= nl2br(htmlspecialchars($reply['content'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                    <?php if ($role === 'admin'): ?>
                    <div class="reply-admin-actions">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette réponse ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="delete_reply">
                            <input type="hidden" name="reply_id" value="<?= (int)$reply['reply_id'] ?>">
                            <button type="submit" class="btn-small btn-small-danger">
                                <?= htmlspecialchars($t['delete'], ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Reply form -->
    <?php if ($thread['is_closed']): ?>
        <div class="forum-closed-msg" role="status">
            🔒 <?= htmlspecialchars($t['closed_msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php elseif (!$user_id): ?>
        <div class="forum-login-prompt">
            <a href="auth" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;">
                <?= htmlspecialchars($t['login_reply'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
    <?php else: ?>
        <section class="reply-compose" aria-labelledby="reply-form-heading">
            <h2 id="reply-form-heading" class="forum-section-title">
                <?= htmlspecialchars($t['reply_title'], ENT_QUOTES, 'UTF-8') ?>
            </h2>
            <form method="POST" action="forum_thread?id=<?= $thread_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_reply">
                <div class="forum-form-group">
                    <label for="reply-content" class="sr-only"><?= htmlspecialchars($t['reply_title'], ENT_QUOTES, 'UTF-8') ?></label>
                    <textarea id="reply-content" name="content"
                              rows="6" required minlength="2"
                              aria-required="true"
                              placeholder="<?= htmlspecialchars($t['reply_ph'], ENT_QUOTES, 'UTF-8') ?>"></textarea>
                </div>
                <button type="submit" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;">
                    <?= htmlspecialchars($t['submit'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>
        </section>
    <?php endif; ?>
</div>
</main>

<?php include 'footer.php'; ?>
