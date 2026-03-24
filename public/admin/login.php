<?php
session_start();
require_once __DIR__ . '/../database.php';

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

$discordWebhookUrl = "https://discord.com/api/webhooks/1486000917999386738/HvV8ve01gurjCAna3mb7sZEG9BzomI546ZEwgH1t7NbWMzvso--jGFhz49OnmkLxHMFJ";
$maxFailedAttempts = 3;
$lockoutMinutes = 15;

$error = '';
$step = isset($_SESSION['pending_2fa_admin_id']) ? 2 : 1;
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'Ismeretlen';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Ismeretlen';

$lockoutEnd = 0;

if (isset($_SESSION['lockout_end'])) {
    if ($_SESSION['lockout_end'] > time()) {
        $lockoutEnd = $_SESSION['lockout_end'];
        $error = "Túl sok hibás próbálkozás! A védelem aktiválódott.";
    } else {
        unset($_SESSION['lockout_end']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutEnd === 0) {
    if (isset($_POST['action']) && $_POST['action'] === 'login_step_1') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            if ($admin['lockout_time'] && strtotime($admin['lockout_time']) > time()) {
                $_SESSION['lockout_end'] = strtotime($admin['lockout_time']);
                $lockoutEnd = $_SESSION['lockout_end'];
                $error = "Túl sok hibás próbálkozás! A védelem aktiválódott.";
            } 
            elseif (password_verify($password, $admin['password'])) {
                $code = sprintf("%06d", mt_rand(1, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                $update = $pdo->prepare("UPDATE admins SET failed_logins = 0, lockout_time = NULL, two_factor_code = ?, two_factor_expires = ? WHERE id = ?");
                $update->execute([$code, $expires, $admin['id']]);

                $_SESSION['pending_2fa_admin_id'] = $admin['id'];
                $step = 2;

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
                $failed = (int)$admin['failed_logins'] + 1;
                if ($failed >= $maxFailedAttempts) {
                    $lockoutTimeStr = date('Y-m-d H:i:s', strtotime("+$lockoutMinutes minutes"));
                    $update = $pdo->prepare("UPDATE admins SET failed_logins = ?, lockout_time = ? WHERE id = ?");
                    $update->execute([$failed, $lockoutTimeStr, $admin['id']]);
                    
                    $_SESSION['lockout_end'] = strtotime($lockoutTimeStr);
                    $lockoutEnd = $_SESSION['lockout_end'];
                    $error = "Túl sok hibás próbálkozás! A védelem aktiválódott.";
                } else {
                    $update = $pdo->prepare("UPDATE admins SET failed_logins = ? WHERE id = ?");
                    $update->execute([$failed, $admin['id']]);
                    $error = "Hibás felhasználónév vagy jelszó! (Hátralévő próbálkozások: " . ($maxFailedAttempts - $failed) . ")";
                }
            }
        } else {
            $error = "Hibás felhasználónév vagy jelszó!";
        }
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'login_step_2') {
        $code = trim($_POST['twofa_code'] ?? '');
        $adminId = $_SESSION['pending_2fa_admin_id'] ?? 0;

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if ($admin && $admin['two_factor_code'] === $code && strtotime($admin['two_factor_expires']) > time()) {
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            $_SESSION['admin_ip'] = $clientIp;
            $_SESSION['admin_user_agent'] = $userAgent;

            $clear = $pdo->prepare("UPDATE admins SET two_factor_code = NULL, two_factor_expires = NULL, failed_logins = 0, last_ip = ? WHERE id = ?");
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@100..700&display=block">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/css/login.css?v=<?= time(); ?>">
</head>
<body>

<div class="login-box glass-panel">
    <img src="https://minotar.net/helm/Steve/80.png" alt="ETHERNIA" class="login-logo" id="dynamic-avatar">
    
    <?php if ($lockoutEnd > 0): ?>
        <h1 class="login-title">Fiók Zárolva</h1>
        <p class="login-subtitle">A biztonsági rendszer aktiválódott.</p>
        
        <div class="lockout-box">
            <span class="material-symbols-rounded lock-icon">lock</span>
            <div id="countdown" class="timer-display" data-end="<?= $lockoutEnd ?>">--:--</div>
            <p style="color: var(--text-muted); font-size: 0.85rem;">perc múlva újrapróbálhatod.</p>
        </div>

    <?php elseif ($step === 1): ?>
        <h1 class="login-title">Vezérlőpult</h1>
        <p class="login-subtitle">Jelentkezz be az adminisztrációs felületre.</p>

        <?php if ($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="login_step_1">
            <div class="form-group">
                <label>Felhasználónév</label>
                <input type="text" name="username" id="username-input" required autocomplete="off" autofocus>
            </div>
            <div class="form-group">
                <label>Jelszó</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-glow-red">Bejelentkezés</button>
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
            <button type="submit" class="btn btn-glow-red">Hitelesítés</button>
            <div style="margin-top: 1rem;">
                <a href="/admin/login.php?cancel=1" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">Vissza a belépéshez</a>
                <?php if(isset($_GET['cancel'])) { unset($_SESSION['pending_2fa_admin_id']); header("Location: /admin/login.php"); exit; } ?>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="/admin/assets/js/login.js?v=<?= time(); ?>"></script>
</body>
</html>