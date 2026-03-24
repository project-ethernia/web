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
            // Generálunk egy egyedi, biztonságos tokent (érvényes 1 óráig)
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$token, $expires, $user['id']]);

            // EMAIL KÜLDÉS LOGIKÁJA
            $resetLink = "https://{$_SERVER['HTTP_HOST']}/auth/reset.php?token=$token";
            $subject = "Jelszo Visszaallitas - ETHERNIA";
            $message = "Szia {$user['username']}!\n\nKaptunk egy kérést a jelszavad visszaállítására.\nKattints az alábbi linkre a folytatáshoz (1 óráig érvényes):\n\n$resetLink\n\nHa nem te kérted, hagyd figyelmen kívül ezt az e-mailt!";
            $headers = "From: noreply@ethernia.hu";

            // (Megjegyzés: Éles szerveren a mail() függvény helyett PHPMailer használata ajánlott!)
            @mail($email, $subject, $message, $headers);
            
            // Biztonság: Még ha nincs is ilyen email, ugyanazt írjuk ki, hogy ne tudják lekerdezni, kinek van fiókja
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Montserrat:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= time(); ?>">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box glass">
            
            <div class="auth-header">
                <span class="material-symbols-rounded" style="font-size: 4rem; color: var(--primary); filter: drop-shadow(0 0 15px var(--primary-glow));">lock_reset</span>
                <h1 class="auth-title" style="margin-top: 1rem;">JELSZÓ RESET</h1>
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
                    <button type="submit" class="btn btn-glow btn-full">Küldés <span class="material-symbols-rounded">send</span></button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                Eszembe jutott! <a href="/auth/login.php">Vissza a belépéshez</a>
            </div>
        </div>
    </div>
</body>
</html>