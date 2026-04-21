<?php
// =========================================================================
// 1. ZONE CONTRÔLEUR (LOGIQUE MÉTIER & SÉCURITÉ)
// Aucune balise HTML ne doit se trouver dans cette zone.
// =========================================================================

require_once __DIR__ . '/../config/Dbh.php';

// Initialisation des variables par défaut
$_SESSION['step'] = 1;
$_SESSION['email'] = '';
$error_message = '';
$pdo = new Dbh();

// 🛡️ SÉCURITÉ CSRF : Génération d'un token unique pour la session

// 🛡️ SÉCURITÉ ANTI-BRUTEFORCE : Initialisation des compteurs
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Vérification si l'utilisateur est temporairement bloqué (ex: 15 minutes)
if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    $remaining_time = ceil(($_SESSION['lockout_time'] - time()) / 60);
    $error_message = "Trop de tentatives échouées. Veuillez réessayer dans $remaining_time minute(s).";
    // On empêche le traitement du formulaire
    $_SERVER['REQUEST_METHOD'] = 'GET';
} elseif (isset($_SESSION['lockout_time']) && time() >= $_SESSION['lockout_time']) {
    // Le temps de blocage est écoulé, on réinitialise
    unset($_SESSION['lockout_time']);
    $_SESSION['login_attempts'] = 0;
}

// TRAITEMENT DES FORMULAIRES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🛡️ SÉCURITÉ CSRF : Vérification du token envoyé par le formulaire
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité : Requête non autorisée (CSRF).");
    }

    // ==========================================
    // ÉTAPE 1 : VÉRIFICATION DE L'E-MAIL
    // ==========================================
    if (isset($_POST['email_check'])) {
        $email = trim($_POST['email']);


    }

    // ==========================================
    // ÉTAPE 2 : CONNEXION (LOGIN)
    // ==========================================
    elseif (isset($_POST['login_submit'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT user_id, password, name FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // 🛡️ SÉCURITÉ FIXATION DE SESSION : On regénère l'ID de session à la connexion
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['login_attempts'] = 0; // Réinitialisation des tentatives

            // Redirection
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['login_attempts']++;
            // 🛡️ ANTI-BRUTEFORCE : Bloquer après 5 échecs
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + (15 * 60); // Bloqué 15 minutes
                $error_message = "Trop de tentatives échouées. Compte bloqué pour 15 minutes.";
            } else {
                $error_message = "Identifiants incorrects. Tentatives restantes : " . (5 - $_SESSION['login_attempts']);
            }
            $step = 2;
        }
    }

    // ==========================================
    // ÉTAPE 3 : INSCRIPTION (REGISTER)
    // ==========================================
    elseif (isset($_POST['register_submit'])) {
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone_number']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        // 👤 UX : Vérification de la confirmation du mot de passe
        if ($password !== $password_confirm) {
            $error_message = "Les mots de passe ne correspondent pas.";
            $step = 3;
        }
        // 👤 UX/SÉCURITÉ : Exigence de complexité (Min 8 caractères, 1 Majuscule, 1 Chiffre)
        elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            $error_message = "Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule et 1 chiffre.";
            $step = 3;
        }
        else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO user (name, email, phone_number, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $hashed_password]);

                // 🛡️ SÉCURITÉ FIXATION DE SESSION
                session_regenerate_id(true);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;

                header('Location: index.php');
                exit();

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_message = "Cet e-mail est déjà utilisé.";
                    $step = 1;
                } else {
                    // 🏗️ ARCHITECTURE : Journalisation des erreurs dans un fichier caché (logs)
                    error_log("[" . date('Y-m-d H:i:s') . "] Erreur Inscription : " . $e->getMessage() . "\n", 3, __DIR__ . '/../config/errors.log');
                    $error_message = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
                    $step = 3;
                }
            }
        }
    }
}
