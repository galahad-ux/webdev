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

        if ($action === 'create') {
            $qfr = trim($_POST['question_fr'] ?? '');
            $afr = trim($_POST['answer_fr']   ?? '');
            $qen = trim($_POST['question_en'] ?? '');
            $aen = trim($_POST['answer_en']   ?? '');
            $ord = max(0, (int)($_POST['sort_order'] ?? 0));
            if ($qfr === '' || $afr === '') {
                $error = 'La question et la réponse en français sont obligatoires.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO faq (question_fr, answer_fr, question_en, answer_en, sort_order, is_published) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$qfr, $afr, $qen ?: $qfr, $aen ?: $afr, $ord]);
                $success = 'Question ajoutée.';
            }

        } elseif ($action === 'update') {
            $id  = (int)($_POST['faq_id'] ?? 0);
            $qfr = trim($_POST['question_fr'] ?? '');
            $afr = trim($_POST['answer_fr']   ?? '');
            $qen = trim($_POST['question_en'] ?? '');
            $aen = trim($_POST['answer_en']   ?? '');
            $ord = max(0, (int)($_POST['sort_order'] ?? 0));
            $pub = isset($_POST['is_published']) ? 1 : 0;
            if ($id > 0 && $qfr !== '' && $afr !== '') {
                $stmt = $pdo->prepare("UPDATE faq SET question_fr=?, answer_fr=?, question_en=?, answer_en=?, sort_order=?, is_published=? WHERE faq_id=?");
                $stmt->execute([$qfr, $afr, $qen ?: $qfr, $aen ?: $afr, $ord, $pub, $id]);
                $success = 'Question mise à jour.';
            } else {
                $error = 'Données invalides.';
            }

        } elseif ($action === 'delete') {
            $id = (int)($_POST['faq_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM faq WHERE faq_id = ?")->execute([$id]);
                $success = 'Question supprimée.';
            }

        } elseif ($action === 'toggle_published') {
            $id = (int)($_POST['faq_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("UPDATE faq SET is_published = 1 - is_published WHERE faq_id = ?")->execute([$id]);
                $success = 'Statut modifié.';
            }
        }
    }
}

$edit_faq = null;
$edit_id  = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM faq WHERE faq_id = ?");
        $stmt->execute([$edit_id]);
        $edit_faq = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

try {
    $faqs = $pdo->query("SELECT * FROM faq ORDER BY sort_order ASC, faq_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'La table FAQ n\'existe pas encore. Exécutez les migrations SQL.';
    $faqs  = [];
}

$page_title = 'FAQ — Admin Momo';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">Gestion de la FAQ</h1>

        <?php if ($success): ?><div class="admin-alert admin-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="admin-alert admin-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <!-- Create / Edit form -->
        <section class="admin-form-section" aria-labelledby="faq-form-title">
            <h2 id="faq-form-title"><?= $edit_faq ? 'Modifier la question' : 'Ajouter une question' ?></h2>
            <form method="POST" action="admin_faq">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="<?= $edit_faq ? 'update' : 'create' ?>">
                <?php if ($edit_faq): ?>
                    <input type="hidden" name="faq_id" value="<?= (int)$edit_faq['faq_id'] ?>">
                <?php endif; ?>

                <div class="admin-form-grid">
                    <div class="admin-form-group">
                        <label for="qfr">Question (FR) <span aria-hidden="true">*</span></label>
                        <input type="text" id="qfr" name="question_fr" required maxlength="500"
                               value="<?= htmlspecialchars($edit_faq['question_fr'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               aria-required="true">
                    </div>
                    <div class="admin-form-group">
                        <label for="qen">Question (EN)</label>
                        <input type="text" id="qen" name="question_en" maxlength="500"
                               value="<?= htmlspecialchars($edit_faq['question_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="admin-form-group col-span-2">
                        <label for="afr">Réponse (FR) <span aria-hidden="true">*</span> <small>HTML basique accepté</small></label>
                        <textarea id="afr" name="answer_fr" rows="4" required aria-required="true"><?= htmlspecialchars($edit_faq['answer_fr'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="admin-form-group col-span-2">
                        <label for="aen">Réponse (EN) <small>HTML basique accepté</small></label>
                        <textarea id="aen" name="answer_en" rows="4"><?= htmlspecialchars($edit_faq['answer_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="admin-form-group">
                        <label for="sort_order">Ordre d'affichage</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                               value="<?= (int)($edit_faq['sort_order'] ?? 0) ?>">
                    </div>
                    <?php if ($edit_faq): ?>
                    <div class="admin-form-group" style="display:flex;align-items:center;gap:0.5rem;margin-top:1.5rem;">
                        <input type="checkbox" id="is_published" name="is_published" value="1"
                               <?= ($edit_faq['is_published'] ?? 1) ? 'checked' : '' ?>>
                        <label for="is_published" style="margin:0;">Publié</label>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="display:flex;gap:1rem;margin-top:1rem;">
                    <button type="submit" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;">
                        <?= $edit_faq ? 'Enregistrer les modifications' : 'Ajouter la question' ?>
                    </button>
                    <?php if ($edit_faq): ?>
                        <a href="admin_faq" class="btn-dashboard" style="width:auto;padding:0.8rem 2rem;background:#666;">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- FAQ list -->
        <section class="admin-form-section" aria-labelledby="faq-list-title">
            <h2 id="faq-list-title">Questions existantes (<?= count($faqs) ?>)</h2>
            <?php if (empty($faqs)): ?>
                <p>Aucune question pour le moment.</p>
            <?php else: ?>
            <div class="admin-table-wrap" tabindex="0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Ordre</th>
                        <th scope="col">Question (FR)</th>
                        <th scope="col">Publié</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($faqs as $faq): ?>
                <tr>
                    <td><?= (int)$faq['sort_order'] ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($faq['question_fr'], 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form method="POST" action="admin_faq" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="toggle_published">
                            <input type="hidden" name="faq_id" value="<?= (int)$faq['faq_id'] ?>">
                            <button type="submit" class="badge <?= $faq['is_published'] ? 'badge-active' : 'badge-inactive' ?>"
                                    style="border:none;cursor:pointer;"
                                    title="<?= $faq['is_published'] ? 'Masquer' : 'Publier' ?>">
                                <?= $faq['is_published'] ? 'Oui' : 'Non' ?>
                            </button>
                        </form>
                    </td>
                    <td class="admin-actions-cell">
                        <a href="admin_faq?edit=<?= (int)$faq['faq_id'] ?>" class="btn-small">Modifier</a>
                        <form method="POST" action="admin_faq" style="display:inline;" onsubmit="return confirm('Supprimer cette question ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="faq_id" value="<?= (int)$faq['faq_id'] ?>">
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
