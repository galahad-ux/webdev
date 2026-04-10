<?php 
$page_title = 'Momo - Book'; 
$page_description = 'Find housing, tours, and more for your next destination with Momo Travel.';
include 'header.php'; 
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<section class="hero" style="padding: 4rem 2%;">
    <h1>Find your perfect stay</h1>
    
    <div class="search-bar">
        <input type="text" aria-label="Destination" placeholder="City, hotel, neighborhood...">
        <input type="date" aria-label="Travel dates" style="border-left: 1px solid #ddd; flex-grow: 0.5;">
        <button>Search</button>
    </div>
</section>

<main class="booking-container">

    <aside class="filters">
        <h3>Filter by:</h3>
    
        <div class="filter-group">
            <h4>Price range</h4>
            <label><input type="checkbox"> €0 - €50</label>
            <label><input type="checkbox"> €50 - €100</label>
            <label><input type="checkbox"> €100 - €200</label>
            <label><input type="checkbox"> €200+</label>
        </div>

        <div class="filter-group">
            <h4>Property Type</h4>
            <label><input type="checkbox"> Hotels</label>
            <label><input type="checkbox"> Apartments</label>
            <label><input type="checkbox"> Villas</label>
            <label><input type="checkbox"> Guest houses</label>
        </div>

        <div class="filter-group">
            <h4>Star Rating</h4>
            <label><input type="checkbox"> 5 stars</label>
            <label><input type="checkbox"> 4 stars</label>
            <label><input type="checkbox"> 3 stars</label>
        </div>
    </aside>

    <section class="results">
        <div class="results-header">
            <h2>Stays in Paris</h2>
            <span>142 properties found</span>
        </div>

        <div class="housing-grid">
            <div class="house-card">
                <div class="house-img">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=500" alt="Hotel" fetchpriority="high">
                    <span class="badge">Top Rated</span>
                </div>
                <div class="house-info">
                    <div class="house-title">
                        <h3>The Royal Tartan Hotel</h3>
                        <span class="rating">9.8</span>
                    </div>
                    <p class="location">Champs-Élysées, Paris</p>
                    <p class="description">Luxury suite with breakfast included and city view.</p>
                    <div class="price-box">
                        <span class="price">€180 / night</span>
                        <button class="btn-book">See availability</button>
                    </div>
                </div>
            </div>

            <div class="house-card">
                <div class="house-img">
                    <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=500" alt="Apartment" fetchpriority="high">
                </div>
                <div class="house-info">
                    <div class="house-title">
                        <h3>Cozy Artist Studio</h3>
                        <span class="rating">8.5</span>
                    </div>
                    <p class="location">Montmartre, Paris</p>
                    <p class="description">Entire apartment • 1 bed • 1 bathroom</p>
                    <div class="price-box">
                        <span class="price">€95 / night</span>
                        <button class="btn-book">See availability</button>
                    </div>
                </div>
            </div>

            <div class="house-card">
                <div class="house-img">
                    <img src="images/book/hotel/anantara.webp" alt="Hotel" fetchpriority="high">
                </div>
                <div class="house-info">
                    <div class="house-title">
                        <h3>Grand Central Boutique</h3>
                        <span class="rating">9.1</span>
                    </div>
                    <p class="location">Le Marais, Paris</p>
                    <p class="description">Modern hotel in the heart of the historic district.</p>
                    <div class="price-box">
                        <span class="price">€550 / night</span>
                        <button class="btn-book">See availability</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="map-section">
        <div id="map" ></div>
    </section>

</main>

<script>
    // Initialisation de la carte (Centrée sur Paris)
    var map = L.map('map').setView([48.8566, 2.3522], 12);

    // Ajout du fond de carte OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Ajout des marqueurs pour les 3 hôtels de la liste
    var marker1 = L.marker([48.8738, 2.2950]).addTo(map).bindPopup("<b>The Royal Tartan Hotel</b><br>€180 / night");
    var marker2 = L.marker([48.8867, 2.3331]).addTo(map).bindPopup("<b>Cozy Artist Studio</b><br>€95 / night");
    var marker3 = L.marker([48.8575, 2.3588]).addTo(map).bindPopup("<b>Grand Central Boutique</b><br>€550 / night");

    // NOUVEAU : L'observateur de redimensionnement infaillible
    var mapDiv = document.getElementById('map');
    var resizeObserver = new ResizeObserver(function() {
        map.invalidateSize();
    });
    resizeObserver.observe(mapDiv);
</script>

<?php 
include 'footer.php'; 
?>