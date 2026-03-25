<?php
// === ETHERNIA ADMIN CORE ===
// Ezt a fájlt minden védett admin oldal tetején be kell hívni!

session_start();

// Hibakeresés (Élesítéskor érdemes kikapcsolni)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Adatbázis kapcsolat behúzása
require_once __DIR__ . '/../../database.php';

// 2. Bejelentkezés ellenőrzése
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/auth/login.php');
    exit;
}

// 3. Kőkemény Biztonság: IP Cím és User-Agent ellenőrzés (Session Hijacking ellen)
$current_ip = $_SERVER['REMOTE_ADDR'];
$current_ua = $_SERVER['HTTP_USER_AGENT'];

if ($_SESSION['admin_ip'] !== $current_ip || $_SESSION['admin_user_agent'] !== $current_ua) {
    // Valaki ellopta a session-t, vagy hálózatot váltott az admin! Azonnali kiléptetés!
    session_unset();
    session_destroy();
    header('Location: /admin/auth/login.php?error=security_breach');
    exit;
}

// 4. Inaktivitás Figyelő (Session Timer) - 30 perc
$timeout_duration = 1800; // 30 perc másodpercben
if (isset($_SESSION['admin_last_activity'])) {
    $elapsed_time = time() - $_SESSION['admin_last_activity'];
    if ($elapsed_time >= $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: /admin/auth/login.php?error=timeout');
        exit;
    }
}
$_SESSION['admin_last_activity'] = time(); // Frissítjük az utolsó aktivitás idejét

// Globális segédfüggvények az admin felülethez
function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Alap admin adatok globális változókba
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'] ?? 'admin';
?>