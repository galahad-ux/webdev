<?php 
$page_title = 'Momo - Blog';
$page_description = 'Read our latest travel tips and stories from around the world.';
include 'header.php'; 
?>

<section class="hero">
    <h1>Travel Tips & Stories</h1>
</section>

<main class="blog-section">
    <div class="blog-filters">
        <button class="active">All</button>
        <button>Tips & Tricks</button>
        <button>Destinations</button>
        <button>Food & Culture</button>
    </div>

    <div class="blog-grid">
        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="images/destinations/New York/NYC.webp" alt="Flight tips">
                <span class="blog-category">Tips & Tricks</span>
            </div>
            <div class="blog-content">
                <span class="blog-date">April 3, 2026</span>
                <h3>How to save money on your flight tickets</h3>
                <p>Tired of always missing discounts? We reveal the best days to book and the secret tools airlines don't want you to know about.</p>
                <a href="#" class="read-more">Read article &rarr;</a>
            </div>
        </article>

        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="images/destinations/Paris/Paris.webp" alt="Paris Hidden Gems">
                <span class="blog-category">Destinations</span>
            </div>
            <div class="blog-content">
                <span class="blog-date">March 28, 2026</span>
                <h3>10 hidden gems in Paris away from the crowds</h3>
                <p>Skip the Eiffel Tower lines and discover the secret passages, local bakeries, and quiet parks that true Parisians love.</p>
                <a href="#" class="read-more">Read article &rarr;</a>
            </div>
        </article>

        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="images/destinations/Tokyo/Tokyo.webp" alt="Tokyo Food Guide">
                <span class="blog-category">Food & Culture</span>
            </div>
            <div class="blog-content">
                <span class="blog-date">March 15, 2026</span>
                <h3>A beginner's guide to Tokyo street food</h3>
                <p>From Takoyaki to Taiyaki, dive into the bustling streets of Tokyo and find out what you absolutely need to taste.</p>
                <a href="#" class="read-more">Read article &rarr;</a>
            </div>
        </article>

        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="images/destinations/Londres/London.webp" alt="London Packing">
                <span class="blog-category">Tips & Tricks</span>
            </div>
            <div class="blog-content">
                <span class="blog-date">March 02, 2026</span>
                <h3>Packing essentials for a rainy London trip</h3>
                <p>Keep calm and stay dry. Here is our ultimate packing list so the British weather doesn't ruin your city break.</p>
                <a href="#" class="read-more">Read article &rarr;</a>
            </div>
        </article>

        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="images/destinations/Cairo/Cairo.webp" alt="Cairo Pyramids">
                <span class="blog-category">Destinations</span>
            </div>
            <div class="blog-content">
                <span class="blog-date">February 18, 2026</span>
                <h3>Walking among the Pharaohs: Cairo itinerary</h3>
                <p>Make the most out of your 3-day trip to Egypt's capital with our optimized itinerary covering all the must-sees.</p>
                <a href="#" class="read-more">Read article &rarr;</a>
            </div>
        </article>

        <article class="blog-card">
            <div class="blog-img-wrapper">
                <img src="images/other/Airplane.webp" alt="Sustainable travel">
                <span class="blog-category">Tips & Tricks</span>
            </div>
            <div class="blog-content">
                <span class="blog-date">February 05, 2026</span>
                <h3>How to travel more sustainably in 2026</h3>
                <p>Eco-friendly travel is easier than you think. Discover simple habits to reduce your footprint while exploring the globe.</p>
                <a href="#" class="read-more">Read article &rarr;</a>
            </div>
        </article>
    </div>
</main>
<?php 
include 'footer.php'; 
?>