<?php
session_start();

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

function admin_2fa_enabled(): bool
{
    $val = getenv('ADMIN_DISCORD_2FA');
    if ($val === false) {
        return true;
    }
    $val = strtolower(trim($val));
    return in_array($val, ['1', 'true', 'yes', 'on'], true);
}

$use2FA = admin_2fa_enabled();

$error = '';
$mode  = 'form';

$pendingRequestId = isset($_SESSION['admin_pending_login_request_id']) ? (int)$_SESSION['admin_pending_login_request_id'] : 0;
$pendingAdminId   = isset($_SESSION['admin_pending_admin_id']) ? (int)$_SESSION['admin_pending_admin_id'] : 0;
$pendingUsername  = isset($_SESSION['admin_pending_username']) ? (string)$_SESSION['admin_pending_username'] : '';

if ($use2FA && $pendingRequestId > 0 && $pendingAdminId > 0) {
    $mode = 'wait';
} else {
    unset(
        $_SESSION['admin_pending_login_request_id'],
        $_SESSION['admin_pending_admin_id'],
        $_SESSION['admin_pending_username']
    );
    $pendingRequestId = 0;
    $pendingAdminId   = 0;
    $pendingUsername  = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'form') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

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
                    log_admin_action(
                        $pdo,
                        0,
                        'Ismeretlen',
                        'Sikertelen admin bejelentkezés',
                        ['username' => $username]
                    );
                } catch (Throwable $e2) {
                }
            } else {
                if ($use2FA) {
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
                    $_SESSION['admin_pending_admin_id']         = (int)$user['id'];
                    $_SESSION['admin_pending_username']         = (string)$user['username'];

                    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role'], $_SESSION['is_admin']);

                    $mode = 'wait';
                    $pendingRequestId = $requestId;
                    $pendingAdminId   = (int)$user['id'];
                    $pendingUsername  = (string)$user['username'];
                } else {
                    $_SESSION['is_admin']      = true;
                    $_SESSION['admin_id']      = (int)$user['id'];
                    $_SESSION['admin_username'] = (string)$user['username'];

                    try {
                        log_admin_action(
                            $pdo,
                            (int)$user['id'],
                            (string)$user['username'],
                            'Sikeres admin bejelentkezés (Discord 2FA kikapcsolva)'
                        );
                    } catch (Throwable $e2) {
                    }

                    header('Location: /admin/index.php');
                    exit;
                }
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
          <?php if ($use2FA): ?>
            Add meg az admin felhasználóneved és jelszavad. Ezután a Discord boton keresztül kell jóváhagynod a belépést.
          <?php else: ?>
            Add meg az admin felhasználóneved és jelszavad a bejelentkezéshez.
          <?php endif; ?>
        </p>

        <?php if ($error): ?>
          <div class="alert alert-error">
            <?= h($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php" class="auth-form" id="admin-login-form">
          <div class="form-group">
            <label for="username">Felhasználónév</label>
            <input
              type="text"
              id="username"
              name="username"
              autocomplete="username"
              required
            >
          </div>

          <div class="form-group">
            <label for="password">Jelszó</label>
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              required
            >
          </div>

          <button type="submit" class="btn auth-btn">Belépés</button>
        </form>

        <p class="auth-footnote">
          <a href="/">← Vissza a főoldalra</a>
        </p>
      <?php else: ?>
        <h1 class="auth-title auth-title-center">Jóváhagyás Discordon</h1>
        <?php if ($pendingUsername): ?>
          <p class="auth-footnote" style="margin-top:4px;margin-bottom:6px;font-size:0.85rem;">
            Folyamatban lévő bejelentkezés: <strong><?= h($pendingUsername); ?></strong>
          </p>
        <?php endif; ?>
        <p class="auth-footnote" style="margin-top:0;margin-bottom:12px;font-size:0.8rem;">
          Nyisd meg a Discordot. A bot üzenetet küldött neked, ahol 3 percen belül jóváhagyhatod vagy elutasíthatod a belépést.
        </p>

        <div id="wait-message" class="auth-footnote" style="font-size:0.82rem;">
          Várakozás a jóváhagyásra…
        </div>
        <div id="wait-error" class="alert alert-error" style="margin-top:10px;display:none;"></div>

        <p class="auth-footnote" style="margin-top:18px;">
          Ha nem te kezdeményezted ezt a belépést, azonnal változtasd meg a jelszavad.
        </p>

        <p class="auth-footnote">
          <a href="/admin/login.php">← Másik fiókkal jelentkeznék be</a>
        </p>

        <p class="auth-footnote">
          <a href="/">Vissza a főoldalra</a>
        </p>

        <script src="/admin/assets/js/login_2fa.js?v=<?= time(); ?>"></script>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
