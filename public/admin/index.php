<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$currentUserId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';
$currentRole     = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'admin';

require_once __DIR__ . '/../database.php';

$newsStats   = ['total' => 0, 'visible' => 0];
$adminStats  = ['total' => 0, 'active' => 0];
$selfInfo    = ['created_at' => null, 'last_login' => null, 'role' => $currentRole];
$logSummary  = ['total' => 0, 'last_action' => null, 'last_by' => null, 'last_at' => null];
$failedLogin = ['last_at' => null, 'ip' => null];

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END) AS visible
        FROM news
    ");
    $row = $stmt->fetch();
    if ($row) {
        $newsStats = [
            'total'   => (int)$row['total'],
            'visible' => (int)$row['visible']
        ];
    }

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active
        FROM admin_users
    ");
    $row = $stmt->fetch();
    if ($row) {
        $adminStats = [
            'total'  => (int)$row['total'],
            'active' => (int)$row['active']
        ];
    }

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

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM admin_logs");
    $row = $stmt->fetch();
    if ($row) {
        $logSummary['total'] = (int)$row['total'];
    }

    $stmt = $pdo->query("
        SELECT created_at,
               COALESCE(username, 'Ismeretlen') AS username,
               action
        FROM admin_logs
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $row = $stmt->fetch();
    if ($row) {
        $logSummary['last_at']     = $row['created_at'];
        $logSummary['last_by']     = $row['username'];
        $logSummary['last_action'] = $row['action'];
    }

    $stmt = $pdo->prepare("
        SELECT created_at, ip_address
        FROM admin_logs
        WHERE action LIKE :fail
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([':fail' => 'Sikertelen admin bejelentkezés%']);
    $row = $stmt->fetch();
    if ($row) {
        $failedLogin['last_at'] = $row['created_at'];
        $failedLogin['ip']      = $row['ip_address'];
    }
} catch (Exception $e) {
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$currentNav = 'dashboard';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin – Főoldal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/dashboard.css?v=<?= time(); ?>">
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300;400;500&display=swap">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="admin-main admin-main-dashboard">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Admin áttekintés</h1>
          <p class="admin-subtitle">
            Üdv, <?= h($currentUsername); ?>! Itt látod gyorsan, mi történik az ETHERNIA admin felületén.
          </p>
        </div>
      </header>

      <section class="admin-section">
        <div class="dashboard-grid">
          <article class="dash-card">
            <div class="dash-card-header">
              <h2>Hírek</h2>
              <span class="dash-pill">Főoldal slider</span>
            </div>
            <p class="dash-number">
              <?= (int)$newsStats['total']; ?>
              <span class="dash-number-sub">összes hír</span>
            </p>
            <p class="dash-muted">
              Látható a nyitó oldalon:
              <strong><?= (int)$newsStats['visible']; ?></strong>
            </p>
            <a href="/admin/news.php" class="dash-link">Ugrás a hírek kezeléséhez →</a>
          </article>

          <article class="dash-card">
            <div class="dash-card-header">
              <h2>Adminok</h2>
              <span class="dash-pill dash-pill-gold">Jogosultság</span>
            </div>
            <p class="dash-number">
              <?= (int)$adminStats['active']; ?>
              <span class="dash-number-sub">aktív admin</span>
            </p>
            <p class="dash-muted">
              Összes admin fiók: <strong><?= (int)$adminStats['total']; ?></strong>
            </p>
            <a href="/admin/admins.php" class="dash-link">Adminok kezelése →</a>
          </article>

          <article class="dash-card">
            <div class="dash-card-header">
              <h2>Te fiókod</h2>
              <span class="dash-pill dash-pill-role">
                <?= strtoupper(h($selfInfo['role'] ?? $currentRole)); ?>
              </span>
            </div>
            <p class="dash-muted">
              Létrehozva:<br>
              <strong>
                <?= !empty($selfInfo['created_at']) ? h($selfInfo['created_at']) : 'ismeretlen'; ?>
              </strong>
            </p>
            <p class="dash-muted">
              Utolsó belépés:<br>
              <strong>
                <?= !empty($selfInfo['last_login']) ? h($selfInfo['last_login']) : 'még nincs adat'; ?>
              </strong>
            </p>
            <p class="dash-tip">
              Tipp: a jelszavadat egy másik tulaj / owner tudja módosítani az Adminok menüben.
            </p>
          </article>
        </div>
      </section>

      <section class="admin-section dashboard-bottom">
        <div class="dashboard-bottom-grid">
          <article class="dash-card dash-card-small">
            <div class="dash-card-header">
              <h2>Rendszer összefoglaló</h2>
              <span class="dash-pill dash-pill-soft">Napló</span>
            </div>

            <div class="dash-stats">
              <div class="dash-stat">
                <div class="dash-stat-label">Összes naplózott esemény</div>
                <div class="dash-stat-value">
                  <?= (int)$logSummary['total']; ?>
                </div>
              </div>

              <div class="dash-stat">
                <div class="dash-stat-label">Utolsó esemény</div>
                <?php if ($logSummary['last_at']): ?>
                  <div class="dash-stat-desc">
                    <strong><?= h($logSummary['last_by']); ?></strong> –
                    <?= h($logSummary['last_action']); ?>
                  </div>
                  <div class="dash-stat-meta">
                    <?= h($logSummary['last_at']); ?>
                  </div>
                <?php else: ?>
                  <div class="dash-stat-desc dash-muted">
                    Még nincs naplózott esemény.
                  </div>
                <?php endif; ?>
              </div>

              <div class="dash-stat">
                <div class="dash-stat-label">Utolsó sikertelen belépés</div>
                <?php if ($failedLogin['last_at']): ?>
                  <div class="dash-stat-desc">
                    IP: <strong><?= h($failedLogin['ip']); ?></strong>
                  </div>
                  <div class="dash-stat-meta">
                    <?= h($failedLogin['last_at']); ?>
                  </div>
                <?php else: ?>
                  <div class="dash-stat-desc dash-muted">
                    Nincs rögzített sikertelen belépés.
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <a href="/admin/logs.php" class="dash-link secondary">Teljes napló megnyitása →</a>
          </article>

          <article class="dash-card dash-card-small">
            <div class="dash-card-header">
              <h2>Gyors műveletek</h2>
              <span class="dash-pill dash-pill-soft">Rövidítések</span>
            </div>

            <div class="quick-actions">
              <a href="/admin/news.php" class="quick-action-link">
                <span class="material-symbols-rounded quick-action-icon">post_add</span>
                <span>Új hír létrehozása</span>
              </a>
              <a href="/admin/admins.php" class="quick-action-link">
                <span class="material-symbols-rounded quick-action-icon">person_add</span>
                <span>Új admin hozzáadása</span>
              </a>
              <a href="/admin/modlog.php" class="quick-action-link">
                <span class="material-symbols-rounded quick-action-icon">gavel</span>
                <span>Discord büntetések megnyitása</span>
              </a>
              <a href="/admin/players.php" class="quick-action-link">
                <span class="material-symbols-rounded quick-action-icon">groups</span>
                <span>Játékosok kezelése</span>
              </a>
            </div>

            <p class="dash-muted dash-note">
              Ezek a linkek segítenek a leggyakoribb admin feladatok gyors elérésében.
            </p>
          </article>
        </div>
      </section>
    </div>
  </div>
</body>
</html>
