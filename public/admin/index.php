<?php
session_start();

/* --- HIBÁK (fejlesztéshez), élesben kikapcsolhatod --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- jogosultság ellenőrzés --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$currentUserId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';
$currentRole     = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'admin';

/* --- KÖZPONTI DB KAPCSOLAT BEHÚZÁSA --- */
/* database.php a public rootban van, ezért egy szinttel feljebb lépünk */
require_once __DIR__ . '/../database.php'; // itt jön létre a $pdo

/* --- kis statok a dashboardra --- */

$newsStats  = ['total' => 0, 'visible' => 0];
$adminStats = ['total' => 0, 'active' => 0];
$selfInfo   = ['created_at' => null, 'last_login' => null, 'role' => $currentRole];
$recentLogs = [];

try {
    // $pdo a database.php-ből jön

    // Hírek stat
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END) AS visible
        FROM news
    ");
    $row = $stmt->fetch();
    if ($row) {
        $newsStats = $row;
    }

    // Admin stat
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active
        FROM admin_users
    ");
    $row = $stmt->fetch();
    if ($row) {
        $adminStats = $row;
    }

    // Saját fiók info
    if ($currentUserId > 0) {
        $stmt = $pdo->prepare("
            SELECT created_at, last_login, role
            FROM admin_users
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $currentUserId]);
        $row = $stmt->fetch();
        if ($row) {
            $selfInfo = $row;
        }
    }

    // Legutóbbi napló bejegyzések
    $stmt = $pdo->query("
        SELECT
            created_at,
            COALESCE(username, 'Ismeretlen') AS username,
            action,
            ip_address
        FROM admin_logs
        ORDER BY created_at DESC
        LIMIT 8
    ");
    $recentLogs = $stmt->fetchAll();

} catch (Exception $e) {
    // Ha valami elhasal, a dashboard akkor is betölt, csak statok nélkül
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Adminok kezelése</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/css/dashboard.css?v=<?= time(); ?>">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php
      $activePage = 'dashboard';
      require __DIR__ . '/_sidebar.php';
    ?>

    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Admin áttekintés</h1>
          <p class="admin-subtitle">
            Üdv, <?php echo h($currentUsername); ?>! Itt látod gyorsan, mi történik az ETHERNIA admin felületén.
          </p>
        </div>
      </header>

      <!-- FELSŐ KÁRTYÁK -->
      <section class="admin-section">
        <div class="dashboard-grid">
          <!-- Hírek kártya -->
          <article class="dash-card">
            <div class="dash-card-header">
              <h2>Hírek</h2>
              <span class="dash-pill">Főoldal slider</span>
            </div>
            <p class="dash-number">
              <?php echo (int)$newsStats['total']; ?>
              <span class="dash-number-sub">összes hír</span>
            </p>
            <p class="dash-muted">
              Látható a nyitó oldalon:
              <strong><?php echo (int)$newsStats['visible']; ?></strong>
            </p>
            <a href="/admin/news.php" class="dash-link">Ugrás a hírek kezeléséhez →</a>
          </article>

          <!-- Adminok kártya -->
          <article class="dash-card">
            <div class="dash-card-header">
              <h2>Adminok</h2>
              <span class="dash-pill dash-pill-gold">Jogosultság</span>
            </div>
            <p class="dash-number">
              <?php echo (int)$adminStats['active']; ?>
              <span class="dash-number-sub">aktív admin</span>
            </p>
            <p class="dash-muted">
              Összes admin fiók: <strong><?php echo (int)$adminStats['total']; ?></strong>
            </p>
            <a href="/admin/admins.php" class="dash-link">Adminok kezelése →</a>
          </article>

          <!-- Saját fiókod -->
          <article class="dash-card">
            <div class="dash-card-header">
              <h2>Te fiókod</h2>
              <span class="dash-pill dash-pill-role">
                <?php echo strtoupper(h($selfInfo['role'] ?? $currentRole)); ?>
              </span>
            </div>
            <p class="dash-muted">
              Létrehozva:<br>
              <strong>
                <?php echo !empty($selfInfo['created_at']) ? h($selfInfo['created_at']) : 'ismeretlen'; ?>
              </strong>
            </p>
            <p class="dash-muted">
              Utolsó belépés:<br>
              <strong>
                <?php echo !empty($selfInfo['last_login']) ? h($selfInfo['last_login']) : 'még nincs adat'; ?>
              </strong>
            </p>
            <p class="dash-tip">
              Tipp: a jelszavadat egy másik tulaj / owner tudja módosítani az Adminok menüben.
            </p>
          </article>
        </div>
      </section>

      <!-- LEGUTÓBBI NAPLÓ BEJEGYZÉSEK -->
      <section class="admin-section">
        <div class="section-header-row">
          <h2 class="section-title">Legutóbbi műveletek</h2>
          <a href="/admin/logs.php" class="dash-link secondary">Teljes napló megnyitása →</a>
        </div>

        <?php if (empty($recentLogs)): ?>
          <p class="dash-muted">
            Még nincs naplózott admin esemény, vagy az admin_logs tábla üres.
          </p>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table admin-log-table">
              <thead>
                <tr>
                  <th>Időpont</th>
                  <th>Admin</th>
                  <th>Esemény</th>
                  <th>IP</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentLogs as $log): ?>
                  <tr>
                    <td class="cell-date"><?php echo h($log['created_at']); ?></td>
                    <td class="cell-username"><?php echo h($log['username']); ?></td>
                    <td class="cell-action-text"><?php echo h($log['action']); ?></td>
                    <td class="cell-ip"><?php echo h($log['ip_address']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</body>
</html>
