<?php 
$page_title = 'Momo - Book your next journey'; 
$page_description = 'Find housing, tours, and more for your next destination with Momo Travel.';
include 'header.php'; 
?>

<section class="hero">
    <h1>Book your next journey</h1>
    <p>Find housing, tours, and more for your next destination</p>
    
    <div class="search-bar">
        <input type="text" aria-label="Destination" placeholder="City, hotel, neighborhood...">
        <input type="date" aria-label="Travel dates" style="border-left: 1px solid #ddd; flex-grow: 0.5;">
        <button>Search</button>
    </div>
</section>

<main class="destinations">
    <h2>Destinations</h2>
    <p>Most popular destinations</p>

    <div class="grid">
        <!-- Carte 1 -->
        <article class="card">
            <img src="images/destinations/Paris/Paris.webp" alt="Paris Tour Eiffel" fetchpriority="high">
            <div class="card-content">
                <h3>Paris</h3>
                <p>Want to see la vie en Rose?</p>
            </div>
        </article>

        <!-- Carte 2 -->
        <article class="card">
            <img src="images/destinations/New York/NYC.webp" alt="New York" fetchpriority="high">
            <div class="card-content">
                <h3>New York</h3>
                <p>Take a bite of the Big Apple</p>
            </div>
        </article>

        <!-- Carte 3 -->
        <article class="card">
            <img src="images/destinations/Tokyo/Tokyo.webp" alt="Tokyo" fetchpriority="high">
            <div class="card-content">
                <h3>Tokyo</h3>
                <p>Chase the cherry blossoms</p>
            </div>
        </article>

        <!-- Carte 4 -->
        <article class="card">
            <img src="images/destinations/Londres/London.webp" alt="London" fetchpriority="high">
            <div class="card-content">
                <h3>London</h3>
                <p>Keep calm and visit London</p>
            </div>
        </article>

        <!-- Carte 5 -->
        <article class="card">
            <img src="images/destinations/Cairo/Cairo.webp" alt="Cairo" fetchpriority="high">
            <div class="card-content">
                <h3>Cairo</h3>
                <p>Walk among the pharaohs</p>
            </div>
        </article>

        <!-- Carte 6 -->
        <article class="card">
            <img src="images/destinations/Honkong/Hongkong.webp" alt="Hong Kong" fetchpriority="high">
            <div class="card-content">
                <h3>Hong Kong</h3>
                <p>Wander in the streets that inspired Wong Kar Wai</p>
            </div>
        </article>
    </div>
</main>

<!--Travel more while spending less-->
<section class="promo-section">
    <h2 class="promo-title">Travel more while spending less</h2>
    
    <img src="images/other/Airplane.webp" alt="Airplane flying" class="promo-img-full">
    
    <p class="promo-text">
        Tired of always missing discounts?<br>
        Discover our tips to save money on your flight tickets, housing, activities and more in our blog section.
    </p>
</section>

<!-- Reviews -->
<section class="reviews-section">
    <h2>Reviews</h2>
    <p class="reviews-subtitle">Browse through our clients’ feedback on their trips with us</p>

    <div class="reviews-grid">

        <!-- Review cards ( modifier quand on passera de statique a non statique) -->
        <article class="review-card">
            <div class="review-header">
                <div class="user-info">
                    <span class="username">travel_lover123</span>
                    <span class="role">user</span>
                </div>
            </div>
            <h3>“So much smoother”</h3>
            <p class="review-text">
                I tried several travel agencies and this one does not compare. Momo makes planning a trip truly enjoyable, th <strong>(read more)</strong>
            </p>
        </article>

        <article class="review-card">
            <div class="review-header">
                <div class="user-info">
                    <span class="username">abitrip_7</span>
                    <span class="role">user</span>
                </div>
            </div>
            <h3>“What a blast”</h3>
            <p class="review-text">
                I am beyond amazed. Absolutely everything was taken care of. The services are so great it makes you want to enco <strong>(read more)</strong>
            </p>
        </article>

        <article class="review-card">
            <div class="review-header">
                <div class="user-info">
                    <span class="username">fromearth903</span>
                    <span class="role">user</span>
                </div>
            </div>
            <h3>“I loved the experience”</h3>
            <p class="review-text">
                I went to Cairo with my friends using Momo. Such a lovely experience. We really benefited from all the ti <strong>(read more)</strong>
            </p>
        </article>
    </div>
</section>

<div id="cookie-banner" class="cookie-banner">
    <p>We use cookies to improve your experience on Momo Travel. Do you accept them? 🍪</p>
    <div class="cookie-buttons">
        <button id="accept-cookies" class="btn-accept">Accept</button>
        <button id="decline-cookies" class="btn-decline">Decline</button>
    </div>
</div>

<?php 
include 'footer.php'; 
?>