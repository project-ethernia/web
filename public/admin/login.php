<?php
session_start();

/* --- HA MÁR BE VAN LÉPVE, DOBHATJUK RÖGTÖN A HÍREKHEZ --- */
if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: /admin/index.php');
    exit;
}

/* --- DB BEÁLLÍTÁSOK: ÁLLÍTSD BE SAJÁT ADATOKRA --- */
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

/* --- LOG FUNKCIÓ BEHÚZÁSA --- */
require_once __DIR__ . '/log.php';

function get_pdo() {
    static $pdo = null;
    global $DB_DSN, $DB_USER, $DB_PASS;
    if ($pdo === null) {
        $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = 'Adj meg felhasználónevet és jelszót.';
    } else {
        try {
            $pdo = get_pdo();
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :u AND is_active = 1 LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Hibás felhasználónév vagy jelszó.';

                // SIKERTELEN BELÉPÉS LOGOLÁSA
                try {
                    log_admin_action(
                        $pdo,
                        0,
                        'Ismeretlen',
                        'Sikertelen admin bejelentkezés',
                        ['username' => $username]
                    );
                } catch (Throwable $e2) {
                    // ha a logolás elhasal, ne dőljön össze a login
                }

            } else {
                // sikeres login
                $_SESSION['admin_id']       = (int)$user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role']     = $user['role'];
                $_SESSION['is_admin']       = true;

                // last_login frissítés
                $upd = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
                $upd->execute([':id' => $user['id']]);

                // SIKERES BELÉPÉS LOGOLÁSA
                try {
                    log_admin_action(
                        $pdo,
                        (int)$user['id'],
                        (string)$user['username'],
                        'Sikeres admin bejelentkezés',
                        []
                    );
                } catch (Throwable $e2) {
                    // ha a logolás elhasal, akkor se álljon meg a login
                }

                header('Location: /admin/news.php');
                exit;
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
  <title>ETHERNIA Admin - Bejelentkezés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="/admin/login.css?v=1">
</head>
<body class="admin-login-body">
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-brand">
        <div class="login-logo-main">ETHERNIA</div>
        <div class="login-logo-sub">Admin felület</div>
      </div>

      <h1 class="login-title">Bejelentkezés</h1>
      <p class="login-subtitle">
        Csak jogosult felhasználók számára. A belépéssel elfogadod, hogy nem trollkodsz. 😈
      </p>

      <form method="POST" action="/admin/login.php" id="admin-login-form">
        <div class="form-group">
          <label for="username">Felhasználónév</label>
          <input type="text" id="username" name="username" autocomplete="username" required>
        </div>

        <div class="form-group">
          <label for="password">Jelszó</label>
          <div class="password-row">
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="btn-eye" id="btn-toggle-password" aria-label="Jelszó mutatása/elrejtése">
              👁
            </button>
          </div>
        </div>

        <?php if ($error): ?>
          <p class="login-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <button type="submit" class="btn-login">Belépés</button>
      </form>

      <p class="login-footer-note">
        <a href="/">← Vissza a főoldalra</a>
      </p>
    </div>
  </div>

  <script src="/admin/login.js?v=1"></script>
</body>
</html>
