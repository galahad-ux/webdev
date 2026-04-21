<?php 
session_start();



// =========================================================================
// 2. ZONE VUE (AFFICHAGE HTML)
// =========================================================================

$page_title = 'Momo - Connexion & Inscription';
include '../header.php';
$_SESSION['step']=1;
$_POST['email']='';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
    <link rel="stylesheet" href="../style.css">
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

        <?php if ($_SESSION['step'] === 1): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Bienvenue</h2>
                    <p>Saisissez votre e-mail pour vous connecter ou créer un compte sur Momo Travel.</p>
                </div>

                <form class="contact-form" method="POST" action="auth.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="email" name="email" placeholder="Votre adresse e-mail" required value="<?= htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') ?>" autofocus style="width: 100%;">
                    <button type="submit" name="email_check" id="btn-submit" style="width: 100%;">Continuer</button>
                </form>

                <div style="text-align: center; margin: 20px 0; color: #777;"><span>Ou</span></div>
                <button class="btn-google" type="button" onclick="alert('Liaison API Google à faire')" style="width: 100%; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google Logo" style="width: 20px;">
                    Continuer avec Google
                </button>

            <?php elseif ($_SESSION['step'] === 2): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Bon retour !</h2>
                    <p>Saisissez votre mot de passe pour le compte<br><strong><?= htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>

                <form class="contact-form" method="POST" action="auth.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') ?>">

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

            <?php elseif ($_SESSION['step'] === 3): ?>
                <div class="contact-header" style="text-align: center; margin-bottom: 2rem;">
                    <h2>Créer un compte</h2>
                    <p>Complétez vos informations pour finaliser l'inscription de<br><strong><?= htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>

                <form class="contact-form" method="POST" action="auth.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') ?>">

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

<?php include '../footer.php'; ?>