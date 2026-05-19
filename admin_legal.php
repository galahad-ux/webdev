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
$active_slug = $_GET['page'] ?? 'cgu';
if (!in_array($active_slug, ['cgu', 'mentions'], true)) {
    $active_slug = 'cgu';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF invalide.';
    } else {
        $slug       = $_POST['slug'] ?? '';
        $content_fr = $_POST['content_fr'] ?? '';
        $content_en = $_POST['content_en'] ?? '';

        if (!in_array($slug, ['cgu', 'mentions'], true)) {
            $error = 'Page invalide.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO legal_page (slug, content_fr, content_en)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE content_fr = VALUES(content_fr), content_en = VALUES(content_en), updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$slug, $content_fr, $content_en]);
                $success = 'Page « ' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . ' » sauvegardée.';
                $active_slug = $slug;
            } catch (PDOException $e) {
                $error = 'Erreur base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }
    }
}

// Load current content for both pages
$pages_data = [];
try {
    $rows = $pdo->query("SELECT slug, content_fr, content_en FROM legal_page WHERE slug IN ('cgu','mentions')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $pages_data[$row['slug']] = $row;
    }
} catch (PDOException $e) {
    $error = 'La table legal_page n\'existe pas encore. Exécutez les migrations SQL.';
}

$default_fr = ['cgu' => '', 'mentions' => ''];
$default_en = ['cgu' => '', 'mentions' => ''];

$page_title = 'Pages légales — Admin Momo';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">CGU &amp; Mentions légales</h1>

        <?php if ($success): ?><div class="admin-alert admin-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="admin-alert admin-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <nav class="admin-tabs" role="tablist" aria-label="Pages légales">
            <a href="admin_legal?page=cgu"
               class="admin-tab <?= $active_slug === 'cgu' ? 'active' : '' ?>"
               role="tab"
               <?= $active_slug === 'cgu' ? 'aria-selected="true"' : 'aria-selected="false"' ?>>
                CGU
            </a>
            <a href="admin_legal?page=mentions"
               class="admin-tab <?= $active_slug === 'mentions' ? 'active' : '' ?>"
               role="tab"
               <?= $active_slug === 'mentions' ? 'aria-selected="true"' : 'aria-selected="false"' ?>>
                Mentions légales
            </a>
        </nav>

        <section class="admin-form-section" aria-labelledby="legal-form-title">
            <?php
                $label = $active_slug === 'cgu' ? 'Conditions Générales d\'Utilisation' : 'Mentions Légales';
                $current = $pages_data[$active_slug] ?? [];
            ?>
            <h2 id="legal-form-title">Éditer : <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="admin-hint">Le contenu accepte le HTML basique (&lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;, &lt;ul&gt;, &lt;li&gt;).</p>

            <form method="POST" action="admin_legal">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($active_slug, ENT_QUOTES, 'UTF-8') ?>">

                <div class="admin-form-group">
                    <label for="content_fr">Contenu en français</label>
                    <textarea id="content_fr" name="content_fr" rows="20" class="legal-textarea"><?= htmlspecialchars($current['content_fr'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="admin-form-group">
                    <label for="content_en">Contenu en anglais</label>
                    <textarea id="content_en" name="content_en" rows="20" class="legal-textarea"><?= htmlspecialchars($current['content_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div style="display:flex;gap:1rem;margin-top:1rem;">
                    <button type="submit" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;">Enregistrer</button>
                    <a href="<?= htmlspecialchars($active_slug, ENT_QUOTES, 'UTF-8') ?>" target="_blank"
                       class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;background:#666;">
                       Prévisualiser ↗
                    </a>
                </div>
            </form>
        </section>
    </div>
</div>
</main>

<?php include 'footer.php'; ?>
