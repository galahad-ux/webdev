<?php
// On s'assure que la session est bien démarrée pour lire $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            <?php echo isset($page_title) ? $page_title : 'Momo Travel'; ?>
        </title>
        <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Book your next journey with Momo Travel. Find housing, tours, and more.'; ?>">
        <meta http-equiv="Content-Security-Policy" content="default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org https://images.unsplash.com; style-src 'self' 'unsafe-inline' https://unpkg.com; script-src 'self' 'unsafe-inline' https://unpkg.com; font-src 'self';">
        <link rel="canonical" href="<?php echo isset($auto_url) ? $auto_url : ''; ?>">
        <link rel="icon" type="image/png" href="images/icones/logo.webp">
        <link rel="preload" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto:wght@300;400;500;700&display=swap" as="style">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto:wght@300;400;500;700&display=swap" media="print" onload="this.media='all'">
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <a href="index" class="logo">momo</a>
            <nav class="nav-center">
                <a href="blog">Blog</a>
                <a href="book">Book</a>
                <a href="#">Tours</a>
            </nav>
            <div class="nav-right">
                <span>EUR</span>
                <span class="lang-flag">🇬🇧</span>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user_page" style="margin-right: 15px; color: inherit; text-decoration: none; font-weight: 500;">
                        <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    <a href="auth" class="btn-signup">Sign up</a>
                <?php endif; ?>
            </div>
        </header>