<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

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

$isLoggedIn = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
$currentUser = $isLoggedIn && !empty($_SESSION['user_username']) ? $_SESSION['user_username'] : null;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Főoldal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body class="site-body">

<header class="hero">
    <div class="hero-inner">
        <div class="hero-stat hero-stat-left">
            <div class="hero-stat-icon hero-stat-icon-discord">
                <span class="hero-icon-symbol"></span>
            </div>
            <div class="hero-stat-text">
                <div class="hero-stat-line">
                    <span id="discord-online">--</span> elérhető tag
                </div>
                <a href="https://dc.ethernia.hu" target="_blank" rel="noopener" class="hero-stat-link">
                    dc.ethernia.hu
                </a>
            </div>
        </div>

        <div class="hero-title">ETHERNIA</div>

        <div class="hero-stat hero-stat-right">
            <div class="hero-stat-text hero-stat-text-right">
                <div class="hero-stat-line">
                    <span id="mc-online">--</span> / <span id="mc-max">--</span> játékos online
                </div>
                <div class="hero-stat-link hero-stat-link-ip">
                    play.ethernia.hu
                </div>
            </div>
            <div class="hero-stat-icon hero-stat-icon-mc">
                <span class="hero-icon-symbol"></span>
            </div>
        </div>
    </div>
</header>

<nav class="main-nav">
    <div class="main-nav-inner">
        <ul class="main-nav-links">
            <li class="active"><a href="/">Főoldal</a></li>
            <li><a href="#">Webshop</a></li>
            <li><a href="#">Szabályzat</a></li>
            <li><a href="#">Statisztikák</a></li>
            <li><a href="#">Kapcsolat</a></li>
        </ul>

        <div class="main-nav-user">
            <?php if ($isLoggedIn): ?>
                <span class="user-pill">
                    Bejelentkezve: <strong><?= h($currentUser); ?></strong>
                </span>
                <a href="/auth/logout.php" class="nav-btn nav-btn-secondary">Kijelentkezés</a>
            <?php else: ?>
                <a href="/auth/login.php" class="nav-btn nav-btn-secondary">Bejelentkezés</a>
                <a href="/auth/register.php" class="nav-btn nav-btn-primary">Regisztráció</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="page-main">

    <section class="news-section">
        <header class="news-header">
            <h2 class="news-section-title">Hírek &amp; frissítések</h2>
            <p class="news-section-subtitle">
                A legfrissebb információk az ETHERNIA világából.
            </p>
        </header>

        <?php if (!empty($news)): ?>
            <div class="news-list">
                <?php foreach (array_slice($news, 0, 5) as $row): ?>
                    <?php
                    $tag = $row['tag'] ?: 'Info';
                    $tagLower = mb_strtolower($tag, 'UTF-8');
                    $tagClass = 'news-tag-pill';
                    if (strpos($tagLower, 'event') !== false) {
                        $tagClass .= ' news-tag-event';
                    } elseif (strpos($tagLower, 'info') !== false) {
                        $tagClass .= ' news-tag-info';
                    } elseif (strpos($tagLower, 'teszt') !== false || strpos($tagLower, 'test') !== false) {
                        $tagClass .= ' news-tag-test';
                    }

                    $dateDisplay = $row['date_display'] ?: '';
                    $shortText = $row['short_text'] ?: '';
                    $fullText = $row['full_text'] ?: '';
                    $author = $row['author'] ?: 'Ismeretlen';
                    ?>
                    <article
                        class="news-card"
                        data-full="<?= h($fullText !== '' ? $fullText : $shortText); ?>"
                    >
                        <div class="news-card-inner">
                            <div class="news-card-header">
                                <span class="<?= $tagClass; ?>"><?= h($tag); ?></span>
                                <span class="news-card-date"><?= h($dateDisplay); ?></span>
                            </div>

                            <div class="news-card-body">
                                <h3 class="news-card-title"><?= h($row['title']); ?></h3>
                                <p class="news-card-short"><?= h($shortText); ?></p>
                            </div>

                            <div class="news-card-footer">
                                <span class="news-card-author">
                                    Közzétette: <strong><?= h($author); ?></strong>
                                </span>
                                <button type="button" class="news-readmore">Részletek</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="news-empty">Jelenleg nincs megjeleníthető hír.</p>
        <?php endif; ?>
    </section>

    <section class="info-bottom">
        <div class="info-bottom-grid">
            <article class="info-bottom-card">
                <h3>Mi az ETHERNIA?</h3>
                <p>
                    Egy modern, közösségközpontú magyar Minecraft szerver, ahol a hangulat és az élmény fontosabb,
                    mint a pay‑to‑win.
                </p>
            </article>

            <article class="info-bottom-card">
                <h3>Események és jutalmak</h3>
                <p>
                    Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes statisztikák várnak.
                </p>
            </article>

            <article class="info-bottom-card">
                <h3>Csatlakozz most</h3>
                <p>
                    Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a kaland már vár rád!
                </p>
            </article>
        </div>
    </section>

</main>

<footer class="footer">
    &copy; <span id="year"></span> ETHERNIA – Nem hivatalos Minecraft oldal.
</footer>

<div class="news-modal" id="news-modal">
    <div class="news-modal-backdrop"></div>
    <div class="news-modal-dialog">
        <button type="button" class="news-modal-close" aria-label="Bezárás">×</button>
        <div class="news-modal-content">
            <div class="news-modal-content-inner"></div>
        </div>
    </div>
</div>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>
