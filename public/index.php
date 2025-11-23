<?php
// index.php – főoldal DB-ből jövő hírekkel

// --- DB beállítások: ÁLLÍTSD BE SAJÁT ADATOKRA (ugyanaz, mint admin/news.php) ---
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'SAJAT_DB_USER';
$DB_PASS = 'SAJAT_DB_JELSZO';

$news = [];

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $stmt = $pdo->query("
        SELECT id, title, tag, date_display, short_text, full_text, order_index
        FROM news
        WHERE is_visible = 1
        ORDER BY order_index ASC, created_at DESC
        LIMIT 50
    ");
    $news = $stmt->fetchAll();
} catch (Exception $e) {
    // Ha gond van, itt eldöntheted, hogy kiírod a hibát, vagy csendben maradsz
    // error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA - Bejelentkezés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- root-abszolút + cache-bust -->
  <link rel="stylesheet" href="/login.css?v=6">
</head>
<body>

  <!-- FELSŐ SÁV: hírek karusszel -->
  <header class="news-header">
    <div class="news-header-inner">
      <h2 class="news-title">Hírek &amp; frissítések</h2>

      <div class="news-strip">
        <div class="news-strip-inner" id="news-strip-inner">
          <?php if (!empty($news)): ?>
            <?php foreach ($news as $row): ?>
              <?php
                $tag         = $row['tag'] !== null ? $row['tag'] : 'Info';
                $tagLower    = mb_strtolower($tag, 'UTF-8');
                $tagClass    = 'news-tag';
                if (strpos($tagLower, 'event') !== false) {
                    $tagClass .= ' news-tag-event';
                } elseif (strpos($tagLower, 'info') !== false) {
                    $tagClass .= ' news-tag-info';
                }
                $dateDisplay = $row['date_display'] !== null ? $row['date_display'] : '';
                $shortText   = $row['short_text'] !== null ? $row['short_text'] : '';
                $fullText    = $row['full_text'] !== null ? $row['full_text'] : '';
              ?>
              <article
                class="news-card"
                <?php if ($fullText !== ''): ?>
                  data-full="<?php echo htmlspecialchars($fullText, ENT_QUOTES, 'UTF-8'); ?>"
                <?php endif; ?>
              >
                <div class="news-meta">
                  <span class="<?php echo $tagClass; ?>">
                    <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                  <span class="news-date">
                    <?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </div>
                <h3 class="news-headline">
                  <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <p class="news-text">
                  <?php echo htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <button type="button" class="news-readmore">Részletek</button>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Ha nincs hír, akár ide tehetsz egy "Nincs hír" placeholdert is -->
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- ALSÓ SÁV: statok + login -->
  <main class="page">
    <!-- BAL: statok -->
    <section class="info-panel">
      <div class="brand-block">
        <div class="brand-logo">ETHERNIA</div>
      </div>

      <div class="stats-grid">
        <article class="stat-card">
          <h2>Minecraft szerver</h2>
          <div class="stat-main">
            <span id="mc-online">--</span>
            <span class="stat-separator">/</span>
            <span id="mc-max">--</span>
          </div>
          <p class="stat-label">jelenleg online játékos</p>
          <p class="stat-sub">IP: <code>play.ethernia.hu</code></p>
        </article>

        <article class="stat-card stat-discord">
          <h2>Discord</h2>
          <div class="stat-main">
            <span id="discord-online">--</span>
          </div>
          <p class="stat-label">tag a szerveren</p>
          <a class="btn-outline" href="https://discord.gg/SAJATMEGHIVO" target="_blank" rel="noopener noreferrer">
            Csatlakozom a Discordhoz
          </a>
        </article>

        <article class="stat-card">
          <h2>Mi vár rád az ETHERNIA-n?</h2>
          <ul class="feature-list">
            <li>Egyedi, fekete–lila dizájn és atmoszféra</li>
            <li>Aktív, barátságos közösség és segítőkész staff</li>
            <li>Eventek, jutalmak, rangok a webes profilodhoz kötve</li>
          </ul>
          <p class="stat-sub">
            Lépj be, nézd meg a statjaidat, és csatlakozz a játékhoz!
          </p>
        </article>
      </div>
    </section>

    <!-- JOBB: login -->
    <section class="auth-section">
      <div class="auth-card">
        <h1 class="auth-title">Bejelentkezés</h1>
        <p class="auth-note">
          Lépj be a fiókodba, hogy elérd a statjaidat, jutalmaidat és a webes funkciókat.
        </p>

        <form class="auth-form" action="index.php" method="POST">
          <div class="form-group">
            <label for="username">Felhasználónév</label>
            <input type="text" id="username" name="username" placeholder="játékosnév" required>
          </div>

          <div class="form-group">
            <label for="password">Jelszó</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>

          <div class="form-row">
            <label class="checkbox">
              <input type="checkbox" name="remember">
              <span>Emlékezz rám</span>
            </label>
            <a href="/new-password.html" class="link-small">Elfelejtett jelszó?</a>
          </div>

          <button type="submit" class="btn auth-btn">Bejelentkezés</button>
        </form>

        <div class="auth-footer-text">
          Nincs még fiókod?
          <a href="/register.html" class="link-accent">Regisztráció</a>
        </div>

        <div class="auth-help">
          Probléma a belépéssel?
          <a href="mailto:support@ethernia.hu">Írj a supportnak</a>.
        </div>
      </div>
    </section>
  </main>

  <!-- Globális footer az oldal alján -->
  <footer class="footer">
    &copy; <span id="year"></span> ETHERNIA &middot; Nem hivatalos Minecraft oldal.
  </footer>

  <!-- HÍR MODAL -->
  <div class="news-modal" id="news-modal" aria-hidden="true">
    <div class="news-modal-backdrop"></div>
    <div class="news-modal-dialog" role="dialog" aria-modal="true">
      <button type="button" class="news-modal-close" aria-label="Bezárás">×</button>
      <div class="news-modal-content">
        <div class="news-modal-content-inner">
          <!-- ide tölti be a JS a kiválasztott hírt -->
        </div>
      </div>
    </div>
  </div>

  <script src="/login.js?v=7"></script>
</body>
</html>
