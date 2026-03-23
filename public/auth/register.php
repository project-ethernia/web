<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!empty($_SESSION['is_user'])) {
    header("Location: /");
    exit;
}

$error = '';
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Érvénytelen kérés!";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            $error = "Minden mező kitöltése kötelező!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Érvénytelen e-mail cím formátum!";
        } elseif (strlen($username) < 3 || strlen($username) > 16) {
            $error = "A felhasználónév 3 és 16 karakter közötti lehet!";
        } elseif (preg_match('/[^A-Za-z0-9_]/', $username)) {
            $error = "A felhasználónév csak betűket, számokat és alulvonást tartalmazhat!";
        } elseif ($password !== $passwordConfirm) {
            $error = "A két jelszó nem egyezik!";
        } elseif (strlen($password) < 8) {
            $error = "A jelszónak legalább 8 karakter hosszúnak kell lennie!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            if ($stmt->fetch()) {
                $error = "Ez a felhasználónév vagy e-mail cím már foglalt!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
                
                try {
                    $insertStmt->execute([
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => $hashedPassword
                    ]);
                    $success = "Sikeres regisztráció! Most már bejelentkezhetsz.";
                } catch (Exception $e) {
                    $error = "Hiba történt a regisztráció során.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Regisztráció</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= time(); ?>">
</head>
<body class="auth-body">

<div class="auth-container glass-panel">
    <h1 class="auth-title">Regisztráció</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php else: ?>
        <form method="POST" action="register.php" class="auth-form" id="register-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="username">Felhasználónév</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-mail cím</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Jelszó</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Jelszó megerősítése</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            
            <button type="submit" class="btn btn-glow w-100">Regisztráció</button>
        </form>
    <?php endif; ?>
    
    <div class="auth-footer">
        <p>Már van fiókod? <a href="/auth/login.php">Bejelentkezés</a></p>
        <a href="/" class="back-link">&larr; Vissza a főoldalra</a>
    </div>
</div>

<script src="/assets/js/auth.js?v=<?= time(); ?>"></script>
</body>
</html>