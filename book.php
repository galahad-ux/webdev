<?php 
// =========================================================================
// 1. ZONE CONTRÔLEUR : SÉCURITÉ MAXIMALE ET LOGIQUE
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/db_connect.php'; 

// =========================================================================
// 2. RÉCUPÉRATION DES DONNÉES ET TRADUCTIONS
// =========================================================================

$lang = $_SESSION['language'] ?? 'fr';
$city_filter = $_GET['city'] ?? '';

// --- LE NOUVEAU SYSTÈME DE TRADUCTION (DICTIONNAIRE) ---
$translations = [
    'fr' => [
        'hero_title' => "Explorer les séjours",
        'search_btn' => "Rechercher",
        'filters' => "Filtres",
        'price' => "Prix",
        'type' => "Type de logement",
        'rating' => "Note minimum",
        'city' => "Villes",
        'hotels' => "Hôtels",
        'apartments' => "Appartements",
        'villas' => "Villas",
        'guest_houses' => "Maisons d'hôtes",
        'superb' => "Superbe",
        'very_good' => "Très bien",
        'good' => "Bien",
        'found' => "logements trouvés",
        'not_found' => "Aucun logement trouvé pour cette destination.",
        'see_avail' => "Voir la disponibilité"
    ],
    'en' => [
        'hero_title' => "Find your perfect stay",
        'search_btn' => "Search",
        'filters' => "Filter by",
        'price' => "Price range",
        'type' => "Property type",
        'rating' => "Minimum rating",
        'city' => "Cities",
        'hotels' => "Hotels",
        'apartments' => "Apartments",
        'villas' => "Villas",
        'guest_houses' => "Guest houses",
        'superb' => "Superb",
        'very_good' => "Very good",
        'good' => "Good",
        'found' => "properties found",
        'not_found' => "No properties found for this destination.",
        'see_avail' => "See availability"
    ]
];

// On stocke le dictionnaire de la bonne langue dans $t pour l'utiliser facilement dans le HTML
$t = $translations[$lang];

// Requête pour récupérer les logements ET leurs traductions ET les villes
$sql = "
    SELECT a.*, 
           act.name AS name, 
           act.description AS description, 
           dt.name AS city_name
    FROM accommodation a
    LEFT JOIN accommodation_translation act 
        ON a.accommodation_id = act.accommodation_id 
        AND act.language_code = :lang1
    LEFT JOIN destination_translation dt 
        ON a.destination_id = dt.destination_id 
        AND dt.language_code = :lang2
";
try {
    $stmt = $pdo->prepare($sql);
    // On passe deux variables distinctes pour éviter le blocage de PDO
    $stmt->execute([
        'lang1' => $lang, 
        'lang2' => $lang
    ]);
    $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si tu veux voir l'erreur s'afficher à l'écran pour déboguer, tu peux décommenter la ligne ci-dessous :
    // echo "Erreur SQL : " . $e->getMessage();
    error_log("Erreur Book.php : " . $e->getMessage());
    $accommodations = []; 
}

// Extraction automatique des villes existantes pour créer le filtre dynamiquement
$available_cities = array_unique(array_column($accommodations, 'city_name'));
// On enlève les éventuelles villes vides
$available_cities = array_filter($available_cities); 
sort($available_cities);

// =========================================================================
// 3. ZONE VUE (HTML & AFFICHAGE)
// =========================================================================

$page_title = 'Momo - Book'; 
$page_description = 'Find housing, tours, and more for your next destination with Momo Travel.';
include 'header.php'; 
?>

<style>
    .booking-container { max-width: 100% !important; }
    .map-section { width: 40%; min-width: 300px; }

    .resizer {
        width: 8px; 
        background-color: transparent;
        cursor: col-resize;
        position: sticky;
        top: 100px;
        height: calc(100vh - 120px); 
        border-radius: 4px;
        transition: background-color 0.2s;
        flex-shrink: 0;
        margin: 0 10px; 
    }

    .resizer:hover, .resizer.dragging {
        background-color: #c1272d; 
    }

    body.is-resizing {
        user-select: none;
        cursor: col-resize;
    }
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<section class="hero" style="padding: 4rem 2%;">
    <h1><?= $t['hero_title'] ?></h1>
    <div class="search-bar">
        <input type="text" id="search-input" 
               value="<?= htmlspecialchars($city_filter, ENT_QUOTES, 'UTF-8') ?>" 
               aria-label="Destination" placeholder="City, hotel, neighborhood...">
        <!-- <input type="date" aria-label="Travel dates" style="border-left: 1px solid #ddd; flex-grow: 0.5;"> -->
        <button><?= $t['search_btn'] ?></button>
    </div>
