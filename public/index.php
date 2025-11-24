<?php
// ------------------------------------------------------
//  INDEX.PHP – Főoldal, hírek + statok
// ------------------------------------------------------
session_start();

require_once __DIR__ . '/database.php';

try {
    $pdo = get_pdo();
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

/* ----------- FELHASZNÁLÓ (PUBLIC) SESSION ----------- */
$isLoggedIn   = !empty($_SESSION['is_user']) && $_SESSION['is_user'] === true;
$currentUser  = $isLoggedIn ? ($_SESSION['user_username'] ?? 'Ismeretlen') : null;

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA – Minecraft szerver</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Főoldal stílus -->
  <link rel="stylesheet" href="/kell.css?v=<?= time(); ?>">
</head>
<body class="home-body">
  <div class="page-shell">

    <!-- HERO + NAV -->
    <header class="hero">
      <div class="hero-top">
        <!-- BAL: Discord panel -->
        <div class="hero-panel hero-panel-left">
          <div class="hero-panel-label">Discord</div>
          <div class="hero-panel-value">
            <span id="discord-online">--</span>
          </div>
          <div class="hero-panel-sub">tag a szerveren</div>
          <a href="https://discord.gg/SAJATMEGHIVO" target="_blank" class="hero-panel-link">
            Csatlakozom →
          </a>
        </div>

        <!-- KÖZÉP: ETHERNIA logó -->
        <div class="hero-logo-block">
          <div class="hero-logo">ETHERNIA</div>
          <div class="hero-logo-sub">Magyar Minecraft közösség</div>
          <div class="hero-logo-ip">
            IP: <code>play.ethernia.hu</code>
          </div>
        </div>

        <!-- JOBB: Minecraft panel -->
        <div class="hero-panel hero-panel-right">
          <div class="hero-panel-label">Minecraft</div>
          <div class="hero-panel-value">
            <span id="mc-online">--</span>
            <span class="hero-panel-sep">/</span>
            <span id="mc-max">--</span>
          </div>
          <div class="hero-panel-sub">játékos online</div>
          <div class="hero-panel-ip-small">play.ethernia.hu</div>
        </div>
      </div>

      <!-- NAVBAR -->
      <nav class="main-nav">
        <div class="nav-inner">
          <ul class="nav-links">
            <li><a href="/" class="nav-link nav-link-active">Főoldal</a></li>
            <li><a href="/shop" class="nav-link">Webshop</a></li>
            <li><a href="/rules" class="nav-link">Szabályzat</a></li>
            <li><a href="/stats" class="nav-link">Statisztikák</a></li>
            <li><a href="/contact" class="nav-link">Kapcsolat</a></li>
          </ul>

          <div class="nav-right">
            <?php if ($isLoggedIn): ?>
              <span class="nav-user">
                Bejelentkezve:
                <strong><?php echo h($currentUser); ?></strong>
              </span>
              <a href="/auth/logout.php" class="nav-link nav-link-ghost">Kijelentkezés</a>
            <?php else: ?>
              <a href="/auth/login.php" class="nav-link nav-link-ghost">Bejelentkezés</a>
              <a href="/auth/register.php" class="nav-link nav-link-primary">Regisztráció</a>
            <?php endif; ?>
          </div>
        </div>
      </nav>
    </header>

    <!-- TARTALOM -->
    <main class="main-content">

      <!-- HÍREK SZEKCIÓ -->
      <section class="news-section">
        <div class="news-header-row">
          <div>
            <h2 class="section-title">Hírek &amp; frissítések</h2>
            <p class="section-subtitle">
              A legfrissebb információk az ETHERNIA világából.
            </p>
          </div>

          <?php if (!empty($news)): ?>
            <div class="news-nav">
              <button type="button" class="news-arrow" data-news-nav="prev" aria-label="Előző hírek">
                ‹
              </button>
              <button type="button" class="news-arrow" data-news-nav="next" aria-label="Következő hírek">
                ›
              </button>
            </div>
          <?php endif; ?>
        </div>

        <?php if (empty($news)): ?>
          <div class="news-empty">
            Még nincs egyetlen közzétett hír sem. Nézz vissza később! 🙂
          </div>
        <?php else: ?>
          <div class="news-list-wrapper">
            <div class="news-list" id="news-list">
              <?php foreach ($news as $row): ?>
                <?php
                  $tag        = $row['tag'] ?? 'Info';
                  $tagLower   = mb_strtolower($tag, 'UTF-8');
                  $tagClass   = 'news-tag';
                  if (strpos($tagLower, 'event') !== false) {
                      $tagClass .= ' news-tag-event';
                  } elseif (strpos($tagLower, 'info') !== false) {
                      $tagClass .= ' news-tag-info';
                  }

                  $dateDisplay = $row['date_display'] ?: '';
                  $shortText   = $row['short_text'] ?: '';
                  $fullText    = $row['full_text'] ?: '';
                  $author      = $row['author'] ?: 'Ismeretlen';

                  $hasDetails = !empty($fullText);
                ?>
                <article
                  class="news-card"
                  <?php if ($hasDetails): ?>
                    data-full="<?php echo h($fullText); ?>"
                  <?php endif; ?>
                >
                  <div class="news-meta">
                    <span class="<?php echo $tagClass; ?>">
                      <?php echo h($tag); ?>
                    </span>
                    <span class="news-date">
                      <?php echo h($dateDisplay); ?>
                    </span>
                  </div>

                  <h3 class="news-headline">
                    <?php echo h($row['title']); ?>
                  </h3>

                  <p class="news-excerpt">
                    <?php echo h($shortText); ?>
                  </p>

                  <div class="news-footer">
                    <span class="news-author">
                      Közzétette: <strong><?php echo h($author); ?></strong>
                    </span>

                    <?php if ($hasDetails): ?>
                      <button type="button" class="news-readmore">
                        Részletek
                      </button>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </section>

      <!-- ALSÓ INFÓ SZEKCIÓ -->
      <section class="features-section">
        <div class="features-grid">
          <article class="feature-card">
            <h3>Mi az ETHERNIA?</h3>
            <p>
              Egy modern, közösségközpontú magyar Minecraft szerver, ahol a
              hangulat és az élmény fontosabb, mint a pay‑to‑win.
            </p>
          </article>

          <article class="feature-card">
            <h3>Eventek &amp; jutalmak</h3>
            <p>
              Rendszeres eventek, szezonális jutalmak, egyedi rangok és webes
              statisztikák várnak.
            </p>
          </article>

          <article class="feature-card">
            <h3>Csatlakozz most</h3>
            <p>
              Lépj be Discordra, kérdezz bátran, és ugorj fel a szerverre – a
              kaland már vár rád!
            </p>
          </article>
        </div>
      </section>

    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
      &copy; <span id="year"></span> ETHERNIA · Nem hivatalos Minecraft oldal.
    </footer>
  </div>

  <!-- HÍR MODAL -->
  <div class="news-modal" id="news-modal">
    <div class="news-modal-backdrop"></div>
    <div class="news-modal-dialog">
      <button type="button" class="news-modal-close" aria-label="Bezárás">×</button>
      <div class="news-modal-content">
        <div class="news-modal-content-inner"></div>
      </div>
    </div>
  </div>

  <script src="/assets/js/index.js?v=10"></script>
</body>
</html>
