<?php
session_start();

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

                $stmtIns = $pdo->prepare('INSERT INTO admin_login_requests (admin_id, status, notified, ip, user_agent) VALUES (:aid, :status, 0, :ip, :ua)');
                $stmtIns->execute([
                    ':aid' => (int)$user['id'],
                    ':status' => 'pending',
                    ':ip' => $ip,
                    ':ua' => $ua
                ]);

                $requestId = (int)$pdo->lastInsertId();

                $_SESSION['admin_pending_login_request_id'] = $requestId;
                $_SESSION['admin_pending_admin_id'] = (int)$user['id'];
                $_SESSION['admin_pending_username'] = (string)$user['username'];

                unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role'], $_SESSION['is_admin']);

                header('Location: /admin/login_wait.php');
                exit;
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
      <h1 class="auth-title auth-title-center">Admin bejelentkezés</h1>
      <p class="auth-footnote" style="margin-top:4px;margin-bottom:10px;font-size:0.8rem;">
        Add meg az admin felhasználóneved és jelszavad. Ezután a Discord boton keresztül kell jóváhagynod a belépést.
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
    </section>
  </main>
  <script src="/assets/js/login.js?v=<?= time(); ?>"></script>
</body>
</html>
