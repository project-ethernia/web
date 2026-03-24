<?php
session_start();
require_once __DIR__ . '/../database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$token, $expires, $user['id']]);

            $resetLink = "https://{$_SERVER['HTTP_HOST']}/auth/reset-password.php?token=$token";
            $subject = "Jelszo Visszaallitas - ETHERNIA";
            $message = "Szia {$user['username']}!\n\nKaptunk egy kérést a jelszavad visszaállítására.\nKattints az alábbi linkre a folytatáshoz (1 óráig érvényes):\n\n$resetLink\n\nHa nem te kérted, hagyd figyelmen kívül ezt az e-mailt!";
            $headers = "From: noreply@ethernia.hu";

            @mail($email, $subject, $message, $headers);
        }
        $success = 'Ha a megadott e-mail cím létezik a rendszerünkben, elküldtük rá a visszaállító linket!';
    } else {
        $error = 'Kérlek, adj meg egy érvényes e-mail címet!';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Elfelejtett Jelszó | ETHERNIA</title>
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
                <span class="material-symbols-rounded auth-main-icon">lock_reset</span>
                <h1 class="auth-title">JELSZÓ RESET</h1>
                <p class="auth-subtitle">Add meg az e-mail címedet, és küldünk egy visszaállító linket.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="auth-alert success"><?= htmlspecialchars($success) ?></div>
            <?php else: ?>
                <form method="POST" action="/auth/forgot.php">
                    <div class="input-group">
                        <label for="email">E-mail cím</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-rounded input-icon">mail</span>
                            <input type="email" id="email" name="email" required placeholder="valami@email.hu">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-auth btn-full">Küldés</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                Eszembe jutott! <a href="/auth/login.php">Vissza a belépéshez</a>
            </div>
        </div>
    </div>
</body>
</html>