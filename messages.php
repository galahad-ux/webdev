<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['user_id'])) {
    header('Location: auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$lang    = $_SESSION['language'] ?? 'fr';
$me      = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Send a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = $lang === 'en' ? 'Invalid security token.' : 'Token de sécurité invalide.';
    } else {
        $to_id  = (int)($_POST['to_user_id'] ?? 0);
        $body   = trim($_POST['content'] ?? '');

        if ($to_id <= 0 || $to_id === $me) {
            $error = $lang === 'en' ? 'Invalid recipient.' : 'Destinataire invalide.';
        } elseif (mb_strlen($body) < 1 || mb_strlen($body) > 5000) {
            $error = $lang === 'en' ? 'Message must be between 1 and 5000 characters.' : 'Le message doit faire entre 1 et 5000 caractères.';
        } else {
            try {
                // Verify recipient exists
                $check = $pdo->prepare("SELECT user_id FROM `user` WHERE user_id = ? AND account_status != 'banned'");
                $check->execute([$to_id]);
                if (!$check->fetch()) {
                    $error = $lang === 'en' ? 'Recipient not found.' : 'Destinataire introuvable.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO internal_message (from_user_id, to_user_id, content) VALUES (?, ?, ?)");
                    $stmt->execute([$me, $to_id, $body]);
                    header('Location: messages?with=' . $to_id . '#msg-end');
                    exit();
                }
            } catch (PDOException $e) {
                $error = $lang === 'en' ? 'Database error.' : 'Erreur base de données.';
            }
        }
    }
}

// Partner for active conversation
$partner_id   = (int)($_GET['with'] ?? 0);
$partner      = null;
$conversation = [];

