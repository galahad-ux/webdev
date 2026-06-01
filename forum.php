<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$lang    = $_SESSION['language'] ?? 'fr';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$role    = $_SESSION['user_role'] ?? 'user';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error   = '';

// Create a new thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_thread') {
    if (!$user_id) {
        header('Location: auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = $lang === 'en' ? 'Invalid security token.' : 'Token de sécurité invalide.';
    } else {
        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');
        if (mb_strlen($title) < 3 || mb_strlen($title) > 200) {
            $error = $lang === 'en' ? 'Title must be between 3 and 200 characters.' : 'Le titre doit faire entre 3 et 200 caractères.';
        } elseif (mb_strlen($content) < 10) {
            $error = $lang === 'en' ? 'Content must be at least 10 characters.' : 'Le contenu doit faire au moins 10 caractères.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO forum_thread (user_id, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $title, $content]);
                $new_id = $pdo->lastInsertId();
                header('Location: forum_thread?id=' . (int)$new_id);
                exit();
            } catch (PDOException $e) {
                $error = $lang === 'en' ? 'Database error. Please try again.' : 'Erreur base de données. Veuillez réessayer.';
            }
        }
    }
}

// Paginate
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$threads = [];
$total   = 0;

try {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM forum_thread")->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT ft.*,
               u.name AS author_name,
               COUNT(fr.reply_id) AS reply_count,
               MAX(fr.created_at) AS last_reply_at
        FROM forum_thread ft
        JOIN `user` u ON ft.user_id = u.user_id
        LEFT JOIN forum_reply fr ON ft.thread_id = fr.thread_id
        GROUP BY ft.thread_id, u.name
        ORDER BY ft.is_pinned DESC, COALESCE(MAX(fr.created_at), ft.created_at) DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $lang === 'en' ? 'Forum unavailable. Run SQL migrations.' : 'Forum indisponible. Exécutez les migrations SQL.';
}

$totalPages = (int)ceil($total / $perPage);

$t = [
    'fr' => [
        'title'        => 'Forum',
        'subtitle'     => 'Échangez avec d\'autres voyageurs',
        'new_thread'   => 'Nouveau sujet',
        'login_to_post'=> 'Connectez-vous pour créer un sujet.',
        'no_threads'   => 'Aucun sujet pour le moment. Soyez le premier à lancer une discussion !',
        'replies'      => 'réponses',
        'pinned'       => 'Épinglé',
        'closed'       => 'Fermé',
        'by'           => 'par',
        'thread_title_label' => 'Titre du sujet',
        'thread_content_label' => 'Contenu',
        'submit'       => 'Publier',
        'cancel'       => 'Annuler',
    ],
    'en' => [
        'title'        => 'Forum',
        'subtitle'     => 'Connect with other travelers',
        'new_thread'   => 'New topic',
        'login_to_post'=> 'Log in to create a topic.',
        'no_threads'   => 'No topics yet. Be the first to start a discussion!',
        'replies'      => 'replies',
        'pinned'       => 'Pinned',
        'closed'       => 'Closed',
        'by'           => 'by',
        'thread_title_label' => 'Topic title',
        'thread_content_label' => 'Content',
        'submit'       => 'Post',
        'cancel'       => 'Cancel',
    ],
][$lang] ?? [];

$show_form = isset($_GET['new']);
$page_title = ($t['title'] ?? 'Forum') . ' — Momo Travel';
include 'header.php';
?>

