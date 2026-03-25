<?php
session_start();
require_once __DIR__ . '/../../database.php';

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

if (isset($_GET['force_check'])) {
    unset($_SESSION['lockout_end']);
    header("Location: /admin/auth/login.php");
    exit;
}

$discordWebhookUrl = "https://discord.com/api/webhooks/1486000917999386738/HvV8ve01gurjCAna3mb7sZEG9BzomI546ZEwgH1t7NbWMzvso--jGFhz49OnmkLxHMFJ";
$maxFailedAttempts = 3;
$lockoutMinutes = 15;

$error = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'timeout') $error = "Biztonsági okokból inaktivitás miatt kijelentkeztettünk.";
    if ($_GET['error'] === 'security_breach') $error = "Biztonsági riasztás: Az IP címed vagy böngésződ megváltozott!";
}

// ÚJ: Valódi IP cím megszerzése (Cloudflare / Proxy támogatás)
function getRealIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$step = isset($_SESSION['pending_2fa_admin_id']) ? 2 : 1;
$clientIp = getRealIp();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

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
            elseif (password_verify($password, $admin['password_hash'])) {
                $code = sprintf("%06d", mt_rand(1, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                $update = $pdo->prepare("UPDATE admins SET failed_logins = 0, lockout_time = NULL, unlock_token = NULL, two_factor_code = ?, two_factor_expires = ? WHERE id = ?");
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
                    $unlockToken = bin2hex(random_bytes(16)); 
                    
                    $update = $pdo->prepare("UPDATE admins SET failed_logins = ?, lockout_time = ?, unlock_token = ? WHERE id = ?");
                    $update->execute([$failed, $lockoutTimeStr, $unlockToken, $admin['id']]);
                    
                    $_SESSION['lockout_end'] = strtotime($lockoutTimeStr);
                    $lockoutEnd = $_SESSION['lockout_end'];
                    $error = "Túl sok hibás próbálkozás! A védelem aktiválódott.";

                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                    $domain = $_SERVER['HTTP_HOST'];
                    $unlockUrl = "{$protocol}://{$domain}/admin/auth/unlock.php?token={$unlockToken}";

                    $msg = "🚨 **VIGYÁZAT: FIÓK ZÁROLVA!** 🚨\nValaki túl sokszor rontotta el a jelszót!\n👤 **Fiók:** `{$admin['username']}`\n🌍 **IP cím:** `{$clientIp}`\n\n🔓 **Kattints ide a zárolás feloldásához:**\n{$unlockUrl}";
                    $json_data = json_encode(["content" => $msg]);
                    $ch = curl_init($discordWebhookUrl);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);

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
            $_SESSION['admin_ip'] = $clientIp; // A kőkemény igazi IP mentődik el!
            $_SESSION['admin_user_agent'] = $userAgent;
            $_SESSION['admin_last_activity'] = time();

            $clear = $pdo->prepare("UPDATE admins SET two_factor_code = NULL, two_factor_expires = NULL, failed_logins = 0, lockout_time = NULL, unlock_token = NULL, last_ip = ? WHERE id = ?");
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
    <title>ETHERNIA | Admin Vezérlőpult</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/login.css?v=<?= time(); ?>">
</head>
<body>

<div class="auth-container">
    
    <?php if ($lockoutEnd > 0): ?>
        <span class="material-symbols-rounded admin-logo">gpp_bad</span>
        <h1 class="auth-title">VÉDELEM AKTÍV</h1>
        <p class="auth-subtitle">Túl sok hibás próbálkozás miatt kizárva.</p>
        
        <div class="lockout-timer" id="countdown" data-end="<?= $lockoutEnd ?>">--:--</div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 2rem;">perc múlva újrapróbálhatod.</p>
        
        <a href="/admin/auth/login.php?force_check=1" class="btn-outline" style="display: block;">Már feloldottam Discordról</a>

    <?php elseif ($step === 1): ?>
        <span class="material-symbols-rounded admin-logo">admin_panel_settings</span>
        <h1 class="auth-title">ETHERNIA</h1>
        <p class="auth-subtitle">Adminisztrációs Vezérlőközpont</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <span class="material-symbols-rounded">error</span> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="action" value="login_step_1">
            <div class="input-group">
                <label>Felhasználónév</label>
                <input type="text" name="username" class="auth-input" required autocomplete="off" autofocus>
            </div>
            <div class="input-group">
                <label>Jelszó</label>
                <input type="password" name="password" class="auth-input" required>
            </div>
            <button type="submit" class="btn-admin">
                <span class="material-symbols-rounded">login</span> Bejelentkezés
            </button>
        </form>

    <?php else: ?>
        <span class="material-symbols-rounded admin-logo" style="color: #f59e0b; filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.5));">phonelink_lock</span>
        <h1 class="auth-title">HITELESÍTÉS</h1>
        <p class="auth-subtitle">A belépési kódot elküldtük az admin Discordra.</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <span class="material-symbols-rounded">error</span> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="action" value="login_step_2">
            <div class="input-group">
                <label style="text-align: center;">6 jegyű kód</label>
                <input type="text" name="twofa_code" class="auth-input code-input" maxlength="6" required autocomplete="off" autofocus placeholder="------">
            </div>
            <button type="submit" class="btn-admin" style="border-color: #f59e0b; color: #f59e0b;">
                <span class="material-symbols-rounded">verified</span> Hitelesítés
            </button>
            <a href="/admin/auth/login.php?cancel=1" class="btn-outline" style="display: block; margin-top: 0.5rem; text-align: center;">Mégse</a>
            <?php if(isset($_GET['cancel'])) { unset($_SESSION['pending_2fa_admin_id']); header("Location: /admin/auth/login.php"); exit; } ?>
        </form>
    <?php endif; ?>

</div>

<?php if ($lockoutEnd > 0): ?>
<script>
    const timerDisplay = document.getElementById('countdown');
    const endTime = parseInt(timerDisplay.getAttribute('data-end')) * 1000;

    function updateTimer() {
        const now = new Date().getTime();
        const diff = endTime - now;

        if (diff <= 0) {
            window.location.href = '/admin/auth/login.php';
            return;
        }

        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        timerDisplay.innerHTML = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
    }

    setInterval(updateTimer, 1000);
    updateTimer();
</script>
<?php endif; ?>

</body>
</html>