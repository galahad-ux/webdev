<?php 
session_start();
require_once __DIR__ . '/../config/db_connect.php';

$lang = $_SESSION['language'] ?? 'fr';

// --- RÉCUPÉRATION DES DESTINATIONS DEPUIS LA BDD ---
$destQuery = $pdo->prepare("
    SELECT d.destination_id, d.image_url, dt.name, 
           COALESCE(dt.slogan, (SELECT slogan FROM destination_translation WHERE destination_id = d.destination_id AND language_code = 'en')) as slogan
    FROM destination d
    JOIN destination_translation dt ON d.destination_id = dt.destination_id
    WHERE dt.language_code = :lang
    LIMIT 6
");
$destQuery->execute(['lang' => $lang]);
$destinations = $destQuery->fetchAll();

// --- RÉCUPÉRATION DES AVIS ---
$reviewQuery = $pdo->prepare("
    SELECT r.*, u.name as username, u.role 
    FROM review r
    JOIN user u ON r.user_id = u.user_id
    WHERE r.status = 'published'
    ORDER BY r.created_at DESC
    LIMIT 3
");
$reviewQuery->execute();
$reviews = $reviewQuery->fetchAll();

$page_title = 'Momo - Book your next journey'; 

// Petite logique de traduction pour le bouton
$explore_text = ($lang == 'fr') ? "Explorer les séjours" : "Explore stays";
$our_dest_text = ($lang == 'fr') ? "Nos destinations" : "Our destinations";
// =========================================================================
// 2. RÉCUPÉRATION DES DONNÉES ET TRADUCTIONS
// =========================================================================

$lang = $_SESSION['language'] ?? 'fr';

$translations = [
    'fr' => [
        'hero_title' => "Réservez votre prochain voyage",
        'hero_subtitle' => "Trouvez un logement, des visites et bien plus encore pour votre destination suivante",
        'search_btn' => "Rechercher",
        'reviews_subtitle' => "Parcourez les avis de nos clients",
        'reviews_title' => "Avis",
    ],
    'en' => [
        'hero_title' => "Book your next journey",
        'hero_subtitle' => "Find housing, tours, and more for your next destination",
        'search_btn' => "Search",
        'reviews_subtitle' => "Browse through our clients’ feedback",
        'reviews_title' => "Reviews",
    ]
];
$t = $translations[$lang];

include 'header.php'; 
?>

<section class="hero">
    <h1><?= $translations[$lang]['hero_title'] ?></h1>
    <p><?= $translations[$lang]['hero_subtitle'] ?></p>
    
    <form action="book" method="GET" class="search-bar">
        <input type="text" name="city" placeholder="City, hotel, neighborhood...">
        <!-- <input type="date" name="date" style="border-left: 1px solid #ddd; flex-grow: 0.5;"> -->
        <button type="submit"><?= $translations[$lang]['search_btn'] ?></button>
    </form>
</section>

<main class="destinations">
    <h2>Destinations</h2>
    <p><?= $our_dest_text ?></p>

    <div class="grid">
        <?php foreach($destinations as $dest): ?>
            <article class="card">
                <img src="<?= htmlspecialchars($dest['image_url'] ?? 'images/destinations/default.webp', ENT_QUOTES, 'UTF-8') ?>" 
                     alt="<?= htmlspecialchars($dest['name'], ENT_QUOTES, 'UTF-8') ?>" 
                     fetchpriority="high">
                
                <div class="card-content">
                    <h3><?= htmlspecialchars($dest['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($dest['slogan'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <p style="margin-top: 10px;">
                        <a href="book?city=<?= urlencode($dest['name']) ?>" class="read-more" style="font-size: 0.9rem;">
                            <?= $explore_text ?> &rarr;
                        </a>
                    </p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>

<section class="reviews-section">
    <h2><?= $translations[$lang]['reviews_title'] ?></h2>
    <p class="reviews-subtitle"><?= $translations[$lang]['reviews_subtitle'] ?></p>
    <div class="reviews-grid">
        <?php foreach($reviews as $rev): ?>
            <article class="review-card">
                <div class="user-info">
                    <span class="username"><?= htmlspecialchars($rev['username'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <h3>“<?= htmlspecialchars($rev['title'], ENT_QUOTES, 'UTF-8') ?>”</h3>
                <p class="review-text"><?= htmlspecialchars($rev['comment'], ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'footer.php'; ?>