<main id="main-content">
    <section class="hero" style="padding: 4rem 2%;">
        <h1><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="hero-subtitle"><?= htmlspecialchars($t['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
    </section>
    <div class="forum-container">
        <div class="forum-page-header">
            <?php if ($user_id): ?>
                <a href="forum?new=1" class="btn-dashboard" style="width:auto;padding:0.8rem 1.8rem;"
                aria-label="<?= htmlspecialchars($t['new_thread'], ENT_QUOTES, 'UTF-8') ?>">
                    + <?= htmlspecialchars($t['new_thread'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php else: ?>
                <a href="auth" class="btn-dashboard" style="width:auto;padding:0.8rem 1.8rem;background:#666;">
                    <?= htmlspecialchars($t['login_to_post'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($error):   ?><div class="forum-alert forum-alert-error"   role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <?php if ($show_form && $user_id): ?>
        <section class="forum-compose-panel" aria-labelledby="new-thread-title">
            <h2 id="new-thread-title"><?= htmlspecialchars($t['new_thread'], ENT_QUOTES, 'UTF-8') ?></h2>
            <form method="POST" action="forum">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="create_thread">

                <div class="forum-form-group">
                    <label for="thread-title"><?= htmlspecialchars($t['thread_title_label'], ENT_QUOTES, 'UTF-8') ?> *</label>
                    <input type="text" id="thread-title" name="title"
                        minlength="3" maxlength="200" required
                        aria-required="true"
                        value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="<?= $lang === 'en' ? 'Enter a clear, descriptive title…' : 'Saisissez un titre clair et descriptif…' ?>">
                </div>

                <div class="forum-form-group">
                    <label for="thread-content"><?= htmlspecialchars($t['thread_content_label'], ENT_QUOTES, 'UTF-8') ?> *</label>
                    <textarea id="thread-content" name="content"
                            rows="8" required minlength="10"
                            aria-required="true"
                            placeholder="<?= $lang === 'en' ? 'Share your question, experience or advice…' : 'Partagez votre question, expérience ou conseil…' ?>"><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div style="display:flex;gap:1rem;">
                    <button type="submit" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;">
                        <?= htmlspecialchars($t['submit'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <a href="forum" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;background:#666;">
                        <?= htmlspecialchars($t['cancel'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <section aria-labelledby="thread-list-heading">
            <h2 id="thread-list-heading" class="sr-only"><?= $lang === 'en' ? 'Topics' : 'Sujets' ?></h2>

            <?php if (empty($threads)): ?>
                <p class="forum-empty"><?= htmlspecialchars($t['no_threads'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <div class="thread-list" role="list">
                <?php foreach ($threads as $thread): ?>
                    <article class="thread-item <?= $thread['is_pinned'] ? 'thread-pinned' : '' ?> <?= $thread['is_closed'] ? 'thread-closed' : '' ?>"
                            role="listitem">
                        <div class="thread-badges">
                            <?php if ($thread['is_pinned']): ?>
                                <span class="badge badge-pinned" aria-label="<?= $t['pinned'] ?>">📌 <?= htmlspecialchars($t['pinned'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($thread['is_closed']): ?>
                                <span class="badge badge-inactive" aria-label="<?= $t['closed'] ?>">🔒 <?= htmlspecialchars($t['closed'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="thread-title">
                            <a href="forum_thread?id=<?= (int)$thread['thread_id'] ?>">
                                <?= htmlspecialchars($thread['title'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </h3>
                        <div class="thread-meta">
                            <span><?= htmlspecialchars($t['by'], ENT_QUOTES, 'UTF-8') ?> <strong><?= htmlspecialchars($thread['author_name'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                            <span>·</span>
                            <time datetime="<?= htmlspecialchars($thread['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(date($lang === 'en' ? 'M j, Y' : 'd/m/Y', strtotime($thread['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                            <span>·</span>
                            <span><?= (int)$thread['reply_count'] ?> <?= htmlspecialchars($t['replies'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="thread-excerpt">
                            <?= htmlspecialchars(mb_strimwidth($thread['content'], 0, 160, '…'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </article>
                <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="forum-pagination" aria-label="Pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?page=<?= $p ?>"
                        class="<?= $p === $page ? 'active' : '' ?>"
                        <?= $p === $page ? 'aria-current="page"' : '' ?>>
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'footer.php'; ?>
