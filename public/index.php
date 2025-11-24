<?php
session_start();

$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

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

$userLoggedIn = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
$currentUser  = $userLoggedIn ? ($_SESSION['user_username'] ?? 'Ismeretlen') : null;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA – Főoldal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body>
  <div class="page-wrap">

    <header class="top-hero">
      <div class="top-stats-row">
        <div class="top-stat-card">
          <div class="top-stat-label">Discord</div>
          <div class="top-stat-value" id="discord-online">--</div>
          <div class="top-stat-caption">tag a szerveren</div>
        </div>

        <div class="top-brand">
          <span class="top-brand-text">ETHERNIA</span>
        </div>

        <div class="top-stat-card">
          <div class="top-stat-label">Minecraft</div>
          <div class="top-stat-value">
            <span id="mc-online">--</span>
            <span class="top-stat-separator">/</span>
            <span id="mc-max">500</span>
          </div>
          <div class="top-stat-caption">játékos online</div>
        </div>
      </div>

      <nav class="main-nav">
        <div class="nav-inner">
          <div class="nav-links-wrap">
            <ul class="nav-links">
              <li class="nav-item nav-item-active"><a href="/">Főoldal</a></li>
              <li class="nav-item"><a href="#">Webshop</a></li>
              <li class="nav-item"><a href="#">Szabályzat</a></li>
              <li class="nav-item"><a href="#">Statisztikák</a></li>
              <li class="nav-item"><a href="#">Kapcsolat</a></li>
            </ul>
          </div>

          <div class="nav-user">
            <?php if ($userLoggedIn): ?>
              <div class="nav-user-pill">
                <span class="nav-user-label">Bejelentkezve</span>
                <span class="nav-user-name"><?= h($currentUser); ?></span>
              </div>
              <a href="/auth/logout.php" class="nav-btn nav-btn-logout">Kijelentkezés</a>
            <?php else: ?>
              <a href="/auth/login.php" class="nav-btn nav-btn-ghost">Bejelentkezés</a>
              <a href="/auth/register.php" class="nav-btn nav-btn-primary">Regisztráció</a>
            <?php endif; ?>
          </div>
        </div>
      </nav>
    </header>

    <main class="main-content">
      <section class="news-section">
        <header class="section-header">
          <div>
            <h2 class="section-title">Hírek &amp; frissítések</h2>
            <p class="section-subtitle">A legfrissebb információk az ETHERNIA világából.</p>
          </div>
        </header>

        <div class="news-shell">
          <button type="button" class="news-arrow news-arrow-left" id="news-prev">‹</button>

          <div class="news-track" id="news-track">
            <?php if (!empty($news)): ?>
              <?php foreach ($news as $row): ?>
                <?php
                  $tag         = $row['tag'] ?? 'Info';
                  $tagLower    = mb_strtolower($tag, 'UTF-8');
                  $tagClass    = 'news-tag-pill';
                  if (strpos($tagLower, 'event') !== false) {
                      $tagClass .= ' news-tag-event';
                  } elseif (strpos($tagLower, 'info') !== false) {
                      $tagClass .= ' news-tag-info';
                  } elseif (strpos($tagLower, 'újdonság') !== false) {
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
                  <div class="news-card-top">
                    <span class="<?= $tagClass; ?>"><?= h($tag); ?></span>
                    <span class="news-card-date"><?= h($dateDisplay); ?></span>
                  </div>
                  <h3 class="news-card-title"><?= h($row['title']); ?></h3>
                  <p class="news-card-body"><?= h($shortText); ?></p>
                  <div class="news-card-footer">
                    <span class="news-card-author">Közzétette: <strong><?= h($author); ?></strong></span>
                    <button type="button" class="news-readmore">Részletek</button>
                  </div>
                </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <button type="button" class="news-arrow news-arrow-right" id="news-next">›</button>
        </div>
      </section>

      <section class="info-strip">
        <div class="info-card">
          <h3 class="info-card-title">Mi az ETHERNIA?</h3>
          <p class="info-card-text">
            Egy modern, közösségközpontú magyar Minecraft szerver, ahol a hangulat és az élmény fontosabb, mint a pay‑to‑win.
          </p>
        </div>
        <div class="info-card">
          <h3 class="info-card-title">Eventek és jutalmak</h3>
          <p class="info-card-text">
            Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes statisztikák várnak.
          </p>
        </div>
        <div class="info-card">
          <h3 class="info-card-title">Csatlakozz most</h3>
          <p class="info-card-text">
            Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a kaland már rád vár.
          </p>
        </div>
      </section>
    </main>

    <footer class="footer">
      <span>© <span id="year"></span> ETHERNIA – Nem hivatalos Minecraft oldal.</span>
    </footer>
  </div>

  <div class="news-modal" id="news-modal">
    <div class="news-modal-backdrop"></div>
    <div class="news-modal-dialog">
      <button type="button" class="news-modal-close">×</button>
      <div class="news-modal-content">
        <div class="news-modal-content-inner"></div>
      </div>
    </div>
  </div>

  <script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>
