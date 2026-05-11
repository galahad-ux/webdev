<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/db_connect.php';

$lang = $_SESSION['language'] ?? 'fr';
$package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$package_id) {
    header('Location: book');
    exit();
}

$t = [
    'fr' => [
        'back'         => '← Retour aux voyages',
        'nights'       => 'nuits',
        'per_person'   => '/ pers.',
        'included'     => 'Ce qui est inclus',
        'hotel'        => 'Hébergement',
        'transport'    => 'Transport',
        'activity'     => 'Activités',
        'participants' => 'personnes max',
        'dates'        => 'Dates disponibles',
        'departure'    => 'Départ',
        'return'       => 'Retour',
        'duration'     => 'Durée',
        'price'        => 'Prix / pers.',
        'spots'        => 'Places',
        'book'         => 'Réserver',
        'full'         => 'Complet',
        'no_dates'     => 'Aucune date disponible pour ce voyage pour le moment.',
        'left'         => 'restantes',
    ],
    'en' => [
        'back'         => '← Back to trips',
        'nights'       => 'nights',
        'per_person'   => '/ person',
        'included'     => "What's included",
        'hotel'        => 'Accommodation',
        'transport'    => 'Transport',
        'activity'     => 'Activities',
        'participants' => 'max participants',
        'dates'        => 'Available dates',
        'departure'    => 'Departure',
        'return'       => 'Return',
        'duration'     => 'Duration',
        'price'        => 'Price / person',
        'spots'        => 'Spots',
        'book'         => 'Book',
        'full'         => 'Fully booked',
        'no_dates'     => 'No dates available for this trip at the moment.',
        'left'         => 'left',
    ],
][$lang];

