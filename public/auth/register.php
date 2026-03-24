<?php
session_start();

if (!empty($_SESSION['is_user']) && $_SESSION['is_user'] === true) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/../database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($username && $email && $password && $password_confirm) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Érvénytelen e-mail cím formátum!';
        } elseif ($password !== $password_confirm) {
            $error = 'A megadott jelszavak nem egyeznek!';
        } elseif (strlen($password) < 6) {
            $error = 'A jelszónak legalább 6 karakterből kell állnia!';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'A felhasználónév csak betűket, számokat és alulvonást tartalmazhat!';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Ez a felhasználónév vagy e-mail cím már használatban van!';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                
                if ($insert->execute([$username, $email, $hashed])) {
                    header('Location: /auth/login.php?registered=1');
                    exit;
                } else {
                    $error = 'Szerver hiba történt a regisztráció során.';
                }
            }
        }
    } else {
        $error = 'Kérlek, tölts ki minden mezőt!';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Regisztráció | ETHERNIA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Montserrat:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= time(); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box glass">
            
            <div class="auth-header">
                <span class="material-symbols-rounded auth-main-icon">person_add</span>
                <h1 class="auth-title">CSATLAKOZZ</h1>
                <p class="auth-subtitle">Hozd létre ETHERNIA fiókodat</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/auth/register.php">
                <div class="input-group">
                    <label for="username">Felhasználónév</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-rounded input-icon">person</span>
                        <input type="text" id="username" name="username" required autocomplete="off" placeholder="Pontos Minecraft neved">
                    </div>
                </div>

                <div class="input-group">
                    <label for="email">E-mail cím</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-rounded input-icon">mail</span>
                        <input type="email" id="email" name="email" required placeholder="valami@email.hu">
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Jelszó</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-rounded input-icon">lock</span>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>
                    <div class="strength-meter">
                        <div class="strength-bar-bg"><div class="strength-bar" id="strength-bar"></div></div>
                        <span class="strength-text" id="strength-text">Írj be egy jelszót...</span>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password_confirm">Jelszó Újra</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-rounded input-icon">password</span>
                        <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••">
                        <span class="material-symbols-rounded match-icon" id="match-icon"></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-solid btn-full">Regisztráció</button>
            </form>

            <div class="auth-footer">
                Már van fiókod? <a href="/auth/login.php">Lépj be itt!</a>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/auth.js?v=<?= time(); ?>"></script>
</body>
</html>