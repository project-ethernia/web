<?php
session_start();

// Ha már be van lépve, azonnal menjen a főoldalra
if (!empty($_SESSION['is_user']) && $_SESSION['is_user'] === true) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/../database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // SIKERES BELÉPÉS
            $_SESSION['is_user'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['last_activity'] = time(); // A 30 perces limithez
            
            header('Location: /');
            exit;
        } else {
            $error = 'Hibás felhasználónév vagy jelszó!';
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
    <title>Belépés | ETHERNIA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Montserrat:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= time(); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box glass">
            
            <div class="auth-header">
                <img src="https://minotar.net/helm/Steve/100.png" alt="Avatar" class="avatar-img" id="dynamic-avatar">
                <h1 class="auth-title">ETHERNIA</h1>
                <p class="auth-subtitle">Jelentkezz be a fiókodba</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="auth-alert success">Sikeres regisztráció! Most már beléphetsz.</div>
            <?php endif; ?>

            <?php if (isset($_GET['reset'])): ?>
                <div class="auth-alert success">A jelszavad sikeresen megváltozott!</div>
            <?php endif; ?>

            <form method="POST" action="/auth/login.php">
                <div class="input-group">
                    <label for="username">Felhasználónév</label>
                    <div class="input-with-icon">
                        <span class="material-symbols-rounded input-icon">person</span>
                        <input type="text" id="username" name="username" required autocomplete="off" placeholder="Minecraft neved">
                    </div>
                </div>

                <div class="input-group">
                    <div class="label-row">
                        <label for="password">Jelszó</label>
                        <a href="/auth/forgot.php" class="forgot-link">Elfelejtetted?</a>
                    </div>
                    <div class="input-with-icon">
                        <span class="material-symbols-rounded input-icon">lock</span>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-glow btn-full">Belépés <span class="material-symbols-rounded">login</span></button>
            </form>

            <div class="auth-footer">
                Még nincs fiókod? <a href="/auth/register.php">Regisztrálj itt!</a>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/auth.js?v=<?= time(); ?>"></script>
</body>
</html>