<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- KÖTELEZŐ BEJELENTKEZÉS ÉS ABSZOLÚT IDŐKORLÁT ---
$timeout_duration = 3600; // 1 óra (másodpercben)

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
// ------------------------------------------

require_once __DIR__ . '/database.php';

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Hírek lekérdezése
try {
    $stmt = $pdo->query("
        SELECT id, title, category, tag, date_display, short_text, full_text, author, created_at
        FROM news
        WHERE is_visible = 1
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $news = $stmt->fetchAll();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$currentUser = !empty($_SESSION['user_username']) ? $_SESSION['user_username'] : 'Játékos';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Modern Minecraft Kalandok</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body>

<header class="hero">
    <div class="hero-bg-glow"></div>
    <div class="hero-content">
        
        <div class="hero-top-row">
            <a href="https://dc.ethernia.hu" target="_blank" class="stat-widget glass hover-lift">
                <div class="stat-icon discord"><img src="/assets/img/discord.png" alt="DC" style="width:24px; filter:brightness(0) invert(1);"></div>
                <div class="stat-info">
                    <span class="stat-value"><span id="discord-online">--</span> online</span>
                    <span class="stat-label">Discord Szerver</span>
                </div>
            </a>
            
            <div class="hero-center-titles">
                <h1 class="hero-title">ETHERNIA</h1>
                <p class="hero-subtitle">A kaland itt kezdődik. Csatlakozz a legmodernebb magyar közösséghez!</p>
            </div>
            
            <div class="stat-widget glass hover-lift copy-ip" data-ip="play.ethernia.hu">
                <div class="stat-icon minecraft">⛏️</div>
                <div class="stat-info">
                    <span class="stat-value"><span id="mc-online">--</span> / <span id="mc-max">--</span></span>
                    <span class="stat-label click-to-copy" id="mc-copy-text">Kattints a másoláshoz</span>
                </div>
            </div>
        </div>

    </div>
</header>

<?php $current_page = 'home'; ?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <section class="section">
        <?php if (!empty($news)): ?>
            <div class="news-grid">
                <?php foreach ($news as $row): ?>
                    <?php
                    $tag = !empty($row['tag']) ? $row['tag'] : (!empty($row['category']) ? ucfirst(strtolower($row['category'])) : 'Info');
                    $tagLower = mb_strtolower($tag, 'UTF-8');
                    $tagClass = 'tag-default';
                    if (strpos($tagLower, 'event') !== false || strpos($tagLower, 'esemény') !== false) $tagClass = 'tag-event';
                    elseif (strpos($tagLower, 'info') !== false || strpos($tagLower, 'információ') !== false) $tagClass = 'tag-info';
                    elseif (strpos($tagLower, 'update') !== false || strpos($tagLower, 'frissítés') !== false) $tagClass = 'tag-update';
                    
                    $dateDisplay = !empty($row['date_display']) ? $row['date_display'] : date('Y. M. d.', strtotime($row['created_at']));
                    $shortText = $row['short_text'] ?: mb_strimwidth(strip_tags($row['full_text']), 0, 80, '...');
                    $author = $row['author'] ?: 'Ethernia';
                    ?>
                    
                    <article class="news-card glass hover-lift" data-full="<?= h($row['full_text'] !== '' ? $row['full_text'] : $shortText); ?>">
                        <div class="news-meta">
                            <span class="badge <?= $tagClass; ?>"><?= h($tag); ?></span>
                            <span class="date"><?= h($dateDisplay); ?></span>
                        </div>
                        <h3 class="news-title"><?= h($row['title']); ?></h3>
                        <p class="news-text"><?= h($shortText); ?></p>
                        <div class="news-footer">
                            <div class="author">
                                <img src="https://minotar.net/helm/<?= h($author); ?>/24.png" alt="<?= h($author); ?>">
                                <span><?= h($author); ?></span>
                            </div>
                            <button class="read-more">Tovább <span class="material-symbols-rounded">arrow_forward</span></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <div class="news-action">
                <a href="/news.php" class="btn btn-outline-glow">Összes hír archívuma</a>
            </div>
        <?php else: ?>
            <div class="empty-state glass">
                <span class="material-symbols-rounded">inbox</span>
                <p>Jelenleg nincs megjeleníthető hír.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="section">
        <div class="features-grid">
            <div class="feature-card glass hover-lift">
                <div class="feature-icon glow-primary"><span class="material-symbols-rounded">rocket_launch</span></div>
                <h3>Modern Játékélmény</h3>
                <p>Nincsenek elavult rendszerek. A legújabb verziókon futunk, optimalizált, szaggatásmentes élményt nyújtva minden játékosnak.</p>
            </div>
            <div class="feature-card glass hover-lift">
                <div class="feature-icon glow-primary"><span class="material-symbols-rounded">diversity_3</span></div>
                <h3>Barátságos Közösség</h3>
                <p>Szigorú, de igazságos moderáció. Nálunk a tisztelet a legfontosabb, így egy toxicitástól mentes környezetben játszhatsz.</p>
            </div>
            <div class="feature-card glass hover-lift">
                <div class="feature-icon glow-primary"><span class="material-symbols-rounded">emoji_events</span></div>
                <h3>Folyamatos Események</h3>
                <p>Rendszeres eventek, egyedi küldetések és szezonális kihívások, hogy sose unatkozz a túlélés során.</p>
            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="footer-content">
        <a href="https://dc.ethernia.hu" target="_blank" class="footer-social glass hover-lift">
            <img src="/assets/img/discord.png" alt="Discord" style="width:24px; filter:brightness(0) invert(1);">
            Csatlakozz Discordon
        </a>
        <p class="copyright">&copy; <span id="year"></span> ETHERNIA. Minden jog fenntartva.</p>
        <p class="disclaimer">Nem hivatalos Minecraft rajongói oldal. Nem állunk kapcsolatban a Mojang Studios-szal.</p>
    </div>
</footer>

<div id="toast-container"></div>

<div class="modal-overlay" id="news-modal">
    <div class="modal-container glass">
        <button class="modal-close" aria-label="Bezárás"><span class="material-symbols-rounded">close</span></button>
        <div class="modal-content" id="modal-content-inner"></div>
    </div>
</div>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>