// Mark messages as read
if ($partner_id > 0) {
    try {
        $pdo->prepare("UPDATE internal_message SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0")
            ->execute([$me, $partner_id]);

        $pStmt = $pdo->prepare("SELECT user_id, name FROM `user` WHERE user_id = ?");
        $pStmt->execute([$partner_id]);
        $partner = $pStmt->fetch(PDO::FETCH_ASSOC);

        if ($partner) {
            $cStmt = $pdo->prepare("
                SELECT im.*, u.name AS from_name
                FROM internal_message im
                JOIN `user` u ON im.from_user_id = u.user_id
                WHERE (im.from_user_id = :me  AND im.to_user_id = :p)
                   OR (im.from_user_id = :me2 AND im.to_user_id = :p2)
                   OR (im.from_user_id = :p3  AND im.to_user_id = :me3)
                ORDER BY im.created_at ASC
            ");
            $cStmt->execute([':me' => $me, ':p' => $partner_id, ':me2' => $me, ':p2' => $partner_id, ':p3' => $partner_id, ':me3' => $me]);
            $conversation = $cStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = $lang === 'en' ? 'Unable to load conversation.' : 'Impossible de charger la conversation.';
    }
}

// Conversations list
$conversations = [];
try {
    $convStmt = $pdo->prepare("
        SELECT
            u.user_id,
            u.name,
            MAX(im.created_at) AS last_date,
            SUM(CASE WHEN im.to_user_id = :me AND im.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
        FROM internal_message im
        JOIN `user` u ON u.user_id = IF(im.from_user_id = :me2, im.to_user_id, im.from_user_id)
        WHERE im.from_user_id = :me3 OR im.to_user_id = :me4
        GROUP BY u.user_id, u.name
        ORDER BY last_date DESC
    ");
    $convStmt->execute([':me' => $me, ':me2' => $me, ':me3' => $me, ':me4' => $me]);
    $conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table may not exist yet
}

// New conversation: search users
$search_results = [];
$search_q = trim($_GET['search'] ?? '');
if ($search_q !== '') {
    try {
        $sStmt = $pdo->prepare("
            SELECT user_id, name FROM `user`
            WHERE (name LIKE ? OR email LIKE ?)
              AND user_id != ?
              AND account_status = 'active'
            LIMIT 10
        ");
        $like = '%' . $search_q . '%';
        $sStmt->execute([$like, $like, $me]);
        $search_results = $sStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$t = [
    'fr' => [
        'title'          => 'Messagerie',
        'no_conv'        => 'Aucune conversation.',
        'select_conv'    => 'Sélectionnez une conversation ou lancez-en une nouvelle.',
        'new_conv'       => 'Nouvelle conversation',
        'search_ph'      => 'Chercher un utilisateur…',
        'send'           => 'Envoyer',
        'type_msg'       => 'Écrivez un message…',
        'write_first'    => 'Envoyez votre premier message à ',
    ],
    'en' => [
        'title'          => 'Messages',
        'no_conv'        => 'No conversations.',
        'select_conv'    => 'Select a conversation or start a new one.',
        'new_conv'       => 'New conversation',
        'search_ph'      => 'Search for a user…',
        'send'           => 'Send',
        'type_msg'       => 'Write a message…',
        'write_first'    => 'Send your first message to ',
    ],
][$lang] ?? [];

$page_title = ($t['title'] ?? 'Messages') . ' — Momo Travel';
include 'header.php';
?>

<main id="main-content">
<div class="messages-layout">

    <!-- Conversations sidebar -->
    <aside class="conversations-panel" aria-label="<?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="conversations-header">
            <h1><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        </div>

        <!-- Search new user -->
        <form class="conv-search-form" method="GET" action="messages" role="search">
            <?php if ($partner_id): ?>
                <input type="hidden" name="with" value="<?= $partner_id ?>">
            <?php endif; ?>
            <input type="search" name="search"
                   value="<?= htmlspecialchars($search_q, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="<?= htmlspecialchars($t['search_ph'], ENT_QUOTES, 'UTF-8') ?>"
                   aria-label="<?= htmlspecialchars($t['new_conv'], ENT_QUOTES, 'UTF-8') ?>">
        </form>

        <?php if (!empty($search_results)): ?>
        <div class="conv-search-results" role="list" aria-label="Résultats de recherche">
            <?php foreach ($search_results as $u): ?>
                <a href="messages?with=<?= (int)$u['user_id'] ?>"
                   class="conversation-item <?= $partner_id === (int)$u['user_id'] ? 'active' : '' ?>"
                   role="listitem"
                   aria-label="Ouvrir la conversation avec <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="conv-avatar" aria-hidden="true"><?= htmlspecialchars(mb_substr($u['name'], 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                    <span class="conv-name"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Existing conversations -->
        <nav aria-label="Conversations">
            <?php if (empty($conversations) && empty($search_results)): ?>
                <p class="conv-empty"><?= htmlspecialchars($t['no_conv'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php foreach ($conversations as $conv): ?>
                <a href="messages?with=<?= (int)$conv['user_id'] ?>"
                   class="conversation-item <?= $partner_id === (int)$conv['user_id'] ? 'active' : '' ?>"
                   aria-label="Conversation avec <?= htmlspecialchars($conv['name'], ENT_QUOTES, 'UTF-8') ?>"
                   <?= $partner_id === (int)$conv['user_id'] ? 'aria-current="true"' : '' ?>>
                    <div class="conv-avatar" aria-hidden="true"><?= htmlspecialchars(mb_substr($conv['name'], 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="conv-info">
                        <span class="conv-name"><?= htmlspecialchars($conv['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="conv-date"><?= htmlspecialchars(date($lang === 'en' ? 'M j' : 'd/m', strtotime($conv['last_date'])), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if ((int)$conv['unread_count'] > 0): ?>
                        <span class="unread-badge" aria-label="<?= (int)$conv['unread_count'] ?> messages non lus">
                            <?= (int)$conv['unread_count'] ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Message area -->
    <div class="messages-panel">
        <?php if (!$partner): ?>
            <div class="messages-empty" role="status">
                <span aria-hidden="true">✉️</span>
                <p><?= htmlspecialchars($t['select_conv'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php else: ?>
            <div class="messages-panel-header">
                <div class="conv-avatar conv-avatar-lg" aria-hidden="true">
                    <?= htmlspecialchars(mb_substr($partner['name'], 0, 1), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <h2><?= htmlspecialchars($partner['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>

            <?php if ($error): ?>
                <div class="forum-alert forum-alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="messages-feed" id="messages-feed" aria-label="Conversation" aria-live="polite">
                <?php if (empty($conversation)): ?>
                    <p class="messages-empty-conv">
                        <?= htmlspecialchars($t['write_first'], ENT_QUOTES, 'UTF-8') ?>
                        <strong><?= htmlspecialchars($partner['name'], ENT_QUOTES, 'UTF-8') ?></strong> !
                    </p>
                <?php else: ?>
                    <?php foreach ($conversation as $msg): ?>
                        <?php $is_mine = ((int)$msg['from_user_id'] === $me); ?>
                        <div class="message-bubble <?= $is_mine ? 'sent' : 'received' ?>"
                             aria-label="<?= $is_mine ? 'Vous' : htmlspecialchars($msg['from_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <p><?= nl2br(htmlspecialchars($msg['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <time class="msg-time" datetime="<?= htmlspecialchars($msg['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(date($lang === 'en' ? 'M j, g:i a' : 'd/m H:i', strtotime($msg['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div id="msg-end" aria-hidden="true"></div>
            </div>

            <form class="message-compose" method="POST" action="messages?with=<?= (int)$partner['user_id'] ?>"
                  aria-label="Envoyer un message">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="to_user_id" value="<?= (int)$partner['user_id'] ?>">
                <label for="msg-input" class="sr-only"><?= htmlspecialchars($t['type_msg'], ENT_QUOTES, 'UTF-8') ?></label>
                <textarea id="msg-input" name="content"
                          rows="2"
                          placeholder="<?= htmlspecialchars($t['type_msg'], ENT_QUOTES, 'UTF-8') ?>"
                          maxlength="5000"
                          required aria-required="true"></textarea>
                <button type="submit" class="btn-dashboard msg-send-btn" aria-label="<?= htmlspecialchars($t['send'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($t['send'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</main>

<script>
// Auto-scroll to bottom of messages feed on load
(function () {
    var feed = document.getElementById('messages-feed');
    if (feed) feed.scrollTop = feed.scrollHeight;

    // Submit on Ctrl+Enter / Cmd+Enter in textarea
    var ta = document.getElementById('msg-input');
    if (ta) {
        ta.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    }
}());
</script>

<?php include 'footer.php'; ?>
