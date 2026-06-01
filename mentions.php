<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$lang = $_SESSION['language'] ?? 'fr';
$content = '';

try {
    $stmt = $pdo->prepare("SELECT content_fr, content_en, updated_at FROM legal_page WHERE slug = 'mentions'");
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
        $content = '<h2>Legal Notice</h2>
<p>In accordance with the provisions of French law n°2004-575 of June 21, 2004 on confidence in the digital economy, users of the Momo Travel website are hereby informed of the identity of the various parties involved in its creation and monitoring.</p>
<h3>Publisher</h3>
<p><strong>Momo Travel</strong><br>
A simplified joint-stock company (SAS) with a capital of €10,000<br>
Registered at the Paris Trade and Companies Register<br>
Headquarters: Paris, France<br>
Email: <a href="mailto:contact@momo-travel.com">contact@momo-travel.com</a></p>
<h3>Hosting</h3>
<p>This site is hosted by <strong>alwaysdata</strong>, 91 rue du Faubourg Saint-Honoré, 75008 Paris, France.</p>
<h3>Intellectual Property</h3>
<p>All content on this site (texts, images, graphics, logos, sounds, software) is protected under copyright law. Any reproduction or distribution, in whole or in part, without the prior written consent of the publisher is prohibited.</p>
<h3>Personal Data</h3>
<p>In accordance with the GDPR and French Data Protection Act, you have the right to access, rectify, and delete your personal data. To exercise these rights, please contact us at <a href="mailto:privacy@momo-travel.com">privacy@momo-travel.com</a>. You may also file a complaint with the CNIL (www.cnil.fr).</p>
<h3>Cookies</h3>
<p>This site uses cookies necessary for its operation. You can configure your browser to refuse cookies; some features of the site may then no longer function.</p>
<h3>Applicable Law</h3>
<p>These legal notices are governed by French law. Any disputes shall be referred to the competent courts of Paris.</p>';
    } else {
        $content = '<h2>Mentions Légales</h2>
<p>Conformément aux dispositions de la loi n°2004-575 du 21 juin 2004 pour la confiance en l\'économie numérique, il est précisé aux utilisateurs du site Momo Travel l\'identité des différents intervenants dans le cadre de sa réalisation et de son suivi.</p>
<h3>Éditeur</h3>
<p><strong>Momo Travel</strong><br>
Société par actions simplifiée (SAS) au capital de 10 000 €<br>
Immatriculée au Registre du Commerce et des Sociétés de Paris<br>
Siège social : Paris, France<br>
Email : <a href="mailto:contact@momo-travel.com">contact@momo-travel.com</a></p>
<h3>Hébergement</h3>
<p>Ce site est hébergé par <strong>alwaysdata</strong>, 91 rue du Faubourg Saint-Honoré, 75008 Paris, France.</p>
<h3>Propriété intellectuelle</h3>
<p>L\'ensemble du contenu du site (textes, images, graphismes, logo, sons, logiciels) est protégé par les lois relatives à la propriété intellectuelle. Toute reproduction ou diffusion, en tout ou partie, sans accord écrit préalable de l\'éditeur est interdite.</p>
<h3>Données personnelles</h3>
<p>Conformément au RGPD et à la loi Informatique et Libertés, vous disposez d\'un droit d\'accès, de rectification et de suppression de vos données personnelles. Pour exercer ces droits, contactez-nous à l\'adresse <a href="mailto:privacy@momo-travel.com">privacy@momo-travel.com</a>. Vous pouvez également déposer une réclamation auprès de la CNIL (www.cnil.fr).</p>
<h3>Cookies</h3>
<p>Ce site utilise des cookies nécessaires à son fonctionnement. Vous pouvez configurer votre navigateur pour refuser les cookies ; certaines fonctionnalités du site pourraient alors ne plus être disponibles.</p>
<h3>Droit applicable</h3>
<p>Les présentes mentions légales sont soumises au droit français. Tout litige sera soumis aux tribunaux compétents de Paris.</p>';
    }
}

$title = $lang === 'en' ? 'Legal Notice' : 'Mentions Légales';
$page_title = $title . ' — Momo Travel';
include 'header.php';
?>

<main id="main-content">
    <section class="hero" style="padding: 4rem 2%;">
        <h1 id="mentions-heading"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if (!empty($updated_at)): ?>
            <p class="legal-updated">
                <?= $lang === 'en' ? 'Last updated:' : 'Dernière mise à jour :' ?>
                <?= htmlspecialchars(date($lang === 'en' ? 'F j, Y' : 'd/m/Y', strtotime($updated_at)), ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
    </section>
    <div class="info-page-container">
        <article class="legal-content" aria-label="<?= $lang === 'en' ? 'Legal notice content' : 'Contenu des mentions légales' ?>">
            <?= $content /* admin-managed HTML */ ?>
        </article>

        <nav class="legal-nav" aria-label="<?= $lang === 'en' ? 'Other legal pages' : 'Autres pages légales' ?>">
            <a href="cgu"><?= $lang === 'en' ? '→ Terms of Service' : '→ CGU' ?></a>
            <a href="faq"><?= $lang === 'en' ? '→ FAQ' : '→ FAQ' ?></a>
        </nav>
    </div>
</main>

<?php include 'footer.php'; ?>
