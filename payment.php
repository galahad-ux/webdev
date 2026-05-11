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

// Require reservation data in session
if (empty($_SESSION['pending_reservation'])) {
    header('Location: book');
    exit();
}

$res  = $_SESSION['pending_reservation'];
$lang = $_SESSION['language'] ?? 'fr';
$user_id = (int)$_SESSION['user_id'];

$t = [
    'fr' => [
        'title'         => 'Paiement',
        'step2'         => 'Étape 2 sur 2 — Paiement',
        'summary'       => 'Votre réservation',
        'travelers'     => 'voyageur(s)',
        'nights'        => 'nuits',
        'total'         => 'Total à payer',
        'payment'       => 'Paiement sécurisé',
        'card_name'     => 'Nom sur la carte',
        'card_number'   => 'Numéro de carte',
        'expiry'        => 'Expiration (MM/AA)',
        'cvv'           => 'CVV',
        'confirm'       => '🔒 Confirmer et payer',
        'back'          => '← Modifier les participants',
        'success_title' => '🎉 Réservation confirmée !',
        'success_body'  => 'Votre voyage est réservé. Retrouvez votre facture dans votre espace personnel.',
        'my_space'      => 'Accéder à mon espace',
        'invoice_no'    => 'N° de facture',
        'err_spots'     => 'Désolé, les places ont été prises entre-temps. Veuillez relancer votre réservation.',
        'err_fields'    => 'Veuillez remplir tous les champs.',
        'departure'     => 'Départ',
        'return'        => 'Retour',
        'per_person'    => '/ pers.',
    ],
    'en' => [
        'title'         => 'Payment',
        'step2'         => 'Step 2 of 2 — Payment',
        'summary'       => 'Your reservation',
        'travelers'     => 'traveler(s)',
        'nights'        => 'nights',
        'total'         => 'Total to pay',
        'payment'       => 'Secure payment',
        'card_name'     => 'Name on card',
        'card_number'   => 'Card number',
        'expiry'        => 'Expiry (MM/YY)',
        'cvv'           => 'CVV',
        'confirm'       => '🔒 Confirm and pay',
        'back'          => '← Edit participants',
        'success_title' => '🎉 Booking confirmed!',
        'success_body'  => 'Your trip is booked. Find your invoice in your personal dashboard.',
        'my_space'      => 'Go to my dashboard',
        'invoice_no'    => 'Invoice no.',
        'err_spots'     => 'Sorry, spots were taken in the meantime. Please restart your booking.',
        'err_fields'    => 'Please fill in all fields.',
        'departure'     => 'Departure',
        'return'        => 'Return',
        'per_person'    => '/ person',
    ],
][$lang];

$error           = '';
$success         = false;
$invoice_number  = '';
$booking_id_done = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF error.');
    }

    $card_name   = trim($_POST['card_name'] ?? '');
    $card_number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry      = trim($_POST['expiry'] ?? '');
    $cvv         = trim($_POST['cvv'] ?? '');

    if (!$card_name || strlen($card_number) < 12 || !$expiry || !$cvv) {
        $error = $t['err_fields'];
    } else {
        $date_id = (int)$res['date_id'];
        $num     = (int)$res['num_participants'];
        $total   = (float)$res['total_price'];

        // Re-check availability (race condition protection)
        $checkStmt = $pdo->prepare("SELECT max_spots, booked_spots FROM trip_date WHERE date_id = ? AND status = 'available' FOR UPDATE");
        $pdo->beginTransaction();
        try {
            $checkStmt->execute([$date_id]);
            $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || (int)$row['max_spots'] - (int)$row['booked_spots'] < $num) {
                $pdo->rollBack();
                $error = $t['err_spots'];
            } else {
                // Create booking
                $insBook = $pdo->prepare("INSERT INTO trip_booking (user_id, date_id, num_participants, total_price, status) VALUES (?, ?, ?, ?, 'confirmed')");
                $insBook->execute([$user_id, $date_id, $num, $total]);
                $booking_id_done = (int)$pdo->lastInsertId();

                // Update booked_spots
                $pdo->prepare("UPDATE trip_date SET booked_spots = booked_spots + ? WHERE date_id = ?")->execute([$num, $date_id]);

                // Generate invoice number
                $invoice_number = 'INV-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                $card_last4 = substr($card_number, -4);

                // Create invoice
                $pdo->prepare("INSERT INTO invoice (booking_id, user_id, amount, card_last4, invoice_number, status) VALUES (?, ?, ?, ?, ?, 'paid')")
                    ->execute([$booking_id_done, $user_id, $total, $card_last4, $invoice_number]);

                $pdo->commit();

                // Clear pending reservation
                unset($_SESSION['pending_reservation']);
                $success = true;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("payment.php: " . $e->getMessage());
            $error = 'Une erreur technique est survenue. Veuillez réessayer.';
        }
    }
}

