<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$lang = $_SESSION['language'] ?? 'fr';
$content = '';

try {
    $stmt = $pdo->prepare("SELECT content_fr, content_en, updated_at FROM legal_page WHERE slug = 'cgu'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $content    = $lang === 'en' ? $row['content_en'] : $row['content_fr'];
        $updated_at = $row['updated_at'];
    }
} catch (PDOException $e) {
    // Table not yet created — default content below
}

if (empty(trim(strip_tags($content)))) {
    $updated_at = '2026-01-01';
    if ($lang === 'en') {
        $content = '<h2>Terms of Service</h2>
<p>Last updated: January 2026</p>
<h3>1. Purpose</h3>
<p>These Terms of Service govern the use of services offered on the Momo Travel website (hereinafter "the Site"). By using the Site, you fully accept these terms.</p>
<h3>2. Access to the Service</h3>
<p>Access to the Site is free of charge. Creating an account is required to make a booking. You agree to provide accurate information upon registration and keep it up to date.</p>
<h3>3. Services Offered</h3>
<p>Momo Travel is a travel booking platform connecting travelers with partner travel agencies. Services include searching, comparing, and booking tourist packages.</p>
<h3>4. Obligations of the User</h3>
<p>You agree to use the Site lawfully and not to disrupt its operation. Any abusive, fraudulent, or malicious use will result in immediate account suspension.</p>
<h3>5. Personal Data</h3>
<p>Personal data collected is processed in accordance with our Privacy Policy and the GDPR. You have the right to access, rectify, and delete your data. To exercise these rights, contact us via the <a href="contact">contact form</a>.</p>
<h3>6. Payment</h3>
<p>Payments are made securely online. We never store your full card details — only the last 4 digits are retained for invoicing purposes.</p>
<h3>7. Intellectual Property</h3>
<p>All content on the Site (texts, images, logos) is protected by intellectual property law. Any reproduction without prior written authorization is prohibited.</p>
<h3>8. Limitation of Liability</h3>
<p>Momo Travel acts as an intermediary between travelers and agencies. Momo Travel cannot be held liable for any failure on the part of the service provider agency, subject to applicable law.</p>
<h3>9. Governing Law</h3>
<p>These Terms are governed by French law. Any dispute will be brought before the competent courts of Paris, France.</p>';
    } else {
        $content = '<h2>Conditions Générales d\'Utilisation</h2>
<p>Dernière mise à jour : janvier 2026</p>
<h3>1. Objet</h3>
<p>Les présentes Conditions Générales d\'Utilisation (ci-après « CGU ») définissent les modalités d\'utilisation des services proposés sur le site Momo Travel (ci-après « le Site »). Toute utilisation du Site implique l\'acceptation pleine et entière des présentes CGU.</p>
<h3>2. Accès au Service</h3>
<p>L\'accès au Site est gratuit. La création d\'un compte est nécessaire pour effectuer une réservation. L\'utilisateur s\'engage à fournir des informations exactes et à les maintenir à jour.</p>
<h3>3. Services proposés</h3>
<p>Momo Travel est une plateforme de réservation de voyages mettant en relation des voyageurs avec des agences de voyage partenaires. Les services comprennent la recherche, la comparaison et la réservation de séjours touristiques.</p>
<h3>4. Obligations de l\'Utilisateur</h3>
<p>L\'utilisateur s\'engage à utiliser le Site de manière licite et à ne pas perturber son fonctionnement. Tout usage abusif, frauduleux ou malveillant entraînera la suspension immédiate du compte.</p>
<h3>5. Données personnelles</h3>
<p>Les données personnelles collectées sont traitées conformément à notre Politique de Confidentialité et au RGPD. Vous disposez d\'un droit d\'accès, de rectification et de suppression de vos données. Pour exercer ces droits, contactez-nous via le <a href="contact">formulaire de contact</a>.</p>
<h3>6. Paiement</h3>
<p>Les paiements sont effectués de manière sécurisée en ligne. Nous ne stockons jamais vos coordonnées bancaires complètes — seuls les 4 derniers chiffres sont conservés à des fins de facturation.</p>
<h3>7. Propriété intellectuelle</h3>
<p>L\'ensemble du contenu du Site (textes, images, logos) est protégé par le droit de la propriété intellectuelle. Toute reproduction sans autorisation écrite préalable est interdite.</p>
<h3>8. Limitation de responsabilité</h3>
<p>Momo Travel agit en qualité d\'intermédiaire entre les voyageurs et les agences. La responsabilité de Momo Travel ne saurait être engagée en cas de manquement de l\'agence prestataire, dans les limites permises par la loi applicable.</p>
<h3>9. Droit applicable</h3>
<p>Les présentes CGU sont soumises au droit français. Tout litige sera soumis aux tribunaux compétents de Paris.</p>';
    }
}

$title = $lang === 'en' ? 'Terms of Service' : 'Conditions Générales d\'Utilisation';
$page_title = $title . ' — Momo Travel';
include 'header.php';
?>

<main id="main-content">
<div class="info-page-container">
    <section class="info-page-hero" aria-labelledby="cgu-heading">
        <h1 id="cgu-heading"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (!empty($updated_at)): ?>
        <p class="legal-updated">
            <?= $lang === 'en' ? 'Last updated:' : 'Dernière mise à jour :' ?>
            <?= htmlspecialchars(date($lang === 'en' ? 'F j, Y' : 'd/m/Y', strtotime($updated_at)), ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php endif; ?>
    </section>

    <article class="legal-content" aria-label="<?= $lang === 'en' ? 'Terms of Service content' : 'Contenu des CGU' ?>">
        <?= $content /* admin-managed HTML */ ?>
    </article>

    <nav class="legal-nav" aria-label="<?= $lang === 'en' ? 'Other legal pages' : 'Autres pages légales' ?>">
        <a href="mentions"><?= $lang === 'en' ? '→ Legal Notice' : '→ Mentions légales' ?></a>
        <a href="faq"><?= $lang === 'en' ? '→ FAQ' : '→ FAQ' ?></a>
    </nav>
</div>
</main>

<?php include 'footer.php'; ?>
