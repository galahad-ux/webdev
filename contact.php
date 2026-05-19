<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

require_once __DIR__ . '/../config/db_connect.php';

$lang = $_SESSION['language'] ?? 'fr';

$t = [
    'fr' => [
        'page_title'     => 'Momo - Contactez-nous',
        'hero_title'     => 'Contactez-nous',
        'contact_text'   => "Vous avez des questions ou besoin d'aide ? Nous sommes là pour vous aider ! Contactez-nous par l'un des moyens suivants :",
        'get_in_touch'   => 'Contactez-nous',
        'email_label'    => 'Email',
        'phone'          => 'Téléphone',
        'address'        => 'Adresse',
        'business_hours' => "Heures d'ouverture",
        'monday_friday'  => 'Lundi - Vendredi : 9h - 18h',
        'saturday_sunday'=> 'Samedi - Dimanche : Fermé',
        'first_name'     => 'Prénom',
        'last_name'      => 'Nom',
        'your_email'     => 'Votre adresse email',
        'subject'        => 'Sujet',
        'message'        => 'Comment pouvons-nous vous aider ?',
        'send_message'   => 'Envoyer le message',
        'rgpd_notice'    => 'Vos données sont conservées 30 jours conformément au RGPD, puis supprimées automatiquement.',
        'success'        => 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.',
        'err_csrf'       => 'Requête invalide. Veuillez réessayer.',
        'err_rate'       => 'Vous avez envoyé trop de messages récemment. Réessayez dans une heure.',
        'err_fields'     => 'Veuillez remplir tous les champs obligatoires.',
        'err_email'      => 'Adresse email invalide.',
        'err_server'     => 'Une erreur est survenue. Veuillez réessayer.',
    ],
    'en' => [
        'page_title'     => 'Momo - Contact Us',
        'hero_title'     => 'Contact Us',
        'contact_text'   => "Have questions or need assistance? We're here to help! Reach out to us through any of the following methods:",
        'get_in_touch'   => 'Get in touch',
        'email_label'    => 'Email',
        'phone'          => 'Phone',
        'address'        => 'Address',
        'business_hours' => 'Business Hours',
        'monday_friday'  => 'Monday - Friday: 9am - 6pm',
        'saturday_sunday'=> 'Saturday - Sunday: Closed',
        'first_name'     => 'First Name',
        'last_name'      => 'Last Name',
        'your_email'     => 'Your Email Address',
        'subject'        => 'Subject',
        'message'        => 'How can we help you?',
        'send_message'   => 'Send Message',
        'rgpd_notice'    => 'Your data is kept for 30 days in accordance with GDPR, then automatically deleted.',
        'success'        => 'Your message has been sent. We will get back to you as soon as possible.',
        'err_csrf'       => 'Invalid request. Please try again.',
        'err_rate'       => 'You have sent too many messages recently. Please try again in one hour.',
        'err_fields'     => 'Please fill in all required fields.',
        'err_email'      => 'Invalid email address.',
        'err_server'     => 'An error occurred. Please try again.',
    ],
][$lang];

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = false;
$error   = '';

