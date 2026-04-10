<?php 
session_start();

// =========================================================================
// 1. ZONE CONTRÔLEUR (LOGIQUE MÉTIER & SÉCURITÉ)
// Aucune balise HTML ne doit se trouver dans cette zone.
// =========================================================================

require_once __DIR__ . '/../config/db_connect.php'; 

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

            <div style="text-align: center; margin: 20px 0; color: #777;"><span>Ou</span></div>
            <button class="btn-google" type="button" onclick="alert('Liaison API Google à faire')" style="width: 100%; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google Logo" style="width: 20px;">
                Continuer avec Google
            </button>

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