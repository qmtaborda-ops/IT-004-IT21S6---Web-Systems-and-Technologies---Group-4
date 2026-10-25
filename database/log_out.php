<?php
session_start();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Go up one folder (../) from the database folder to find Landing
header("Location: ../landing-page/landing.html");
exit;
?>
