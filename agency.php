<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

require_once __DIR__ . '/../config/db_connect.php';

// Only agency or admin
$role = $_SESSION['user_role'] ?? 'user';
if (!isset($_SESSION['user_id']) || !in_array($role, ['agency', 'admin'])) {
    header('Location: auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$lang    = $_SESSION['language'] ?? 'fr';
$user_id = (int)$_SESSION['user_id'];

$t = [
    'fr' => [
        'dashboard'    => 'Tableau de bord Agence',
        'verified'     => '✓ Vérifié',
        'stat_pkgs'    => 'Packages voyage',
        'stat_bkgs'    => 'Réservations totales',
        'stat_rev'     => 'Chiffre d\'affaires',
        'my_pkgs'      => 'Mes packages voyage',
        'new_pkg'      => '+ Nouveau package',
        'no_pkgs'      => 'Vous n\'avez pas encore de packages voyage.',
        'create_first' => 'Créer votre premier package',
        'col_name'     => 'Nom',
        'col_dest'     => 'Destination',
        'col_status'   => 'Statut',
        'col_bookings' => 'Réservations',
        'col_revenue'  => 'Chiffre d\'affaires',
        'col_actions'  => 'Actions',
        'edit'         => 'Modifier',
        'view'         => 'Voir',
        'untitled'     => 'Sans titre',
    ],
    'en' => [
        'dashboard'    => 'Agency Dashboard',
        'verified'     => '✓ Verified',
        'stat_pkgs'    => 'Trip packages',
        'stat_bkgs'    => 'Total bookings',
        'stat_rev'     => 'Total revenue',
        'my_pkgs'      => 'My Trip Packages',
        'new_pkg'      => '+ New package',
        'no_pkgs'      => 'You have no trip packages yet.',
        'create_first' => 'Create your first package',
        'col_name'     => 'Name',
        'col_dest'     => 'Destination',
        'col_status'   => 'Status',
        'col_bookings' => 'Bookings',
        'col_revenue'  => 'Revenue',
        'col_actions'  => 'Actions',
        'edit'         => 'Edit',
        'view'         => 'View',
        'untitled'     => 'Untitled',
    ],
][$lang];

// Fetch agency record
$agencyStmt = $pdo->prepare("SELECT * FROM agency WHERE user_id = ?");
$agencyStmt->execute([$user_id]);
$agency = $agencyStmt->fetch(PDO::FETCH_ASSOC);

if (!$agency) {
    // Admin without an agency record — still allow viewing
    $agency = ['agency_id' => null, 'company_name' => 'Admin', 'verified' => 1];
}

$agency_id = $agency['agency_id'];

// Stats
$totalPackages = 0;
$totalBookings = 0;
$totalRevenue  = 0;
$packages      = [];

if ($agency_id) {
    $pkgStmt = $pdo->prepare("
        SELECT tp.*,
               COALESCE(tpt.name, tpt_fb.name) AS name,
               dt.name AS city_name,
               COUNT(DISTINCT tb.booking_id) AS booking_count,
               COALESCE(SUM(tb.total_price), 0) AS revenue
        FROM trip_package tp
        LEFT JOIN trip_package_translation tpt ON tp.package_id = tpt.package_id AND tpt.language_code = :lang
        LEFT JOIN trip_package_translation tpt_fb ON tp.package_id = tpt_fb.package_id AND tpt_fb.language_code = 'fr'
        LEFT JOIN destination_translation dt ON tp.destination_id = dt.destination_id AND dt.language_code = :lang2
        LEFT JOIN trip_date td ON tp.package_id = td.package_id
        LEFT JOIN trip_booking tb ON td.date_id = tb.date_id AND tb.status = 'confirmed'
        WHERE tp.agency_id = :agency_id
        GROUP BY tp.package_id, tpt.name, tpt_fb.name, dt.name
        ORDER BY tp.created_at DESC
    ");
    $pkgStmt->execute(['lang' => $lang, 'lang2' => $lang, 'agency_id' => $agency_id]);
    $packages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPackages = count($packages);
    foreach ($packages as $p) {
        $totalBookings += (int)$p['booking_count'];
        $totalRevenue  += (float)$p['revenue'];
    }
}

$page_title = 'Momo - ' . $t['dashboard'];
include 'header.php';
?>

<style>
    .agency-container { max-width: 1100px; margin: 3rem auto; padding: 0 2%; flex-grow: 1; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
    .stat-card { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 1.5rem; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .stat-card .stat-number { font-size: 2.2rem; font-weight: bold; color: #c1272d; }
    .stat-card .stat-label { color: #888; font-size: 0.9rem; margin-top: 0.3rem; }
    .pkg-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .pkg-table th { background: #f9f9f9; padding: 1rem; text-align: left; font-size: 0.9rem; color: #555; border-bottom: 2px solid #eee; }
    .pkg-table td { padding: 1rem; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 0.95rem; }
    .pkg-table tr:last-child td { border-bottom: none; }
    .pkg-table tr:hover td { background: #fafafa; }
    .status-pill { display: inline-block; padding: 0.2rem 0.7rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
    .status-published { background: #e6f4ea; color: #1e8e3e; }
    .status-draft { background: #f0f0f0; color: #888; }
    .status-archived { background: #fee; color: #c1272d; }
    .btn-sm { padding: 0.4rem 0.9rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold; text-decoration: none; display: inline-block; }
    .btn-edit { background: #4a1c35; color: #fff; }
    .btn-edit:hover { background: #6b2950; }
    .btn-new { background: #c1272d; color: #fff; }
    .btn-new:hover { background: #a01f24; }
    .section-title { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: #4a1c35; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
</style>

<section class="hero" style="padding: 3rem 2%;">
    <h1><?= $t['dashboard'] ?></h1>
    <p style="color:#ddd;"><?= htmlspecialchars($agency['company_name'], ENT_QUOTES, 'UTF-8') ?>
        <?php if ($agency['verified']): ?> <?= $t['verified'] ?><?php endif; ?>
    </p>
</section>

<div class="agency-container">

    <!-- Stats -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $totalPackages ?></div>
            <div class="stat-label"><?= $t['stat_pkgs'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalBookings ?></div>
            <div class="stat-label"><?= $t['stat_bkgs'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number">€<?= number_format($totalRevenue, 0, ',', ' ') ?></div>
            <div class="stat-label"><?= $t['stat_rev'] ?></div>
        </div>
    </div>

    <!-- Package list -->
    <div class="section-title">
        <span><?= $t['my_pkgs'] ?></span>
        <?php if ($agency_id): ?>
            <a href="package_form" class="btn-sm btn-new"><?= $t['new_pkg'] ?></a>
        <?php endif; ?>
    </div>

    <?php if (empty($packages)): ?>
        <div style="background:#fff; border:1px solid #eee; border-radius:8px; padding: 3rem; text-align:center; color:#888;">
            <p><?= $t['no_pkgs'] ?></p>
            <?php if ($agency_id): ?>
                <a href="package_form" class="btn-sm btn-new" style="margin-top:1rem; display:inline-block;"><?= $t['create_first'] ?></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="pkg-table">
                <thead>
                    <tr>
                        <th><?= $t['col_name'] ?></th>
                        <th><?= $t['col_dest'] ?></th>
                        <th><?= $t['col_status'] ?></th>
                        <th><?= $t['col_bookings'] ?></th>
                        <th><?= $t['col_revenue'] ?></th>
                        <th><?= $t['col_actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($packages as $pkg): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pkg['name'] ?? $t['untitled'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($pkg['city_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="status-pill status-<?= htmlspecialchars($pkg['status'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($pkg['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= (int)$pkg['booking_count'] ?></td>
                        <td>€<?= number_format((float)$pkg['revenue'], 0, ',', ' ') ?></td>
                        <td>
                            <a href="package_form?id=<?= (int)$pkg['package_id'] ?>" class="btn-sm btn-edit"><?= $t['edit'] ?></a>
                            &nbsp;
                            <a href="trip?id=<?= (int)$pkg['package_id'] ?>" class="btn-sm" style="background:#f0f0f0; color:#333;" target="_blank"><?= $t['view'] ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
