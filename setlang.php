<?php
session_start();
$allowed = ['fr', 'en'];
$lang = $_GET['lang'] ?? 'fr';
if (in_array($lang, $allowed, true)) {
    $_SESSION['language'] = $lang;
}
$back = $_SERVER['HTTP_REFERER'] ?? 'index';
header('Location: ' . $back);
exit();
