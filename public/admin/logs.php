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

/* --- DB --- */
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = new PDO(
        $DB_DSN,
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
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
  <title>ETHERNIA Admin - Napló</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- reuse admin design -->
  <link rel="stylesheet" href="/admin/admins.css?v=1">
  <style>
    .log-action {
      font-size: 0.85rem;
    }
    .log-pill {
      display: inline-flex;
      align-items: center;
      padding: 0.05rem 0.4rem;
      border-radius: 999px;
      font-size: 0.7rem;
      margin-right: 0.3rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      opacity: 0.9;
    }
    .log-pill-login { background: rgba(34, 197, 94, 0.18); color: #bbf7d0; }
    .log-pill-logout { background: rgba(248, 113, 113, 0.2); color: #fecaca; }
    .log-pill-news { background: rgba(59, 130, 246, 0.2); color: #bfdbfe; }
    .log-pill-admin { background: rgba(168, 85, 247, 0.22); color: #e9d5ff; }
    .log-pill-other { background: rgba(148, 163, 184, 0.25); color: #e5e7eb; }

    .cell-ua {
      max-width: 260px;
      font-size: 0.7rem;
      opacity: 0.7;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .search-bar {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      margin-top: 0.75rem;
    }

    .search-bar input[type="text"] {
      flex: 1;
      padding: 0.45rem 0.7rem;
      border-radius: 999px;
      border: 1px solid rgba(31, 41, 55, 0.9);
      background: rgba(15, 23, 42, 0.95);
      color: #e5e7eb;
      font-size: 0.85rem;
    }

    .search-bar button {
      padding: 0.4rem 0.9rem;
      border-radius: 999px;
      border: none;
      font-size: 0.8rem;
      cursor: pointer;
      background: rgba(55, 65, 81, 1);
      color: #e5e7eb;
    }

    .search-bar button:hover {
      background: rgba(75, 85, 99, 1);
    }

    .admin-table tbody tr:hover {
      background: rgba(15, 23, 42, 0.9);
    }
  </style>
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
