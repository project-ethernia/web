<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!empty($_SESSION['is_user'])) {
    header("Location: /");
    exit;
}

$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Érvénytelen kérés!";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Minden mező kitöltése kötelező!";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['is_user'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                header("Location: /");
                exit;
            } else {
                $error = "Helytelen felhasználónév vagy jelszó!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Bejelentkezés</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= time(); ?>">
</head>
<body class="auth-body">

<div class="auth-container glass-panel">
    <h1 class="auth-title">Bejelentkezés</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="login.php" class="auth-form" id="login-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="form-group">
            <label for="username">Felhasználónév</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Jelszó</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-glow w-100">Bejelentkezés</button>
    </form>
    
    <div class="auth-footer">
        <p>Nincs még fiókod? <a href="/auth/register.php">Regisztráció</a></p>
        <a href="/" class="back-link">&larr; Vissza a főoldalra</a>
    </div>
</div>

<script src="/assets/js/auth.js?v=<?= time(); ?>"></script>
</body>
</html>