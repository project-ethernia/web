<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$timeout_duration = 3600; // 1 óra

// --- KÖTELEZŐ BEJELENTKEZÉS ---
if (empty($_SESSION['is_user']) || $_SESSION['is_user'] !== true) {
    header('Location: /auth/login.php');
    exit;
}

if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

$elapsed_time = time() - $_SESSION['login_time'];
if ($elapsed_time >= $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: /auth/login.php?error=timeout');
    exit;
}
$remaining_time = $timeout_duration - $elapsed_time;

require_once __DIR__ . '/database.php';

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$user_id = $_SESSION['user_id'];
$msg = '';
$msgType = '';

// --- JELSZÓ VÁLTOZTATÁS LOGIKA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $new_pass_conf = $_POST['new_password_confirm'] ?? '';

    if ($old_pass && $new_pass && $new_pass_conf) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $dbUser = $stmt->fetch();

        if ($dbUser && password_verify($old_pass, $dbUser['password'])) {
            if ($new_pass !== $new_pass_conf) {
                $msg = 'Az új jelszavak nem egyeznek!';
                $msgType = 'error';
            } elseif (strlen($new_pass) < 6) {
                $msg = 'Az új jelszónak legalább 6 karakternek kell lennie!';
                $msgType = 'error';
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($update->execute([$hashed, $user_id])) {
                    $msg = 'A jelszavad sikeresen frissítve lett!';
                    $msgType = 'success';
                } else {
                    $msg = 'Hiba történt a mentés során.';
                    $msgType = 'error';
                }
            }
        } else {
            $msg = 'A jelenlegi jelszó helytelen!';
            $msgType = 'error';
        }
    } else {
        $msg = 'Kérlek tölts ki minden jelszó mezőt!';
        $msgType = 'error';
    }
}

// --- JÁTÉKOS ADATAINAK LEKÉRÉSE ---
try {
    $stmt = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        session_destroy();
        header('Location: /auth/login.php');
        exit;
    }
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$currentUser = $userData['username'];
$regDate = date('Y. M. d.', strtotime($userData['created_at']));
$maskedEmail = substr($userData['email'], 0, 3) . '***@' . explode('@', $userData['email'])[1];

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= h($currentUser) ?> Profilja | ETHERNIA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/profile.css?v=<?= time(); ?>">
</head>
<body>

<?php $current_page = 'profile'; ?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container" style="padding-top: 8rem; min-height: 80vh;">
    
    <div class="section-header">
        <h1 class="section-title">Vezérlőpult</h1>
        <div class="title-line"></div>
        <p class="section-subtitle">Kezeld a fiókodat és tekintsd meg a statisztikáidat.</p>
    </div>

    <?php if ($msg): ?>
        <div class="profile-alert <?= $msgType === 'success' ? 'success' : 'error' ?> glass">
            <span class="material-symbols-rounded"><?= $msgType === 'success' ? 'check_circle' : 'error' ?></span>
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">
        
        <aside class="profile-sidebar">
            <div class="profile-card glass">
                <div class="profile-card-bg"></div>
                <div class="skin-container">
                    <img src="https://minotar.net/armor/body/<?= h($currentUser); ?>/250.png" alt="<?= h($currentUser); ?>" class="skin-3d">
                </div>
                <h2 class="profile-username"><?= h($currentUser); ?></h2>
                <div class="profile-rank">Játékos</div>
                
                <div class="profile-meta">
                    <div class="meta-item">
                        <span class="material-symbols-rounded">mail</span>
                        <span><?= h($maskedEmail); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="material-symbols-rounded">calendar_month</span>
                        <span>Csatlakozott: <?= h($regDate); ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="profile-main">
            
            <h3 class="panel-title"><span class="material-symbols-rounded">monitoring</span> Szerver Statisztikák</h3>
            <div class="stats-grid">
                <div class="stat-box glass hover-lift">
                    <div class="stat-box-icon" style="color: #eab308; background: rgba(234, 179, 8, 0.1);"><span class="material-symbols-rounded">monetization_on</span></div>
                    <div class="stat-box-info">
                        <span class="stat-box-val">0 ETE</span>
                        <span class="stat-box-label">Egyenleg</span>
                    </div>
                </div>
                <div class="stat-box glass hover-lift">
                    <div class="stat-box-icon" style="color: #ef4444; background: rgba(239, 68, 68, 0.1);"><span class="material-symbols-rounded">swords</span></div>
                    <div class="stat-box-info">
                        <span class="stat-box-val">0</span>
                        <span class="stat-box-label">Ölések</span>
                    </div>
                </div>
                <div class="stat-box glass hover-lift">
                    <div class="stat-box-icon" style="color: #a855f7; background: rgba(168, 85, 247, 0.1);"><span class="material-symbols-rounded">skull</span></div>
                    <div class="stat-box-info">
                        <span class="stat-box-val">0</span>
                        <span class="stat-box-label">Halálok</span>
                    </div>
                </div>
                <div class="stat-box glass hover-lift">
                    <div class="stat-box-icon" style="color: #38bdf8; background: rgba(56, 189, 248, 0.1);"><span class="material-symbols-rounded">schedule</span></div>
                    <div class="stat-box-info">
                        <span class="stat-box-val">0 óra</span>
                        <span class="stat-box-label">Játszott idő</span>
                    </div>
                </div>
            </div>

            <h3 class="panel-title" style="margin-top: 3rem;"><span class="material-symbols-rounded">security</span> Biztonság</h3>
            <div class="security-panel glass">
                <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">Itt tudod megváltoztatni a fiókod jelszavát. Ne add meg a jelszavadat senkinek, még az adminoknak sem!</p>
                
                <form method="POST" action="/profile.php" class="password-form">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="input-group">
                        <label for="old_password">Jelenlegi Jelszó</label>
                        <div class="input-with-icon">
                            <span class="material-symbols-rounded input-icon">lock_open</span>
                            <input type="password" id="old_password" name="old_password" required placeholder="Jelenlegi jelszavad">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label for="new_password">Új Jelszó</label>
                            <div class="input-with-icon">
                                <span class="material-symbols-rounded input-icon">lock</span>
                                <input type="password" id="new_password" name="new_password" required placeholder="Új jelszó">
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="new_password_confirm">Új Jelszó Megerősítése</label>
                            <div class="input-with-icon">
                                <span class="material-symbols-rounded input-icon">lock</span>
                                <input type="password" id="new_password_confirm" name="new_password_confirm" required placeholder="Új jelszó újra">
                                <span class="material-symbols-rounded match-icon" id="prof-match-icon"></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-auth" style="margin-top: 1rem;">Jelszó Módosítása</button>
                </form>
            </div>

        </div>
    </div>
</main>

<footer class="footer">
    <p class="copyright">&copy; <span id="year"></span> ETHERNIA.</p>
</footer>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
<script src="/assets/js/profile.js?v=<?= time(); ?>"></script>
</body>
</html>