// Fetch package with translation + destination
$stmt = $pdo->prepare("
    SELECT tp.*,
           COALESCE(tpt.name, tpt_fb.name) AS name,
           COALESCE(tpt.description, tpt_fb.description) AS description,
           COALESCE(tpt.highlights, tpt_fb.highlights) AS highlights,
           dt.name AS city_name,
           d.latitude, d.longitude,
           ag.company_name
    FROM trip_package tp
    LEFT JOIN destination d ON tp.destination_id = d.destination_id
    LEFT JOIN trip_package_translation tpt ON tp.package_id = tpt.package_id AND tpt.language_code = :lang
    LEFT JOIN trip_package_translation tpt_fb ON tp.package_id = tpt_fb.package_id AND tpt_fb.language_code = 'fr'
    LEFT JOIN destination_translation dt ON tp.destination_id = dt.destination_id AND dt.language_code = :lang2
    LEFT JOIN agency ag ON tp.agency_id = ag.agency_id
    WHERE tp.package_id = :id AND tp.status = 'published'
");
$stmt->execute(['lang' => $lang, 'lang2' => $lang, 'id' => $package_id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$package) {
    header('Location: book');
    exit();
}

// Fetch inclusions grouped by type
$incStmt = $pdo->prepare("SELECT * FROM trip_inclusion WHERE package_id = ? ORDER BY sort_order ASC");
$incStmt->execute([$package_id]);
$inclusions = $incStmt->fetchAll(PDO::FETCH_ASSOC);

$inclByType = ['hotel' => [], 'transport' => [], 'activity' => []];
foreach ($inclusions as $inc) {
    $type = $inc['type'];
    if (isset($inclByType[$type])) $inclByType[$type][] = $inc;
}

// Fetch available dates (future, not full)
$dateStmt = $pdo->prepare("
    SELECT * FROM trip_date
    WHERE package_id = ?
    AND departure_date >= CURDATE()
    AND status = 'available'
    ORDER BY departure_date ASC
");
$dateStmt->execute([$package_id]);
$dates = $dateStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Momo - ' . htmlspecialchars($package['name'], ENT_QUOTES, 'UTF-8');
include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .trip-detail-container { max-width: 1100px; margin: 3rem auto; padding: 0 2%; flex-grow: 1; }
    .trip-hero-img { width: 100%; height: 420px; object-fit: cover; border-radius: 10px; margin-bottom: 2rem; }
    .trip-meta { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
    .trip-badge { background: #f0f0f0; border-radius: 20px; padding: 0.3rem 0.9rem; font-size: 0.85rem; color: #444; }
    .trip-badge.agency { background: #fff3cd; color: #856404; }
    .inclusion-section { margin-bottom: 2rem; }
    .inclusion-section h4 { font-size: 1rem; font-weight: bold; color: #4a1c35; margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.5rem; }
    .inclusion-list { list-style: none; padding: 0; margin: 0; }
    .inclusion-list li { padding: 0.6rem 0; border-bottom: 1px solid #f5f5f5; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: start; }
    .inclusion-list li:last-child { border-bottom: none; }
    .inclusion-name { font-weight: 600; }
    .inclusion-desc { color: #666; font-size: 0.85rem; margin-top: 0.1rem; }
    .inclusion-limit { font-size: 0.8rem; color: #c1272d; white-space: nowrap; margin-left: 1rem; }
    .dates-table { width: 100%; border-collapse: collapse; }
    .dates-table th { text-align: left; padding: 0.8rem; background: #f9f9f9; font-size: 0.9rem; color: #555; font-weight: 600; border-bottom: 2px solid #eee; }
    .dates-table td { padding: 0.8rem; border-bottom: 1px solid #f0f0f0; font-size: 0.95rem; vertical-align: middle; }
    .dates-table tr:hover td { background: #fafafa; }
    .spots-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
    .spots-ok { background: #e6f4ea; color: #1e8e3e; }
    .spots-low { background: #fff3cd; color: #856404; }
    .spots-full { background: #fee; color: #c1272d; }
    .btn-reserve { background: #c1272d; color: #fff; border: none; padding: 0.5rem 1.2rem; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.9rem; text-decoration: none; display: inline-block; }
    .btn-reserve:hover { background: #a01f24; }
    .btn-reserve[disabled], .btn-reserve.disabled { background: #ccc; cursor: not-allowed; pointer-events: none; }
    .section-box { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    #mini-map { height: 220px; border-radius: 8px; }
</style>

<div class="trip-detail-container">
    <a href="book" style="color: #c1272d; font-size: 0.95rem; text-decoration: none; display: inline-block; margin-bottom: 1.5rem;"><?= $t['back'] ?></a>

    <img src="<?= htmlspecialchars($package['image_url'] ?? 'images/destinations/default.webp', ENT_QUOTES, 'UTF-8') ?>"
         alt="<?= htmlspecialchars($package['name'], ENT_QUOTES, 'UTF-8') ?>"
         class="trip-hero-img">

    <h1 style="font-family:'Playfair Display',serif; font-size:2rem; margin-bottom: 0.5rem;">
        <?= htmlspecialchars($package['name'], ENT_QUOTES, 'UTF-8') ?>
    </h1>

    <div class="trip-meta">
        <?php if ($package['city_name']): ?>
            <span class="trip-badge">📍 <?= htmlspecialchars($package['city_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if ($package['company_name']): ?>
            <span class="trip-badge agency">🏢 <?= htmlspecialchars($package['company_name'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>

    <p style="color:#555; line-height:1.8; font-size:1.05rem; margin-bottom: 2rem;">
        <?= htmlspecialchars($package['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if (!empty($package['highlights'])): ?>
    <div style="background: #fff9f9; border-left: 4px solid #c1272d; padding: 1rem 1.5rem; border-radius: 0 8px 8px 0; margin-bottom: 2rem;">
        <strong><?= $t['included'] ?> :</strong>
        <?= htmlspecialchars($package['highlights'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">

        <!-- Inclusions -->
        <div class="section-box">
            <h3 style="font-family:'Playfair Display',serif; color:#c1272d; margin-bottom:1.5rem;">
                <?= $t['included'] ?>
            </h3>

            <?php foreach (['hotel' => '🏨 ' . $t['hotel'], 'transport' => '✈️ ' . $t['transport'], 'activity' => '🎯 ' . $t['activity']] as $type => $label): ?>
                <?php if (!empty($inclByType[$type])): ?>
                <div class="inclusion-section">
                    <h4><?= $label ?></h4>
                    <ul class="inclusion-list">
                        <?php foreach ($inclByType[$type] as $inc): ?>
                        <li>
                            <div>
                                <div class="inclusion-name"><?= htmlspecialchars($inc['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($inc['description']): ?>
                                    <div class="inclusion-desc"><?= htmlspecialchars($inc['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($inc['max_participants'] !== null): ?>
                                <span class="inclusion-limit"><?= (int)$inc['max_participants'] ?> <?= $t['participants'] ?></span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Mini map -->
        <div class="section-box" style="padding: 0; overflow: hidden;">
            <div id="mini-map"></div>
        </div>
    </div>

    <!-- Available dates -->
    <div class="section-box">
        <h3 style="font-family:'Playfair Display',serif; color:#c1272d; margin-bottom:1.5rem;">
            📅 <?= $t['dates'] ?>
        </h3>

        <?php if (empty($dates)): ?>
            <p style="color:#888;"><?= $t['no_dates'] ?></p>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="dates-table">
                <thead>
                    <tr>
                        <th><?= $t['departure'] ?></th>
                        <th><?= $t['return'] ?></th>
                        <th><?= $t['duration'] ?></th>
                        <th><?= $t['price'] ?></th>
                        <th><?= $t['spots'] ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dates as $d): ?>
                    <?php
                    $available = (int)$d['max_spots'] - (int)$d['booked_spots'];
                    $isFull = $available <= 0;
                    $isLow  = !$isFull && $available <= 5;
                    $spotsClass = $isFull ? 'spots-full' : ($isLow ? 'spots-low' : 'spots-ok');
                    $spotsText  = $isFull ? $t['full'] : $available . ' ' . $t['left'];
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($d['departure_date'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($d['return_date'])) ?></td>
                        <td><?= (int)$d['duration_nights'] ?> <?= $t['nights'] ?></td>
                        <td><strong>€<?= number_format((float)$d['price_per_person'], 0, ',', ' ') ?></strong></td>
                        <td><span class="spots-badge <?= $spotsClass ?>"><?= $spotsText ?></span></td>
                        <td>
                            <?php if (!$isFull): ?>
                                <a href="reservation?date_id=<?= (int)$d['date_id'] ?>" class="btn-reserve"><?= $t['book'] ?></a>
                            <?php else: ?>
                                <span class="btn-reserve disabled"><?= $t['full'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var lat = <?= (float)($package['latitude'] ?? 48.8566) ?>;
var lng = <?= (float)($package['longitude'] ?? 2.3522) ?>;
var miniMap = L.map('mini-map').setView([lat, lng], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '© OpenStreetMap'}).addTo(miniMap);
L.marker([lat, lng]).addTo(miniMap).bindPopup('<?= addslashes(htmlspecialchars($package['city_name'] ?? '', ENT_QUOTES, 'UTF-8')) ?>').openPopup();
</script>

<?php include 'footer.php'; ?>
