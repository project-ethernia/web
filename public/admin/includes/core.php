<?php
// === ETHERNIA ADMIN CORE ===
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../config/roles.php'; 
require_once __DIR__ . '/logger.php';

function getRealIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; 
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]); 
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

// 2. Bejelentkezés ellenőrzése
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/auth/login.php');
    exit;
}

// 3. Kőkemény Biztonság: IP Cím és User-Agent ellenőrzés
$current_ip = getRealIp();
$current_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

if (!isset($_SESSION['admin_ip']) || !isset($_SESSION['admin_user_agent']) || 
    $_SESSION['admin_ip'] !== $current_ip || $_SESSION['admin_user_agent'] !== $current_ua) {
    
    session_unset();
    session_destroy();
    header('Location: /admin/auth/login.php?error=security_breach');
    exit;
}

// 4. Inaktivitás Figyelő (Session Timer) - 30 perc
$timeout_duration = 1800;
if (isset($_SESSION['admin_last_activity'])) {
    $elapsed_time = time() - $_SESSION['admin_last_activity'];
    if ($elapsed_time >= $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: /admin/auth/login.php?error=timeout');
        exit;
    }
}
$_SESSION['admin_last_activity'] = time();

// Globális segédfüggvények
function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'] ?? 'support'; // Alap rang, ha nincs
?>