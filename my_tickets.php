<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$lang    = $_SESSION['language'] ?? 'fr';
$success = '';
$error   = '';

$t = [
    'fr' => [
        'title'     => 'Mes tickets support',
        'new'       => 'Nouveau ticket',
        'subject'   => 'Sujet',
        'message'   => 'Message',
        'send'      => 'Envoyer',
        'open'      => 'Ouvert',
        'closed'    => 'Fermé',
        'replied'   => 'Répondu',
        'no_tkt'    => 'Vous n\'avez pas encore de tickets.',
        'reply'     => 'Réponse :',
        'back'      => '← Mon espace',
        'sent'      => 'Ticket envoyé avec succès.',
        'err_empty' => 'Veuillez remplir tous les champs.',
    ],
    'en' => [
        'title'     => 'My support tickets',
        'new'       => 'New ticket',
        'subject'   => 'Subject',
        'message'   => 'Message',
        'send'      => 'Send',
        'open'      => 'Open',
        'closed'    => 'Closed',
        'replied'   => 'Replied',
        'no_tkt'    => 'You have no support tickets yet.',
        'reply'     => 'Reply:',
        'back'      => '← My dashboard',
        'sent'      => 'Ticket sent successfully.',
        'err_empty' => 'Please fill in all fields.',
    ],
][$lang];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF error.');
    }

    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$subject || !$message) {
        $error = $t['err_empty'];
    } else {
        $subject = mb_substr($subject, 0, 255);
        $pdo->prepare("INSERT INTO ticket (user_id, subject, message) VALUES (?, ?, ?)")
            ->execute([$user_id, $subject, $message]);
        $success = $t['sent'];
    }
}

// Fetch user's tickets
$stmt = $pdo->prepare("SELECT * FROM ticket WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Momo - ' . $t['title'];
include 'header.php';
?>

<style>
    .ticket-container { max-width: 800px; margin: 3rem auto; padding: 0 2%; flex-grow: 1; }
    .ticket-form-card, .ticket-list-card { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 0.4rem; color: #555; font-size: 0.9rem; }
    .form-group input, .form-group textarea { width: 100%; padding: 0.9rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; background: #fafafa; box-sizing: border-box; font-family: inherit; }
    .form-group textarea { min-height: 100px; resize: vertical; }
    .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #c1272d; background: #fff; }
    .btn-send { background: #c1272d; color: #fff; border: none; padding: 0.9rem 2rem; font-size: 1rem; font-weight: bold; border-radius: 4px; cursor: pointer; }
    .btn-send:hover { background: #a01f24; }
    .ticket-item { border: 1px solid #eee; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
    .ticket-item .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.8rem; }
    .ticket-item h4 { margin: 0; font-size: 1rem; color: #333; }
    .ticket-item .ticket-date { font-size: 0.8rem; color: #aaa; }
    .ticket-item .ticket-body { color: #555; font-size: 0.95rem; line-height: 1.6; }
    .ticket-item .ticket-reply { background: #f0f9ff; border-left: 4px solid #0369a1; padding: 0.8rem 1rem; border-radius: 0 6px 6px 0; margin-top: 1rem; }
    .ticket-item .ticket-reply strong { color: #0369a1; font-size: 0.85rem; }
    .status-pill { display: inline-block; padding: 0.2rem 0.7rem; border-radius: 12px; font-size: 0.78rem; font-weight: bold; }
    .status-open { background: #fff3cd; color: #856404; }
    .status-closed { background: #f0f0f0; color: #888; }
    .status-replied { background: #e0f2fe; color: #0369a1; }
</style>

<section class="hero" style="padding: 3rem 2%;">
    <h1><?= $t['title'] ?></h1>
</section>

<div class="ticket-container">
    <a href="user_page" style="color:#c1272d; text-decoration:none; font-size:0.95rem; display:inline-block; margin-bottom:1.5rem;"><?= $t['back'] ?></a>

    <?php if ($success): ?>
        <div style="background:#e6f4ea; color:#1e8e3e; padding:1rem; border-radius:4px; margin-bottom:1.5rem; font-weight:600;">✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#fee; color:#c1272d; padding:1rem; border-radius:4px; margin-bottom:1.5rem; font-weight:600;">⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- New ticket form -->
    <div class="ticket-form-card">
        <h3 style="font-family:'Playfair Display',serif; color:#c1272d; margin-bottom:1.5rem;"><?= $t['new'] ?></h3>
        <form method="POST" action="my_tickets">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-group">
                <label><?= $t['subject'] ?></label>
                <input type="text" name="subject" maxlength="255" required placeholder="<?= $lang === 'fr' ? 'Ex : Problème avec ma réservation' : 'E.g. Issue with my booking' ?>">
            </div>
            <div class="form-group">
                <label><?= $t['message'] ?></label>
                <textarea name="message" required placeholder="<?= $lang === 'fr' ? 'Décrivez votre problème ou question...' : 'Describe your issue or question...' ?>"></textarea>
            </div>
            <button type="submit" class="btn-send"><?= $t['send'] ?></button>
        </form>
    </div>

    <!-- Ticket list -->
    <div class="ticket-list-card">
        <h3 style="font-family:'Playfair Display',serif; color:#4a1c35; margin-bottom:1.5rem;">
            <?= $lang === 'fr' ? 'Mes tickets' : 'My tickets' ?>
            <span style="font-size:0.9rem; color:#888; font-weight:normal; margin-left:0.5rem;">(<?= count($tickets) ?>)</span>
        </h3>

        <?php if (empty($tickets)): ?>
            <p style="color:#888;"><?= $t['no_tkt'] ?></p>
        <?php else: ?>
            <?php foreach ($tickets as $tk): ?>
                <?php
                $status = $tk['status'];
                if ($tk['admin_reply']) $status = 'replied';
                ?>
                <div class="ticket-item">
                    <div class="ticket-header">
                        <h4><?= htmlspecialchars($tk['subject'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <span class="status-pill status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?= $t[$status === 'replied' ? 'replied' : ($status === 'closed' ? 'closed' : 'open')] ?>
                            </span>
                            <span class="ticket-date"><?= date('d/m/Y H:i', strtotime($tk['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="ticket-body"><?= nl2br(htmlspecialchars($tk['message'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php if ($tk['admin_reply']): ?>
                        <div class="ticket-reply">
                            <strong><?= $lang === 'fr' ? 'Réponse de Momo Travel :' : 'Momo Travel reply:' ?></strong>
                            <br>
                            <?= nl2br(htmlspecialchars($tk['admin_reply'], ENT_QUOTES, 'UTF-8')) ?>
                            <br>
                            <small style="color:#aaa;"><?= $tk['replied_at'] ? date('d/m/Y H:i', strtotime($tk['replied_at'])) : '' ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
