<?php
// =========================================================================
// 1. ZONE CONTRÔLEUR : SÉCURITÉ ET LOGIQUE
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

require_once __DIR__ . '/../config/db_connect.php'; 

// 🛡️ SÉCURITÉ : Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

// 🛡️ SÉCURITÉ : Vérification du Token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité (CSRF). Veuillez retourner à la page précédente.");
    }
} else {
    header('Location: book.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$accommodation_id = $_POST['accommodation_id'] ?? null;
$lang = $_SESSION['language'] ?? 'fr';

// --- DICTIONNAIRE DE TRADUCTION ---
$t = [
    'fr' => [
        'title' => "Finaliser la réservation",
        'summary' => "Récapitulatif du logement",
        'dates' => "1. Sélectionnez vos dates",
        'checkin' => "Date d'arrivée",
        'checkout' => "Date de départ",
        'nights' => "nuit(s)",
        'payment' => "2. Paiement sécurisé",
        'card_name' => "Nom sur la carte",
        'card_number' => "Numéro de carte",
        'expiry' => "Expiration (MM/AA)",
        'cvv' => "CVV",
        'total' => "Prix total",
        'confirm' => "Payer et confirmer",
        'success' => "🎉 Réservation et paiement confirmés ! Vous pouvez retrouver les détails dans votre espace personnel.",
        'err_dates' => "⚠️ La date de départ doit être ultérieure à la date d'arrivée.",
        'err_past' => "⚠️ Vous ne pouvez pas réserver à une date passée."
    ],
    'en' => [
        'title' => "Complete your booking",
        'summary' => "Accommodation Summary",
        'dates' => "1. Select your dates",
        'checkin' => "Check-in Date",
        'checkout' => "Check-out Date",
        'nights' => "night(s)",
        'payment' => "2. Secure Payment",
        'card_name' => "Name on card",
        'card_number' => "Card Number",
        'expiry' => "Expiry (MM/YY)",
        'cvv' => "CVV",
        'total' => "Total Price",
        'confirm' => "Pay and confirm",
        'success' => "🎉 Booking and payment successfully confirmed! You can find it in your dashboard.",
        'err_dates' => "⚠️ Check-out date must be after check-in date.",
        'err_past' => "⚠️ You cannot book for a past date."
    ]
][$lang];

$message = '';

// 1. Récupération des infos du logement choisi
$sql = "
    SELECT a.*, act.name AS name, act.description AS description, dt.name AS city_name
    FROM accommodation a
    LEFT JOIN accommodation_translation act ON a.accommodation_id = act.accommodation_id AND act.language_code = :lang1
    LEFT JOIN destination_translation dt ON a.destination_id = dt.destination_id AND dt.language_code = :lang2
    WHERE a.accommodation_id = :acc_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['lang1' => $lang, 'lang2' => $lang, 'acc_id' => $accommodation_id]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$acc) {
    die("Logement introuvable.");
}

// 2. Traitement de la réservation si le bouton "Confirmer" a été cliqué
if (isset($_POST['confirm_booking'])) {
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $today = date('Y-m-d');

    if ($checkin < $today) {
        $message = "<div class='msg-error'>{$t['err_past']}</div>";
    } elseif ($checkout <= $checkin) {
        $message = "<div class='msg-error'>{$t['err_dates']}</div>";
    } else {
        // Calcul du prix côté serveur
        $date1 = new DateTime($checkin);
        $date2 = new DateTime($checkout);
        $diff = $date1->diff($date2)->days;
        $total_price = $diff * $acc['price_per_night'];

        // Insertion en BDD (On n'enregistre PAS les infos de la carte bleue, c'est interdit et inutile ici)
        $insert = $pdo->prepare("INSERT INTO booking (user_id, accommodation_id, check_in, check_out, total_price) VALUES (?, ?, ?, ?, ?)");
        if ($insert->execute([$user_id, $accommodation_id, $checkin, $checkout, $total_price])) {
            $message = "<div class='msg-success'>{$t['success']}</div>";
            unset($_POST['confirm_booking']); 
        }
    }
}

// =========================================================================
// 3. ZONE VUE (HTML & CSS INTÉGRÉ)
// =========================================================================
$page_title = 'Momo - Booking';
include 'header.php'; 
?>

<style>
    .booking-process-container {
        max-width: 1200px; 
        margin: 4rem auto;
        padding: 0 2%;
        display: flex;
        flex-direction: column;
        gap: 2.5rem;
        flex-grow: 1;
    }

    @media(min-width: 800px) {
        .booking-process-container {
            flex-direction: row;
            align-items: flex-start;
        }
    }

    .booking-summary, .booking-form-box {
        background: #fff;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #eaeaea;
        width: 100%; /* Prend toute la largeur sur mobile */
    }

    @media(min-width: 850px) {
        .booking-process-container {
            flex-direction: row;
            align-items: flex-start;
        }
        .booking-summary {
            flex: 0 0 42%; /* La carte du logement a sa place garantie (42%) */
        }
        .booking-form-box {
            flex: 0 0 54%; /* Le formulaire prend 54% (laissant 4% de gap) */
        }
    }

    .booking-summary img {
        width: 100%;
        height: 350px; /* <-- Changé de 250px à 350px pour un effet plus premium */
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }

    .booking-summary h2 {
        font-family: 'Playfair Display', serif;
        color: #c1272d;
        margin-bottom: 0.5rem;
    }

    .booking-summary h3 {
        margin-bottom: 1.5rem;
        color: #4a1c35; /* Optionnel : je lui mets la même couleur que tes autres sous-titres pour l'harmonie */
    }

    .booking-summary .price-tag {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-top: 1rem;
        display: block;
    }

    .form-group {
        margin-bottom: 1.2rem;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: bold;
        margin-bottom: 0.5rem;
        color: #555;
        font-size: 0.9rem;
    }

    .form-group input {
        padding: 0.8rem 1rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
        font-family: inherit;
        background-color: #fafafa;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #6772e5; /* Violet Stripe */
        background-color: #fff;
    }

    .payment-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px solid #e1e5eb;
        margin-bottom: 1.5rem;
    }

    .total-box {
        background: #fff;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px dashed #ccc;
    }

    .total-box div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 1.1rem;
        color: #555;
    }

    .total-box .final-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #c1272d;
        border-top: 1px solid #ddd;
        padding-top: 1rem;
        margin-top: 1rem;
    }

    .btn-submit-booking {
        background-color: #c1272d;
        color: white;
        border: none;
        padding: 1.2rem;
        font-size: 1.2rem;
        font-weight: bold;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
        transition: 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }

    .btn-submit-booking:hover {
        background-color: #a01f24;
    }

    .msg-success { background: #e6f4ea; color: #1e8e3e; padding: 1rem; border-radius: 4px; font-weight: bold; margin-bottom: 2rem; text-align: center; line-height: 1.5; }
    .msg-error { background: #fee; color: #c1272d; padding: 1rem; border-radius: 4px; font-weight: bold; margin-bottom: 2rem; text-align: center; }

    
</style>

<section class="hero">
    <h1 style="font-family: 'Playfair Display', serif;"><?= $t['title'] ?></h1>
</section>

<main class="booking-process-container">
    
    <aside class="booking-summary">
        <h3><?= $t['summary'] ?></h3>
        <img src="<?= htmlspecialchars($acc['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Hotel">
        <h2><?= htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p style="color: #666; margin-bottom: 1rem;"><?= htmlspecialchars($acc['city_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= htmlspecialchars($acc['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <span class="price-tag" id="price-per-night" data-price="<?= $acc['price_per_night'] ?>">
            €<?= number_format($acc['price_per_night'], 2) ?> / <?= $t['nights'] ?>
        </span>
    </aside>

    <section class="booking-form-box">
        <?= $message ?>
        
        <?php if (!isset($_POST['confirm_booking']) || !empty($message) && strpos($message, 'msg-error') !== false): ?>
            <form method="POST" action="process_booking.php" id="booking-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="accommodation_id" value="<?= $accommodation_id ?>">
                
                <h3 style="margin-bottom: 1rem; color: #4a1c35;"><?= $t['dates'] ?></h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label><?= $t['checkin'] ?></label>
                        <input type="date" name="checkin" id="checkin" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label><?= $t['checkout'] ?></label>
                        <input type="date" name="checkout" id="checkout" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                </div>

                <hr style="border: none; border-top: 1px solid #eaeaea; margin: 1.5rem 0;">

                <div class="payment-section">
                    <h3 style="margin-bottom: 1rem; color: #4a1c35; display: flex; justify-content: space-between; align-items: center;">
                        <?= $t['payment'] ?>
                        <span style="font-size: 0.9rem; color: #6772e5; font-weight: normal;">🔒 Secured</span>
                    </h3>
                    
                    <div class="form-group">
                        <label><?= $t['card_name'] ?></label>
                        <input type="text" name="card_name" placeholder="John Doe" required autocomplete="cc-name">
                    </div>
                    
                    <div class="form-group">
                        <label><?= $t['card_number'] ?></label>
                        <input type="text" name="card_number" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required autocomplete="cc-number">
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <div class="form-group" style="flex: 1;">
                            <label><?= $t['expiry'] ?></label>
                            <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5" required autocomplete="cc-exp">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label><?= $t['cvv'] ?></label>
                            <input type="password" name="cvv" placeholder="123" maxlength="4" required autocomplete="cc-csc">
                        </div>
                    </div>
                </div>

                <div class="total-box">
                    <div>
                        <span id="calc-nights">0 <?= $t['nights'] ?></span>
                        <span id="calc-base">0 x €<?= number_format($acc['price_per_night'], 2) ?></span>
                    </div>
                    <div class="final-price">
                        <span><?= $t['total'] ?></span>
                        <span id="calc-total">€0.00</span>
                    </div>
                </div>

                <button type="submit" name="confirm_booking" class="btn-submit-booking" id="btn-submit" disabled style="opacity: 0.5; cursor: not-allowed;">
                    🔒 <?= $t['confirm'] ?>
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="user_page.php" style="display: inline-block; background: #333; color: white; padding: 1rem 2rem; border-radius: 4px; text-decoration: none; font-weight: bold;">Aller à mon Espace</a>
            </div>
        <?php endif; ?>
    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');
    const cardNumber = document.getElementById('card_number');
    const expiry = document.getElementById('expiry');
    
    if (!checkinInput || !checkoutInput) return;

    const pricePerNight = parseFloat(document.getElementById('price-per-night').getAttribute('data-price'));
    const displayNights = document.getElementById('calc-nights');
    const displayBase = document.getElementById('calc-base');
    const displayTotal = document.getElementById('calc-total');
    const btnSubmit = document.getElementById('btn-submit');
    const nightText = "<?= $t['nights'] ?>";

    // Calcul du prix
    function calculatePrice() {
        const checkinDate = new Date(checkinInput.value);
        const checkoutDate = new Date(checkoutInput.value);

        if (checkinInput.value) {
            let minCheckout = new Date(checkinDate);
            minCheckout.setDate(minCheckout.getDate() + 1);
            checkoutInput.min = minCheckout.toISOString().split('T')[0];
        }

        if (checkinInput.value && checkoutInput.value && checkoutDate > checkinDate) {
            const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
            const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
            const total = nights * pricePerNight;

            displayNights.innerText = nights + " " + nightText;
            displayBase.innerText = nights + " x €" + pricePerNight.toFixed(2); 
            displayTotal.innerText = "€" + total.toFixed(2);

            btnSubmit.disabled = false;
            btnSubmit.style.opacity = "1";
            btnSubmit.style.cursor = "pointer";
        } else {
            displayNights.innerText = "0 " + nightText;
            displayBase.innerText = "0 x €" + pricePerNight.toFixed(2); 
            displayTotal.innerText = "€0.00";
            
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = "0.5";
            btnSubmit.style.cursor = "not-allowed";
        }
    }

    checkinInput.addEventListener('change', calculatePrice);
    checkoutInput.addEventListener('change', calculatePrice);

    // Formatage automatique de la carte bleue (Ajout des espaces)
    if(cardNumber) {
        cardNumber.addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
        });
    }

    // Formatage de la date d'expiration (Ajout du slash MM/YY)
    if(expiry) {
        expiry.addEventListener('input', function (e) {
            let val = e.target.value.replace(/[^\d]/g, '');
            if(val.length > 2) {
                val = val.substring(0, 2) + '/' + val.substring(2, 4);
            }
            e.target.value = val;
        });
    }
});
</script>

<?php include 'footer.php'; ?>