<?php
session_start();

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

// Behúzzuk a központi rangrendszert
require_once __DIR__ . '/config/roles.php';

$currentUser = !empty($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';
$adminId = !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

// EZ A SOR HIÁNYZOTT, EMIATT HALT HALÁLT AZ EGÉSZ OLDAL:
$adminRole = !empty($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'admin';
?>