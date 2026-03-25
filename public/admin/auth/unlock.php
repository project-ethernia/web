<?php
session_start();
require_once __DIR__ . '/../../database.php';

$token = $_GET['token'] ?? '';
$success = false;

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE unlock_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();

    if ($admin) {
        $update = $pdo->prepare("UPDATE admins SET failed_logins = 0, lockout_time = NULL, unlock_token = NULL WHERE id = ?");
        $update->execute([$admin['id']]);
        unset($_SESSION['lockout_end']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Zárolás Feloldása</title>
    <link rel="stylesheet" href="/assets/css/globals.css">
    <link rel="stylesheet" href="/admin/assets/css/login.css">
    <style>
        body { background: #0b0710; display: flex; align-items: center; justify-content: center; height: 100vh; color: #fff; font-family: 'Outfit', sans-serif;}
        .box { background: rgba(20,15,25,0.8); padding: 3rem; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.1); }
        .icon { font-size: 5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="box">
        <?php if ($success): ?>
            <div class="icon" style="color: #22c55e;">✔️</div>
            <h2>Fiók Feloldva!</h2>
            <a href="/admin/auth/login.php" style="color: #a855f7; text-decoration: none;">Tovább a belépéshez</a>
        <?php else: ?>
            <div class="icon" style="color: #ef4444;">❌</div>
            <h2>Érvénytelen Link</h2>
            <a href="/admin/auth/login.php" style="color: #ef4444; text-decoration: none;">Vissza a belépéshez</a>
        <?php endif; ?>
    </div>
</body>
</html>