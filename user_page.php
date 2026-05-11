<?php
session_start();

// =========================================================================
// 1. ZONE CONTRÔLEUR : LE TANK (SÉCURITÉ ET LOGIQUE)
// =========================================================================

require_once __DIR__ . '/../config/db_connect.php'; 

// 🛡️ SÉCURITÉ : Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// 🛡️ SÉCURITÉ : Anti-Clickjacking
header("X-Frame-Options: DENY");

// 🛡️ SÉCURITÉ : Anti-CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🛡️ SÉCURITÉ : Anti-Bruteforce Applicatif
if (!isset($_SESSION['action_attempts'])) {
    $_SESSION['action_attempts'] = 0;
}

// Récupération des données actuelles
$stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité (CSRF).");
    }

    // --- ACTION 1 : MISE À JOUR DU PROFIL ---
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = preg_replace('/[^0-9+]/', '', $_POST['phone_number']);
        $language = in_array($_POST['language'], ['fr', 'en']) ? $_POST['language'] : 'fr';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $sql = "UPDATE user SET name=?, email=?, phone_number=?, language=?, dark_theme=?, activate_notification=? WHERE user_id=?";
                $pdo->prepare($sql)->execute([
                    $name, $email, $phone, $language, 
                    isset($_POST['dark_theme']) ? 1 : 0, 
                    isset($_POST['activate_notification']) ? 1 : 0, 
                    $user_id
                ]);
                $_SESSION['user_name'] = $name; 
                $_SESSION['language'] = $language; // On met à jour la langue de la session en direct !
                
                $success_message = ($language == 'fr') ? "Profil mis à jour avec succès." : "Profile updated successfully.";
                
                $user['name'] = $name; $user['email'] = $email; $user['phone_number'] = $phone;
                $user['language'] = $language; $user['dark_theme'] = isset($_POST['dark_theme']);
                $user['activate_notification'] = isset($_POST['activate_notification']);
            } catch (PDOException $e) {
                $error_message = ($e->getCode() == 23000) ? "Cet e-mail est déjà utilisé." : "Erreur base de données.";
            }
        } else {
            $error_message = "Format d'e-mail invalide.";
        }
    }

    // --- ACTION 2 : PHOTO DE PROFIL ---
    elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['profile_pic']['tmp_name'];
        $mime = mime_content_type($tmp);
        
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            $safe_folder = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($user['name'])) . '_' . $user_id;
            $dir_path = __DIR__ . "/images/profile/" . $safe_folder;
            if (!is_dir($dir_path)) mkdir($dir_path, 0755, true);

            $img = null;
            if ($mime === 'image/jpeg') $img = imagecreatefromjpeg($tmp);
            elseif ($mime === 'image/png') $img = imagecreatefrompng($tmp);
            elseif ($mime === 'image/webp') $img = imagecreatefromwebp($tmp);

            if ($img) {
                $dest = $dir_path . "/image.webp";
                $db_path = "images/profile/" . $safe_folder . "/image.webp";
                imagewebp($img, $dest, 80);
                imagedestroy($img);
                $pdo->prepare("UPDATE user SET profile_picture=? WHERE user_id=?")->execute([$db_path, $user_id]);
                $user['profile_picture'] = $db_path;
                $success_message = "Photo mise à jour.";
            }
        } else {
            $error_message = "Format invalide (JPG/PNG/WEBP uniquement).";
        }
    }

    // --- ACTION 3 : MOT DE PASSE ---
    elseif (isset($_POST['change_pwd'])) {
        if ($_SESSION['action_attempts'] >= 5) {
            $error_message = "Trop de tentatives. Veuillez vous reconnecter.";
        } else {
            if (password_verify($_POST['old_pwd'], $user['password'])) {
                if ($_POST['new_pwd'] === $_POST['conf_pwd'] && preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $_POST['new_pwd'])) {
                    $hash = password_hash($_POST['new_pwd'], PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE user SET password=? WHERE user_id=?")->execute([$hash, $user_id]);
                    $success_message = "Mot de passe modifié.";
                    $_SESSION['action_attempts'] = 0;
                } else {
                    $error_message = "Mot de passe invalide (8 caractères, 1 Majuscule, 1 Chiffre min).";
                    $_SESSION['action_attempts']++;
                }
            } else {
                $error_message = "Ancien mot de passe incorrect.";
                $_SESSION['action_attempts']++;
            }
        }
    }

    // --- ACTION 4 : RGPD EXPORT ---
    elseif (isset($_POST['export_data'])) {
        $export = $user;
        unset($export['password']); 
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="mes_donnees_momo.json"');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    // --- ACTION 5 : RGPD SUPPRESSION TOTALE ---
    elseif (isset($_POST['delete_acc'])) {
        $safe_folder = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($user['name'])) . '_' . $user_id;
        $dir_path = __DIR__ . "/images/profile/" . $safe_folder;

        if (is_dir($dir_path) && strpos(realpath($dir_path), 'images/profile') !== false) {
            $files = glob($dir_path . '/*');
            foreach ($files as $file) { if(is_file($file)) unlink($file); }
            rmdir($dir_path);
        }

        $pdo->prepare("DELETE FROM user WHERE user_id=?")->execute([$user_id]);
        session_unset(); session_destroy();
        header('Location: index.php');
        exit();
    }
    // --- ACTION 6 : DÉCONNEXION ---
    elseif (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }
}