</section>

<main class="booking-container">

    <aside class="filters">
        <h3><?= $t['filters'] ?></h3>
        
        <?php if(!empty($available_cities)): ?>
        <div class="filter-group">
            <h4><?= $t['city'] ?></h4>
            <?php foreach($available_cities as $city): ?>
                <label>
                    <input type="checkbox" class="filter-city" value="<?= htmlspecialchars(strtolower($city), ENT_QUOTES, 'UTF-8') ?>"> 
                    <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="filter-group">
            <h4><?= $t['price'] ?></h4>
            <label><input type="checkbox" class="filter-price" value="0-50"> €0 - €50</label>
            <label><input type="checkbox" class="filter-price" value="50-100"> €50 - €100</label>
            <label><input type="checkbox" class="filter-price" value="100-200"> €100 - €200</label>
            <label><input type="checkbox" class="filter-price" value="200-2000"> €200+</label>
        </div>

        <div class="filter-group">
            <h4><?= $t['type'] ?></h4>
            <label><input type="checkbox" class="filter-type" value="Hotels"> <?= $t['hotels'] ?></label>
            <label><input type="checkbox" class="filter-type" value="Apartments"> <?= $t['apartments'] ?></label>
            <label><input type="checkbox" class="filter-type" value="Villas"> <?= $t['villas'] ?></label>
            <label><input type="checkbox" class="filter-type" value="Guest houses"> <?= $t['guest_houses'] ?></label>
        </div>

        <div class="filter-group">
            <h4><?= $t['rating'] ?></h4>
            <label><input type="checkbox" class="filter-rating" value="9"> 9+ (<?= $t['superb'] ?>)</label>
            <label><input type="checkbox" class="filter-rating" value="8"> 8+ (<?= $t['very_good'] ?>)</label>
            <label><input type="checkbox" class="filter-rating" value="7"> 7+ (<?= $t['good'] ?>)</label>
        </div>
    </aside>

    <section class="results">
        <div class="results-header">
            <h2>Our Stays</h2>
            <span><?= count($accommodations) ?> <?= $t['found'] ?></span>
        </div>

        <div class="housing-grid">
            <?php if(empty($accommodations)): ?>
                <p style="padding: 2rem; text-align: center; color: #666;"><?= $t['not_found'] ?></p>
            <?php else: ?>
                <?php foreach($accommodations as $acc): ?>
                    <div class="house-card" 
                         data-price="<?= htmlspecialchars($acc['price_per_night'], ENT_QUOTES, 'UTF-8') ?>" 
                         data-type="<?= htmlspecialchars($acc['type'], ENT_QUOTES, 'UTF-8') ?>" 
                         data-rating="<?= htmlspecialchars($acc['rating'], ENT_QUOTES, 'UTF-8') ?>"
                         data-location="<?= htmlspecialchars(strtolower($acc['city_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="house-img">
                            <img src="<?= htmlspecialchars($acc['image_url'] ?? 'images/book/hotel/default.webp', ENT_QUOTES, 'UTF-8') ?>" alt="Hotel" fetchpriority="high">
                            <?php if($acc['rating'] >= 9.0): ?>
                                <span class="badge">Top Rated</span>
                            <?php endif; ?>
                        </div>
                        <div class="house-info">
                            <div class="house-title">
                                <h3><?= htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="rating"><?= htmlspecialchars($acc['rating'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="location"><?= htmlspecialchars($acc['city_name'] ?? 'Unknown Location', ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="description"><?= htmlspecialchars($acc['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="price-box">
                                <span class="price">€<?= htmlspecialchars($acc['price_per_night'], ENT_QUOTES, 'UTF-8') ?> / night</span>
                                
                                <form method="POST" action="process_booking.php" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="accommodation_id" value="<?= $acc['accommodation_id'] ?>">
                                    <button type="submit" class="btn-book"><?= $t['see_avail'] ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="resizer" id="dragMe" title="Redimensionner la carte"></div>

    <section class="map-section" id="mapContainer">
        <div id="map"></div>
    </section>

</main>

<script>
    // --- 1. INITIALISATION DE LA CARTE ---
    var map = L.map('map').setView([48.8566, 2.3522], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var mapDiv = document.getElementById('map');
    var resizeObserver = new ResizeObserver(function() {
        map.invalidateSize();
    });
    resizeObserver.observe(mapDiv);

    // --- 2. PLACEMENT DYNAMIQUE DES MARQUEURS ---
    var accommodations = <?= json_encode($accommodations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    if (accommodations.length > 0 && accommodations[0].latitude && accommodations[0].longitude) {
        map.setView([accommodations[0].latitude, accommodations[0].longitude], 12);
    }

    accommodations.forEach(function(acc) {
        if (acc.latitude && acc.longitude) {
            var safeName = acc.name.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            var safePrice = parseFloat(acc.price_per_night).toFixed(2);
            var marker = L.marker([acc.latitude, acc.longitude]).addTo(map);
            marker.bindPopup("<b>" + safeName + "</b><br>€" + safePrice + " / night");
        }
    });

    // --- 3. SCRIPT DE FILTRAGE AMÉLIORÉ ---
    document.addEventListener("DOMContentLoaded", function() {
        // On cible tous les types de filtres maintenant
        const checkboxes = document.querySelectorAll('.filter-price, .filter-type, .filter-city, .filter-rating');
        const houses = document.querySelectorAll('.house-card');
        const searchInput = document.getElementById('search-input'); 

        function filterResults() {
            // Récupération de tous les choix actifs
            const activePrices = Array.from(document.querySelectorAll('.filter-price:checked')).map(cb => cb.value);
            const activeTypes = Array.from(document.querySelectorAll('.filter-type:checked')).map(cb => cb.value);
            const activeCities = Array.from(document.querySelectorAll('.filter-city:checked')).map(cb => cb.value);
            const activeRatings = Array.from(document.querySelectorAll('.filter-rating:checked')).map(cb => parseFloat(cb.value));
            
            const searchText = searchInput.value.toLowerCase();

            houses.forEach(house => {
                const price = parseFloat(house.getAttribute('data-price'));
                const type = house.getAttribute('data-type');
                const rating = parseFloat(house.getAttribute('data-rating')) || 0; // Nouvel attribut !
                const location = house.getAttribute('data-location') || "";
                const hotelName = house.querySelector('h3').innerText.toLowerCase();

                // 1. Recherche par texte
                const searchMatch = hotelName.includes(searchText) || location.includes(searchText);
                
                // 2. Filtre Prix
                let priceMatch = activePrices.length === 0; 
                activePrices.forEach(range => {
                    const [min, max] = range.split('-').map(Number);
                    if (price >= min && price <= max) priceMatch = true;
                });

                // 3. Filtre Type
                let typeMatch = activeTypes.length === 0 || activeTypes.includes(type);

                // 4. Filtre Ville (Checkbox)
                let cityMatch = activeCities.length === 0 || activeCities.includes(location);

                // 5. Filtre Note
                // Si l'utilisateur coche "8+" et "9+", on doit afficher tout ce qui est >= 8
                let ratingMatch = activeRatings.length === 0;
                if (activeRatings.length > 0) {
                    const minRatingRequired = Math.min(...activeRatings);
                    if (rating >= minRatingRequired) ratingMatch = true;
                }

                // Affichage final : il faut que TOUTES les conditions soient respectées
                if (searchMatch && priceMatch && typeMatch && cityMatch && ratingMatch) {
                    house.style.display = "";
                } else {
                    house.style.display = "none";
                }
            });
        }
        
        if (searchInput.value !== "") {
            filterResults();
        }

        checkboxes.forEach(cb => cb.addEventListener('change', filterResults));
        searchInput.addEventListener('input', filterResults);
    }); 

    // --- 4. SCRIPT DE REDIMENSIONNEMENT DE LA CARTE ---
    const resizer = document.getElementById('dragMe');
    const mapContainer = document.getElementById('mapContainer');
    let isDragging = false;

    resizer.addEventListener('mousedown', function(e) {
        isDragging = true;
        resizer.classList.add('dragging');
        document.body.classList.add('is-resizing'); 
    });

    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;

        const container = document.querySelector('.booking-container');
        const containerRect = container.getBoundingClientRect();
        let newWidth = containerRect.right - e.clientX - 25; 
        const minWidth = 300; 
        const maxWidth = containerRect.width * 0.65; 

        if (newWidth < minWidth) newWidth = minWidth;
        if (newWidth > maxWidth) newWidth = maxWidth;

        mapContainer.style.width = newWidth + 'px';
        mapContainer.style.flex = `0 0 ${newWidth}px`; 
        map.invalidateSize();
    });

    document.addEventListener('mouseup', function(e) {
        if (isDragging) {
            isDragging = false;
            resizer.classList.remove('dragging');
            document.body.classList.remove('is-resizing');
        }
    });
</script>

<?php include 'footer.php'; ?>