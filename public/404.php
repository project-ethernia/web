<?php
declare(strict_types=1);

// 404.php - dinamikus 404 oldal
// Minden, amit megjelenítünk, escape-elve lesz.
// Beállítások:
$logEnabled = false;                   // Naplózás be/ki
$logDir = __DIR__ . '/../logs';       // Javasolt webrooton kívüli naplókönyvtár
$logFile = $logDir . '/404.log';

// HTTP 404 státusz
http_response_code(404);

// Kért útvonal (biztonságosan kezelve)
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$escapedUri = htmlspecialchars($uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Egyszerű naplózás (ha engedélyezve)
if ($logEnabled) {
    // Győződj meg róla, hogy a $logDir létezik és írható (jobb, ha webrooton kívül van)
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }
    $now = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ua = str_replace(["\r", "\n"], '', $ua); // ne törjük a log formátumot új sorral
    $line = sprintf("[%s] %s \"%s\" \"%s\"\n", $now, $ip, $uri, $ua);
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// Egyszerű, biztonságos HTML megjelenítés
?>
<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <title>404 – Az oldal nem található</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial;line-height:1.4;background:#0f1720;color:#e6edf3;margin:0;padding:0}
    .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
    .card{max-width:820px;background:rgba(255,255,255,0.02);padding:2rem;border-radius:8px;box-shadow:0 8px 24px rgba(2,6,23,0.6)}
    h1{margin:0 0 0.5rem;font-size:1.75rem}
    p{margin:.4rem 0;color:#cbd5e1}
    .small{font-size:.9rem;color:#94a3b8}
    a{color:#7cc0ff;text-decoration:none}
    a:hover{text-decoration:underline}
    .meta{margin-top:1rem;padding-top:1rem;border-top:1px dashed rgba(255,255,255,0.03);font-size:.9rem;color:#9fb4d9}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card" role="main" aria-labelledby="title">
      <h1 id="title">404 – Ez az oldal nem található</h1>
      <p>Sajnálom, az <strong><?php echo $escapedUri; ?></strong> cím nem elérhető.</p>
      <p class="small">Lehet, hogy elgépelés történt, vagy az oldal ideiglenesen megszűnt.</p>

      <p><a href="/" title="Vissza a főoldalra">Vissza a főoldalra</a> · <a href="/main" title="Fő tartalom">Fő tartalom</a></p>

      <div class="meta" aria-hidden="true">
        <div>Ha úgy gondolod, ez hibás link, értesítsd az adminokat.</div>
        <div style="margin-top:.5rem;color:#7aa0d6">Hiba kód: 404</div>
      </div>
    </div>
  </div>
</body>
</html>