// =========================================================================
// FETCH BOOKINGS AND INVOICES
// =========================================================================
$lang = $_SESSION['language'] ?? 'fr';

try {
    $bookStmt = $pdo->prepare("
        SELECT tb.*, td.departure_date, td.return_date, td.duration_nights,
               COALESCE(tpt.name, tpt_fb.name) AS package_name,
               dt.name AS city_name,
               inv.invoice_id, inv.invoice_number, inv.amount AS invoice_amount
        FROM trip_booking tb
        JOIN trip_date td ON tb.date_id = td.date_id
        JOIN trip_package tp ON td.package_id = tp.package_id
        LEFT JOIN trip_package_translation tpt ON tp.package_id = tpt.package_id AND tpt.language_code = :lang
        LEFT JOIN trip_package_translation tpt_fb ON tp.package_id = tpt_fb.package_id AND tpt_fb.language_code = 'fr'
        LEFT JOIN destination_translation dt ON tp.destination_id = dt.destination_id AND dt.language_code = :lang2
        LEFT JOIN invoice inv ON tb.booking_id = inv.booking_id
        WHERE tb.user_id = :uid
        ORDER BY tb.created_at DESC
    ");
    $bookStmt->execute(['lang' => $lang, 'lang2' => $lang, 'uid' => $user_id]);
    $my_bookings = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_bookings = [];
}

// =========================================================================
// DICTIONNAIRE DE TRADUCTION COMPLET
// =========================================================================

$translations = [
    'fr' => [
        'hero_title' => "Espace Personnel",
        'dascard-h3' => "📸 Ma Photo",
        'update_image' => "Mettre à jour l'image",
        'factures' => "💳 Mes Factures",
        'my_trips' => "✈️ Mes Voyages",
        'support' => "💬 Support",
        'no_trips' => "Vous n'avez pas encore de réservation.",
        'no_invoices' => "Aucune facture disponible.",
        'download_pdf' => "Télécharger PDF",
        'status_confirmed' => "Confirmé",
        'status_cancelled' => "Annulé",
        'contact_support' => "Contacter le support",
        'support_desc' => "Une question ou un problème avec votre réservation ? Notre équipe vous répond.",
        'info_title' => "📝 Informations",
        'fullname' => "Nom complet",
        'phone' => "Téléphone",
        'email' => "E-mail",
        'language' => "Langue",
        'dark_theme' => "Thème sombre",
        'notifications' => "Notifications",
        'save_info' => "Enregistrer les infos",
        'security_title' => "Sécurité",
        'current_pwd' => "Mot de passe actuel",
        'new_pwd' => "Nouveau mot de passe",
        'new_pwd_placeholder' => "Min 8 car., 1 Maj, 1 chiffre",
        'confirm_pwd' => "Confirmer le mot de passe",
        'update_pwd' => "Modifier le mot de passe",
        'danger_zone' => "Zone de Danger (RGPD)",
        'danger_desc' => "Exportez vos données ou supprimez définitivement votre compte Momo Travel.",
        'export_btn' => "📥 Exporter (JSON)",
        'logout_btn' => "🚪 Déconnexion",
        'logout_confirm' => "Êtes-vous sûr de vouloir vous déconnecter ?",
        'delete_btn' => "🗑️ Supprimer mon compte",
        'delete_confirm' => "Action irréversible ! Supprimer le compte ?"
    ],
    'en' => [
        'hero_title' => "Personal Area",
        'dascard-h3' => "📸 My Photo",
        'update_image' => "Update image",
        'factures' => "💳 My Invoices",
        'my_trips' => "✈️ My Trips",
        'support' => "💬 Support",
        'no_trips' => "You have no bookings yet.",
        'no_invoices' => "No invoices available.",
        'download_pdf' => "Download PDF",
        'status_confirmed' => "Confirmed",
        'status_cancelled' => "Cancelled",
        'contact_support' => "Contact support",
        'support_desc' => "A question or issue with your booking? Our team is here to help.",
        'info_title' => "📝 Information",
        'fullname' => "Full Name",
        'phone' => "Phone",
        'email' => "Email",
        'language' => "Language",
        'dark_theme' => "Dark theme",
        'notifications' => "Notifications",
        'save_info' => "Save information",
        'security_title' => "Security",
        'current_pwd' => "Current password",
        'new_pwd' => "New password",
        'new_pwd_placeholder' => "Min 8 chars, 1 Uppercase, 1 Number",
        'confirm_pwd' => "Confirm password",
        'update_pwd' => "Update password",
        'danger_zone' => "Danger Zone (GDPR)",
        'danger_desc' => "Export your data or permanently delete your Momo Travel account.",
        'export_btn' => "📥 Export (JSON)",
        'logout_btn' => "🚪 Logout",
        'logout_confirm' => "Are you sure you want to log out?",
        'delete_btn' => "🗑️ Delete my account",
        'delete_confirm' => "Irreversible action! Delete account?"
    ]
];
$t = $translations[$lang];

// =========================================================================
// 2. ZONE VUE (AFFICHAGE HTML & CSS INTÉGRÉ)
// =========================================================================
$page_title = 'Momo - Mon Espace';
include 'header.php';
?>

<style>
    .dash-wrapper { max-width: 1200px; margin: 3rem auto; padding: 0 2%; width: 100%; flex-grow: 1; }
    .dash-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; align-items: start; }
    @media (min-width: 800px) { .dash-grid { grid-template-columns: repeat(2, 1fr); } .col-full { grid-column: 1 / -1; } }
    
    .dash-card { background: white; border: 1px solid #eaeaea; border-radius: 8px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03); height: 100%; display: flex; flex-direction: column; }
    .dash-card h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: #c1272d; margin-bottom: 1.5rem; border-bottom: 2px solid #f9f9f9; padding-bottom: 0.5rem; }
    
    .dash-form { display: flex; flex-direction: column; flex-grow: 1; }
    .dash-form input[type="text"], .dash-form input[type="email"], .dash-form input[type="tel"], .dash-form input[type="password"], .dash-form select { width: 100%; padding: 1rem; border: 1px solid #ccc; border-radius: 4px; font-family: 'Roboto', sans-serif; font-size: 1rem; background-color: #fafafa; margin-bottom: 1.2rem; box-sizing: border-box; }
    .dash-form input:focus, .dash-form select:focus { outline: none; border-color: #c1272d; background-color: #fff; }
    .dash-form label { font-size: 0.9rem; color: #555; font-weight: bold; display: block; margin-bottom: 0.3rem; }
    
    .dash-btn { background-color: #c1272d; color: white; border: none; padding: 1rem; font-size: 1.1rem; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; transition: 0.3s; text-align: center; display: inline-block; text-decoration: none; box-sizing: border-box; margin-top: auto; }
    .dash-btn:hover { background-color: #a01f24; }
    .dash-btn-stripe { background-color: #6772e5; }
    .dash-btn-stripe:hover { background-color: #5469d4; }
    .dash-btn-danger { background: transparent; color: #c1272d; border: 2px solid #c1272d; }
    .dash-btn-danger:hover { background: #fff5f5; }
</style>

<section class="hero">
    <h1><?= $t['hero_title']; ?></h1>
</section>

<main class="dash-wrapper">
    
    <?php if($success_message): ?>
        <div style="background: #e6f4ea; color: #1e8e3e; padding: 1rem; border-radius: 4px; font-weight: bold; margin-bottom: 2rem; text-align: center;">✓ <?= $success_message ?></div>
    <?php endif; ?>
    <?php if($error_message): ?>
        <div style="background: #fee; color: #c1272d; padding: 1rem; border-radius: 4px; font-weight: bold; margin-bottom: 2rem; text-align: center;">⚠️ <?= $error_message ?></div>
    <?php endif; ?>

    <div class="dash-grid">

        <section class="dash-card" style="text-align: center;">
            <h3><?= $t['dascard-h3']; ?></h3>
            <div style="width: 150px; height: 150px; margin: 0 auto 1.5rem; border-radius: 50%; overflow: hidden; border: 4px solid #eee;">
                <?php 
                $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=c1272d&color=fff&size=150';
                if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
                    $avatar_url = htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8');
                }
                ?>
                <img src="<?= $avatar_url ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="dash-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="file" name="profile_pic" accept="image/*" style="padding: 0.5rem; background: transparent; border: none; margin-bottom: 1rem;">
                <button type="submit" class="dash-btn"><?= $t['update_image']; ?></button>
            </form>
        </section>

        <!-- My Trips -->
        <section class="dash-card col-full">
            <h3><?= $t['my_trips'] ?></h3>
            <?php if (empty($my_bookings)): ?>
                <p style="color:#888;"><?= $t['no_trips'] ?></p>
                <a href="book" class="dash-btn" style="margin-top:1rem; display:inline-block; width:auto; padding: 0.8rem 1.5rem;"><?= $lang === 'fr' ? 'Explorer les voyages' : 'Explore trips' ?></a>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                        <thead>
                            <tr style="background:#f9f9f9;">
                                <th style="text-align:left; padding:0.7rem; border-bottom:2px solid #eee; color:#555;"><?= $lang === 'fr' ? 'Voyage' : 'Trip' ?></th>
                                <th style="text-align:left; padding:0.7rem; border-bottom:2px solid #eee; color:#555;"><?= $lang === 'fr' ? 'Départ' : 'Departure' ?></th>
                                <th style="text-align:left; padding:0.7rem; border-bottom:2px solid #eee; color:#555;"><?= $lang === 'fr' ? 'Retour' : 'Return' ?></th>
                                <th style="text-align:left; padding:0.7rem; border-bottom:2px solid #eee; color:#555;"><?= $lang === 'fr' ? 'Voyageurs' : 'Travelers' ?></th>
                                <th style="text-align:left; padding:0.7rem; border-bottom:2px solid #eee; color:#555;"><?= $lang === 'fr' ? 'Total' : 'Total' ?></th>
                                <th style="text-align:left; padding:0.7rem; border-bottom:2px solid #eee; color:#555;"><?= $lang === 'fr' ? 'Statut' : 'Status' ?></th>
                                <th style="padding:0.7rem; border-bottom:2px solid #eee;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($my_bookings as $bk): ?>
                            <tr>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0;">
                                    <strong><?= htmlspecialchars($bk['package_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <br><small style="color:#888;"><?= htmlspecialchars($bk['city_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0;"><?= date('d/m/Y', strtotime($bk['departure_date'])) ?></td>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0;"><?= date('d/m/Y', strtotime($bk['return_date'])) ?></td>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0;"><?= (int)$bk['num_participants'] ?></td>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0; font-weight:bold;">€<?= number_format((float)$bk['total_price'], 2, ',', ' ') ?></td>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0;">
                                    <span style="display:inline-block; padding:0.2rem 0.6rem; border-radius:12px; font-size:0.78rem; font-weight:bold; background:<?= $bk['status'] === 'confirmed' ? '#e6f4ea' : '#fee' ?>; color:<?= $bk['status'] === 'confirmed' ? '#1e8e3e' : '#c1272d' ?>;">
                                        <?= $bk['status'] === 'confirmed' ? $t['status_confirmed'] : $t['status_cancelled'] ?>
                                    </span>
                                </td>
                                <td style="padding:0.7rem; border-bottom:1px solid #f0f0f0;">
                                    <?php if ($bk['invoice_id']): ?>
                                        <a href="invoice_pdf?id=<?= (int)$bk['invoice_id'] ?>" target="_blank"
                                           style="color:#c1272d; font-size:0.82rem; text-decoration:none; font-weight:bold;">
                                            📄 <?= $t['download_pdf'] ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Support Tickets -->
        <section class="dash-card">
            <h3><?= $t['support'] ?></h3>
            <p style="color:#666; line-height:1.6; font-size:1rem; flex-grow:1;"><?= $t['support_desc'] ?></p>
            <a href="my_tickets" class="dash-btn" style="background:#4a1c35; margin-top:1rem;"><?= $t['contact_support'] ?></a>
        </section>

        <section class="dash-card">
            <h3><?= $t['info_title']; ?></h3>
            <form method="POST" class="dash-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="update_profile" value="1">
                
                <label><?= $t['fullname']; ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                
                <label><?= $t['phone']; ?></label>
                <input type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                
                <label><?= $t['email']; ?></label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" required>

                <label><?= $t['language']; ?></label>
                <select name="language">
                    <option value="fr" <?= $user['language']=='fr'?'selected':'' ?>>Français 🇫🇷</option>
                    <option value="en" <?= $user['language']=='en'?'selected':'' ?>>English 🇬🇧</option>
                </select>

                <div style="margin: 1rem 0 1.5rem; display: flex; flex-direction: column; gap: 0.8rem;">
                    <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="dark_theme" <?= $user['dark_theme']?'checked':'' ?> style="width: 20px; margin:0;">
                        <?= $t['dark_theme']; ?>
                    </label>
                    <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="activate_notification" <?= $user['activate_notification']?'checked':'' ?> style="width: 20px; margin:0;">
                        <?= $t['notifications']; ?>
                    </label>
                </div>

                <button type="submit" class="dash-btn"><?= $t['save_info']; ?></button>
            </form>
        </section>

        <section class="dash-card">
            <h3>🔒 <?= $t['security_title']; ?></h3>
            <form method="POST" class="dash-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="change_pwd" value="1">
                
                <label><?= $t['current_pwd']; ?></label>
                <input type="password" name="old_pwd" required>
                
                <label><?= $t['new_pwd']; ?></label>
                <input type="password" name="new_pwd" placeholder="<?= $t['new_pwd_placeholder']; ?>" required>
                
                <label><?= $t['confirm_pwd']; ?></label>
                <input type="password" name="conf_pwd" required>
                
                <button type="submit" class="dash-btn" style="background-color: #4a1c35;"><?= $t['update_pwd']; ?></button>
            </form>
        </section>

        <section class="dash-card col-full" style="background-color: #fff9f9; border: 1px dashed #c1272d;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h3 style="color: #c1272d; border: none; margin-bottom: 0.5rem;"><?= $t['danger_zone']; ?></h3>
                <p style="color: #666;"><?= $t['danger_desc']; ?></p>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <form method="POST" style="flex: 1; min-width: 250px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="export_data" class="dash-btn" style="background: #333;"><?= $t['export_btn']; ?></button>
                </form>

                <div style="display: flex; justify-content: flex-end; flex: 1; min-width: 250px;">
                    <form method="POST" onsubmit="return confirm('<?= htmlspecialchars($t['logout_confirm'], ENT_QUOTES) ?>');" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="logout" class="dash-btn" style="background-color: #4a1c35;">
                            <?= $t['logout_btn']; ?>
                        </button>
                    </form>
                </div>
                
                <form method="POST" onsubmit="return confirm('<?= htmlspecialchars($t['delete_confirm'], ENT_QUOTES) ?>');" style="flex: 1; min-width: 250px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="delete_acc" class="dash-btn dash-btn-danger"><?= $t['delete_btn']; ?></button>
                </form>
            </div>
        </section>

    </div>
</main>

<?php include 'footer.php'; ?>