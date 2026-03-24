<?php
session_start();

// 1. Alap ellenőrzés
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

// 2. Munkamenet eltérítés (Session Hijacking) elleni brutál védelem
$current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

if (
    empty($_SESSION['admin_ip']) || $_SESSION['admin_ip'] !== $current_ip ||
    empty($_SESSION['admin_user_agent']) || $_SESSION['admin_user_agent'] !== $current_user_agent
) {
    // Ha bármi megváltozott (másik netre csatlakozott, vagy hackelik), azonnal megsemmisítjük a sessiont!
    session_unset();
    session_destroy();
    header('Location: /admin/login.php?error=session_hijack');
    exit;
}

require_once __DIR__ . '/config/roles.php';

$currentUser = !empty($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';
$adminId = !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$adminRole = !empty($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'admin';
?>