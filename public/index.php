<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Hírek betöltése adatbázisból
$news = [];
try {
    $pdo = get_pdo();
    $stmt = $pdo->query("
        SELECT id, title, tag, date_display, short_text, full_text, author
        FROM news
        WHERE is_visible = 1
        ORDER BY order_index ASC, created_at DESC
        LIMIT 12
    ");
    $news = $stmt->fetchAll();
} catch (Exception $e) {
    $news = [];
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA - Minecraft Szerver</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
</head>
<body class="public-body">

  <div class="page-shell">
    <!-- HERO + NAVBAR -->
    <header class="hero">
      <div class="hero-top">
        <!-- Discord stat kártya -->
        <section class="stat-card stat-card-discord">
          <div class="stat-label">Discord</div>
          <div class="stat-main">
            <span id="discord-online" class="stat-value">—</span>
            <span class="stat-unit">online</span>
          </div>
          <p class="stat-sub">Csatlakozz a közösséghez, event infók, support.</p>
          <a href="https://discord.gg/ethernia" target="_blank" class="stat-link">
            Belépés a szerverre →
          </a>
        </section>

        <!-- Középső ETHERNIA blokk -->
        <section class="hero-center">
          <p class="hero-eyebrow">Minecraft szerver • survival • közösség</p>
          <h1 class="hero-title">ETHERNIA</h1>
          <p class="hero-subtitle">
            Fókuszban a játékélmény, a közösség és a jutalmak. Csatlakozz, és nézd meg,
            mire vagy képes ETHERNIA világában.
          </p>

          <div class="hero-actions">
            <button type="button" class="btn primary" id="copy-ip-btn" data-ip="play.ethernia.hu">
              Csatlakozás: <span class="ip-text">play.ethernia.hu</span>
            </button>
            <a href="/auth/register.php" class="btn subtle">
              Webes fiók létrehozása
            </a>
          </div>
        </section>

        <!-- Minecraft stat kártya -->
        <section class="stat-card stat-card-mc">
          <div class="stat-label">Minecraft</div>
          <div class="stat-main">
            <span id="mc-online" class="stat-value">—</span>
            <span class="stat-unit">online</span>
          </div>
          <p class="stat-sub">
            Elérhető slot: <span id="mc-max" class="stat-inline">?</span>
          </p>
          <p class="stat-sub small">
            IP: <strong>play.ethernia.hu</strong>
          </p>
        </section>
      </div>

      <!-- NAVBAR -->
      <nav class="main-nav">
        <div class="nav-inner">
          <a href="#top" class="nav-logo">ETHERNIA</a>
          <div class="nav-links">
            <a href="#news">Hírek</a>
            <a href="#about">Információk</a>
            <a href="#rewards">Jutalmak</a>
            <a href="#faq">GYIK</a>
          </div>
          <div class="nav-actions">
            <a href="/auth/login.php" class="nav-btn nav-btn-ghost">Bejelentkezés</a>
            <a href="/auth/register.php" class="nav-btn nav-btn-primary">Regisztráció</a>
          </div>
        </div>
      </nav>
    </header>

    <main>
      <!-- HÍREK -->
      <section class="section" id="news">
        <div class="section-header">
          <h2>Hírek &amp; frissítések</h2>
          <p>
            Az aktuális események, patch note-ok, újdonságok és közösségi bejelentések.
          </p>
        </div>

        <?php if (empty($news)): ?>
          <p class="news-empty">Jelenleg nincsenek megjeleníthető hírek.</p>
        <?php else: ?>
          <div class="news-slider">
            <button
              class="news-arrow news-arrow-left"
              type="button"
              aria-label="Előző hírek"
              id="news-prev"
            >
              ‹
            </button>

            <div class="news-track-wrapper">
              <div class="news-track" id="news-track">
                <?php foreach ($news as $item): ?>
                  <?php
                    $tag = $item['tag'] ?: 'Info';
                    $tagLower = mb_strtolower($tag, 'UTF-8');
                    $tagClass = 'news-tag';
                    if (strpos($tagLower, 'event') !== false) {
                        $tagClass .= ' news-tag-event';
                    } elseif (strpos($tagLower, 'info') !== false) {
                        $tagClass .= ' news-tag-info';
                    } elseif (strpos($tagLower, 'teszt') !== false) {
                        $tagClass .= ' news-tag-test';
                    }
                  ?>
                  <article class="news-card">
                    <div class="news-meta">
                      <span class="<?= h($tagClass); ?>">
                        <?= h($tag); ?>
                      </span>
                      <span class="news-date">
                        <?= h($item['date_display']); ?>
                      </span>
                    </div>

                    <h3 class="news-title"><?= h($item['title']); ?></h3>

                    <?php if (!empty($item['short_text'])): ?>
                      <p class="news-text">
                        <?= h($item['short_text']); ?>
                      </p>
                    <?php endif; ?>

                    <p class="news-author">
                      Közzétette:
                      <strong><?= h($item['author'] ?: 'ETHERNIA Staff'); ?></strong>
                    </p>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>

            <button
              class="news-arrow news-arrow-right"
              type="button"
              aria-label="Következő hírek"
              id="news-next"
            >
              ›
            </button>
          </div>
        <?php endif; ?>
      </section>

      <!-- INFO / JUTALMAK / GYIK – placeholder szekciók, ugyanazzal a dizájnnal -->

      <section class="section section-grid" id="about">
        <div class="section-header">
          <h2>Mi az az ETHERNIA?</h2>
          <p>Modern, közösség-központú Minecraft szerver, webes statokkal és jutalmakkal.</p>
        </div>

        <div class="info-grid">
          <article class="info-card">
            <h3>Survival alapokkal</h3>
            <p>
              Nem Pay2Win, nem telespamelt menük – inkább stabil alap survival élmény,
              kényelmi extrákkal és átgondolt egyensúllyal.
            </p>
          </article>

          <article class="info-card">
            <h3>Webes statok</h3>
            <p>
              Regisztrált fiókkal visszanézheted statjaidat, aktivitásod,
              és idővel külön jutalmakat is kapsz a játékidő alapján.
            </p>
          </article>

          <article class="info-card">
            <h3>Közösség és eventek</h3>
            <p>
              Rendszeres közösségi eventek, minijátékok, kisebb versenyek –
              minden a Discordon és itt a főoldali hírekben kommunikálva.
            </p>
          </article>
        </div>
      </section>

      <section class="section" id="rewards">
        <div class="section-header">
          <h2>Jutalmak &amp; webes fiók</h2>
          <p>Regisztrálj weben, hogy később egyedi jutalmakat, tag-eket és kozmetikai cuccokat kaphass.</p>
        </div>

        <div class="rewards-layout">
          <div class="rewards-text">
            <ul class="rewards-list">
              <li>📊 Áttekintés a játékidődről és statjaidról</li>
              <li>🎖 Különleges rangok, címkék, ha aktív vagy</li>
              <li>🎁 Időszakos jutalmak, amiket weben tudsz átvenni</li>
              <li>🔐 Fiókkezelés: e-mail csere, jelszócsere, biztonsági beállítások</li>
            </ul>
            <a href="/auth/register.php" class="btn primary rewards-btn">
              Regisztráció webes fiókra →
            </a>
          </div>

          <div class="rewards-card">
            <p class="rewards-label">Gyors infó</p>
            <p class="rewards-line">
              🌐 Webes fiók: <strong>nem kötelező</strong>, de erősen ajánlott.
            </p>
            <p class="rewards-line">
              🔗 Minecraft és web összekötés: később in-game paranccsal.
            </p>
            <p class="rewards-line">
              🧩 Jutalmak: fokozatosan kerülnek be, ahogy készítjük a rendszert.
            </p>
          </div>
        </div>
      </section>

      <section class="section" id="faq">
        <div class="section-header">
          <h2>Gyakori kérdések</h2>
          <p>Rövid válaszok a legtipikusabb kérdésekre.</p>
        </div>

        <div class="faq-grid">
          <article class="info-card">
            <h3>Hogyan csatlakozom a szerverre?</h3>
            <p>
              Nyisd meg a Minecraftot, add hozzá új szerverként:
              <strong>play.ethernia.hu</strong>, majd csatlakozz.
            </p>
          </article>
          <article class="info-card">
            <h3>Bedrock támogatott?</h3>
            <p>
              Ha szeretnéd, később be tudunk vezetni Bedrock supportot – a hírek közt
              jelezzük, ha bekerül.
            </p>
          </article>
          <article class="info-card">
            <h3>Kell webes fiók?</h3>
            <p>
              Játszani nem kötelező, de ha statokat, jutalmakat és extra funkciókat
              akarsz, akkor érdemes létrehozni.
            </p>
          </article>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <p>© <span id="year"></span> ETHERNIA • Nem hivatalos Minecraft szerver, nem kapcsolódik a Mojanghoz.</p>
    </footer>
  </div>

  <script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>
