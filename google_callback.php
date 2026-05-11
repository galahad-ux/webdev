<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_ID);
$client->setClientSecret(GOOGLE_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if(!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $user_info = $google_oauth->userinfo->get();
        
        $email = $user_info->email;
        $name = $user_info->name;

        // On vérifie si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT user_id, name FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Création automatique pour les nouveaux utilisateurs
            $dummy_pwd = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO user (name, email, password) VALUES (?, ?, ?)");
            $ins->execute([$name, $email, $dummy_pwd]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['user_id'];
            $name = $user['name'];
        }

        // Connexion de la session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;

        header('Location: index.php');
        exit();
    }
}
header('Location: auth.php?error=google');
exit();