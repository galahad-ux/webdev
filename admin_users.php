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
$me      = (int)$_SESSION['user_id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF invalide.';
    } else {
        $action  = $_POST['action'] ?? '';
        $uid     = (int)($_POST['user_id'] ?? 0);

        if ($uid === $me) {
            $error = 'Vous ne pouvez pas modifier votre propre compte depuis ce panneau.';
        } elseif ($action === 'set_role' && $uid > 0) {
            $new_role = $_POST['role'] ?? '';
            if (!in_array($new_role, ['user', 'agency', 'admin'], true)) {
                $error = 'Rôle invalide.';
            } else {
                $stmt = $pdo->prepare("UPDATE `user` SET role = ? WHERE user_id = ?");
                $stmt->execute([$new_role, $uid]);
                $success = 'Rôle mis à jour.';
            }
        } elseif ($action === 'set_status' && $uid > 0) {
            $new_status = $_POST['status'] ?? '';
            if (!in_array($new_status, ['active', 'banned'], true)) {
                $error = 'Statut invalide.';
            } else {
                $stmt = $pdo->prepare("UPDATE `user` SET account_status = ? WHERE user_id = ?");
                $stmt->execute([$new_status, $uid]);
                $success = 'Statut mis à jour.';
            }
        }
    }
}

// Search & paginate
$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
if ($search !== '') {
    $where    = 'WHERE name LIKE :q OR email LIKE :q2';
    $like     = '%' . $search . '%';
    $params   = ['q' => $like, 'q2' => $like];
}

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `user` $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $listStmt = $pdo->prepare("SELECT user_id, name, email, role, account_status, is_verified, created_at FROM `user` $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $listStmt->bindValue(':' . $k, $v);
    }
    $listStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $listStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $listStmt->execute();
    $users = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erreur base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $users = [];
    $total = 0;
}

$totalPages = (int)ceil($total / $perPage);
$page_title = 'Utilisateurs — Admin Momo';
include 'header.php';
?>

<main id="main-content">
<div class="admin-layout">
    <?php include 'admin_nav.php'; ?>

    <div class="admin-main">
        <h1 class="admin-page-title">Gestion des utilisateurs</h1>

        <?php if ($success): ?><div class="admin-alert admin-alert-success" role="alert"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="admin-alert admin-alert-error"   role="alert"><?= htmlspecialchars($error,   ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <form class="admin-search-form" method="GET" action="admin_users" role="search" aria-label="Rechercher un utilisateur">
            <input type="search" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Rechercher par nom ou email…"
                   aria-label="Terme de recherche">
            <button type="submit" class="btn-dashboard" style="width:auto;padding:0.7rem 1.5rem;">Rechercher</button>
            <?php if ($search): ?>
                <a href="admin_users" class="btn-dashboard" style="width:auto;padding:0.7rem 1.5rem;background:#666;">Réinitialiser</a>
            <?php endif; ?>
        </form>

        <p class="admin-count"><?= $total ?> utilisateur<?= $total > 1 ? 's' : '' ?> trouvé<?= $total > 1 ? 's' : '' ?></p>

        <?php if (!empty($users)): ?>
        <div class="admin-table-wrap" role="region" aria-label="Liste des utilisateurs" tabindex="0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nom</th>
                        <th scope="col">Email</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Inscription</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-status-<?= htmlspecialchars($u['account_status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($u['account_status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="admin-actions-cell">
                            <?php if ($u['user_id'] !== $me): ?>
                            <!-- Change role -->
                            <form method="POST" action="admin_users" class="inline-form" aria-label="Changer le rôle de <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="set_role">
                                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                <select name="role" aria-label="Nouveau rôle">
                                    <option value="user"   <?= $u['role'] === 'user'   ? 'selected' : '' ?>>user</option>
                                    <option value="agency" <?= $u['role'] === 'agency' ? 'selected' : '' ?>>agency</option>
                                    <option value="admin"  <?= $u['role'] === 'admin'  ? 'selected' : '' ?>>admin</option>
                                </select>
                                <button type="submit" class="btn-small">Appliquer</button>
                            </form>
                            <!-- Ban / Unban -->
                            <form method="POST" action="admin_users" class="inline-form" aria-label="<?= ($u['account_status'] ?? '') === 'banned' ? 'Réactiver' : 'Bannir' ?> <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                <?php if (($u['account_status'] ?? 'active') === 'banned'): ?>
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="btn-small btn-small-success">Réactiver</button>
                                <?php else: ?>
                                    <input type="hidden" name="status" value="banned">
                                    <button type="submit" class="btn-small btn-small-danger"
                                            onclick="return confirm('Bannir cet utilisateur ?')">Bannir</button>
                                <?php endif; ?>
                            </form>
                            <?php else: ?>
                            <em style="color:#999;">Vous</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="admin-pagination" aria-label="Pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                   class="<?= $p === $page ? 'active' : '' ?>"
                   <?= $p === $page ? 'aria-current="page"' : '' ?>>
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</main>

<?php include 'footer.php'; ?>
