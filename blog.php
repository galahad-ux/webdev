<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$lang = $_SESSION['language'] ?? 'fr';

$t = [
    'fr' => [
        'page_title' => 'Momo - Blog',
        'hero'       => 'Conseils Voyage & Récits',
        'all'        => 'Tout',
        'tips'       => 'Conseils & Astuces',
        'dest'       => 'Destinations',
        'food'       => 'Gastronomie & Culture',
        'read_more'  => 'Lire l\'article →',
        'articles'   => [
            [
                'img'      => 'images/destinations/New York/NYC.webp',
                'alt'      => 'Astuces vol',
                'cat'      => 'Conseils & Astuces',
                'date'     => '3 avril 2026',
                'title'    => 'Comment économiser sur vos billets d\'avion',
                'excerpt'  => 'Fatigué de toujours rater les promotions ? On vous révèle les meilleurs jours pour réserver et les outils secrets que les compagnies aériennes ne veulent pas que vous connaissiez.',
            ],
            [
                'img'      => 'images/destinations/Paris/Paris.webp',
                'alt'      => 'Trésors cachés Paris',
                'cat'      => 'Destinations',
                'date'     => '28 mars 2026',
                'title'    => '10 trésors cachés à Paris loin des foules',
                'excerpt'  => 'Évitez les files de la Tour Eiffel et découvrez les passages secrets, boulangeries locales et parcs tranquilles que les vrais Parisiens adorent.',
            ],
            [
                'img'      => 'images/destinations/Tokyo/Tokyo.webp',
                'alt'      => 'Street food Tokyo',
                'cat'      => 'Gastronomie & Culture',
                'date'     => '15 mars 2026',
                'title'    => 'Guide du débutant pour la street food de Tokyo',
                'excerpt'  => 'Du Takoyaki au Taiyaki, plongez dans les rues animées de Tokyo et découvrez ce que vous devez absolument goûter.',
            ],
            [
                'img'      => 'images/destinations/Londres/London.webp',
                'alt'      => 'Bagages Londres',
                'cat'      => 'Conseils & Astuces',
                'date'     => '2 mars 2026',
                'title'    => 'L\'essentiel à emporter pour un voyage pluvieux à Londres',
                'excerpt'  => 'Gardez votre calme et restez au sec. Voici notre liste ultime pour que la météo britannique ne gâche pas votre escapade.',
            ],
            [
                'img'      => 'images/destinations/Cairo/Cairo.webp',
                'alt'      => 'Pyramides du Caire',
                'cat'      => 'Destinations',
                'date'     => '18 février 2026',
                'title'    => 'Marcher parmi les Pharaons : itinéraire au Caire',
                'excerpt'  => 'Profitez au maximum de vos 3 jours dans la capitale égyptienne avec notre itinéraire optimisé couvrant tous les incontournables.',
            ],
            [
                'img'      => 'images/other/Airplane.webp',
                'alt'      => 'Voyage durable',
                'cat'      => 'Conseils & Astuces',
                'date'     => '5 février 2026',
                'title'    => 'Comment voyager de manière plus durable en 2026',
                'excerpt'  => 'Le voyage éco-responsable est plus simple qu\'on ne le croit. Découvrez des habitudes simples pour réduire votre empreinte tout en explorant le monde.',
            ],
        ],
    ],
    'en' => [
        'page_title' => 'Momo - Blog',
        'hero'       => 'Travel Tips & Stories',
        'all'        => 'All',
        'tips'       => 'Tips & Tricks',
        'dest'       => 'Destinations',
        'food'       => 'Food & Culture',
        'read_more'  => 'Read article →',
        'articles'   => [
            [
                'img'      => 'images/destinations/New York/NYC.webp',
                'alt'      => 'Flight tips',
                'cat'      => 'Tips & Tricks',
                'date'     => 'April 3, 2026',
                'title'    => 'How to save money on your flight tickets',
                'excerpt'  => 'Tired of always missing discounts? We reveal the best days to book and the secret tools airlines don\'t want you to know about.',
            ],
            [
                'img'      => 'images/destinations/Paris/Paris.webp',
                'alt'      => 'Paris Hidden Gems',
                'cat'      => 'Destinations',
                'date'     => 'March 28, 2026',
                'title'    => '10 hidden gems in Paris away from the crowds',
                'excerpt'  => 'Skip the Eiffel Tower lines and discover the secret passages, local bakeries, and quiet parks that true Parisians love.',
            ],
            [
                'img'      => 'images/destinations/Tokyo/Tokyo.webp',
                'alt'      => 'Tokyo Food Guide',
                'cat'      => 'Food & Culture',
                'date'     => 'March 15, 2026',
                'title'    => 'A beginner\'s guide to Tokyo street food',
                'excerpt'  => 'From Takoyaki to Taiyaki, dive into the bustling streets of Tokyo and find out what you absolutely need to taste.',
            ],
            [
                'img'      => 'images/destinations/Londres/London.webp',
                'alt'      => 'London Packing',
                'cat'      => 'Tips & Tricks',
                'date'     => 'March 02, 2026',
                'title'    => 'Packing essentials for a rainy London trip',
                'excerpt'  => 'Keep calm and stay dry. Here is our ultimate packing list so the British weather doesn\'t ruin your city break.',
            ],
            [
                'img'      => 'images/destinations/Cairo/Cairo.webp',
                'alt'      => 'Cairo Pyramids',
                'cat'      => 'Destinations',
                'date'     => 'February 18, 2026',
                'title'    => 'Walking among the Pharaohs: Cairo itinerary',
                'excerpt'  => 'Make the most out of your 3-day trip to Egypt\'s capital with our optimized itinerary covering all the must-sees.',
            ],
            [
                'img'      => 'images/other/Airplane.webp',
                'alt'      => 'Sustainable travel',
                'cat'      => 'Tips & Tricks',
                'date'     => 'February 05, 2026',
                'title'    => 'How to travel more sustainably in 2026',
                'excerpt'  => 'Eco-friendly travel is easier than you think. Discover simple habits to reduce your footprint while exploring the globe.',
            ],
        ],
    ],
][$lang];

$page_title = $t['page_title'];
$page_description = $lang === 'fr'
    ? 'Conseils de voyage et récits du monde entier par Momo Travel.'
    : 'Read our latest travel tips and stories from around the world.';
include 'header.php';
?>

<section class="hero">
    <h1><?= $t['hero'] ?></h1>
</section>

<main class="blog-section">
    <div class="blog-filters">
        <button class="active"><?= $t['all'] ?></button>
        <button><?= $t['tips'] ?></button>
        <button><?= $t['dest'] ?></button>
        <button><?= $t['food'] ?></button>
    </div>

    <div class="blog-grid">
        <?php foreach ($t['articles'] as $article): ?>
        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="<?= htmlspecialchars($article['img'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($article['alt'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="blog-category"><?= htmlspecialchars($article['cat'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="blog-content">
                <span class="blog-date"><?= htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8') ?></span>
                <h3><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($article['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
                <a href="#" class="read-more"><?= $t['read_more'] ?></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
