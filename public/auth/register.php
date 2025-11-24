<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../database.php';

$errors  = [];
$success = false;

$old_username = '';
$old_email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_username = trim($_POST['username'] ?? '');
    $old_email    = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $password2    = $_POST['password_confirm'] ?? '';


    if ($old_username === '') {
        $errors[] = 'A felhasználónév megadása kötelező.';
    } elseif (mb_strlen($old_username, 'UTF-8') < 3 || mb_strlen($old_username, 'UTF-8') > 32) {
        $errors[] = 'A felhasználónév 3–32 karakter hosszú legyen.';
    }

    if ($old_email === '') {
        $errors[] = 'Az e-mail cím megadása kötelező.';
    } elseif (!filter_var($old_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Érvényes e-mail címet adj meg.';
    }

    if ($password === '' || $password2 === '') {
        $errors[] = 'A jelszó és a jelszó megerősítése is kötelező.';
    } elseif ($password !== $password2) {
        $errors[] = 'A két jelszó nem egyezik.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'A jelszó legalább 8 karakter legyen.';
    }

    if (empty($errors)) {
        try {
            // név vagy email foglalt‑e?
            $check = $pdo->prepare("
                SELECT id, username, email
                FROM web_users
                WHERE username = :u OR email = :e
                LIMIT 1
            ");
            $check->execute([
                ':u' => $old_username,
                ':e' => $old_email,
            ]);
            $existing = $check->fetch();

            if ($existing) {
                if (strcasecmp($existing['username'], $old_username) === 0) {
                    $errors[] = 'Ez a felhasználónév már foglalt.';
                }
                if (strcasecmp($existing['email'], $old_email) === 0) {
                    $errors[] = 'Ezzel az e-mail címmel már regisztráltak.';
                }
            } else {
                // minden oké, beszúrás
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $ins = $pdo->prepare("
                    INSERT INTO web_users (username, email, password_hash, created_at)
                    VALUES (:u, :e, :h, NOW())
                ");
                $ins->execute([
                    ':u' => $old_username,
                    ':e' => $old_email,
                    ':h' => $hash,
                ]);

                $success = true;
                // opcionálisan: automatikus bejelentkezés
                // $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                // $_SESSION['username'] = $old_username;
            }
        } catch (Exception $e) {
            $errors[] = 'Adatbázis hiba történt. Próbáld újra később.';
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
  <title>ETHERNIA - Regisztráció</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Ide tehetsz külön register.css-t is később -->
  <link rel="stylesheet" href="/assets/css/register.css?v=<?= time(); ?>">
</head>
<body class="public-body">

  <main class="auth-page">
    <section class="auth-card">
      <h1 class="auth-title">Regisztráció</h1>
      <p class="auth-subtitle">
        Hozz létre ETHERNIA fiókot, hogy elérd a webes statokat és jutalmakat.
      </p>

      <?php if ($success): ?>
        <div class="alert alert-success">
          Sikeres regisztráció! Most már bejelentkezhetsz.
          <br>
          <a href="/login.php" class="link-accent">Ugrás a bejelentkezéshez →</a>
        </div>
      <?php else: ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">
            <ul>
              <?php foreach ($errors as $err): ?>
                <li><?php echo h($err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="/register.php" class="auth-form" id="register-form">
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
            <label for="email">E-mail cím</label>
            <input
              type="email"
              id="email"
              name="email"
              value="<?php echo h($old_email); ?>"
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

          <div class="form-group">
            <label for="password_confirm">Jelszó megerősítése</label>
            <input
              type="password"
              id="password_confirm"
              name="password_confirm"
              required
            >
          </div>

          <button type="submit" class="btn auth-btn">Regisztráció</button>
        </form>

      <?php endif; ?>

      <p class="auth-footnote">
        Már van fiókod?
        <a href="/login.php" class="link-accent">Bejelentkezés</a>
      </p>
    </section>
  </main>

  <script src="/assets/js/register.js?v=<?= time(); ?>"></script>
</body>
</html>
