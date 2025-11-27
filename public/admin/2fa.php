<?php
session_start();

if (empty($_SESSION['admin_2fa_user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

$error = '';
$pendingId = (int)$_SESSION['admin_2fa_user_id'];
$pendingUsername = isset($_SESSION['admin_2fa_username']) ? (string)$_SESSION['admin_2fa_username'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';

    if ($code === '' || !preg_match('/^[0-9]{6}$/', $code)) {
        $error = 'Érvényes, 6 számjegyű kódot adj meg.';
    } else {
        try {
            $codeHash = hash('sha256', $code);

            $stmt = $pdo->prepare('SELECT * FROM admin_2fa_codes WHERE admin_id = :aid AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
            $stmt->execute([
                ':aid' => $pendingId
            ]);
            $row = $stmt->fetch();

            if (!$row || !hash_equals($row['code_hash'], $codeHash)) {
                $error = 'Hibás vagy lejárt kód.';
            } else {
                $upd = $pdo->prepare('UPDATE admin_2fa_codes SET used = 1 WHERE id = :id');
                $upd->execute([':id' => $row['id']]);

                $stmt2 = $pdo->prepare('SELECT * FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
                $stmt2->execute([':id' => $pendingId]);
                $user = $stmt2->fetch();

                if (!$user) {
                    $error = 'A felhasználó már nem aktív.';
                } else {
                    $_SESSION['admin_id'] = (int)$user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['is_admin'] = true;

                    unset($_SESSION['admin_2fa_user_id'], $_SESSION['admin_2fa_username']);

                    $upd2 = $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
                    $upd2->execute([':id' => $user['id']]);

                    try {
                        log_admin_action(
                            $pdo,
                            (int)$user['id'],
                            (string)$user['username'],
                            'Sikeres admin bejelentkezés (Discord 2FA)',
                            []
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
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - 2FA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/register.css?v=<?= time(); ?>">
</head>
<body class="public-body">
  <main class="auth-page">
    <section class="auth-card">
      <h1 class="auth-title auth-title-center">Discord 2FA</h1>
      <p class="auth-footnote" style="margin-top:4px;margin-bottom:10px;font-size:0.8rem;">
        Kérj kódot a Discord botunktól a <strong>/panelcode</strong> paranccsal. Ha rendelkezel a megfelelő ranggal, kapsz egy 6 számjegyű kódot privát üzenetben.
      </p>
      <?php if ($pendingUsername): ?>
        <p class="auth-footnote" style="margin-top:0;margin-bottom:10px;font-size:0.8rem;">
          Belépés folyamatban: <strong><?= htmlspecialchars($pendingUsername, ENT_QUOTES, 'UTF-8'); ?></strong>
        </p>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/2fa.php" class="auth-form" id="admin-2fa-form">
        <div class="form-group">
          <label for="code">Discord 2FA kód</label>
          <input
            type="text"
            id="code"
            name="code"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            required
          >
        </div>

        <button type="submit" class="btn auth-btn">Belépés</button>
      </form>

      <p class="auth-footnote">
        <a href="/admin/login.php">← Másik fiókkal jelentkeznék be</a>
      </p>
      <p class="auth-footnote">
        <a href="/">Vissza a főoldalra</a>
      </p>
    </section>
  </main>
  <script src="/assets/js/login.js?v=<?= time(); ?>"></script>
</body>
</html>
