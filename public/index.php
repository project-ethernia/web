<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// require_once __DIR__ . '/database.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* // Adatbázis kapcsolat - a teszt kedvéért most kikommentelve, hogy lásd a dizájnt, 
// de nálad ez az eredeti marad!
try {
    $pdo = get_pdo();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$stmt = $pdo->query("
    SELECT id, title, tag, date_display, short_text, full_text, order_index, author
    FROM news
    WHERE is_visible = 1
    ORDER BY order_index ASC, created_at DESC
");
$news = $stmt->fetchAll();
*/

// Ideiglenes tesztadatok a dizájn teszteléséhez (ezt töröld ki, ha az adatbázisod be van kötve)
$news = [
    ['title' => 'Tavaszi Nagy Frissítés', 'tag' => 'Event', 'date_display' => '2026. Márc. 20.', 'short_text' => 'Hatalmas tavaszi frissítéssel bővült a szerver, új ládák és küldetések várnak!', 'full_text' => 'Részletesebb leírás a tavaszi frissítésről...', 'author' => 'Adminisztrátor'],
    ['title' => 'Karbantartás a hétvégén', 'tag' => 'Info', 'date_display' => '2026. Márc. 18.', 'short_text' => 'Vasárnap hajnalban rövid hálózatfejlesztés lesz, a szerver 10 percig nem lesz elérhető.', 'full_text' => '', 'author' => 'Rendszergazda'],
];

$isLoggedIn = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
$currentUser = $isLoggedIn && !empty($_SESSION['user_username']) ? $_SESSION['user_username'] : null;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Modern Minecraft Kalandok</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
    </head>
<body class="site-body">

<header class="hero">
    <div class="hero-bg-glow"></div>
    <div class="hero-inner">
        <a href="https://dc.ethernia.hu" target="_blank" rel="noopener" class="hero-stat-card discord-card">
            <div class="stat-icon-wrapper">
                <img src="/assets/img/discord.png" alt="Discord" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23fff\'><path d=\'M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189z\'/></svg>'" class="hero-logo-img">
            </div>
            <div class="stat-content">
                <span class="stat-value"><span id="discord-online">--</span> tag</span>
                <span class="stat-label">Discord</span>
            </div>
        </a>

        <div class="hero-center">
            <h1 class="hero-title">ETHERNIA</h1>
            <p class="hero-subtitle">A kaland itt kezdődik</p>
        </div>

        <div class="hero-stat-card mc-card copy-ip" data-ip="play.ethernia.hu" title="Kattints az IP másolásához!">
            <div class="stat-content text-right">
                <span class="stat-value"><span id="mc-online">--</span> / <span id="mc-max">--</span></span>
                <span class="stat-label click-to-copy">Kattints a másoláshoz</span>
            </div>
            <div class="stat-icon-wrapper mc-icon-wrapper">
                <span class="mc-icon">⛏️</span>
            </div>
        </div>
    </div>
</header>

<nav class="main-nav">
    <div class="main-nav-inner glass-panel">
        <ul class="main-nav-links">
            <li class="active"><a href="/">Főoldal</a></li>
            <li><a href="#">Webshop</a></li>
            <li><a href="#">Szabályzat</a></li>
            <li><a href="#">Statisztikák</a></li>
            <li><a href="#">Kapcsolat</a></li>
        </ul>

        <div class="main-nav-user">
            <?php if ($isLoggedIn): ?>
                <div class="user-profile-badge">
                    <img src="https://minotar.net/helm/<?= h($currentUser); ?>/32.png" alt="Skin" class="mc-avatar">
                    <span class="user-name"><?= h($currentUser); ?></span>
                </div>
                <a href="/auth/logout.php" class="btn btn-outline">Kijelentkezés</a>
            <?php else: ?>
                <a href="/auth/login.php" class="btn btn-outline login-button">Bejelentkezés</a>
                <a href="/auth/register.php" class="btn btn-glow register-button">Regisztráció</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="page-main">
    <section class="news-section">
        <header class="section-header">
            <h2 class="section-title">Hírek & Frissítések</h2>
            <div class="title-underline"></div>
            <p class="section-subtitle">A legfrissebb információk az ETHERNIA világából.</p>
        </header>

        <?php if (!empty($news)): ?>
            <div class="news-grid">
                <?php foreach (array_slice($news, 0, 5) as $row): ?>
                    <?php
                    $tag = $row['tag'] ?: 'Info';
                    $tagLower = mb_strtolower($tag, 'UTF-8');
                    $tagClass = 'tag-default';
                    
                    if (strpos($tagLower, 'event') !== false) $tagClass = 'tag-event';
                    elseif (strpos($tagLower, 'info') !== false) $tagClass = 'tag-info';
                    elseif (strpos($tagLower, 'teszt') !== false || strpos($tagLower, 'test') !== false) $tagClass = 'tag-test';

                    $dateDisplay = $row['date_display'] ?: '';
                    $shortText = $row['short_text'] ?: '';
                    $fullText = $row['full_text'] ?: '';
                    $author = $row['author'] ?: 'Ismeretlen';
                    ?>
                    <article class="news-card glass-panel" data-full="<?= h($fullText !== '' ? $fullText : $shortText); ?>">
                        <div class="news-card-header">
                            <span class="news-badge <?= $tagClass; ?>"><?= h($tag); ?></span>
                            <span class="news-date"><?= h($dateDisplay); ?></span>
                        </div>
                        <div class="news-card-body">
                            <h3 class="news-title"><?= h($row['title']); ?></h3>
                            <p class="news-excerpt"><?= h($shortText); ?></p>
                        </div>
                        <div class="news-card-footer">
                            <div class="news-author">
                                <div class="author-avatar-placeholder"></div>
                                <span><?= h($author); ?></span>
                            </div>
                            <button type="button" class="btn btn-text news-readmore">Tovább olvasom <span class="arrow">→</span></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state glass-panel">
                <span class="empty-icon">📭</span>
                <p>Jelenleg nincs megjeleníthető hír.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card glass-panel">
                <div class="feature-icon">✨</div>
                <h3>Mi az ETHERNIA?</h3>
                <p>Egy modern, közösségközpontú magyar Minecraft szerver, ahol a hangulat és az élmény fontosabb, mint a pay-to-win.</p>
            </div>
            <div class="feature-card glass-panel">
                <div class="feature-icon">🎁</div>
                <h3>Események & Jutalmak</h3>
                <p>Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes statisztikák várnak rád a mindennapokban.</p>
            </div>
            <div class="feature-card glass-panel">
                <div class="feature-icon">🚀</div>
                <h3>Csatlakozz Most</h3>
                <p>Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a kaland már csak egy kattintásra van!</p>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-social">
            <a href="https://dc.ethernia.hu" target="_blank" rel="noopener" class="social-icon discord-icon" title="Discord">
                <img src="/assets/img/discord.png" alt="Discord" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23a855f7\'><path d=\'M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189z\'/></svg>'" class="social-img">
            </a>
            </div>

        <div class="footer-text">
            <p class="footer-copy">&copy; <span id="year"></span> ETHERNIA. Minden jog fenntartva.</p>
            <p class="footer-mojang">Nem hivatalos Minecraft rajongói oldal. A tartalom nem áll kapcsolatban a Mojang Studios-szal.</p>
        </div>
    </div>
</footer>

<div class="modal-overlay" id="news-modal">
    <div class="modal-container glass-panel">
        <button class="modal-close" aria-label="Bezárás">&times;</button>
        <div class="modal-content" id="modal-content-inner">
            </div>
    </div>
</div>

<div id="toast-container" class="toast-container"></div>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>