$page_title = 'Momo - ' . $t['title'];
include 'header.php';
?>

<style>
    .pay-container { max-width: 900px; margin: 3rem auto; padding: 0 2%; flex-grow: 1; display: flex; gap: 2rem; flex-wrap: wrap; }
    .pay-summary { flex: 0 0 320px; background: #fff; border: 1px solid #eaeaea; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: fit-content; }
    .pay-summary img { width: 100%; height: 190px; object-fit: cover; }
    .pay-summary-body { padding: 1.5rem; }
    .pay-summary-body h3 { font-family: 'Playfair Display', serif; color: #c1272d; margin-bottom: 1rem; }
    .pay-form-box { flex: 1; min-width: 280px; }
    .step-indicator { background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; font-weight: 600; color: #666; font-size: 0.9rem; }
    .form-card { background: #fff; border: 1px solid #eaeaea; border-radius: 8px; padding: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 0.4rem; color: #555; font-size: 0.9rem; }
    .form-group input { width: 100%; padding: 0.9rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; background: #fafafa; box-sizing: border-box; }
    .form-group input:focus { outline: none; border-color: #6772e5; background: #fff; }
    .secure-badge { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .secure-badge h3 { color: #4a1c35; font-size: 1.1rem; }
    .total-final { background: #f9f9f9; border-radius: 8px; padding: 1.2rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .total-final .amount { font-size: 1.8rem; font-weight: bold; color: #c1272d; }
    .btn-pay { background: #c1272d; color: #fff; border: none; padding: 1.2rem; font-size: 1.1rem; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; transition: 0.3s; }
    .btn-pay:hover { background: #a01f24; }
    .info-row { display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.9rem; border-bottom: 1px solid #f5f5f5; }
    .info-row:last-child { border-bottom: none; }
    .info-row span:first-child { color: #888; }
    .success-box { text-align: center; padding: 3rem 2rem; }
    .success-box h2 { font-family: 'Playfair Display', serif; color: #1e8e3e; font-size: 1.8rem; margin-bottom: 1rem; }
    .success-box p { color: #555; margin-bottom: 2rem; font-size: 1.05rem; }
    .success-invoice { background: #f0f9f0; border: 1px solid #a8d5b5; border-radius: 8px; padding: 1rem 1.5rem; display: inline-block; margin-bottom: 2rem; font-size: 1.1rem; }
</style>

<section class="hero" style="padding: 3rem 2%;">
    <h1><?= $t['title'] ?></h1>
</section>

<div class="pay-container">

    <?php if (!$success): ?>

    <!-- Summary sidebar -->
    <aside class="pay-summary">
        <img src="<?= htmlspecialchars($res['image_url'] ?? 'images/destinations/default.webp', ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($res['package_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="pay-summary-body">
            <h3><?= htmlspecialchars($res['package_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
            <p style="color:#888; margin-bottom:1rem;">📍 <?= htmlspecialchars($res['city_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="info-row">
                <span><?= $t['departure'] ?></span>
                <span><?= date('d/m/Y', strtotime($res['departure_date'])) ?></span>
            </div>
            <div class="info-row">
                <span><?= $t['return'] ?></span>
                <span><?= date('d/m/Y', strtotime($res['return_date'])) ?></span>
            </div>
            <div class="info-row">
                <span><?= $lang === 'fr' ? 'Durée' : 'Duration' ?></span>
                <span><?= (int)$res['duration_nights'] ?> <?= $t['nights'] ?></span>
            </div>
            <div class="info-row">
                <span><?= $lang === 'fr' ? 'Voyageurs' : 'Travelers' ?></span>
                <span><?= (int)$res['num_participants'] ?> <?= $t['travelers'] ?></span>
            </div>
            <div class="info-row">
                <span><?= $lang === 'fr' ? 'Tarif' : 'Rate' ?></span>
                <span>€<?= number_format($res['price_per_person'], 0, ',', ' ') ?> <?= $t['per_person'] ?></span>
            </div>
        </div>
    </aside>

    <!-- Payment form -->
    <div class="pay-form-box">
        <div class="step-indicator"><?= $t['step2'] ?></div>

        <?php if ($error): ?>
            <div style="background:#fee; color:#c1272d; padding:1rem; border-radius:4px; margin-bottom:1rem; font-weight:600;">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="payment">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="secure-badge">
                    <h3><?= $t['payment'] ?></h3>
                    <span style="color:#6772e5; font-size:0.9rem;">🔒 Secured</span>
                </div>

                <div class="form-group">
                    <label><?= $t['card_name'] ?></label>
                    <input type="text" name="card_name" placeholder="John Doe" required autocomplete="cc-name">
                </div>

                <div class="form-group">
                    <label><?= $t['card_number'] ?></label>
                    <input type="text" name="card_number" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required autocomplete="cc-number">
                </div>

                <div style="display:flex; gap:1rem;">
                    <div class="form-group" style="flex:1;">
                        <label><?= $t['expiry'] ?></label>
                        <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5" required autocomplete="cc-exp">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label><?= $t['cvv'] ?></label>
                        <input type="password" name="cvv" placeholder="123" maxlength="4" required autocomplete="cc-csc">
                    </div>
                </div>

                <div class="total-final">
                    <span style="color:#555;"><?= $t['total'] ?></span>
                    <span class="amount">€<?= number_format((float)$res['total_price'], 2, ',', ' ') ?></span>
                </div>

                <button type="submit" name="confirm_payment" class="btn-pay"><?= $t['confirm'] ?></button>
            </form>

            <div style="text-align:center; margin-top:1.5rem;">
                <a href="reservation?date_id=<?= (int)$res['date_id'] ?>" style="color:#888; font-size:0.9rem; text-decoration:none;">
                    <?= $t['back'] ?>
                </a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Success state (full width) -->
    <div style="flex:1;">
        <div class="form-card success-box">
            <h2><?= $t['success_title'] ?></h2>
            <p><?= $t['success_body'] ?></p>
            <div class="success-invoice">
                <?= $t['invoice_no'] ?> : <strong><?= htmlspecialchars($invoice_number, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <br>
            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                <a href="user_page" style="display:inline-block; background:#c1272d; color:#fff; padding:0.9rem 2rem; border-radius:4px; text-decoration:none; font-weight:bold;">
                    <?= $t['my_space'] ?>
                </a>
                <?php if ($booking_id_done): ?>
                    <?php
                    // Fetch invoice_id for the PDF link
                    $inv = $pdo->prepare("SELECT invoice_id FROM invoice WHERE booking_id = ?");
                    $inv->execute([$booking_id_done]);
                    $invRow = $inv->fetch();
                    if ($invRow):
                    ?>
                    <a href="invoice_pdf?id=<?= (int)$invRow['invoice_id'] ?>" target="_blank"
                       style="display:inline-block; background:#333; color:#fff; padding:0.9rem 2rem; border-radius:4px; text-decoration:none; font-weight:bold;">
                        📄 <?= $lang === 'fr' ? 'Télécharger la facture (PDF)' : 'Download invoice (PDF)' ?>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
var cardInput = document.getElementById('card_number');
var expiryInput = document.getElementById('expiry');
if (cardInput) {
    cardInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim().substring(0, 19);
    });
}
if (expiryInput) {
    expiryInput.addEventListener('input', function(e) {
        var v = e.target.value.replace(/[^\d]/g, '');
        if (v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4);
        e.target.value = v;
    });
}
</script>

<?php include 'footer.php'; ?>
