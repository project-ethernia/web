<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: /admin/news.php");
    exit;
}

$error = '';

if (empty($_SESSION['csrf_admin_token'])) {
    $_SESSION['csrf_admin_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_admin_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Érvénytelen kérés!";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Minden mező kitöltése kötelező!";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                header("Location: /admin/news.php");
                exit;
            } else {
                $error = "Helytelen adminisztrátori név vagy jelszó!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Admin Bejelentkezés</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/admin/assets/css/login.css?v=<?= time(); ?>">
</head>
<body class="admin-auth-body">

<div class="auth-container glass-panel">
    <div class="shield-icon">🛡️</div>
    <h1 class="auth-title">Vezérlőpult</h1>
    <p class="auth-subtitle">Szigorúan bizalmas terület</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="login.php" class="auth-form" id="admin-login-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_admin_token'] ?>">
        
        <div class="form-group">
            <label for="username">Adminisztrátor</label>
            <input type="text" id="username" name="username" required autocomplete="off">
        </div>
        
        <div class="form-group">
            <label for="password">Jelszó</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-glow-red w-100">Belépés a rendszerbe</button>
    </form>
    
    <div class="auth-footer">
        <a href="/" class="back-link">&larr; Vissza a publikus oldalra</a>
    </div>
</div>

<script src="/admin/assets/js/login.js?v=<?= time(); ?>"></script>
</body>
</html>