<?php 
session_start();
require_once __DIR__ . '/../config/db_connect.php'; // Charge la BDD + les clés Google
require_once __DIR__ . '/../vendor/autoload.php';      // Charge la bibliothèque Google

// Préparation du client Google
$client = new Google_Client();
$client->setClientId(GOOGLE_ID);
$client->setClientSecret(GOOGLE_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT);
$client->addScope("email");
$client->addScope("profile");

// Génération de l'URL de connexion
$google_login_url = $client->createAuthUrl();

// =========================================================================
// 1. ZONE CONTRÔLEUR (LOGIQUE MÉTIER & SÉCURITÉ)
// Aucune balise HTML ne doit se trouver dans cette zone.
// =========================================================================


// Initialisation des variables par défaut
$step = 1;
$email = '';
$error_message = '';

// 🛡️ SÉCURITÉ CSRF : Génération d'un token unique pour la session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

// ==========================================
// INTERCEPTION DU LIEN MOT DE PASSE OUBLIÉ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT user_id FROM user WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    if ($stmt->fetch()) {
        $step = 5; // Étape de saisie du nouveau mot de passe
    } else {
        $error_message = "Ce lien de réinitialisation est invalide ou a expiré.";
        $step = 1;
    }
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
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $step = 2; // Existe -> Login
            } else {
                $step = 3; // N'existe pas -> Register
            }
        } else {
            $error_message = "Veuillez entrer une adresse e-mail valide.";
        }
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
    // ==========================================
    // ÉTAPE 4 : DEMANDE DE MOT DE PASSE OUBLIÉ
    // ==========================================
    elseif (isset($_POST['goto_forgot'])) {
        $email = trim($_POST['email'] ?? '');
        $step = 4;
    }
    
    elseif (isset($_POST['forgot_submit'])) {
        $email = trim($_POST['email']);
        
        $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Jeton valide 1 heure
            
            $stmt = $pdo->prepare("UPDATE user SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);

            // ⚠️ SIMULATION DE L'E-MAIL (À remplacer par PHPMailer en production)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'])[0] . "?token=" . $token;
            // mail($email, "Réinitialisation", "Lien : " . $reset_link);
            
            // Pour tester sans serveur mail, on affiche le lien dans le message d'erreur
            $error_message = "Lien envoyé (SIMULATION DEV) : <a href='$reset_link'>Cliquez ici</a>";
        } else {
            // Message générique pour des raisons de sécurité (ne pas révéler si l'e-mail existe ou non)
            $error_message = "Si cette adresse existe, un e-mail a été envoyé.";
        }
        $step = 1;
    }

    // ==========================================
    // ÉTAPE 5 : SAUVEGARDE DU NOUVEAU MOT DE PASSE
    // ==========================================
    elseif (isset($_POST['reset_submit'])) {
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];
        $new_password_confirm = $_POST['new_password_confirm'];

        if ($new_password !== $new_password_confirm) {
            $error_message = "Les mots de passe ne correspondent pas.";
            $step = 5;
            $_GET['token'] = $token; // Pour garder le token dans le formulaire
        } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $new_password)) {
            $error_message = "Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule et 1 chiffre.";
            $step = 5;
            $_GET['token'] = $token;
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE user SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user['user_id']]);
                
                $error_message = "Mot de passe mis à jour ! Vous pouvez vous connecter.";
                $step = 1;
            } else {
                $error_message = "Le lien a expiré ou est invalide.";
                $step = 1;
            }
        }
    }
}

// =========================================================================
// 2. ZONE VUE (AFFICHAGE HTML)
// =========================================================================

$page_title = 'Momo - Connexion & Inscription';
include 'header.php'; 
?>

<section class="hero">
    <h1>Espace Membre</h1>
</section>

