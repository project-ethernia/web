<?php
// ------------------------------------------------------
//  INDEX.PHP – főoldal, hírek betöltése közvetlenül DB-ből
// ------------------------------------------------------
session_start();

/* ----------- DB BEÁLLÍTÁSOK (ÁLLÍTSD BE!) ----------- */
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

/* ----------- DB KAPCSOLAT ----------- */
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Adatbázis hiba: " . $e->getMessage());
}

/* ----------- HÍREK LEKÉRÉSE ----------- */
$stmt = $pdo->query("
    SELECT id, title, tag, date_display, short_text, full_text, order_index, author
    FROM news
    WHERE is_visible = 1
    ORDER BY order_index ASC, created_at DESC
");
$news = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA - Bejelentkezés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="assets/css/kell.css?v=<?= time(); ?>">
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
                $tag = $row['tag'] ?? 'Info';
                $tagLower = mb_strtolower($tag, 'UTF-8');
                $tagClass = "news-tag";

                if (strpos($tagLower, 'event') !== false) {
                    $tagClass .= " news-tag-event";
                } elseif (strpos($tagLower, 'info') !== false) {
                    $tagClass .= " news-tag-info";
                }

                $dateDisplay = $row['date_display'] ?: '';
                $shortText   = $row['short_text'] ?: '';
                $fullText    = $row['full_text'] ?: '';
                $author      = $row['author'] ?: "Ismeretlen";
              ?>

              <article
                class="news-card"
                <?php if (!empty($fullText)): ?>
                  data-full="<?php echo htmlspecialchars($fullText, ENT_QUOTES, 'UTF-8'); ?>"
                <?php endif; ?>
              >
                <div class="news-meta">
                  <span class="<?php echo $tagClass; ?>">
                    <?php echo htmlspecialchars($tag); ?>
                  </span>
                  <span class="news-date">
                    <?php echo htmlspecialchars($dateDisplay); ?>
                  </span>
                </div>

                <h3 class="news-headline">
                  <?php echo htmlspecialchars($row['title']); ?>
                </h3>

                <p class="news-text">
                  <?php echo htmlspecialchars($shortText); ?>
                </p>

                <p class="news-author">
                  Közzétette: <strong><?php echo htmlspecialchars($author); ?></strong>
                </p>

                <button type="button" class="news-readmore">Részletek</button>
              </article>

            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </header>

  <!-- ALSÓ SÁV: statok + login -->
  <main class="page">
    <!-- BAL PANEL -->
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
          <a class="btn-outline" href="https://discord.gg/SAJATMEGHIVO" target="_blank">
            Csatlakozom a Discordhoz
          </a>
        </article>

        <article class="stat-card">
          <h2>Mi vár rád az ETHERNIA-n?</h2>
          <ul class="feature-list">
            <li>Egyedi, fekete–lila atmoszféra</li>
            <li>Aktív közösség és segítőkész staff</li>
            <li>Eventek, jutalmak, rangok</li>
          </ul>
        </article>

      </div>
    </section>

    <!-- JOBB OLDALI LOGIN -->
    <section class="auth-section">
      <div class="auth-card">
        <h1 class="auth-title">Bejelentkezés</h1>

        <p class="auth-note">
          Lépj be a fiókodba, hogy elérd a statjaidat és jutalmaidat.
        </p>

        <form class="auth-form" action="index.php" method="POST">
          <div class="form-group">
            <label for="username">Felhasználónév</label>
            <input type="text" id="username" name="username" required>
          </div>

          <div class="form-group">
            <label for="password">Jelszó</label>
            <input type="password" id="password" name="password" required>
          </div>

          <div class="form-row">
            <label class="checkbox">
              <input type="checkbox" name="remember">
              <span>Emlékezz rám</span>
            </label>
            <a class="link-small" href="/new-password.html">Elfelejtett jelszó?</a>
          </div>

          <button type="submit" class="btn auth-btn">Bejelentkezés</button>
        </form>

        <div class="auth-footer-text">
          Nincs még fiókod?
          <a class="link-accent" href="/register.html">Regisztráció</a>
        </div>
      </div>
    </section>

  </main>

  <!-- FOOTER -->
  <footer class="footer">
    &copy; <span id="year"></span> ETHERNIA &middot; Nem hivatalos Minecraft oldal.
  </footer>

  <!-- HÍR MODAL -->
  <div class="news-modal" id="news-modal">
    <div class="news-modal-backdrop"></div>
    <div class="news-modal-dialog">
      <button type="button" class="news-modal-close">×</button>
      <div class="news-modal-content">
        <div class="news-modal-content-inner"></div>
      </div>
    </div>
  </div>

  <script src="/login.js?v=7"></script>
</body>
</html>
