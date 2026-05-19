<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$lang = $_SESSION['language'] ?? 'fr';

$faqs = [];
try {
    $stmt = $pdo->query("SELECT * FROM faq WHERE is_published = 1 ORDER BY sort_order ASC, faq_id ASC");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table not yet created — defaults used below
}

if (empty($faqs)) {
    $faqs = [
        ['faq_id' => 1,
         'question_fr' => 'Comment puis-je réserver un voyage ?',
         'answer_fr'   => 'Rendez-vous sur la page <a href="book">Book</a>, sélectionnez votre destination et suivez les étapes de réservation. Un compte est nécessaire pour finaliser votre réservation.',
         'question_en' => 'How can I book a trip?',
         'answer_en'   => 'Go to the <a href="book">Book</a> page, select your destination and follow the booking steps. An account is required to complete your booking.'],
        ['faq_id' => 2,
         'question_fr' => 'Puis-je annuler ou modifier ma réservation ?',
         'answer_fr'   => 'Pour toute modification, contactez-nous via le <a href="contact">formulaire de contact</a> ou ouvrez un ticket depuis votre <a href="user_page">espace personnel</a>.',
         'question_en' => 'Can I cancel or modify my booking?',
         'answer_en'   => 'For any modification, contact us via the <a href="contact">contact form</a> or open a ticket from your <a href="user_page">personal space</a>.'],
        ['faq_id' => 3,
         'question_fr' => 'Comment fonctionne le paiement ?',
         'answer_fr'   => 'Le paiement est sécurisé et effectué en ligne par carte bancaire. Seuls les 4 derniers chiffres de votre carte sont conservés pour votre facture.',
         'question_en' => 'How does payment work?',
         'answer_en'   => 'Payment is secure and processed online by credit/debit card. Only the last 4 digits of your card are kept for your invoice.'],
        ['faq_id' => 4,
         'question_fr' => 'Qu\'est-ce qui est inclus dans le prix du voyage ?',
         'answer_fr'   => 'Le prix comprend les éléments listés dans la section « Inclusions » de chaque voyage (hébergement, transport, activités). Les repas et dépenses personnelles sont généralement en supplément.',
         'question_en' => 'What is included in the trip price?',
         'answer_en'   => 'The price includes the items listed in the "Inclusions" section of each trip (accommodation, transport, activities). Meals and personal expenses are generally extra.'],
        ['faq_id' => 5,
         'question_fr' => 'Comment contacter le service client ?',
         'answer_fr'   => 'Vous pouvez nous contacter via le <a href="contact">formulaire de contact</a> ou en ouvrant un ticket depuis votre <a href="user_page">espace personnel</a>.',
         'question_en' => 'How can I contact customer support?',
         'answer_en'   => 'You can reach us via the <a href="contact">contact form</a> or by opening a ticket from your <a href="user_page">personal space</a>.'],
        ['faq_id' => 6,
         'question_fr' => 'Mes données personnelles sont-elles sécurisées ?',
         'answer_fr'   => 'Oui. Nous ne stockons jamais vos données bancaires complètes. Vos informations sont protégées conformément au RGPD. Consultez nos <a href="mentions">Mentions légales</a> pour plus de détails.',
         'question_en' => 'Is my personal data secure?',
         'answer_en'   => 'Yes. We never store your full banking details. Your information is protected in compliance with GDPR. See our <a href="mentions">Legal Notice</a> for details.'],
    ];
}

$t = [
    'fr' => ['title' => 'Foire Aux Questions', 'subtitle' => 'Trouvez des réponses à vos questions les plus fréquentes.', 'no_faq' => 'Aucune question disponible pour le moment.', 'cta' => 'Vous n\'avez pas trouvé votre réponse ?', 'contact' => 'Contactez-nous'],
    'en' => ['title' => 'Frequently Asked Questions', 'subtitle' => 'Find answers to your most common questions.', 'no_faq' => 'No questions available at the moment.', 'cta' => 'Didn\'t find your answer?', 'contact' => 'Contact Us'],
][$lang] ?? ['title' => 'FAQ', 'subtitle' => '', 'no_faq' => '', 'cta' => '', 'contact' => 'Contact'];

$page_title = $t['title'] . ' — Momo Travel';
include 'header.php';
?>

<main id="main-content">
<div class="info-page-container">
    <section class="info-page-hero" aria-labelledby="faq-heading">
        <h1 id="faq-heading"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($t['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
    </section>

    <section class="faq-section" aria-label="<?= $lang === 'en' ? 'Frequently asked questions' : 'Questions fréquentes' ?>">
        <?php if (empty($faqs)): ?>
            <p class="faq-empty"><?= htmlspecialchars($t['no_faq'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <div class="faq-list" role="list">
                <?php foreach ($faqs as $faq):
                    $q = $lang === 'en' ? ($faq['question_en'] ?? $faq['question_fr']) : $faq['question_fr'];
                    $a = $lang === 'en' ? ($faq['answer_en']   ?? $faq['answer_fr'])   : $faq['answer_fr'];
                    $bid = 'faq-btn-' . (int)$faq['faq_id'];
                    $cid = 'faq-ans-' . (int)$faq['faq_id'];
                ?>
                <div class="faq-item" role="listitem">
                    <button class="faq-question"
                            id="<?= $bid ?>"
                            aria-expanded="false"
                            aria-controls="<?= $cid ?>"
                            type="button">
                        <span><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="faq-icon" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer"
                         id="<?= $cid ?>"
                         role="region"
                         aria-labelledby="<?= $bid ?>"
                         hidden>
                        <p><?= $a /* admin-managed HTML */ ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="info-page-cta" aria-label="<?= $lang === 'en' ? 'Need more help' : 'Besoin d\'aide' ?>">
        <p><?= htmlspecialchars($t['cta'], ENT_QUOTES, 'UTF-8') ?></p>
        <a href="contact" class="btn-dashboard" style="width:auto;display:inline-block;padding:0.8rem 2.5rem;">
            <?= htmlspecialchars($t['contact'], ENT_QUOTES, 'UTF-8') ?>
        </a>
    </section>
</div>
</main>

<script>
(function () {
    var btns = document.querySelectorAll('.faq-question');
    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            var content  = document.getElementById(this.getAttribute('aria-controls'));
            var icon     = this.querySelector('.faq-icon');

            btns.forEach(function (other) {
                if (other !== btn) {
                    other.setAttribute('aria-expanded', 'false');
                    var oc = document.getElementById(other.getAttribute('aria-controls'));
                    var oi = other.querySelector('.faq-icon');
                    if (oc) oc.hidden = true;
                    if (oi) oi.textContent = '+';
                }
            });

            this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            content.hidden = expanded;
            icon.textContent = expanded ? '+' : '−';
        });

        btn.addEventListener('keydown', function (e) {
            var arr = Array.from(btns);
            var idx = arr.indexOf(this);
            if (e.key === 'ArrowDown' && arr[idx + 1]) { e.preventDefault(); arr[idx + 1].focus(); }
            if (e.key === 'ArrowUp'   && arr[idx - 1]) { e.preventDefault(); arr[idx - 1].focus(); }
        });
    });
}());
</script>

<?php include 'footer.php'; ?>