// RGPD cleanup — delete expired messages (opportunistic, runs on each page load)
try {
    $pdo->exec("DELETE FROM contact_message WHERE expires_at < NOW()");
} catch (PDOException $e) {
    // Table may not exist yet — silently ignore
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = $t['err_csrf'];
    }

    // Honeypot — bots fill this, humans don't
    elseif (!empty($_POST['website'])) {
        // Silently discard; show success to avoid fingerprinting
        $success = true;
    }

    else {
        $ip         = $_SERVER['REMOTE_ADDR'];
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $subject    = trim($_POST['subject']    ?? '');
        $message    = trim($_POST['message']    ?? '');

        // Validation
        if ($first_name === '' || $last_name === '' || $email === '' || $subject === '' || $message === '') {
            $error = $t['err_fields'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $t['err_email'];
        } else {
            // Rate limit: max 3 messages per IP per hour
            $rateStmt = $pdo->prepare("
                SELECT COUNT(*) FROM contact_message
                WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $rateStmt->execute([$ip]);
            $recentCount = (int)$rateStmt->fetchColumn();

            if ($recentCount >= 3) {
                $error = $t['err_rate'];
            } else {
                // Store in DB (RGPD: expires in 30 days)
                try {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO contact_message
                            (first_name, last_name, email, subject, message, ip_address, expires_at)
                        VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                    ");
                    $insertStmt->execute([$first_name, $last_name, $email, $subject, $message, $ip]);
                } catch (PDOException $e) {
                    $error = $t['err_server'];
                }

                if ($error === '') {
                    // Send email
                    $to      = 'momo-vacation@alwaysdata.net';
                    $esc_subject = '=?UTF-8?B?' . base64_encode('[Momo Contact] ' . $subject) . '?=';
                    $body    = "Nouveau message de contact reçu via momo-vacation.com\n\n"
                             . "Prénom  : $first_name\n"
                             . "Nom     : $last_name\n"
                             . "Email   : $email\n"
                             . "Sujet   : $subject\n\n"
                             . "--- Message ---\n"
                             . $message . "\n\n"
                             . "--- Infos techniques ---\n"
                             . "IP      : $ip\n"
                             . "Date    : " . date('Y-m-d H:i:s') . "\n"
                             . "Langue  : $lang\n";

                    $headers = implode("\r\n", [
                        'From: noreply@momo-vacation.com',
                        'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>',
                        'Content-Type: text/plain; charset=UTF-8',
                        'X-Mailer: PHP/' . PHP_VERSION,
                    ]);

                    mail($to, $esc_subject, $body, $headers);

                    // Regenerate CSRF token after successful submit
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $success = true;
                }
            }
        }
    }
}

$page_title       = $t['page_title'];
$page_description = $lang === 'fr'
    ? 'Contactez Momo Travel pour toute question sur vos réservations, voyages et plus encore.'
    : 'Contact Momo Travel for questions about your bookings, tours, and more.';
include 'header.php';
?>

<section class="hero">
    <h1><?= $t['hero_title'] ?></h1>
</section>

<main class="contact-section">
    <div class="contact-header">
        <p><?= $t['contact_text'] ?></p>
    </div>

    <?php if ($success): ?>
        <div style="max-width:700px; margin:1.5rem auto; padding:1rem 1.5rem; background:#e6f4ea; border:1px solid #a8d5b5; border-radius:8px; color:#1e8e3e; font-weight:500;">
            <?= $t['success'] ?>
        </div>
    <?php elseif ($error !== ''): ?>
        <div style="max-width:700px; margin:1.5rem auto; padding:1rem 1.5rem; background:#fee; border:1px solid #f5c6cb; border-radius:8px; color:#c1272d; font-weight:500;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="contact-container">
        <aside class="contact-info">
            <h3><?= $t['get_in_touch'] ?></h3>
            <ul>
                <li><strong><?= $t['email_label'] ?>:</strong> momo-vacation@alwaysdata.net</li>
                <li><strong><?= $t['phone'] ?>:</strong> +33 6 79 46 09 58</li>
                <li><strong><?= $t['address'] ?>:</strong> 267 Av. de Navarre, 16000 Angoulême, France</li>
            </ul>
            <div class="business-hours">
                <h4><?= $t['business_hours'] ?></h4>
                <p><?= $t['monday_friday'] ?></p>
                <p><?= $t['saturday_sunday'] ?></p>
            </div>
        </aside>

        <form id="mon-formulaire" class="contact-form" action="contact" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <!-- Honeypot: hidden from real users, traps bots -->
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="display:none;" aria-hidden="true">

            <div class="form-row">
                <input type="text" name="first_name" placeholder="<?= $t['first_name'] ?>"
                       value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                <input type="text" name="last_name" placeholder="<?= $t['last_name'] ?>"
                       value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <input type="email" name="email" placeholder="<?= $t['your_email'] ?>"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <input type="text" name="subject" placeholder="<?= $t['subject'] ?>"
                   value="<?= htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <textarea name="message" rows="6" placeholder="<?= $t['message'] ?>" required><?= htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <p style="font-size:0.8rem; color:#888; margin:0.5rem 0 1rem;">
                <?= $t['rgpd_notice'] ?>
            </p>

            <button type="submit" id="btn-submit"><?= $t['send_message'] ?></button>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>
