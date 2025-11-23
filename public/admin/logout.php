<?php
// /admin/logout.php
session_start();

require_once __DIR__ . '/_log.php';

// DB adatok – ugyanaz, mint news.php-ben
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

try {
    $pdo = new PDO(
        $DB_DSN,
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    // ha bármi elfüstöl, legalább lépjünk ki normálisan
    $pdo = null;
}

// logolás kijelentkezés előtt
if ($pdo && !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_username'])) {
    log_admin_action(
        $pdo,
        (int)$_SESSION['admin_id'],
        (string)$_SESSION['admin_username'],
        'Kijelentkezés',
        []
    );
}

// session törlése
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// vissza a login oldalra
header('Location: /admin/login.php');
exit;
