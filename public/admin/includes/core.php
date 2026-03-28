<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../config/roles.php'; 
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/discord.php';

define('RCON_HOST',     '127.0.0.1');
define('RCON_PORT',     25575);
define('RCON_PASSWORD', 'a_te_rcon_jelszavad');
define('RCON_TIMEOUT',  3);

require_once __DIR__ . '/rcon.php';

function getRealIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP']; 
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]); 
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/auth/login.php');
    exit;
}

$current_ip = getRealIp();
$current_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

if (!isset($_SESSION['admin_ip']) || !isset($_SESSION['admin_user_agent']) || 
    $_SESSION['admin_ip'] !== $current_ip || $_SESSION['admin_user_agent'] !== $current_ua) {
    session_unset();
    session_destroy();
    header('Location: /admin/auth/login.php?error=security_breach');
    exit;
}

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

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

function setFlash(string $type, string $message): void {
    $_SESSION['flash_msg'] = $message;
    $_SESSION['flash_type'] = $type;
}

function displayFlash(): void {
    if (isset($_SESSION['flash_msg']) && isset($_SESSION['flash_type'])) {
        $msg = h($_SESSION['flash_msg']);
        $type = $_SESSION['flash_type'];
        $icon = $type === 'success' ? 'check_circle' : ($type === 'warning' ? 'warning' : 'error');
        echo '<div class="alert-box ' . $type . '"><span class="material-symbols-rounded">' . $icon . '</span>' . $msg . '</div>';
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    }
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'] ?? 'support';
?>