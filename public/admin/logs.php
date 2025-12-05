<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$role = !empty($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';
if (!in_array($role, ['owner', 'admin'], true)) {
    http_response_code(403);
    echo "Nincs jogosultságod a napló megtekintéséhez.";
    exit;
}

$currentUsername = !empty($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

require_once __DIR__ . '/../database.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT id, admin_id, username, action, context, ip_address, user_agent, created_at
        FROM admin_logs";
$params = [];

if ($q !== '') {
    $sql .= " WHERE username LIKE :q OR action LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

$sql .= " ORDER BY created_at DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$currentNav = 'logs';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Műveletnapló</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/logs.css?v=<?= time(); ?>">
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300;400;500&display=swap">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php require __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main logs-page">
      <header class="admin-header logs-header">
        <div class="logs-header-main">
          <h1 class="admin-title">Admin műveletnapló</h1>
          <p class="admin-subtitle">
            Bejelentkezések, kijelentkezések, hír- és admin módosítások, IP címekkel és böngészőinfóval.
          </p>
        </div>

        <form class="logs-search" method="get" action="/admin/logs.php">
          <div class="logs-search-input-wrap">
            <span class="material-symbols-rounded logs-search-icon">search</span>
            <input
              type="text"
              name="q"
              placeholder="Keresés admin név vagy esemény alapján..."
              value="<?php echo h($q); ?>"
            >
          </div>
          <button type="submit" class="logs-search-btn">Keresés</button>
        </form>
      </header>

      <section class="admin-section logs-section">
        <?php if (empty($logs)): ?>
          <div class="admin-empty">
            <p>Még nincs naplózott esemény.</p>
          </div>
        <?php else: ?>
          <div class="logs-table-card">
            <div class="logs-table-header-row">
              <span class="logs-table-caption">
                Összesen <?php echo count($logs); ?> bejegyzés
              </span>
            </div>

            <div class="admin-table-wrapper logs-table-wrapper">
              <table class="admin-table logs-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Admin</th>
                    <th>Esemény</th>
                    <th>IP</th>
                    <th>Böngésző</th>
                    <th>Időpont</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                  <?php
                    $action = $log['action'];
                    $pillClass = 'log-pill-other';
                    $pillText  = 'EGYÉB';

                    $lower = mb_strtolower($action, 'UTF-8');
                    if (strpos($lower, 'sikeres admin bejelentkezés') !== false) {
                        $pillClass = 'log-pill-login';
                        $pillText  = 'LOGIN';
                    } elseif (strpos($lower, 'sikertelen admin bejelentkezés') !== false) {
                        $pillClass = 'log-pill-login-fail';
                        $pillText  = 'LOGIN FAIL';
                    } elseif (strpos($lower, 'kijelentkezés') !== false) {
                        $pillClass = 'log-pill-logout';
                        $pillText  = 'LOGOUT';
                    } elseif (strpos($lower, 'hír') !== false) {
                        $pillClass = 'log-pill-news';
                        $pillText  = 'NEWS';
                    } elseif (strpos($lower, 'admin') !== false) {
                        $pillClass = 'log-pill-admin';
                        $pillText  = 'ADMIN';
                    }

                    $uaShort = $log['user_agent'];
                  ?>
                  <tr>
                    <td class="cell-id"><?php echo (int)$log['id']; ?></td>
                    <td class="cell-username"><?php echo h($log['username'] ?: 'Ismeretlen'); ?></td>
                    <td class="cell-action">
                      <span class="log-pill <?php echo $pillClass; ?>">
                        <?php echo $pillText; ?>
                      </span>
                      <span class="cell-action-text">
                        <?php echo h($action); ?>
                      </span>
                    </td>
                    <td class="cell-ip"><?php echo h($log['ip_address']); ?></td>
                    <td class="cell-ua" title="<?php echo h($log['user_agent']); ?>">
                      <?php echo h($uaShort); ?>
                    </td>
                    <td class="cell-date"><?php echo h($log['created_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
