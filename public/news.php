<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

try {
    // ITT NINCS LIMIT! Az összes látható hírt lekérjük.
    $stmt = $pdo->query("
        SELECT id, title, category, tag, date_display, short_text, full_text, author, created_at
        FROM news
        WHERE is_visible = 1
        ORDER BY created_at DESC
    ");
    $news = $stmt->fetchAll();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$isLoggedIn = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
$currentUser = $isLoggedIn && !empty($_SESSION['user_username']) ? $_SESSION['user_username'] : null;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Összes Hír és Frissítés</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
    <style>
        /* Egy kis extra CSS, hogy a menü alatt szépen kezdődjön az oldal, mivel nincs nagy fejléc */
        .page-main-sub {
            padding-top: 120px; 
            min-height: 80vh;
        }
    </style>
</head>
<body class="site-body">

<nav class="main-nav">
    <div class="main-nav-inner glass-panel">
        <ul class="main-nav-links">
            <li><a href="/">Főoldal</a></li>
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

<main class="page-main page-main-sub">
    <section class="news-section">
        <header class="section-header">
            <h1 class="section-title">Hírarchívum</h1>
            <div class="title-underline"></div>
            <p class="section-subtitle">Böngéssz az ETHERNIA összes eddigi bejelentése és frissítése között.</p>
        </header>

        <?php if (!empty($news)): ?>
            <div class="news-grid">
                <?php foreach ($news as $row): ?>
                    <?php
                    $tag = !empty($row['tag']) ? $row['tag'] : (!empty($row['category']) ? ucfirst(strtolower($row['category'])) : 'Info');
                    $tagLower = mb_strtolower($tag, 'UTF-8');
                    $tagClass = 'tag-default';
                    
                    if (strpos($tagLower, 'event') !== false || strpos($tagLower, 'esemény') !== false) $tagClass = 'tag-event';
                    elseif (strpos($tagLower, 'info') !== false || strpos($tagLower, 'információ') !== false) $tagClass = 'tag-info';
                    elseif (strpos($tagLower, 'update') !== false || strpos($tagLower, 'frissítés') !== false) $tagClass = 'tag-event';
                    elseif (strpos($tagLower, 'teszt') !== false || strpos($tagLower, 'test') !== false) $tagClass = 'tag-test';

                    $dateDisplay = !empty($row['date_display']) ? $row['date_display'] : date('Y. M. d.', strtotime($row['created_at']));
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
                                <img src="https://minotar.net/helm/<?= h($author); ?>/24.png" alt="Skin" style="border-radius: 4px; width: 24px; height: 24px; image-rendering: pixelated; margin-right: 8px;">
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
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="/" class="btn btn-outline" style="padding: 0.8rem 2rem;">Vissza a főoldalra</a>
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