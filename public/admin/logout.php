<?php
// /admin/logout.php
session_start();

// minden session adat törlése
$_SESSION = [];

// session cookie érvénytelenítése (ha van)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// vissza a login oldalra
header('Location: /admin/login.php');
exit;
