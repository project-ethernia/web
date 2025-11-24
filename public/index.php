<?php
session_start();

require_once __DIR__ . '/database.php';

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = get_pdo();
    $stmt = $pdo->query("
        SELECT id, title, tag, date_display, short_text, full_text, order_index, author
        FROM news
        WHERE is_visible = 1
        ORDER BY order_index ASC, created_at DESC
    ");
    $news = $stmt->fetchAll();
} catch (Exception $e) {
    $news = [];
}

$isLoggedIn = !empty($_SESSION['is_user']) && !empty($_SESSION['user_username']);
$currentUser = $isLoggedIn ? $_SESSION['user_username'] : null;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Főoldal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body class="front-body">
<header class="hero">
    <div class="hero-inner">
        <div class="hero-top-row">
            <div class="top-card top-card-discord">
                <div class="top-card-label">Discord</div>
                <div class="top-card-main">
                    <span id="discord-online">–</span>
                </div>
                <div class="top-card-sub">tag a szerveren</div>
                <a href="https://discord.gg/SAJATMEGHIVO" class="top-card-link" target="_blank" rel="noreferrer">Csatlakozom →</a>
            </div>

            <div class="hero-center-title">ETHERNIA</div>

            <div class="top-card top-card-mc">
                <div class="top-card-label">Minecraft</div>
                <div class="top-card-main">
                    <span id="mc-online">–</span>
                    <span class="top-card-sep">/</span>
                    <span id="mc-max">–</span>
                </div>
                <div class="top-card-sub">játékos online</div>
                <div class="top-card-ip">
                    <span>IP:</span> <code>play.ethernia.hu</code>
                </div>
            </div>
        </div>

        <nav class="main-nav">
            <div class="main-nav-inner">
                <ul class="nav-links">
                    <li><a href="/" class="nav-link nav-link-active">Főoldal</a></li>
                    <li><a href="#" class="nav-link">Webshop</a></li>
                    <li><a href="#" class="nav-link">Szabályzat</a></li>
                    <li><a href="#" class="nav-link">Statisztikák</a></li>
                    <li><a href="#" class="nav-link">Kapcsolat</a></li>
                </ul>

                <div class="nav-user">
                    <?php if ($isLoggedIn): ?>
                        <span class="nav-user-text">Bejelentkezve: <strong><?= h($currentUser); ?></strong></span>
                        <a href="/auth/logout.php" class="nav-user-btn">Kijelentkezés</a>
                    <?php else: ?>
                        <a href="/auth/login.php" class="nav-user-btn">Bejelentkezés</a>
                        <a href="/auth/register.php" class="nav-user-btn nav-user-btn-ghost">Regisztráció</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="page">
    <section class="news-section">
        <header class="news-header">
            <div>
                <h2 class="news-title">Hírek &amp; frissítések</h2>
                <p class="news-subtitle">A legfrissebb információk az ETHERNIA világából.</p>
            </div>
            <div class="news-nav-buttons">
                <button type="button" class="news-nav-btn" id="news-prev">‹</button>
                <button type="button" class="news-nav-btn" id="news-next">›</button>
            </div>
        </header>

        <div class="news-list-wrapper">
            <div class="news-list" id="news-list">
                <?php if (!empty($news)): ?>
                    <?php foreach ($news as $row): ?>
                        <?php
                        $tag = $row['tag'] ?: 'Info';
                        $tagLower = mb_strtolower($tag, 'UTF-8');
                        $tagClass = 'news-pill';
                        if (mb_strpos($tagLower, 'event') !== false) {
                            $tagClass .= ' news-pill-event';
                        } elseif (mb_strpos($tagLower, 'info') !== false) {
                            $tagClass .= ' news-pill-info';
                        } elseif (mb_strpos($tagLower, 'újdonság') !== false || mb_strpos($tagLower, 'ujdonsag') !== false) {
                            $tagClass .= ' news-pill-new';
                        }
                        $dateDisplay = $row['date_display'] ?: '';
                        $shortText = $row['short_text'] ?: '';
                        $fullText = $row['full_text'] ?: '';
                        $author = $row['author'] ?: 'Ismeretlen';
                        ?>
                        <article
                            class="news-card"
                            data-title="<?= h($row['title']); ?>"
                            data-tag="<?= h($tag); ?>"
                            data-date="<?= h($dateDisplay); ?>"
                            data-text="<?= h($fullText !== '' ? $fullText : $shortText); ?>"
                        >
                            <div class="news-card-top">
                                <span class="<?= $tagClass; ?>"><?= h($tag); ?></span>
                                <span class="news-card-date"><?= h($dateDisplay); ?></span>
                            </div>
                            <h3 class="news-card-title"><?= h($row['title']); ?></h3>
                            <p class="news-card-text"><?= h($shortText); ?></p>
                            <p class="news-card-author">Közzétette: <strong><?= h($author); ?></strong></p>
                            <button type="button" class="news-detail-btn">Részletek</button>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="news-empty">
                        Jelenleg nincs megjeleníthető hír.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="info-tiles">
        <article class="info-card">
            <h3>Mi az ETHERNIA?</h3>
            <p>Egy modern, közösségközpontú magyar Minecraft szerver, ahol a hangulat és az élmény fontosabb, mint a pay‑to‑win.</p>
        </article>
        <article class="info-card">
            <h3>Események és jutalmak</h3>
            <p>Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes statisztikák várnak.</p>
        </article>
        <article class="info-card">
            <h3>Csatlakozz most</h3>
            <p>Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a kaland már vár rád!</p>
        </article>
    </section>
</main>

<footer class="footer">
    © <span id="year"></span> ETHERNIA · Nem hivatalos Minecraft oldal.
</footer>

<div class="news-modal" id="news-modal">
    <div class="news-modal-backdrop" id="news-modal-backdrop"></div>
    <div class="news-modal-dialog">
        <button type="button" class="news-modal-close" id="news-modal-close">×</button>
        <div class="news-modal-content">
            <div class="news-modal-meta">
                <span class="news-modal-pill" id="news-modal-tag"></span>
                <span class="news-modal-date" id="news-modal-date"></span>
            </div>
            <h3 class="news-modal-title" id="news-modal-title"></h3>
            <p class="news-modal-text" id="news-modal-text"></p>
        </div>
    </div>
</div>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>
