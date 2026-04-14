<?php 
$page_title = 'Momo - Contact Us';
$page_description = 'Have questions or need assistance? Contact Momo Travel for support with your bookings, tours, and more.';

$lang = $_SESSION['language'] ?? 'fr';

$translations = [
    'fr' => [
        'hero_title' => "Contactez-nous",
        'contact_text' => "Vous avez des questions ou besoin d'aide ? Nous sommes là pour vous aider ! Contactez-nous par l'un des moyens suivants :",
        'get_in_touch' => "Contactez-nous",
        'email' => "Email",
        'phone' => "Téléphone",
        'address' => "Adresse",
        'business_hours' => "Heures d'ouverture",
        'monday_friday' => "Lundi - Vendredi : 9h - 18h",
        'saturday_sunday' => "Samedi - Dimanche : Fermé",
        'first_name' => "Prénom",
        'last_name' => "Nom",
        'your_email' => "Votre adresse email",
        'subject' => "Sujet",
        'message' => "Comment pouvons-nous vous aider ?",
        'send_message' => "Envoyer le message",
    ],
    'en' => [
        'hero_title' => "Contact Us",
        'contact_text' => "Have questions or need assistance? We're here to help! Reach out to us through any of the following methods:",
        'get_in_touch' => "Get in touch",
        'email' => "Email",
        'phone' => "Phone",
        'address' => "Address",
        'business_hours' => "Business Hours",
        'monday_friday' => "Monday - Friday: 9am - 6pm",
        'saturday_sunday' => "Saturday - Sunday: Closed",
        'first_name' => "First Name",
        'last_name' => "Last Name",
        'your_email' => "Your Email Address",
        'subject' => "Subject",
        'message' => "How can we help you?",
        'send_message' => "Send Message",
    ]
];
$t = $translations[$lang];

include 'header.php'; 
?>


<section class="hero">
    <h1><?php echo $t['hero_title']; ?></h1>
</section>

<main class="contact-section">
    <div class="contact-header">
        <p><?php echo $t['contact_text']; ?></p>
    </div>

    <div class="contact-container">
        <aside class="contact-info">
            <h3><?php echo $t['get_in_touch']; ?></h3>
            <ul>
                <li><strong><?php echo $t['email']; ?>:</strong> momo-vacation@alwaysdata.net</li>
                <li><strong><?php echo $t['phone']; ?>:</strong> +33 6 79 46 09 58</li>
                <li><strong><?php echo $t['address']; ?>:</strong> 267 Av. de Navarre, 16000 Angoulême, France</li>
            </ul>
            <div class="business-hours">
                <h4><?php echo $t['business_hours']; ?></h4>
                <p><?php echo $t['monday_friday']; ?></p>
                <p><?php echo $t['saturday_sunday']; ?></p>
            </div>
        </aside>

        <form id="mon-formulaire" class="contact-form" action="/traitement" method="POST">
            <div class="form-row">
                <input type="text" name="first_name" placeholder="<?php echo $t['first_name']; ?>" required>
                <input type="text" name="last_name" placeholder="<?php echo $t['last_name']; ?>" required>
            </div>
            <input type="email" name="email" placeholder="<?php echo $t['your_email']; ?>" required>
            <input type="text" name="subject" placeholder="<?php echo $t['subject']; ?>" required>
            <textarea name="message" rows="6" placeholder="<?php echo $t['message']; ?>" required></textarea>
            
            <button type="submit" id="btn-submit"><?php echo $t['send_message']; ?></button>
        </form> 
    </div>
</main>


<?php 
include 'footer.php'; 
?>