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
    die('Adatbázis hiba: ' . $e->getMessage());
}

require_once __DIR__ . '/news_tags.php';

$stmt = $pdo->query("
    SELECT id, title, tag, date_display, short_text, full_text, order_index, author
    FROM news
    WHERE is_visible = 1
    ORDER BY order_index ASC, created_at DESC
");
$news = $stmt->fetchAll();

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$isLoggedIn = !empty($_SESSION['is_user']);
$currentUser = $isLoggedIn ? ($_SESSION['user_username'] ?? 'Ismeretlen') : null;
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
    <div class="hero-shell">
      <div class="hero-row">
        <section class="hero-stat-card">
          <div class="hero-stat-label">Discord</div>
          <div class="hero-stat-value" id="discord-online">--</div>
          <div class="hero-stat-sub">tag a szerveren</div>
          <a href="https://discord.gg/SAJATMEGHIVO" target="_blank" class="hero-stat-link">Csatlakozom →</a>
        </section>

        <div class="hero-title-wrap">
          <div class="hero-title">ETHERNIA</div>
        </div>

        <section class="hero-stat-card">
          <div class="hero-stat-label">Minecraft</div>
          <div class="hero-stat-value">
            <span id="mc-online">--</span>
            <span class="hero-stat-separator">/</span>
            <span id="mc-max">--</span>
          </div>
          <div class="hero-stat-sub">játékos online</div>
          <div class="hero-stat-sub hero-stat-ip">IP: <span>play.ethernia.hu</span></div>
        </section>
      </div>

      <nav class="main-nav">
        <div class="main-nav-inner">
          <ul class="nav-links">
            <li><a href="/" class="nav-link is-active">Főoldal</a></li>
            <li><a href="#" class="nav-link">Webshop</a></li>
            <li><a href="#" class="nav-link">Szabályzat</a></li>
            <li><a href="#" class="nav-link">Statisztikák</a></li>
            <li><a href="#" class="nav-link">Kapcsolat</a></li>
          </ul>

          <div class="nav-user">
            <?php if ($isLoggedIn): ?>
              <span class="nav-user-label">Bejelentkezve:</span>
              <span class="nav-user-name"><?= h($currentUser); ?></span>
              <a href="/auth/logout.php" class="nav-user-button">Kijelentkezés</a>
            <?php else: ?>
              <a href="/auth/login.php" class="nav-user-button nav-user-button-ghost">Bejelentkezés</a>
              <a href="/auth/register.php" class="nav-user-button">Regisztráció</a>
            <?php endif; ?>
          </div>
        </div>
      </nav>
    </div>
  </header>

  <main class="main-shell">
    <section class="news-section">
      <div class="news-shell">
        <header class="news-header-row">
          <div>
            <h2 class="news-heading">Hírek &amp; frissítések</h2>
            <p class="news-subtitle">
              A legfrissebb információk az ETHERNIA világából.
            </p>
          </div>
        </header>

        <?php if (!empty($news)): ?>
          <div class="news-slider">
            <button type="button" class="news-arrow news-arrow-left" aria-label="Előző">
              <span class="news-arrow-icon">‹</span>
            </button>

            <div class="news-viewport">
              <div class="news-row">
                <?php foreach ($news as $row): ?>
                  <?php
                    $rawTag = $row['tag'] ?: 'Info';
                    $tagConfig = $NEWS_TAGS[$rawTag] ?? $NEWS_TAGS['_default'];
                    $tagStyle = $tagConfig['style'];
                    $tagLabel = $tagConfig['label'];
                    $dateDisplay = $row['date_display'] ?: '';
                    $shortText = $row['short_text'] ?: '';
                    $fullText = $row['full_text'] ?: '';
                    $author = $row['author'] ?: 'Ismeretlen';
                  ?>
                  <article
                    class="news-card"
                    data-full="<?= h($fullText); ?>"
                  >
                    <div class="news-card-body">
                      <div class="news-meta">
                        <span class="news-tag" style="<?= h($tagStyle); ?>">
                          <?= h($tagLabel); ?>
                        </span>
                        <span class="news-date"><?= h($dateDisplay); ?></span>
                      </div>

                      <h3 class="news-headline"><?= h($row['title']); ?></h3>

                      <p class="news-text">
                        <?= h($shortText); ?>
                      </p>
                    </div>

                    <div class="news-card-footer">
                      <p class="news-author">
                        Közzétette: <strong><?= h($author); ?></strong>
                      </p>
                      <button type="button" class="news-btn news-readmore">
                        Részletek
                      </button>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="button" class="news-arrow news-arrow-right" aria-label="Következő">
              <span class="news-arrow-icon">›</span>
            </button>
          </div>
        <?php else: ?>
          <p class="news-empty">Még nincs megjeleníthető hír.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="feature-section">
      <div class="feature-grid">
        <article class="feature-card">
          <h3 class="feature-title">Mi az ETHERNIA?</h3>
          <p class="feature-text">
            Egy modern, közösségközpontú magyar Minecraft szerver, ahol a hangulat és az élmény fontosabb, mint a pay-to-win.
          </p>
        </article>

        <article class="feature-card">
          <h3 class="feature-title">Események és jutalmak</h3>
          <p class="feature-text">
            Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes statisztikák várnak.
          </p>
        </article>

        <article class="feature-card">
          <h3 class="feature-title">Csatlakozz most</h3>
          <p class="feature-text">
            Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a kaland már vár rád.
          </p>
        </article>
      </div>
    </section>
  </main>

  <footer class="footer">
    © <span id="year"></span> ETHERNIA – Nem hivatalos Minecraft oldal.
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
