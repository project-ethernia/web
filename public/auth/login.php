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
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['is_user'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            
            // AZ ÚJ ABSZOLÚT IDŐZÍTŐ ALAPJA: A belépés pillanata
            $_SESSION['login_time'] = time(); 
            
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
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= time(); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box glass">
            
            <div class="auth-header">
                <span class="material-symbols-rounded auth-main-icon">lock_person</span>
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
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'timeout'): ?>
                <div class="auth-alert error">A munkameneted lejárt (1 óra). Kérlek, lépj be újra!</div>
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

                <button type="submit" class="btn btn-auth btn-full">Belépés</button>
            </form>

            <div class="auth-footer">
                Még nincs fiókod? <a href="/auth/register.php">Regisztrálj itt!</a>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/auth.js?v=<?= time(); ?>"></script>
</body>
</html>