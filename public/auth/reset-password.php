<?php
session_start();
require_once __DIR__ . '/../database.php';

$error = '';
$validToken = false;
$user_id = null;

// 1. Megnézzük, van-e token az URL-ben (pl. ?token=abcd123...)
$token = $_GET['token'] ?? '';

if ($token) {
    // 2. Kikeressük az embert a token alapján
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // 3. Ellenőrizzük, hogy nem járt-e le az idő (1 óra)
        if (strtotime($user['reset_expires']) > time()) {
            $validToken = true;
            $user_id = $user['id'];
        } else {
            $error = 'A visszaállító link lejárt! Kérlek, igényelj egy újat.';
        }
    } else {
        $error = 'Érvénytelen visszaállító link! Lehet, hogy már felhasználtad.';
    }
} else {
    $error = 'Hiányzó biztonsági token! A linkből hiányzik az azonosító.';
}

// 4. Ha elküldték a formot ÉS érvényes a token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($password && $password_confirm) {
        if ($password !== $password_confirm) {
            $error = 'A megadott jelszavak nem egyeznek!';
        } elseif (strlen($password) < 6) {
            $error = 'A jelszónak legalább 6 karakterből kell állnia!';
        } else {
            // SIKERES CSERE: Titkosítjuk az újat, és töröljük a tokent a rendszerből!
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            
            if ($update->execute([$hashed, $user_id])) {
                // Átdobjuk a login oldalra egy siker üzenettel
                header('Location: /auth/login.php?reset=1');
                exit;
            } else {
                $error = 'Adatbázis hiba történt a jelszó mentésekor.';
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
    <title>Új Jelszó | ETHERNIA</title>
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
                <span class="material-symbols-rounded auth-main-icon">key</span>
                <h1 class="auth-title">ÚJ JELSZÓ</h1>
                <p class="auth-subtitle">Add meg az új titkos jelszavadat</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!$validToken): ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="/auth/forgot.php" class="btn btn-auth">Új link kérése</a>
                </div>
            <?php else: ?>
                <form method="POST" action="/auth/reset-password.php?token=<?= htmlspecialchars($token) ?>">
                    
                    <div class="input-group">
                        <label for="password">Új Jelszó</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-rounded input-icon">lock</span>
                            <input type="password" id="password" name="password" required placeholder="Új jelszavad">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password_confirm">Új Jelszó Újra</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-rounded input-icon">lock</span>
                            <input type="password" id="password_confirm" name="password_confirm" required placeholder="Új jelszavad megerősítése">
                            <span class="material-symbols-rounded match-icon" id="match-icon"></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-auth btn-full">Jelszó Mentése</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                Eszembe jutott a régi! <a href="/auth/login.php">Vissza a belépéshez</a>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/auth.js?v=<?= time(); ?>"></script>
</body>
</html>