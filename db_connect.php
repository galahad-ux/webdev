<?php
// Remplacer ces valeurs par tes vrais identifiants Alwaysdata
$host = 'mysql-momo-vacation.alwaysdata.net'; 
$db   = 'momo-vacation_bdd';
$user = 'momo-vacation_website';
$pass = 'FhR^?emJ8h4xzF]c?NCDE]j0@8QC:RJqvN3.rB;NsrBE]a<43R1BAuR@uBGB*K@H';
$charset = 'utf8mb4'; // Recommandé pour gérer correctement tous les caractères (accents, emojis...)

// Construction de la chaîne de connexion
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Options de sécurité et de gestion d'erreurs pour PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Affiche les erreurs SQL sous forme d'exceptions (très utile pour le débogage)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourne les résultats sous forme de tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Améliore la sécurité des requêtes préparées
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 1. On écrit la vraie erreur critique dans le fichier de log du serveur (invisible pour le public)
    error_log("Erreur de connexion BDD : " . $e->getMessage());
    
    // 2. On arrête le script et on affiche un message d'erreur générique et sécurisé
    die("Désolé, une erreur technique est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
}
?>