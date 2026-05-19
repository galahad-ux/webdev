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

$setting_keys = [
    'site_name'        => ['label' => 'Nom du site', 'type' => 'text', 'default' => 'Momo Travel'],
    'primary_color'    => ['label' => 'Couleur principale', 'type' => 'color', 'default' => '#c1272d'],
    'hero_title_fr'    => ['label' => 'Titre hero (FR)', 'type' => 'text', 'default' => 'Découvrez le monde avec Momo'],
    'hero_title_en'    => ['label' => 'Titre hero (EN)', 'type' => 'text', 'default' => 'Discover the world with Momo'],
    'hero_subtitle_fr' => ['label' => 'Sous-titre hero (FR)', 'type' => 'text', 'default' => 'Des voyages sur mesure pour des moments inoubliables'],
    'hero_subtitle_en' => ['label' => 'Sous-titre hero (EN)', 'type' => 'text', 'default' => 'Tailor-made trips for unforgettable moments'],
    'footer_tagline_fr'=> ['label' => 'Slogan footer (FR)', 'type' => 'text', 'default' => '© 2026 Momo Travel. Tous droits réservés.'],
    'footer_tagline_en'=> ['label' => 'Slogan footer (EN)', 'type' => 'text', 'default' => '© 2026 Momo Travel. All rights reserved.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF invalide.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($setting_keys as $key => $meta) {
                $val = trim($_POST[$key] ?? $meta['default']);
                if ($meta['type'] === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
                    $val = $meta['default'];
                }
                $stmt->execute([$key, $val]);
            }
            $success = 'Paramètres enregistrés. Remarque : certains changements (couleur, textes) nécessitent une mise à jour du code source pour être pleinement appliqués.';
        } catch (PDOException $e) {
            $error = 'La table site_settings n\'existe pas encore. Exécutez les migrations SQL.';
        }
    }
}

$settings = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$page_title = 'Rendu visuel — Admin Momo';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">Rendu visuel &amp; Paramètres</h1>

        <?php if ($success): ?><div class="admin-alert admin-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="admin-alert admin-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <div class="admin-alert" style="background:#fff8e1;border-left:4px solid #f39c12;padding:1rem;margin-bottom:1.5rem;border-radius:4px;" role="note">
            <strong>Note :</strong> Les paramètres de couleur et de texte sont stockés en base de données.
            L'application complète au thème du site nécessite une intervention sur le code CSS/PHP.
        </div>

        <section class="admin-form-section" aria-labelledby="theme-form-title">
            <h2 id="theme-form-title">Paramètres du site</h2>
            <form method="POST" action="admin_theme">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="admin-form-grid">
                <?php foreach ($setting_keys as $key => $meta):
                    $val = $settings[$key] ?? $meta['default'];
                ?>
                    <div class="admin-form-group <?= in_array($key, ['hero_subtitle_fr','hero_subtitle_en','footer_tagline_fr','footer_tagline_en']) ? 'col-span-2' : '' ?>">
                        <label for="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <?php if ($meta['type'] === 'color'): ?>
                            <div style="display:flex;align-items:center;gap:1rem;">
                                <input type="color" id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                       name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                       value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                       style="width:60px;height:40px;cursor:pointer;border:1px solid #ccc;border-radius:4px;">
                                <input type="text" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>_text"
                                       value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                       pattern="^#[0-9a-fA-F]{6}$"
                                       placeholder="#c1272d"
                                       style="width:120px;"
                                       aria-label="Valeur hexadécimale">
                            </div>
                        <?php else: ?>
                            <input type="text" id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="255">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;margin-top:1rem;">
                    Enregistrer les paramètres
                </button>
            </form>
        </section>
    </div>
</div>
</main>

<?php include 'footer.php'; ?>
