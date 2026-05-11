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

$lang = $_SESSION['language'] ?? 'fr';

$t = [
    'fr' => [
        'title'        => 'Votre réservation',
        'step1'        => 'Étape 1 sur 2 — Participants',
        'trip_summary' => 'Récapitulatif du voyage',
        'departure'    => 'Départ',
        'return'       => 'Retour',
        'nights'       => 'nuits',
        'per_person'   => '/ pers.',
        'spots_left'   => 'places disponibles',
        'nb_people'    => 'Nombre de voyageurs',
        'total'        => 'Total estimé',
        'continue'     => 'Continuer vers le paiement →',
        'back'         => '← Retour au voyage',
        'full'         => 'Ce départ est complet.',
        'err_spots'    => 'Pas assez de places disponibles.',
        'err_min'      => 'Minimum 1 participant.',
        'err_invalid'  => 'Date invalide.',
        'included'     => 'Ce qui est inclus',
    ],
    'en' => [
        'title'        => 'Your reservation',
        'step1'        => 'Step 1 of 2 — Participants',
        'trip_summary' => 'Trip summary',
        'departure'    => 'Departure',
        'return'       => 'Return',
        'nights'       => 'nights',
        'per_person'   => '/ person',
        'spots_left'   => 'spots available',
        'nb_people'    => 'Number of travelers',
        'total'        => 'Estimated total',
        'continue'     => 'Continue to payment →',
        'back'         => '← Back to trip',
        'full'         => 'This departure is fully booked.',
        'err_spots'    => 'Not enough spots available.',
        'err_min'      => 'Minimum 1 participant.',
        'err_invalid'  => 'Invalid date.',
        'included'     => "What's included",
    ],
][$lang];

$error = '';
$date_id = filter_input(INPUT_GET, 'date_id', FILTER_VALIDATE_INT);

// Also accept date_id from POST (form re-display on error)
if (!$date_id && isset($_POST['date_id'])) {
    $date_id = filter_var($_POST['date_id'], FILTER_VALIDATE_INT);
}

if (!$date_id) {
    header('Location: book');
    exit();
}

// Fetch date + package
$stmt = $pdo->prepare("
    SELECT td.*,
           COALESCE(tpt.name, tpt_fb.name) AS package_name,
           COALESCE(tpt.highlights, tpt_fb.highlights) AS highlights,
           tp.image_url, tp.package_id,
           dt.name AS city_name
    FROM trip_date td
    JOIN trip_package tp ON td.package_id = tp.package_id
    LEFT JOIN trip_package_translation tpt ON tp.package_id = tpt.package_id AND tpt.language_code = :lang
    LEFT JOIN trip_package_translation tpt_fb ON tp.package_id = tpt_fb.package_id AND tpt_fb.language_code = 'fr'
    LEFT JOIN destination_translation dt ON tp.destination_id = dt.destination_id AND dt.language_code = :lang2
    WHERE td.date_id = :date_id AND td.status = 'available' AND tp.status = 'published'
");
$stmt->execute(['lang' => $lang, 'lang2' => $lang, 'date_id' => $date_id]);
$tripDate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tripDate) {
    header('Location: book');
    exit();
}

$available = (int)$tripDate['max_spots'] - (int)$tripDate['booked_spots'];
$isFull = $available <= 0;

// Handle POST — store in session and redirect to payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF error.');
    }

    $num = filter_var($_POST['num_participants'] ?? 1, FILTER_VALIDATE_INT);

    if (!$num || $num < 1) {
        $error = $t['err_min'];
    } elseif ($num > $available) {
        $error = $t['err_spots'];
    } else {
        // Re-check departure date is still in the future
        if ($tripDate['departure_date'] < date('Y-m-d')) {
            $error = $t['err_invalid'];
        } else {
            $total = $num * (float)$tripDate['price_per_person'];
            $_SESSION['pending_reservation'] = [
                'date_id'          => $date_id,
                'package_id'       => (int)$tripDate['package_id'],
                'num_participants'  => $num,
                'total_price'      => $total,
                'price_per_person' => (float)$tripDate['price_per_person'],
                'package_name'     => $tripDate['package_name'],
                'city_name'        => $tripDate['city_name'],
                'departure_date'   => $tripDate['departure_date'],
                'return_date'      => $tripDate['return_date'],
                'duration_nights'  => (int)$tripDate['duration_nights'],
                'image_url'        => $tripDate['image_url'],
            ];
            header('Location: payment');
            exit();
        }
    }
}

$page_title = 'Momo - ' . $t['title'];
include 'header.php';
?>

