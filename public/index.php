<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Adatbázis hiba: " . $e->getMessage());
}

$stmt = $pdo->query("
    SELECT id, title, tag, date_display, short_text, full_text, order_index, author
    FROM news
    WHERE is_visible = 1
    ORDER BY order_index ASC, created_at DESC
");
$news = $stmt->fetchAll();

$isUser       = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
$currentUser  = $isUser ? ($_SESSION['user_username'] ?? 'Ismeretlen') : null;

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA - Főoldal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body>

<header class="hero">
  <div class="hero-top">
    <div class="hero-stat-card">
      <div class="hero-stat-label">Discord</div>
      <div class="hero-stat-value" id="discord-online">--</div>
      <div class="hero-stat-sub">tag a szerveren</div>
      <a href="https://discord.gg/SAJATMEGHIVO" target="_blank" class="hero-stat-link">Csatlakozom →</a>
    </div>

    <div class="hero-title">ETHERNIA</div>

    <div class="hero-stat-card">
      <div class="hero-stat-label">Minecraft</div>
      <div class="hero-stat-value">
        <span id="mc-online">--</span>
        <span class="hero-stat-sep">/</span>
        <span id="mc-max">--</span>
      </div>
      <div class="hero-stat-sub">játékos online</div>
      <div class="hero-stat-sub hero-stat-ip">IP: <code>play.ethernia.hu</code></div>
    </div>
  </div>

  <nav class="main-nav">
    <div class="nav-center">
      <ul class="nav-links">
        <li><a href="/" class="nav-link nav-link-active">Főoldal</a></li>
        <li><a href="#" class="nav-link">Webshop</a></li>
        <li><a href="#" class="nav-link">Szabályzat</a></li>
        <li><a href="#" class="nav-link">Statisztikák</a></li>
        <li><a href="#" class="nav-link">Kapcsolat</a></li>
      </ul>
    </div>
    <div class="nav-right">
      <?php if ($isUser): ?>
        <span class="nav-user-pill">Bejelentkezve: <strong><?= h($currentUser); ?></strong></span>
        <a href="/auth/logout.php" class="nav-btn nav-btn-outline">Kijelentkezés</a>
      <?php else: ?>
        <a href="/auth/login.php" class="nav-btn nav-btn-outline">Bejelentkezés</a>
        <a href="/auth/register.php" class="nav-btn nav-btn-main">Regisztráció</a>
      <?php endif; ?>
    </div>
  </nav>
</header>

<main class="page-main">
  <section class="news-section">
    <div class="news-header-row">
      <div>
        <h2 class="news-section-title">Hírek &amp; frissítések</h2>
        <p class="news-section-subtitle">A legfrissebb információk az ETHERNIA világából.</p>
      </div>
      <div class="news-arrows">
        <button type="button" class="news-arrow" id="news-prev" aria-label="Előző hír">‹</button>
        <button type="button" class="news-arrow" id="news-next" aria-label="Következő hír">›</button>
      </div>
    </div>

    <div class="news-viewport">
      <div class="news-track" id="news-track">
        <?php foreach ($news as $row): ?>
          <?php
            $tag        = $row['tag'] ?? 'Info';
            $tagLower   = mb_strtolower($tag, 'UTF-8');
            $tagClass   = 'news-tag';
            if (strpos($tagLower, 'event') !== false) {
              $tagClass .= ' news-tag-event';
            } elseif (strpos($tagLower, 'info') !== false) {
              $tagClass .= ' news-tag-info';
            } elseif (strpos($tagLower, 'újdonság') !== false || strpos($tagLower, 'ujdonsag') !== false) {
              $tagClass .= ' news-tag-new';
            }

            $dateDisplay = $row['date_display'] ?: '';
            $shortText   = $row['short_text'] ?: '';
            $fullText    = $row['full_text'] ?: '';
            $author      = $row['author'] ?: 'Ismeretlen';
          ?>
          <article
            class="news-card"
            data-full="<?= $fullText !== '' ? h($fullText) : ''; ?>"
          >
            <div class="news-meta">
              <span class="<?= $tagClass; ?>"><?= h($tag); ?></span>
              <span class="news-date"><?= h($dateDisplay); ?></span>
            </div>

            <h3 class="news-headline"><?= h($row['title']); ?></h3>

            <p class="news-text"><?= h($shortText); ?></p>

            <div class="news-footer">
              <p class="news-author">Közzétette: <strong><?= h($author); ?></strong></p>
              <button type="button" class="news-readmore">Részletek</button>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if (empty($news)): ?>
          <div class="news-empty">
            Jelenleg nincs megjeleníthető hír. Nézz vissza később!
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="feature-row">
    <div class="feature-card">
      <h3>Mi az ETHERNIA?</h3>
      <p>Egy modern, közösségközpontú magyar Minecraft szerver, ahol a hangulat és az élmény fontosabb, mint a pay-to-win.</p>
    </div>
    <div class="feature-card">
      <h3>Események és jutalmak</h3>
      <p>Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes statisztikák várnak.</p>
    </div>
    <div class="feature-card">
      <h3>Csatlakozz most</h3>
      <p>Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a kaland már vár rád!</p>
    </div>
  </section>
</main>

<footer class="footer">
  &copy; <span id="year"></span> ETHERNIA · Nem hivatalos Minecraft oldal.
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
