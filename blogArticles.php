<?php
// Skeleton page: content is expected to be fetched from the database.
// Modeled after `trip.php` (session + DB include + prepared query + fallback UI).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// NOTE: the blog article controller/model is not present in the repository.
// This file provides the DB skeleton; you only need to adapt the SQL to your schema.
require_once __DIR__ . '/../../config/db_connect.php';

$lang = $_SESSION['language'] ?? 'fr';
$article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Fallback: if no id is provided, show a “not found” style redirect.
if (!$article_id) {
    header('Location: blog');
    exit();
}




$page_title = 'Momo - Blog Article';
$page_description = 'Blog article (loaded from database).';

// Placeholder for DB fetched values.
$article = null;
$t = [
    'fr' => ['not_found' => 'Article introuvable.', 'back' => '← Retour au blog'],
    'en' => ['not_found' => 'Article not found.', 'back' => '← Back to blog'],
][$lang] ?? [
    'not_found' => 'Article not found.',
    'back' => '← Back to blog'
];


$stmt = $pdo->prepare("SELECT * FROM articles WHERE article_id = ? AND language_code = ? ORDER BY sort_order ASC");
$stmt->execute([$article_id, $lang]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);


include '../header.php';
?>

<section class="hero">
    <h1><?= htmlspecialchars('Blog Article #'.$article_id, ENT_QUOTES, 'UTF-8') ?></h1>
</section>

<main class="blog-section">
    <article class="blog-card" style="max-width: 900px; margin: 0 auto;">
        <div class="blog-img-wrapper">
            <img src="../images/other/Airplane.webp" alt="Blog article cover">
            <span class="blog-category">&nbsp;</span>
        </div>

        <div class="blog-content">
            <span class="blog-date">&nbsp;</span>
            <h3>&nbsp;</h3>

            <?php if (!$article): ?>
                <p><?= htmlspecialchars($t['not_found'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                
                <?= htmlspecialchars($article['published_at'], ENT_QUOTES, 'UTF-8') ?></span> 
                <h3><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3> 
                <p><?= nl2br(htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8')) ?></p> 
            <?php endif; ?>
        </div>
    </article>
</main>

<?php include '../footer.php'; ?>