<style>
    .res-container { max-width: 900px; margin: 3rem auto; padding: 0 2%; flex-grow: 1; display: flex; gap: 2rem; flex-wrap: wrap; }
    .res-summary { flex: 0 0 350px; background: #fff; border: 1px solid #eaeaea; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: fit-content; }
    .res-summary img { width: 100%; height: 200px; object-fit: cover; }
    .res-summary-body { padding: 1.5rem; }
    .res-summary-body h3 { font-family: 'Playfair Display', serif; color: #c1272d; margin-bottom: 0.5rem; }
    .res-form-box { flex: 1; min-width: 280px; }
    .step-indicator { background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; font-weight: 600; color: #666; font-size: 0.9rem; }
    .form-card { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 0.5rem; color: #555; font-size: 0.9rem; }
    .form-group select { width: 100%; padding: 0.9rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; background: #fafafa; }
    .total-box { background: #f9f9f9; border-radius: 8px; padding: 1.2rem; margin: 1.5rem 0; display: flex; justify-content: space-between; align-items: center; }
    .total-amount { font-size: 1.6rem; font-weight: bold; color: #c1272d; }
    .btn-continue { background: #c1272d; color: #fff; border: none; padding: 1.1rem; font-size: 1.1rem; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; transition: 0.3s; }
    .btn-continue:hover { background: #a01f24; }
    .info-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f5f5f5; font-size: 0.95rem; }
    .info-row:last-child { border-bottom: none; }
    .info-row span:first-child { color: #888; }
    .info-row span:last-child { font-weight: 600; }
</style>

<section class="hero" style="padding: 3rem 2%;">
    <h1><?= $t['title'] ?></h1>
</section>

<div class="res-container">

    <!-- Summary sidebar -->
    <aside class="res-summary">
        <img src="<?= htmlspecialchars($tripDate['image_url'] ?? 'images/destinations/default.webp', ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($tripDate['package_name'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="res-summary-body">
            <h3><?= htmlspecialchars($tripDate['package_name'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p style="color:#888; margin-bottom:1rem;">📍 <?= htmlspecialchars($tripDate['city_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="info-row">
                <span><?= $t['departure'] ?></span>
                <span><?= date('d/m/Y', strtotime($tripDate['departure_date'])) ?></span>
            </div>
            <div class="info-row">
                <span><?= $t['return'] ?></span>
                <span><?= date('d/m/Y', strtotime($tripDate['return_date'])) ?></span>
            </div>
            <div class="info-row">
                <span><?= $lang === 'fr' ? 'Durée' : 'Duration' ?></span>
                <span><?= (int)$tripDate['duration_nights'] ?> <?= $t['nights'] ?></span>
            </div>
            <div class="info-row">
                <span><?= $lang === 'fr' ? 'Tarif' : 'Price' ?></span>
                <span>€<?= number_format((float)$tripDate['price_per_person'], 0, ',', ' ') ?> <?= $t['per_person'] ?></span>
            </div>
            <div class="info-row">
                <span><?= $lang === 'fr' ? 'Disponibilité' : 'Availability' ?></span>
                <span style="color: <?= $available <= 5 ? '#856404' : '#1e8e3e' ?>">
                    <?= $available ?> <?= $t['spots_left'] ?>
                </span>
            </div>
            <?php if ($tripDate['highlights']): ?>
            <div style="margin-top:1rem; padding-top:1rem; border-top: 1px solid #f0f0f0; font-size:0.85rem; color:#555;">
                <strong><?= $t['included'] ?> :</strong><br>
                <?= htmlspecialchars($tripDate['highlights'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Reservation form -->
    <div class="res-form-box">
        <div class="step-indicator"><?= $t['step1'] ?></div>

        <?php if ($error): ?>
            <div style="background:#fee; color:#c1272d; padding:1rem; border-radius:4px; margin-bottom:1rem; font-weight:600;">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($isFull): ?>
            <div class="form-card" style="text-align:center; padding: 3rem;">
                <p style="font-size:1.1rem; color:#c1272d; font-weight:bold;">😕 <?= $t['full'] ?></p>
                <a href="trip?id=<?= (int)$tripDate['package_id'] ?>" style="color:#c1272d; text-decoration:none;"><?= $t['back'] ?></a>
            </div>
        <?php else: ?>
            <div class="form-card">
                <form method="POST" action="reservation?date_id=<?= $date_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="date_id" value="<?= $date_id ?>">

                    <div class="form-group">
                        <label for="num_participants"><?= $t['nb_people'] ?></label>
                        <select name="num_participants" id="num_participants" onchange="updateTotal(this.value)">
                            <?php for ($i = 1; $i <= min($available, 20); $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> <?= $lang === 'fr' ? 'voyageur' . ($i > 1 ? 's' : '') : 'traveler' . ($i > 1 ? 's' : '') ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="total-box">
                        <span style="font-size:1rem; color:#555;"><?= $t['total'] ?></span>
                        <span class="total-amount" id="total-display">
                            €<?= number_format((float)$tripDate['price_per_person'], 0, ',', ' ') ?>
                        </span>
                    </div>

                    <button type="submit" name="proceed_to_payment" class="btn-continue">
                        <?= $t['continue'] ?>
                    </button>
                </form>

                <div style="text-align:center; margin-top:1.5rem;">
                    <a href="trip?id=<?= (int)$tripDate['package_id'] ?>" style="color:#888; font-size:0.9rem; text-decoration:none;">
                        <?= $t['back'] ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
var pricePerPerson = <?= (float)$tripDate['price_per_person'] ?>;
function updateTotal(n) {
    var total = n * pricePerPerson;
    document.getElementById('total-display').textContent = '€' + total.toLocaleString('fr-FR', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}
</script>

<?php include 'footer.php'; ?>
