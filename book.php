<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/db_connect.php';

$lang = $_SESSION['language'] ?? 'fr';
$city_filter = trim($_GET['city'] ?? '');

$t = [
    'fr' => [
        'hero'       => 'Nos voyages organisés',
        'search_ph'  => 'Destination, nom du voyage...',
        'search_btn' => 'Rechercher',
        'filters'    => 'Filtres',
        'cities'     => 'Destination',
        'price'      => 'Budget / pers.',
        'duration'   => 'Durée',
        'nights_s'   => 'nuit',
        'nights_p'   => 'nuits',
        'short'      => 'Court (1-4 nuits)',
        'medium'     => 'Moyen (5-7 nuits)',
        'long'       => 'Long (8+ nuits)',
        'from'       => 'À partir de',
        'per_person' => '/ pers.',
        'spots_left' => 'places restantes',
        'full'       => 'Complet',
        'see_more'   => 'Voir ce voyage',
        'no_results' => 'Aucun voyage ne correspond à vos critères.',
        'found'      => 'voyage(s) trouvé(s)',
        'next_dep'   => 'Prochain départ :',
        'from_price' => 'Dès',
    ],
    'en' => [
        'hero'       => 'Our organized trips',
        'search_ph'  => 'Destination, trip name...',
        'search_btn' => 'Search',
        'filters'    => 'Filters',
        'cities'     => 'Destination',
        'price'      => 'Budget / person',
        'duration'   => 'Duration',
        'nights_s'   => 'night',
        'nights_p'   => 'nights',
        'short'      => 'Short (1-4 nights)',
        'medium'     => 'Medium (5-7 nights)',
        'long'       => 'Long (8+ nights)',
        'from'       => 'From',
        'per_person' => '/ person',
        'spots_left' => 'spots left',
        'full'       => 'Fully booked',
        'see_more'   => 'View trip',
        'no_results' => 'No trips match your criteria.',
        'found'      => 'trip(s) found',
        'next_dep'   => 'Next departure:',
        'from_price' => 'From',
    ],
][$lang];

// Fetch all published packages with translations and aggregate data
$sql = "
    SELECT
        tp.package_id,
        tp.image_url,
        tp.destination_id,
        COALESCE(tpt.name, tpt_fb.name) AS name,
        COALESCE(tpt.highlights, tpt_fb.highlights) AS highlights,
        dt.name AS city_name,
        d.latitude,
        d.longitude,
        MIN(td.price_per_person) AS min_price,
        MIN(td.departure_date) AS next_departure,
        MIN(td.duration_nights) AS min_nights,
        SUM(td.max_spots - td.booked_spots) AS total_spots_left
    FROM trip_package tp
    LEFT JOIN destination d
        ON tp.destination_id = d.destination_id
    LEFT JOIN trip_package_translation tpt
        ON tp.package_id = tpt.package_id AND tpt.language_code = :lang
    LEFT JOIN trip_package_translation tpt_fb
        ON tp.package_id = tpt_fb.package_id AND tpt_fb.language_code = 'fr'
    LEFT JOIN destination_translation dt
        ON tp.destination_id = dt.destination_id AND dt.language_code = :lang2
    LEFT JOIN trip_date td
        ON tp.package_id = td.package_id
        AND td.status = 'available'
        AND td.departure_date >= CURDATE()
        AND td.booked_spots < td.max_spots
    WHERE tp.status = 'published'
    GROUP BY tp.package_id, tp.image_url, tp.destination_id, tpt.name, tpt.highlights, tpt_fb.name, tpt_fb.highlights, dt.name, d.latitude, d.longitude
    ORDER BY next_departure ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['lang' => $lang, 'lang2' => $lang]);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("book.php SQL error: " . $e->getMessage());
    $packages = [];
}

$available_cities = array_unique(array_filter(array_column($packages, 'city_name')));
sort($available_cities);