<main class="contact-section">
    <div class="contact-container" style="max-width: 500px; margin: 0 auto; display: flex; flex-direction: column;">
        
        <?php if (!empty($error_message)): ?>
            <div style="background-color: #fee; color: #c1272d; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem;">
                <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                <h2>Bienvenue</h2>
                <p>Saisissez votre e-mail pour vous connecter ou créer un compte sur Momo Travel.</p>
            </div>
            
            <form class="contact-form" method="POST" action="auth.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="email" name="email" placeholder="Votre adresse e-mail" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autofocus style="width: 100%;">
               <button type="submit" name="email_check" id="btn-submit" style="width: 100%;">Continuer</button>
            </form>

            <div class="auth-divider"><span>OU</span></div>

            <a href="<?= filter_var($google_login_url, FILTER_SANITIZE_URL) ?>" class="btn-google" style="text-decoration: none;">
                <img src='images/logo/google.webp' alt="Google Logo" style="width: 20px;">
                Continuer avec Google
            </a>
            <?php elseif ($step === 2): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Bon retour !</h2>
                    <p>Saisissez votre mot de passe pour le compte<br><strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>
            
                <form class="contact-form" method="POST" action="auth.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                        <input type="password" name="password" id="login_pwd" placeholder="Mot de passe" required autofocus style="width: 100%; padding-right: 40px; margin-bottom: 0;">
                        <button type="button" onclick="togglePwd('login_pwd')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 0; outline: none;">👁️</button>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem; text-align: left; margin-bottom: 1rem;">
                        <input type="checkbox" name="remember_me" id="remember_me" style="width: auto; margin-bottom: 0;">
                        <label for="remember_me" style="font-size: 0.9rem; color: #555; cursor: pointer;">Se souvenir de moi</label>
                    </div>

                    <div style="text-align: right; margin-bottom: 1.5rem;">
                        <button type="submit" name="goto_forgot" formnovalidate style="background: none; border: none; color: #c1272d; font-size: 0.9rem; cursor: pointer; text-decoration: underline; padding: 0; width: auto; font-weight: 500;">Mot de passe oublié ?</button>
                    </div>

                    <button type="submit" name="login_submit" id="btn-submit" style="width: 100%;">Se connecter</button>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="auth.php" style="color: #555; text-decoration: underline; font-size: 0.9rem;">Utiliser un autre e-mail</a>
                </div>

            <?php elseif ($step === 3): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Créer un compte</h2>
                    <p>Complétez vos informations pour finaliser l'inscription de<br><strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>
                
                <form class="contact-form" method="POST" action="auth.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <input type="text" name="name" placeholder="Prénom et Nom" required autofocus style="width: 100%;">
                    <input type="tel" name="phone_number" placeholder="Téléphone (optionnel)" style="width: 100%;">
                    
                    <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                        <input type="password" name="password" id="reg_pwd" placeholder="Mot de passe (8 car., 1 Maj., 1 chiffre)" required style="width: 100%; padding-right: 40px; margin-bottom: 0;">
                        <button type="button" onclick="togglePwd('reg_pwd')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 0; outline: none;">👁️</button>
                    </div>

                    <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                        <input type="password" name="password_confirm" id="reg_pwd_conf" placeholder="Confirmez votre mot de passe" required style="width: 100%; padding-right: 40px; margin-bottom: 0;">
                        <button type="button" onclick="togglePwd('reg_pwd_conf')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 0; outline: none;">👁️</button>
                    </div>
                    
                    <button type="submit" name="register_submit" id="btn-submit" style="width: 100%; margin-top: 10px;">S'inscrire</button>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="auth.php" style="color: #555; text-decoration: underline; font-size: 0.9rem;">Utiliser un autre e-mail</a>
                </div>

            <?php elseif ($step === 4): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Mot de passe oublié</h2>
                    <p>Saisissez votre e-mail pour recevoir un lien de réinitialisation.</p>
                </div>
                
                <form class="contact-form" method="POST" action="auth2.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="email" name="email" placeholder="Votre adresse e-mail" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autofocus style="width: 100%;">
                    
                    <button type="submit" name="forgot_submit" id="btn-submit" style="width: 100%;">Envoyer le lien</button>
                </form>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="auth2.php" style="color: #555; text-decoration: underline; font-size: 0.9rem;">Retour à la connexion</a>
                </div>

            <?php elseif ($step === 5): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Nouveau mot de passe</h2>
                    <p>Créez un nouveau mot de passe sécurisé.</p>
                </div>
                
                <form class="contact-form" method="POST" action="auth2.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? $_POST['token'], ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                        <input type="password" name="new_password" id="reset_pwd" placeholder="Nouveau mot de passe (8 car., 1 Maj., 1 chiffre)" required autofocus style="width: 100%; padding-right: 40px; margin-bottom: 0;">
                        <button type="button" onclick="togglePwd('reset_pwd')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 0; outline: none;">👁️</button>
                    </div>

                    <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                        <input type="password" name="new_password_confirm" id="reset_pwd_conf" placeholder="Confirmez le mot de passe" required style="width: 100%; padding-right: 40px; margin-bottom: 0;">
                        <button type="button" onclick="togglePwd('reset_pwd_conf')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 0; outline: none;">👁️</button>
                    </div>
                    
                    <button type="submit" name="reset_submit" id="btn-submit" style="width: 100%;">Enregistrer et se connecter</button>
                </form>

        <?php endif; ?>
    </div>
</main>

<script>
// Script pour afficher/masquer le mot de passe
function togglePwd(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}
</script>

<?php include 'footer.php'; ?>