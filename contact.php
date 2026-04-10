<?php 
$page_title = 'Momo - Contact Us';
$page_description = 'Have questions or need assistance? Contact Momo Travel for support with your bookings, tours, and more.';
include 'header.php'; 
?>


<section class="hero">
    <h1>Contact Us</h1>
</section>

<main class="contact-section">
    <div class="contact-header">
        <p>Have questions or need assistance? We're here to help! Reach out to us through any of the following methods:</p>
    </div>

    <div class="contact-container">
        <aside class="contact-info">
            <h3>Get in touch</h3>
            <ul>
                <li><strong>Email:</strong> momo-vacation@alwaysdata.net</li>
                <li><strong>Phone:</strong> +33 6 79 46 09 58</li>
                <li><strong>Address:</strong> 267 Av. de Navarre, 16000 Angoulême, France</li>
            </ul>
            <div class="business-hours">
                <h4>Business Hours</h4>
                <p>Monday - Friday: 9am - 6pm</p>
                <p>Saturday - Sunday: Closed</p>
            </div>
        </aside>

        <form id="mon-formulaire" class="contact-form" action="/traitement" method="POST">
            <div class="form-row">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>
            <input type="email" name="email" placeholder="Your Email Address" required>
            <input type="text" name="subject" placeholder="Subject" required>
            <textarea name="message" rows="6" placeholder="How can we help you?" required></textarea>
            
            <button type="submit" id="btn-submit">Send Message</button>
        </form> 
    </div>
</main>


<?php 
include 'footer.php'; 
?>