$page_title = 'Momo - Book';
$page_description = 'Find your perfect organized trip with Momo Travel.';
include 'header.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .booking-container { max-width: 100% !important; display: flex; gap: 0; }
    .resizer {
        width: 8px; background: transparent; cursor: col-resize;
        position: sticky; top: 100px; height: calc(100vh - 120px);
        border-radius: 4px; transition: background 0.2s; flex-shrink: 0; margin: 0 8px;
    }
    .resizer:hover, .resizer.dragging { background: #c1272d; }
    body.is-resizing { user-select: none; cursor: col-resize; }
    .trip-card {
        display: flex; background: #fff; border: 1px solid #eaeaea; border-radius: 8px;
        overflow: hidden; transition: box-shadow 0.2s; margin-bottom: 1.2rem;
    }
    .trip-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    .trip-card img { width: 220px; min-width: 220px; height: 170px; object-fit: cover; }
    .trip-card-body { padding: 1.2rem; display: flex; flex-direction: column; justify-content: space-between; flex: 1; min-width: 0; }
    .trip-card-body h3 { font-family: 'Playfair Display', serif; font-size: 1.2rem; margin-bottom: 0.3rem; }
    .trip-card-body .city { color: #888; font-size: 0.85rem; margin-bottom: 0.5rem; }
    .trip-card-body .highlights { font-size: 0.85rem; color: #555; margin-bottom: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .trip-card-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .trip-price { font-size: 1.3rem; font-weight: bold; color: #c1272d; }
    .trip-price small { font-size: 0.8rem; color: #888; font-weight: normal; }
    .trip-spots { font-size: 0.8rem; color: #888; }
    .trip-spots.full { color: #c1272d; font-weight: bold; }
    .btn-view { display: inline-block; margin-top: 0.5rem; background: #c1272d; color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; font-size: 0.9rem; }
    .btn-view:hover { background: #a01f24; }
    .filters { margin-right: 1.5rem; }
    .results { min-width: 0 !important; }

    /* Desktop : carte sticky sur le côté */
    @media (min-width: 901px) {
        .map-section { flex: 0 0 35%; width: 35%; }
        #map { height: calc(100vh - 120px); position: sticky; top: 100px; }
    }

    .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0 0.5rem; }

    /* Mobile Layout Override - Bulletproof */
    @media (max-width: 1100px) {
        .booking-container {
            flex-direction: column !important;
            flex-wrap: nowrap !important;
            gap: 2rem !important;
            padding-bottom: 3rem !important;
        }
        .filters {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            position: relative !important;
            top: auto !important;
        }
        .filters h3 { grid-column: 1 / -1; margin-bottom: 0; }
        .results {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        .resizer { display: none !important; }
        .map-section {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 400px !important;
            position: relative !important;
            top: auto !important;
            order: 10 !important;
            margin: 0 !important;
        }
        #map {
            height: 100% !important;
            width: 100% !important;
            position: relative !important;
            top: auto !important;
            z-index: 1 !important;
        }
    }

    @media (max-width: 600px) {
        .trip-card { flex-direction: column; }
        .trip-card img { width: 100%; min-width: unset; height: 180px; }
        .map-section { height: 300px !important; margin-bottom: 3rem !important; }
    }
</style>

<section class="hero" style="padding: 4rem 2%;">
    <h1><?= $t['hero'] ?></h1>
    <div class="search-bar">
        <input type="text" id="search-input"
               value="<?= htmlspecialchars($city_filter, ENT_QUOTES, 'UTF-8') ?>"
               aria-label="Search" placeholder="<?= $t['search_ph'] ?>">
        <button><?= $t['search_btn'] ?></button>
    </div>
</section>

<main class="booking-container">

    <aside class="filters">
        <h3><?= $t['filters'] ?></h3>

        <?php if (!empty($available_cities)): ?>
        <div class="filter-group">
            <h4><?= $t['cities'] ?></h4>
            <?php foreach ($available_cities as $city): ?>
                <label>
                    <input type="checkbox" class="filter-city"
                           value="<?= htmlspecialchars(strtolower($city), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="filter-group">
            <h4><?= $t['price'] ?></h4>
            <label><input type="checkbox" class="filter-price" value="0-500"> &lt; €500</label>
            <label><input type="checkbox" class="filter-price" value="500-1000"> €500 – €1 000</label>
            <label><input type="checkbox" class="filter-price" value="1000-2000"> €1 000 – €2 000</label>
            <label><input type="checkbox" class="filter-price" value="2000-99999"> €2 000+</label>
        </div>

        <div class="filter-group">
            <h4><?= $t['duration'] ?></h4>
            <label><input type="checkbox" class="filter-duration" value="short"> <?= $t['short'] ?></label>
            <label><input type="checkbox" class="filter-duration" value="medium"> <?= $t['medium'] ?></label>
            <label><input type="checkbox" class="filter-duration" value="long"> <?= $t['long'] ?></label>
        </div>
    </aside>

    <section class="results">
        <div class="results-header">
            <h2><?= $t['hero'] ?></h2>
            <span id="result-count"><?= count($packages) ?> <?= $t['found'] ?></span>
        </div>

        <div id="trip-list">
            <?php if (empty($packages)): ?>
                <p style="padding: 2rem; text-align: center; color: #666;"><?= $t['no_results'] ?></p>
            <?php else: ?>
                <?php foreach ($packages as $pkg): ?>
                    <?php
                    $spots = (int)($pkg['total_spots_left'] ?? 0);
                    $isFull = ($spots <= 0);
                    ?>
                    <div class="trip-card"
                         data-name="<?= htmlspecialchars(strtolower($pkg['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                         data-city="<?= htmlspecialchars(strtolower($pkg['city_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                         data-price="<?= (float)($pkg['min_price'] ?? 0) ?>"
                         data-nights="<?= (int)($pkg['min_nights'] ?? 0) ?>">

                        <img src="<?= htmlspecialchars($pkg['image_url'] ?? 'images/destinations/default.webp', ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($pkg['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                        <div class="trip-card-body">
                            <div>
                                <h3><?= htmlspecialchars($pkg['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="city"><?= htmlspecialchars($pkg['city_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    &nbsp;•&nbsp; <?= (int)($pkg['min_nights'] ?? 0) ?> <?= $t['nights_p'] ?>
                                </p>
                                <p class="highlights"><?= htmlspecialchars($pkg['highlights'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="trip-card-footer">
                                <div>
                                    <div class="trip-price">
                                        <?= $t['from_price'] ?> €<?= number_format((float)($pkg['min_price'] ?? 0), 0, ',', ' ') ?>
                                        <small><?= $t['per_person'] ?></small>
                                    </div>
                                    <?php if ($pkg['next_departure']): ?>
                                        <small style="color:#888;"><?= $t['next_dep'] ?> <?= date('d/m/Y', strtotime($pkg['next_departure'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align:right;">
                                    <?php if ($isFull): ?>
                                        <span class="trip-spots full"><?= $t['full'] ?></span>
                                    <?php else: ?>
                                        <span class="trip-spots"><?= $spots ?> <?= $t['spots_left'] ?></span>
                                    <?php endif; ?>
                                    <br>
                                    <a href="trip?id=<?= (int)$pkg['package_id'] ?>" class="btn-view"><?= $t['see_more'] ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="resizer" id="dragMe" title="Resize map"></div>

    <section class="map-section" id="mapContainer">
        <div id="map"></div>
    </section>

</main>

<script>
var map = L.map('map').setView([20, 10], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
}).addTo(map);
new ResizeObserver(() => map.invalidateSize()).observe(document.getElementById('map'));

var packages = <?= json_encode(array_map(function($p) {
    return [
        'package_id'   => $p['package_id'],
        'name'         => $p['name'],
        'city_name'    => $p['city_name'],
        'min_price'    => $p['min_price'],
        'latitude'     => $p['latitude'],
        'longitude'    => $p['longitude'],
    ];
}, $packages), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

packages.forEach(function(p) {
    if (!p.latitude || !p.longitude) return;
    var icon = L.divIcon({
        className: '', html: '<div style="background:#c1272d;color:#fff;padding:4px 8px;border-radius:4px;font-weight:bold;font-size:0.85rem;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,0.3);">€' + Math.round(p.min_price) + '</div>',
        iconAnchor: [20, 20]
    });
    L.marker([p.latitude, p.longitude], {icon: icon}).addTo(map)
        .bindPopup('<b>' + p.name.replace(/</g,'&lt;') + '</b><br>' + p.city_name + '<br><a href="trip?id=' + p.package_id + '">Voir le voyage</a>');
});

// Client-side filtering
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search-input');
    var cards       = document.querySelectorAll('.trip-card');
    var countEl     = document.getElementById('result-count');
    var foundText   = '<?= $t['found'] ?>';

    function filter() {
        var searchVal    = searchInput.value.toLowerCase();
        var activeCities = [...document.querySelectorAll('.filter-city:checked')].map(c => c.value);
        var activePrices = [...document.querySelectorAll('.filter-price:checked')].map(c => c.value);
        var activeDur    = [...document.querySelectorAll('.filter-duration:checked')].map(c => c.value);
        var visible = 0;

        cards.forEach(function(card) {
            var name    = card.dataset.name || '';
            var city    = card.dataset.city || '';
            var price   = parseFloat(card.dataset.price) || 0;
            var nights  = parseInt(card.dataset.nights) || 0;

            var durCategory = nights >= 8 ? 'long' : (nights >= 5 ? 'medium' : 'short');

            var matchSearch = !searchVal || name.includes(searchVal) || city.includes(searchVal);
            var matchCity   = activeCities.length === 0 || activeCities.includes(city);
            var matchDur    = activeDur.length === 0    || activeDur.includes(durCategory);
            var matchPrice  = activePrices.length === 0;
            activePrices.forEach(function(range) {
                var parts = range.split('-');
                if (price >= parseFloat(parts[0]) && price <= parseFloat(parts[1])) matchPrice = true;
            });

            var show = matchSearch && matchCity && matchDur && matchPrice;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        countEl.textContent = visible + ' ' + foundText;
    }

    if (searchInput.value) filter();
    document.querySelectorAll('.filter-city, .filter-price, .filter-duration').forEach(cb => cb.addEventListener('change', filter));
    searchInput.addEventListener('input', filter);
});

// Map resizer
var resizer     = document.getElementById('dragMe');
var mapContainer = document.getElementById('mapContainer');
var isDragging  = false;
resizer.addEventListener('mousedown', function() { isDragging = true; resizer.classList.add('dragging'); document.body.classList.add('is-resizing'); });
document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    var container = document.querySelector('.booking-container');
    var rect      = container.getBoundingClientRect();
    var newWidth  = Math.min(Math.max(rect.right - e.clientX - 25, 280), rect.width * 0.65);
    mapContainer.style.width = newWidth + 'px';
    mapContainer.style.flex  = '0 0 ' + newWidth + 'px';
    map.invalidateSize();
});
document.addEventListener('mouseup', function() {
    if (isDragging) { isDragging = false; resizer.classList.remove('dragging'); document.body.classList.remove('is-resizing'); }
});
</script>

<?php include 'footer.php'; ?>
