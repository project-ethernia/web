<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ha már be van lépve, mehet a főoldalra
if (!empty($_SESSION['is_user']) && $_SESSION['is_user'] === true) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/../database.php';

$error        = '';
$old_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_username = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';

    if ($old_username === '' || $password === '') {
        $error = 'Adj meg felhasználónevet és jelszót.';
    } else {
        try {
            $pdo = get_pdo();

            // CSAK felhasználónév alapján keresünk, NEM e-maillel
            $stmt = $pdo->prepare("
                SELECT id, username, email, password_hash
                FROM web_users
                WHERE username = :u
                LIMIT 1
            ");
            $stmt->execute([
                ':u' => $old_username,
            ]);

            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Hibás felhasználónév vagy jelszó.';
            } else {
                // sikeres login
                $_SESSION['user_id']       = (int)$user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['is_user']       = true;

                // last_login + last_ip frissítés
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;

                $upd = $pdo->prepare("
                    UPDATE web_users
                    SET last_login = NOW(), last_ip = :ip
                    WHERE id = :id
                ");
                $upd->execute([
                    ':ip' => $ip,
                    ':id' => $user['id'],
                ]);

                header('Location: /');
                exit;
            }
        } catch (Exception $e) {
            // ha debugolni akarsz:
            // $error = 'Adatbázis hiba: ' . $e->getMessage();
            $error = 'Adatbázis hiba történt. Próbáld újra később.';
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
  <title>ETHERNIA - Bejelentkezés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/login.css?v=<?= time(); ?>">
</head>
<body class="public-body">

  <main class="auth-page">
    <section class="auth-card">
      <h1 class="auth-title auth-title-center">Bejelentkezés</h1>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <?php echo h($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/auth/login.php" class="auth-form" id="login-form">
        <div class="form-group">
          <label for="username">Felhasználónév</label>
          <input
            type="text"
            id="username"
            name="username"
            value="<?php echo h($old_username); ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="password">Jelszó</label>
          <input
            type="password"
            id="password"
            name="password"
            required
          >
        </div>

        <button type="submit" class="btn auth-btn">Bejelentkezés</button>
      </form>

      <p class="auth-footnote">
        Nincs még fiókod?
        <a href="/auth/register.php" class="link-accent">Regisztráció</a>
      </p>

      <p class="auth-footnote">
        <a href="/" class="link-accent">← Vissza a főoldalra</a>
      </p>
    </section>
  </main>

  <script src="/assets/js/login.js?v=<?= time(); ?>"></script>
</body>
</html>
