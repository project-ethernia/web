<?php
session_start();

/* --- Ha már be van lépve a sima user, mehet a főoldalra --- */
if (!empty($_SESSION['is_user']) && $_SESSION['is_user'] === true) {
    header('Location: /');
    exit;
}

/* --- Közös DB kapcsolat --- */
require_once __DIR__ . '/../database.php'; // itt van a get_pdo()

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password        = isset($_POST['password']) ? $_POST['password'] : '';

    if ($usernameOrEmail === '' || $password === '') {
        $error = 'Adj meg felhasználónevet/E-mail címet és jelszót.';
    } else {
        try {
            $pdo = get_pdo();

            // Lehet felhasználónév VAGY e-mail
            $stmt = $pdo->prepare("
                SELECT id, username, email, password_hash
                FROM web_users
                WHERE username = :ue OR email = :ue
                LIMIT 1
            ");
            $stmt->execute([':ue' => $usernameOrEmail]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Hibás adatok – ellenőrizd a felhasználónevet/E-mail címet és a jelszót.';
            } else {
                // Sikeres belépés
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

                // TODO: ha később lesz webes log tábla, itt lehet logolni

                header('Location: /'); // ha inkább /profile.php kell, itt átírhatod
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
    <title>ETHERNIA – Bejelentkezés</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/assets/css/login.css?v=1">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-logo-main">ETHERNIA</div>
                <div class="auth-logo-sub">Webes fiók</div>
            </div>

            <h1 class="auth-title">Bejelentkezés</h1>

            <form class="auth-form" method="POST" action="/auth/login.php">
                <div class="form-group">
                    <label for="username">Felhasználónév vagy e‑mail</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        required
                        value="<?php echo isset($usernameOrEmail) ? h($usernameOrEmail) : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <div class="password-row">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="btn-eye" id="btn-toggle-password" aria-label="Jelszó mutatása/elrejtése">
                            👁
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <p class="auth-error"><?php echo h($error); ?></p>
                <?php endif; ?>

                <button type="submit" class="btn-primary auth-submit">Bejelentkezés</button>
            </form>

            <p class="auth-footer-text">
                Nincs még fiókod?
                <a href="/auth/register.php" class="auth-link">Regisztráció</a>
            </p>

            <p class="auth-footer-text small">
                <a href="/">← Vissza a főoldalra</a>
            </p>
        </div>
    </div>

    <script src="/assets/js/login.js?v=1"></script>
</body>
</html>
