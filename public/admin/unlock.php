<?php
session_start();
require_once __DIR__ . '/../database.php';

$token = $_GET['token'] ?? '';
$success = false;

// JAVÍTÁS: Kivettük a szigorú && strlen($token) === 32 ellenőrzést,
// hogy ha az adatbázisod esetleg csonkolta a kódot, akkor is működjön!
if (!empty($token)) {
    // Megkeressük, kié ez a kód
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE unlock_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Töröljük a tiltást és a token-t
        $update = $pdo->prepare("UPDATE admins SET failed_logins = 0, lockout_time = NULL, unlock_token = NULL WHERE id = ?");
        $update->execute([$admin['id']]);
        
        // Töröljük a helyi session tiltást is
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/css/login.css?v=<?= time(); ?>">
    <style>
        .success-icon { font-size: 5rem; color: #22c55e; filter: drop-shadow(0 0 15px rgba(34, 197, 94, 0.4)); margin-bottom: 1rem; }
        .error-icon { font-size: 5rem; color: #ef4444; filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.4)); margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-box glass-panel">
        <?php if ($success): ?>
            <div class="success-icon">✔️</div>
            <h1 class="login-title">Fiók Feloldva!</h1>
            <p class="login-subtitle">A fiók zárolása sikeresen megszűnt. Most már be tudsz jelentkezni.</p>
            <a href="/admin/login.php" class="btn btn-glow-red" style="text-decoration:none; display:inline-block; margin-top:1rem;">Tovább a belépéshez</a>
        <?php else: ?>
            <div class="error-icon">❌</div>
            <h1 class="login-title">Érvénytelen Link</h1>
            <p class="login-subtitle">Ez a feloldó link érvénytelen vagy már felhasználták.</p>
            <a href="/admin/login.php" class="btn btn-outline" style="text-decoration:none; display:inline-block; margin-top:1rem; border:1px solid #ef4444; color:#ef4444;">Vissza a belépéshez</a>
        <?php endif; ?>
    </div>
</body>
</html>