<?php
session_start();

/* --- HIBÁK --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- jogosultság ellenőrzés --- */
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

/* --- KÖZPONTI DB KAPCSOLAT BEHÚZÁSA --- */
/* database.php a public rootban van, ezért egy szinttel feljebb lépünk */
require_once __DIR__ . '/../database.php'; // itt jön létre a $pdo

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* --- Szűrés: q = kereső (admin név / akció) --- */
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

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Műveletnapló</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/css/logs.css?v=<?= time(); ?>">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php
      $activePage = 'logs';
      require __DIR__ . '/_sidebar.php';
    ?>

    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Admin műveletnapló</h1>
          <p class="admin-subtitle">
            Bejelentkezések, kijelentkezések, hír- és admin módosítások, IP címekkel és böngészőinfóval.
          </p>

          <form class="search-bar" method="get" action="/admin/logs.php">
            <input
              type="text"
              name="q"
              placeholder="Keresés admin név vagy esemény alapján..."
              value="<?php echo h($q); ?>"
            >
            <button type="submit">Keresés</button>
          </form>
        </div>
      </header>

      <section class="admin-section">
        <?php if (empty($logs)): ?>
          <div class="admin-empty">
            <p>Még nincs naplózott esemény.</p>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table">
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
                    if (strpos($lower, 'bejelentkezés') !== false) {
                        $pillClass = 'log-pill-login';
                        $pillText  = 'LOGIN';
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
                    <td><?php echo (int)$log['id']; ?></td>
                    <td><?php echo h($log['username'] ?: 'Ismeretlen'); ?></td>
                    <td class="log-action">
                      <span class="log-pill <?php echo $pillClass; ?>">
                        <?php echo $pillText; ?>
                      </span>
                      <?php echo h($action); ?>
                    </td>
                    <td><?php echo h($log['ip_address']); ?></td>
                    <td class="cell-ua" title="<?php echo h($log['user_agent']); ?>">
                      <?php echo h($uaShort); ?>
                    </td>
                    <td><?php echo h($log['created_at']); ?></td>
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
