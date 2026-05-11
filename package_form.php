<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/db_connect.php';

$role = $_SESSION['user_role'] ?? 'user';
if (!isset($_SESSION['user_id']) || !in_array($role, ['agency', 'admin'])) {
    header('Location: auth');
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$lang      = $_SESSION['language'] ?? 'fr';
$package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$success   = '';
$error     = '';

// Fetch agency for this user
$agencyStmt = $pdo->prepare("SELECT agency_id FROM agency WHERE user_id = ?");
$agencyStmt->execute([$user_id]);
$agencyRow = $agencyStmt->fetch(PDO::FETCH_ASSOC);
$agency_id = $agencyRow ? (int)$agencyRow['agency_id'] : null;

if (!$agency_id) {
    header('Location: agency');
    exit();
}

// If editing, verify ownership
$package = null;
if ($package_id) {
    $pkgStmt = $pdo->prepare("SELECT * FROM trip_package WHERE package_id = ? AND agency_id = ?");
    $pkgStmt->execute([$package_id, $agency_id]);
    $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);
    if (!$package) {
        header('Location: agency');
        exit();
    }
}

// Fetch all destinations for the dropdown
$destStmt = $pdo->prepare("SELECT d.destination_id, dt.name FROM destination d JOIN destination_translation dt ON d.destination_id = dt.destination_id AND dt.language_code = 'fr' ORDER BY dt.name");
$destStmt->execute();
$destinations = $destStmt->fetchAll(PDO::FETCH_ASSOC);

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF error.');
    }

    $action = $_POST['_action'] ?? 'save_package';

    // =============================================
    // ACTION: Save main package info + translations
    // =============================================
    if ($action === 'save_package') {
        $destination_id = filter_var($_POST['destination_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $image_url      = trim($_POST['image_url'] ?? '');
        $base_price     = filter_var($_POST['base_price'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.0;
        $status         = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived']) ? $_POST['status'] : 'draft';
        $name_fr        = trim($_POST['name_fr'] ?? '');
        $name_en        = trim($_POST['name_en'] ?? '');
        $desc_fr        = trim($_POST['desc_fr'] ?? '');
        $desc_en        = trim($_POST['desc_en'] ?? '');
        $high_fr        = trim($_POST['high_fr'] ?? '');
        $high_en        = trim($_POST['high_en'] ?? '');

        if (!$name_fr) {
            $error = 'Le nom en français est obligatoire.';
        } else {
            try {
                if (!$package_id) {
                    // Create
                    $pdo->prepare("INSERT INTO trip_package (agency_id, destination_id, image_url, base_price, status) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$agency_id, $destination_id, $image_url, $base_price, $status]);
                    $package_id = (int)$pdo->lastInsertId();
                    header('Location: package_form?id=' . $package_id . '&saved=1');
                    exit();
                } else {
                    // Update
                    $pdo->prepare("UPDATE trip_package SET destination_id=?, image_url=?, base_price=?, status=? WHERE package_id=? AND agency_id=?")
                        ->execute([$destination_id, $image_url, $base_price, $status, $package_id, $agency_id]);
                }

                // Upsert translations
                foreach ([['fr', $name_fr, $desc_fr, $high_fr], ['en', $name_en, $desc_en, $high_en]] as [$lc, $nm, $de, $hi]) {
                    $pdo->prepare("INSERT INTO trip_package_translation (package_id, language_code, name, description, highlights) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), highlights=VALUES(highlights)")
                        ->execute([$package_id, $lc, $nm, $de, $hi]);
                }

                $success = 'Package saved.';
                // Reload package
                $pkgStmt = $pdo->prepare("SELECT * FROM trip_package WHERE package_id = ?");
                $pkgStmt->execute([$package_id]);
                $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }

    // =============================================
    // ACTION: Add a departure date
    // =============================================
    elseif ($action === 'add_date' && $package_id) {
        $dep     = $_POST['dep_date'] ?? '';
        $ret     = $_POST['ret_date'] ?? '';
        $nights  = filter_var($_POST['nights'] ?? 0, FILTER_VALIDATE_INT);
        $price   = filter_var($_POST['date_price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $spots   = filter_var($_POST['max_spots'] ?? 20, FILTER_VALIDATE_INT);

        if ($dep && $ret && $dep < $ret && $nights > 0 && $price > 0 && $spots > 0) {
            $pdo->prepare("INSERT INTO trip_date (package_id, departure_date, return_date, duration_nights, price_per_person, max_spots) VALUES (?,?,?,?,?,?)")
                ->execute([$package_id, $dep, $ret, $nights, $price, $spots]);
            $success = 'Date added.';
        } else {
            $error = 'Invalid date fields. Return date must be after departure date.';
        }
    }

    // =============================================
    // ACTION: Remove a date
    // =============================================
    elseif ($action === 'remove_date' && $package_id) {
        $date_id = filter_var($_POST['date_id'] ?? 0, FILTER_VALIDATE_INT);
        if ($date_id) {
            // Only remove if no bookings
            $check = $pdo->prepare("SELECT COUNT(*) FROM trip_booking tb JOIN trip_date td ON tb.date_id = td.date_id WHERE td.date_id = ? AND td.package_id = ?");
            $check->execute([$date_id, $package_id]);
            if ($check->fetchColumn() == 0) {
                $pdo->prepare("DELETE FROM trip_date WHERE date_id = ? AND package_id = ?")->execute([$date_id, $package_id]);
                $success = 'Date removed.';
            } else {
                $error = 'Cannot remove a date that has bookings.';
            }
        }
    }

    // =============================================
    // ACTION: Add an inclusion
    // =============================================
    elseif ($action === 'add_inclusion' && $package_id) {
        $type  = in_array($_POST['inc_type'] ?? '', ['hotel', 'transport', 'activity']) ? $_POST['inc_type'] : 'activity';
        $name  = trim($_POST['inc_name'] ?? '');
        $desc  = trim($_POST['inc_desc'] ?? '');
        $maxp  = filter_var($_POST['inc_max'] ?? null, FILTER_VALIDATE_INT);
        $order = filter_var($_POST['inc_order'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

        if ($name) {
            $pdo->prepare("INSERT INTO trip_inclusion (package_id, type, name, description, max_participants, sort_order) VALUES (?,?,?,?,?,?)")
                ->execute([$package_id, $type, $name, $desc ?: null, $maxp ?: null, $order]);
            $success = 'Inclusion added.';
        } else {
            $error = 'Name is required.';
        }
    }

    // =============================================
    // ACTION: Remove an inclusion
    // =============================================
    elseif ($action === 'remove_inclusion' && $package_id) {
        $inc_id = filter_var($_POST['inclusion_id'] ?? 0, FILTER_VALIDATE_INT);
        if ($inc_id) {
            $pdo->prepare("DELETE FROM trip_inclusion WHERE inclusion_id = ? AND package_id = ?")->execute([$inc_id, $package_id]);
            $success = 'Inclusion removed.';
        }
    }

    // Redirect to avoid POST re-submit on refresh
    if (!$error && $package_id) {
        header('Location: package_form?id=' . $package_id . ($success ? '&saved=1' : ''));
        exit();
    }
}

// Handle ?saved=1 flash message
if (isset($_GET['saved'])) $success = 'Package saved successfully.';

// Load current translations
$trans = ['fr' => ['name' => '', 'description' => '', 'highlights' => ''],
          'en' => ['name' => '', 'description' => '', 'highlights' => '']];
if ($package_id) {
    $tStmt = $pdo->prepare("SELECT * FROM trip_package_translation WHERE package_id = ?");
    $tStmt->execute([$package_id]);
    foreach ($tStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $lc = $row['language_code'];
        if (isset($trans[$lc])) $trans[$lc] = $row;
    }
}

// Load dates and inclusions
$dates = $inclusions = [];
if ($package_id) {
    $dStmt = $pdo->prepare("SELECT * FROM trip_date WHERE package_id = ? ORDER BY departure_date ASC");
    $dStmt->execute([$package_id]);
    $dates = $dStmt->fetchAll(PDO::FETCH_ASSOC);

    $iStmt = $pdo->prepare("SELECT * FROM trip_inclusion WHERE package_id = ? ORDER BY sort_order ASC, inclusion_id ASC");
    $iStmt->execute([$package_id]);
    $inclusions = $iStmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Momo - ' . ($package_id ? 'Edit Package' : 'New Package');
include 'header.php';

// Helper
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<style>
    .pf-container { max-width: 950px; margin: 3rem auto; padding: 0 2%; flex-grow: 1; }
    .pf-card { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .pf-card h3 { font-family: 'Playfair Display', serif; color: #c1272d; margin-bottom: 1.5rem; font-size: 1.3rem; border-bottom: 1px solid #f0f0f0; padding-bottom: 0.8rem; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; font-size: 0.9rem; font-weight: bold; color: #555; margin-bottom: 0.3rem; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; background: #fafafa; box-sizing: border-box; font-family: inherit; }
    .form-group textarea { min-height: 90px; resize: vertical; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #c1272d; background: #fff; }
    .btn-save { background: #c1272d; color: #fff; border: none; padding: 0.9rem 2rem; font-size: 1rem; font-weight: bold; border-radius: 4px; cursor: pointer; }
    .btn-save:hover { background: #a01f24; }
    .btn-danger-sm { background: transparent; color: #c1272d; border: 1px solid #c1272d; padding: 0.3rem 0.7rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
    .btn-danger-sm:hover { background: #fee; }
    .btn-add { background: #4a1c35; color: #fff; border: none; padding: 0.7rem 1.2rem; font-size: 0.9rem; font-weight: bold; border-radius: 4px; cursor: pointer; }
    .btn-add:hover { background: #6b2950; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .data-table th { text-align: left; padding: 0.6rem; color: #555; background: #f9f9f9; border-bottom: 2px solid #eee; }
    .data-table td { padding: 0.6rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
    .tag { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 10px; font-size: 0.78rem; font-weight: bold; }
    .tag-hotel { background: #e8f4f8; color: #0369a1; }
    .tag-transport { background: #f0fdf4; color: #15803d; }
    .tag-activity { background: #fdf4ff; color: #7e22ce; }
    @media(max-width:600px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<section class="hero" style="padding: 3rem 2%;">
    <h1><?= $package_id ? 'Edit Package' : 'New Trip Package' ?></h1>
</section>

<div class="pf-container">

    <div style="margin-bottom:1.5rem;">
        <a href="agency" style="color:#c1272d; text-decoration:none; font-size:0.95rem;">← Back to dashboard</a>
    </div>

    <?php if ($success): ?>
        <div style="background:#e6f4ea; color:#1e8e3e; padding:1rem; border-radius:4px; margin-bottom:1.5rem; font-weight:600;">✓ <?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#fee; color:#c1272d; padding:1rem; border-radius:4px; margin-bottom:1.5rem; font-weight:600;">⚠️ <?= h($error) ?></div>
    <?php endif; ?>

    <!-- Main package info -->
    <div class="pf-card">
        <h3>Package Information</h3>
        <form method="POST" action="package_form<?= $package_id ? '?id=' . $package_id : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="_action" value="save_package">

            <div class="form-grid">
                <div class="form-group">
                    <label>Destination</label>
                    <select name="destination_id">
                        <option value="">— none —</option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= (int)$d['destination_id'] ?>"
                                <?= ($package['destination_id'] ?? null) == $d['destination_id'] ? 'selected' : '' ?>>
                                <?= h($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (['published', 'draft', 'archived'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($package['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Image URL (path from website root)</label>
                    <input type="text" name="image_url" value="<?= h($package['image_url'] ?? '') ?>" placeholder="images/destinations/Paris/Paris.webp">
                </div>
                <div class="form-group">
                    <label>Base price (€ / person)</label>
                    <input type="number" name="base_price" step="0.01" min="0" value="<?= h($package['base_price'] ?? '0') ?>">
                </div>
            </div>

            <hr style="border:none; border-top:1px solid #f0f0f0; margin: 1.5rem 0;">
            <h4 style="color:#4a1c35; margin-bottom:1rem;">Translations</h4>

            <div class="form-grid">
                <div>
                    <p style="font-weight:bold; color:#888; margin-bottom:1rem;">🇫🇷 Français</p>
                    <div class="form-group"><label>Name (FR)</label><input type="text" name="name_fr" value="<?= h($trans['fr']['name']) ?>" required></div>
                    <div class="form-group"><label>Description (FR)</label><textarea name="desc_fr"><?= h($trans['fr']['description']) ?></textarea></div>
                    <div class="form-group"><label>Highlights (FR) — one line</label><input type="text" name="high_fr" value="<?= h($trans['fr']['highlights']) ?>"></div>
                </div>
                <div>
                    <p style="font-weight:bold; color:#888; margin-bottom:1rem;">🇬🇧 English</p>
                    <div class="form-group"><label>Name (EN)</label><input type="text" name="name_en" value="<?= h($trans['en']['name']) ?>"></div>
                    <div class="form-group"><label>Description (EN)</label><textarea name="desc_en"><?= h($trans['en']['description']) ?></textarea></div>
                    <div class="form-group"><label>Highlights (EN) — one line</label><input type="text" name="high_en" value="<?= h($trans['en']['highlights']) ?>"></div>
                </div>
            </div>

            <button type="submit" class="btn-save">Save package</button>
        </form>
    </div>

    <?php if ($package_id): ?>

    <!-- Departure dates -->
    <div class="pf-card">
        <h3>Departure Dates</h3>

        <?php if ($dates): ?>
        <div style="overflow-x:auto; margin-bottom:1.5rem;">
            <table class="data-table">
                <thead><tr><th>Departure</th><th>Return</th><th>Nights</th><th>€/person</th><th>Spots</th><th>Booked</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dates as $d): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($d['departure_date'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($d['return_date'])) ?></td>
                        <td><?= (int)$d['duration_nights'] ?></td>
                        <td>€<?= number_format((float)$d['price_per_person'], 2) ?></td>
                        <td><?= (int)$d['max_spots'] ?></td>
                        <td><?= (int)$d['booked_spots'] ?></td>
                        <td>
                            <form method="POST" action="package_form?id=<?= $package_id ?>" onsubmit="return confirm('Remove this date?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="_action" value="remove_date">
                                <input type="hidden" name="date_id" value="<?= (int)$d['date_id'] ?>">
                                <button type="submit" class="btn-danger-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:#888; margin-bottom:1.5rem;">No dates yet.</p>
        <?php endif; ?>

        <form method="POST" action="package_form?id=<?= $package_id ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="_action" value="add_date">
            <div class="form-grid">
                <div class="form-group"><label>Departure date</label><input type="date" name="dep_date" required min="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label>Return date</label><input type="date" name="ret_date" required></div>
                <div class="form-group"><label>Duration (nights)</label><input type="number" name="nights" min="1" required></div>
                <div class="form-group"><label>Price per person (€)</label><input type="number" name="date_price" step="0.01" min="0" required></div>
                <div class="form-group"><label>Max spots</label><input type="number" name="max_spots" min="1" value="20" required></div>
            </div>
            <button type="submit" class="btn-add">+ Add date</button>
        </form>
    </div>

    <!-- Inclusions -->
    <div class="pf-card">
        <h3>What's Included</h3>

        <?php if ($inclusions): ?>
        <div style="overflow-x:auto; margin-bottom:1.5rem;">
            <table class="data-table">
                <thead><tr><th>Type</th><th>Name</th><th>Description</th><th>Max participants</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($inclusions as $inc): ?>
                    <tr>
                        <td><span class="tag tag-<?= h($inc['type']) ?>"><?= h($inc['type']) ?></span></td>
                        <td><?= h($inc['name']) ?></td>
                        <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= h($inc['description'] ?? '') ?>"><?= h($inc['description'] ?? '—') ?></td>
                        <td><?= $inc['max_participants'] !== null ? (int)$inc['max_participants'] : '∞' ?></td>
                        <td>
                            <form method="POST" action="package_form?id=<?= $package_id ?>" onsubmit="return confirm('Remove?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="_action" value="remove_inclusion">
                                <input type="hidden" name="inclusion_id" value="<?= (int)$inc['inclusion_id'] ?>">
                                <button type="submit" class="btn-danger-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:#888; margin-bottom:1.5rem;">No inclusions yet.</p>
        <?php endif; ?>

        <form method="POST" action="package_form?id=<?= $package_id ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="_action" value="add_inclusion">
            <div class="form-grid">
                <div class="form-group">
                    <label>Type</label>
                    <select name="inc_type">
                        <option value="hotel">🏨 Hotel</option>
                        <option value="transport">✈️ Transport</option>
                        <option value="activity">🎯 Activity</option>
                    </select>
                </div>
                <div class="form-group"><label>Name</label><input type="text" name="inc_name" required placeholder="e.g. The Savoy 4★"></div>
                <div class="form-group"><label>Description</label><input type="text" name="inc_desc" placeholder="Short description (optional)"></div>
                <div class="form-group"><label>Max participants (leave blank for hotel/transport)</label><input type="number" name="inc_max" min="1" placeholder="e.g. 15 for activity"></div>
                <div class="form-group"><label>Sort order</label><input type="number" name="inc_order" value="<?= count($inclusions) + 1 ?>" min="0"></div>
            </div>
            <button type="submit" class="btn-add">+ Add inclusion</button>
        </form>
    </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
