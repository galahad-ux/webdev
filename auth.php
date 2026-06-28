<?php 
session_start();

// =========================================================================
// 1. ZONE CONTRÔLEUR (LOGIQUE MÉTIER & SÉCURITÉ)
// Aucune balise HTML ne doit se trouver dans cette zone.
// =========================================================================

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$client = new Google_Client();
$client->setClientId(GOOGLE_ID);
$client->setClientSecret(GOOGLE_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT);
$client->addScope("email");
$client->addScope("profile");
$google_login_url = $client->createAuthUrl();

// Initialisation des variables par défaut
$step             = 1;
$email            = '';
$name             = '';
$phone            = '';
$error_message    = '';
$suggest_register = false;
$lang             = $_SESSION['language'] ?? 'fr';

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

// GET handler: jump directly to register step with pre-filled email (from "suggest register" link)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'register') {
    $email = trim(filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $step  = 3;
}

// Capture redirect target (only allow relative URLs — no open-redirect)
if (!empty($_GET['redirect'])) {
    $redir = $_GET['redirect'];
    if (isset($redir[0]) && $redir[0] === '/' && strpos($redir, '//') === false) {
        $_SESSION['redirect_after_login'] = $redir;
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
                $step = 2; // Exists -> Login
            } else {
                // Stay on step 1 — show error + suggest creating an account
                $step = 1;
                $suggest_register = true;
                $error_message = ($lang ?? 'fr') === 'en'
                    ? "No account found for this email address. Did you make a typo?"
                    : "Aucun compte trouvé pour cette adresse e-mail. Avez-vous fait une faute de frappe ?";
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

        $stmt = $pdo->prepare("SELECT user_id, password, name, role, language FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']       = $user['user_id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['language']      = $user['language'] ?? 'fr';
            $_SESSION['login_attempts'] = 0;
            
            // Redirection
            $dest = $_SESSION['redirect_after_login'] ?? 'index';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $dest);
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
                
                session_regenerate_id(true);

                $new_user_id = (int)$pdo->lastInsertId();
                $account_type = $_POST['account_type'] ?? 'user';

                // If registering as agency, create agency record
                if ($account_type === 'agency') {
                    $company_name = trim($_POST['company_name'] ?? $name);
                    $pdo->prepare("UPDATE user SET role='agency' WHERE user_id=?")->execute([$new_user_id]);
                    $pdo->prepare("INSERT INTO agency (user_id, company_name) VALUES (?, ?)")->execute([$new_user_id, $company_name]);
                    $_SESSION['user_role'] = 'agency';
                } else {
                    $_SESSION['user_role'] = 'user';
                }

                $_SESSION['user_id']   = $new_user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['language']  = 'fr';

                $dest = $_SESSION['redirect_after_login'] ?? 'index';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $dest);
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
            $expires = gmdate('Y-m-d H:i:s', time() + 3600);

            $stmt = $pdo->prepare("UPDATE user SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);

            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/auth.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                $mail->addAddress($email);
                $mail->Subject = 'Réinitialisation de votre mot de passe — Momo Travel';
                $mail->isHTML(true);
                $mail->Body = "
                    <p>Bonjour,</p>
                    <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
                    <p><a href='" . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . "' style='background:#c1272d;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;'>Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien expire dans <strong>1 heure</strong>. Si vous n'avez pas fait cette demande, ignorez cet e-mail.</p>
                    <p>L'équipe Momo Travel</p>
                ";
                $mail->AltBody = "Réinitialisez votre mot de passe : " . $reset_link . " (lien valide 1 heure)";
                $mail->send();
                $error_message = "Un e-mail de réinitialisation vous a été envoyé.";
            } catch (Exception $e) {
                error_log("[" . date('Y-m-d H:i:s') . "] PHPMailer reset error: " . $mail->ErrorInfo . "\n", 3, __DIR__ . '/../config/errors.log');
                $error_message = "Erreur lors de l'envoi de l'e-mail. Veuillez réessayer.";
            }
        } else {
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

<section class="hero" style="padding: 4rem 2%;">
    <h1>Espace Membre</h1>
</section>

<main class="auth-section">
    <div class="auth-container">
        
        <?php if (!empty($error_message)): ?>
            <div style="background-color: #fee; color: #c1272d; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; text-align: left;">
                <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($reset_link_html)): ?><?= $reset_link_html ?><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <h2>Bienvenue</h2>
            <p>Saisissez votre e-mail pour vous connecter ou créer un compte sur Momo Travel.</p>
            
            <form class="auth-form" method="POST" action="auth.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="email" name="email" placeholder="Votre adresse e-mail" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autofocus>
                <button type="submit" name="email_check">Continuer</button>
            </form>

            <?php if (!empty($suggest_register)): ?>
                <div style="margin-top:1.2rem; padding:1rem 1.2rem; background:#fff8e1; border-radius:6px; border-left:3px solid #f0a500; font-size:0.9rem; color:#555;">
                    Pas encore de compte ?
                    <a href="auth.php?action=register&email=<?= urlencode($email) ?>" style="color:#c1272d; font-weight:600; text-decoration:none;">
                        Créer un compte avec cette adresse →
                    </a>
                </div>
            <?php endif; ?>

            <div class="auth-divider"><span>Ou</span></div>

            <a href="<?= filter_var($google_login_url, FILTER_SANITIZE_URL) ?>" class="btn-google" style="text-decoration: none;">
                <img src="images/logo/google.webp" alt="Google Logo" style="width: 20px;">
                Continuer avec Google
            </a>

        <?php elseif ($step === 2): ?>
            <h2>Bon retour !</h2>
            <p>Saisissez votre mot de passe pour le compte<br><strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong></p>
            
            <form class="auth-form" method="POST" action="auth.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                
                <div style="position: relative; width: 100%;">
                    <input type="password" name="password" id="login_pwd" placeholder="Mot de passe" required autofocus style="padding-right: 40px;">
                    <span onclick="togglePwd('login_pwd')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; user-select: none;">👁️</span>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <label for="remember_me">Se souvenir de moi</label>
                </div>

                <div style="text-align: right; margin-bottom: 1.5rem;">
                    <button type="submit" name="goto_forgot" formnovalidate style="background: none; border: none; color: #c1272d; font-size: 0.9rem; cursor: pointer; text-decoration: underline; padding: 0; width: auto; font-weight: 500;">Mot de passe oublié ?</button>
                </div>
                
                <button type="submit" name="login_submit">Se connecter</button>
            </form>
            
            <a href="auth.php" class="auth-back-link">Utiliser un autre e-mail</a>

        <?php elseif ($step === 3): ?>
            <h2>Créer un compte</h2>
            <p>Complétez vos informations pour finaliser l'inscription de<br><strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong></p>
            
            <form class="auth-form" method="POST" action="auth.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                
                <select name="account_type" id="account_type" onchange="toggleAgencyFields(this.value)" style="margin-bottom:1rem;">
                    <option value="user">Je suis un voyageur</option>
                    <option value="agency">Je représente une agence de voyage</option>
                </select>
                <div id="agency-fields" style="display:none;">
                    <input type="text" name="company_name" placeholder="Nom de l'agence" style="margin-bottom:1rem;">
                </div>
                <input type="text" name="name" placeholder="Prénom et Nom" required autofocus value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                <input type="tel" name="phone_number" placeholder="Téléphone (optionnel)" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
                
                <div style="position: relative; width: 100%;">
                    <input type="password" name="password" id="reg_pwd" placeholder="Mot de passe (8 car., 1 Maj., 1 chiffre)" required style="padding-right: 40px;">
                    <span onclick="togglePwd('reg_pwd')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; user-select: none;">👁️</span>
                </div>

                <div style="position: relative; width: 100%;">
                    <input type="password" name="password_confirm" id="reg_pwd_conf" placeholder="Confirmez votre mot de passe" required style="padding-right: 40px;">
                    <span onclick="togglePwd('reg_pwd_conf')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; user-select: none;">👁️</span>
                </div>
                
                <button type="submit" name="register_submit">S'inscrire</button>
            </form>
            
            <a href="auth.php" class="auth-back-link">Utiliser un autre e-mail</a>

            <?php elseif ($step === 4): ?>
            <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                <h2>Mot de passe oublié</h2>
                <p>Saisissez votre e-mail pour recevoir un lien de réinitialisation.</p>
            </div>
            
            <form class="contact-form" method="POST" action="auth.php" onsubmit="document.getElementById('btn-submit').disabled=true;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="forgot_submit" value="1">
                <input type="email" name="email" placeholder="Votre adresse e-mail" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autofocus style="width: 100%;">

                <button type="submit" id="btn-submit" style="width: 100%;">Envoyer le lien</button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="auth.php" style="color: #555; text-decoration: underline; font-size: 0.9rem;">Retour à la connexion</a>
            </div>

        <?php elseif ($step === 5): ?>
            <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                <h2>Nouveau mot de passe</h2>
                <p>Créez un nouveau mot de passe sécurisé.</p>
            </div>
            
            <form class="contact-form" method="POST" action="auth.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? $_POST['token'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                    <input type="password" name="new_password" id="reset_pwd" placeholder="Nouveau mot de passe (8 car., 1 Maj., 1 chiffre)" required autofocus style="width: 100%; box-sizing: border-box; padding-right: 40px; margin-bottom: 0;">
                    <span onclick="togglePwd('reset_pwd')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; user-select: none;">👁️</span>
                </div>

                <div style="position: relative; width: 100%; margin-bottom: 1rem;">
                    <input type="password" name="new_password_confirm" id="reset_pwd_conf" placeholder="Confirmez le mot de passe" required style="width: 100%; box-sizing: border-box; padding-right: 40px; margin-bottom: 0;">
                    <span onclick="togglePwd('reset_pwd_conf')" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; user-select: none;">👁️</span>
                </div>
                
                <button type="submit" name="reset_submit" id="btn-submit" style="width: 100%;">Enregistrer et se connecter</button>
            </form>

        <?php endif; ?>
    </div>
</main>

<script>
function toggleAgencyFields(val) {
    document.getElementById('agency-fields').style.display = val === 'agency' ? 'block' : 'none';
}
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