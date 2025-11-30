<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$twofa_enabled = isset($_ENV['ADMIN_2FA_ENABLED']) && $_ENV['ADMIN_2FA_ENABLED'] === 'true';

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$mode = 'form';

$pendingRequestId = $_SESSION['admin_pending_login_request_id'] ?? 0;
$pendingAdminId   = $_SESSION['admin_pending_admin_id'] ?? 0;
$pendingUsername  = $_SESSION['admin_pending_username'] ?? '';

if ($twofa_enabled && $pendingRequestId > 0 && $pendingAdminId > 0) {
    $mode = 'wait';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'form') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Adj meg felhasználónevet és jelszót.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :u AND is_active = 1 LIMIT 1');
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Hibás felhasználónév vagy jelszó.';
                try {
                    log_admin_action($pdo, 0, 'Ismeretlen', 'Sikertelen admin bejelentkezés', ['username' => $username]);
                } catch (Throwable $e2) {}
            } else {
                if (!$twofa_enabled) {
                    $_SESSION['admin_id'] = (int)$user['id'];
                    $_SESSION['admin_username'] = (string)$user['username'];
                    $_SESSION['admin_role'] = (string)$user['role'];
                    $_SESSION['is_admin'] = true;
                    unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);
                    header('Location: /admin/index.php');
                    exit;
                }

                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

                $stmtIns = $pdo->prepare('
                    INSERT INTO admin_login_requests (admin_id, status, notified, ip, user_agent)
                    VALUES (:aid, :status, 0, :ip, :ua)
                ');
                $stmtIns->execute([
                    ':aid'    => (int)$user['id'],
                    ':status' => 'pending',
                    ':ip'     => $ip,
                    ':ua'     => $ua
                ]);

                $requestId = (int)$pdo->lastInsertId();

                $_SESSION['admin_pending_login_request_id'] = $requestId;
                $_SESSION['admin_pending_admin_id'] = (int)$user['id'];
                $_SESSION['admin_pending_username'] = (string)$user['username'];

                unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role'], $_SESSION['is_admin']);

                $mode = 'wait';
                $pendingRequestId = $requestId;
                $pendingAdminId   = (int)$user['id'];
                $pendingUsername  = (string)$user['username'];
            }
        } catch (Exception $e) {
            $error = 'Adatbázis hiba: ' . $e->getMessage();
        }
    }
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Bejelentkezés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/login.css?v=<?= time(); ?>">
</head>
<body class="public-body">
  <main class="auth-page">
    <section class="auth-card">
      <?php if ($mode === 'form'): ?>
        <h1 class="auth-title auth-title-center">Admin bejelentkezés</h1>
        <p class="auth-footnote" style="margin-top:4px;margin-bottom:10px;font-size:0.8rem;">
          Add meg az admin felhasználóneved és jelszavad<?= $twofa_enabled ? ', majd Discordon hagyd jóvá a belépést.' : '.' ?>
        </p>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= h($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php" class="auth-form" id="admin-login-form">
          <div class="form-group">
            <label for="username">Felhasználónév</label>
            <input type="text" id="username" name="username" autocomplete="username" required>
          </div>
          <div class="form-group">
            <label for="password">Jelszó</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn auth-btn">Belépés</button>
        </form>

        <p class="auth-footnote"><a href="/">← Vissza a főoldalra</a></p>

      <?php else: ?>
        <h1 class="auth-title auth-title-center">Jóváhagyás Discordon</h1>

        <?php if ($pendingUsername): ?>
          <p class="auth-footnote" style="margin-top:4px;margin-bottom:6px;font-size:0.85rem;">
            Folyamatban lévő bejelentkezés: <strong><?= h($pendingUsername); ?></strong>
          </p>
        <?php endif; ?>

        <p class="auth-footnote" style="margin-top:0;margin-bottom:12px;font-size:0.8rem;">
          Nyisd meg a Discordot. A bot üzenetet küldött számodra.
        </p>

        <div id="wait-message" class="auth-footnote" style="font-size:0.82rem;">Várakozás a jóváhagyásra…</div>
        <div id="wait-error" class="alert alert-error" style="margin-top:10px;display:none;"></div>

        <p class="auth-footnote" style="margin-top:18px;"><a href="/admin/login.php">← Másik fiókkal jelentkeznék be</a></p>
        <p class="auth-footnote"><a href="/">Vissza a főoldalra</a></p>

        <script src="/admin/assets/js/login_2fa.js?v=<?= time(); ?>"></script>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
