<?php
session_start();

// Détruire toutes les variables de session
session_unset();

// Détruire la session elle-même
session_destroy();

// Rediriger vers la page d'accueil (ou auth.php selon ton choix)
header('Location: index.php');
exit();
?>