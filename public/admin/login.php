<?php
session_start();
require_once __DIR__ . '/../database.php';

// Ha már teljesen be van jelentkezve, vigyük a főoldalra
if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

// --- BEÁLLÍTÁSOK ---
$discordWebhookUrl = "https://discord.com/api/webhooks/1486000917999386738/HvV8ve01gurjCAna3mb7sZEG9BzomI546ZEwgH1t7NbWMzvso--jGFhz49OnmkLxHMFJ";
$maxFailedAttempts = 3; // Hány rontás után tiltson?
$lockoutMinutes = 15;   // Hány percig tartson a tiltás?
// -------------------

$error = '';
$step = isset($_SESSION['pending_2fa_admin_id']) ? 2 : 1;
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'Ismeretlen';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Ismeretlen';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- 1. LÉPÉS: FELHASZNÁLÓNÉV ÉS JELSZÓ ELLENŐRZÉSE ---
    if (isset($_POST['action']) && $_POST['action'] === 'login_step_1') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Brute-force ellenőrzés (Zárolva van?)
            if ($admin['lockout_time'] && strtotime($admin['lockout_time']) > time()) {
                $remaining = ceil((strtotime($admin['lockout_time']) - time()) / 60);
                $error = "Túl sok hibás próbálkozás! A fiók zárolva. Próbáld újra $remaining perc múlva.";
            } 
            // Jelszó ellenőrzés (Nálad password_verify kell, ha hashelted, most feltételezzük, hogy hashelt)
            elseif (password_verify($password, $admin['password'])) {
                // Sikeres jelszó! Generáljunk egy 2FA kódot
                $code = sprintf("%06d", mt_rand(1, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                // Adatbázis frissítése (rontások nullázása, kód beírása)
                $update = $pdo->prepare("UPDATE admins SET failed_logins = 0, lockout_time = NULL, two_factor_code = ?, two_factor_expires = ? WHERE id = ?");
                $update->execute([$code, $expires, $admin['id']]);

                // Ideiglenes session a 2. lépéshez
                $_SESSION['pending_2fa_admin_id'] = $admin['id'];
                $step = 2;

                // Webhook küldése a Discordra
                $msg = "🔐 **{$admin['username']}** próbál belépni az Admin Panelre!\n🌍 **IP cím:** `{$clientIp}`\n🔑 **Hitelesítő kód:** `{$code}`\n⏳ *Érvényes 5 percig.*";
                $json_data = json_encode(["content" => $msg]);
                $ch = curl_init($discordWebhookUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);

            } else {
                // Rossz jelszó! Brute-force számláló növelése
                $failed = (int)$admin['failed_logins'] + 1;
                $lockout = NULL;
                if ($failed >= $maxFailedAttempts) {
                    $lockout = date('Y-m-d H:i:s', strtotime("+$lockoutMinutes minutes"));
                }
                $update = $pdo->prepare("UPDATE admins SET failed_logins = ?, lockout_time = ? WHERE id = ?");
                $update->execute([$failed, $lockout, $admin['id']]);
                
                $error = "Hibás felhasználónév vagy jelszó!";
            }
        } else {
            $error = "Hibás felhasználónév vagy jelszó!";
        }
    }

    // --- 2. LÉPÉS: 2FA KÓD ELLENŐRZÉSE ---
    elseif (isset($_POST['action']) && $_POST['action'] === 'login_step_2') {
        $code = trim($_POST['twofa_code'] ?? '');
        $adminId = $_SESSION['pending_2fa_admin_id'] ?? 0;

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if ($admin && $admin['two_factor_code'] === $code && strtotime($admin['two_factor_expires']) > time()) {
            // SIKERES TELJES BELÉPÉS!
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            
            // Biztonsági (Hijacking elleni) adatok mentése
            $_SESSION['admin_ip'] = $clientIp;
            $_SESSION['admin_user_agent'] = $userAgent;

            // Kód törlése az adatbázisból, IP frissítése
            $clear = $pdo->prepare("UPDATE admins SET two_factor_code = NULL, two_factor_expires = NULL, last_ip = ? WHERE id = ?");
            $clear->execute([$clientIp, $admin['id']]);

            unset($_SESSION['pending_2fa_admin_id']);
            header('Location: /admin/index.php');
            exit;
        } else {
            $error = "Hibás vagy lejárt hitelesítő kód!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA - Admin Bejelentkezés</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: var(--bg-deep); }
        .login-box { width: 100%; max-width: 400px; padding: 3rem 2rem; border-radius: 16px; text-align: center; }
        .login-logo { width: 80px; margin-bottom: 1.5rem; filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.5)); }
        .login-title { font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 700; }
        .login-subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem; }
        .form-group { text-align: left; margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .form-group input { width: 100%; padding: 0.8rem 1rem; background: rgba(0,0,0,0.4); border: 1px solid var(--glass-border); border-radius: 8px; color: #fff; font-size: 1rem; outline: none; transition: border-color 0.3s; }
        .form-group input:focus { border-color: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.2); }
        .btn-login { width: 100%; padding: 0.8rem; font-size: 1rem; }
        .error-msg { background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.8rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; border: 1px solid rgba(239, 68, 68, 0.3); }
        .code-input { text-align: center; font-size: 1.5rem !important; letter-spacing: 0.2em; font-weight: 700; }
    </style>
</head>
<body>

<div class="login-box glass-panel">
    <img src="/assets/img/logo.png" alt="ETHERNIA" class="login-logo" onerror="this.src='https://minotar.net/helm/Steve/80.png'">
    
    <?php if ($step === 1): ?>
        <h1 class="login-title">Vezérlőpult</h1>
        <p class="login-subtitle">Jelentkezz be az adminisztrációs felületre.</p>

        <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="login_step_1">
            <div class="form-group">
                <label>Felhasználónév</label>
                <input type="text" name="username" required autocomplete="off" autofocus>
            </div>
            <div class="form-group">
                <label>Jelszó</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-glow-red btn-login">Bejelentkezés</button>
        </form>

    <?php else: ?>
        <h1 class="login-title">Kétlépcsős Azonosítás</h1>
        <p class="login-subtitle">A hitelesítő kódot elküldtük az ETHERNIA Discord szerverére.</p>

        <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="login_step_2">
            <div class="form-group">
                <label>6 jegyű kód</label>
                <input type="text" name="twofa_code" class="code-input" maxlength="6" required autocomplete="off" autofocus placeholder="123456">
            </div>
            <button type="submit" class="btn btn-glow-red btn-login">Hitelesítés</button>
            <div style="margin-top: 1rem;">
                <a href="/admin/login.php?cancel=1" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">Vissza a belépéshez</a>
                <?php if(isset($_GET['cancel'])) { unset($_SESSION['pending_2fa_admin_id']); header("Location: /admin/login.php"); exit; } ?>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>