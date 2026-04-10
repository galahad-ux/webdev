<?php
session_start();

// SÉCURITÉ : Si l'utilisateur n'est pas connecté, on le renvoie vers auth.php
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

$page_title = 'Momo - Mon Compte';
include 'header.php';
?>

<section class="hero">
    <h1>Mon Compte</h1>
</section>

<main class="contact-section">
    <div class="contact-container" style="text-align: center; padding: 50px 20px;">
        <h2>Bonjour, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?> ! 👋</h2>
        <p>Bienvenue sur votre espace personnel Momo Travel.</p>
        <p>Adresse e-mail associée : <?php /* Tu pourras afficher l'email ici plus tard en le récupérant dans la BDD ou la session */ ?></p>
        
        <br>
        <a href="logout.php" id="btn-submit" style="display: inline-block; text-decoration: none; background-color: #c1272d;">Me déconnecter</a>
    </div>
</main>

<?php include 'footer.php'; ?>