<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/config/roles.php';

$currentUser = !empty($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';
$adminId = !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
?>