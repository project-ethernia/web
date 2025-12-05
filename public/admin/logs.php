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
    echo 'Nincs jogosultságod a napló megtekintéséhez.';
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
$totalLogs = is_array($logs) ? count($logs) : 0;
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

    <?php
    $currentNav = 'logs';
    require __DIR__ . '/_sidebar.php';
    ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Admin műveletnapló</h1>
                <p class="admin-page-subtitle">
                    Bejelentkezések, kijelentkezések, hír- és admin módosítások, IP címekkel és böngészőinfóval.
                </p>
            </div>
            <div class="pill-counter">
                Összesen <?= (int)$totalLogs; ?> bejegyzés
            </div>
        </header>

        <section class="logs-section">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-flex">
                        <h2 class="logs-card-title">Szűrés</h2>
                    </div>
                </div>
                <div class="card-body">
                    <form class="modlog-filters logs-filters" method="get" action="/admin/logs.php">
                        <div class="filter-group filter-group-logs">
                            <label for="logs-search">Keresés admin név vagy esemény alapján</label>
                            <div class="logs-search-row">
                                <input
                                    type="text"
                                    id="logs-search"
                                    name="q"
                                    placeholder="Keresés admin név vagy esemény alapján..."
                                    value="<?= h($q); ?>"
                                >
                                <button type="submit" class="btn btn-primary">Keresés</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-flex">
                        <h2 class="logs-card-title">Napló bejegyzések</h2>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <p class="table-empty">Még nincs naplózott esemény.</p>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table logs-table">
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
                                    $actionText = $log['action'];
                                    $pillClass = 'log-pill-other';
                                    $pillText = 'EGYÉB';

                                    $lower = mb_strtolower($actionText, 'UTF-8');
                                    if (strpos($lower, 'sikeres admin bejelentkezés') !== false) {
                                        $pillClass = 'log-pill-login';
                                        $pillText = 'LOGIN';
                                    } elseif (strpos($lower, 'sikertelen admin bejelentkezés') !== false) {
                                        $pillClass = 'log-pill-fail';
                                        $pillText = 'FAIL';
                                    } elseif (strpos($lower, 'kijelentkezés') !== false) {
                                        $pillClass = 'log-pill-logout';
                                        $pillText = 'LOGOUT';
                                    } elseif (strpos($lower, 'hír') !== false) {
                                        $pillClass = 'log-pill-news';
                                        $pillText = 'NEWS';
                                    } elseif (strpos($lower, 'admin') !== false) {
                                        $pillClass = 'log-pill-admin';
                                        $pillText = 'ADMIN';
                                    }

                                    $uaFull = $log['user_agent'] ?? '';
                                    $uaShort = mb_substr($uaFull, 0, 70, 'UTF-8');
                                    if (mb_strlen($uaFull, 'UTF-8') > 70) {
                                        $uaShort .= '…';
                                    }
                                    ?>
                                    <tr>
                                        <td class="cell-id">#<?= (int)$log['id']; ?></td>
                                        <td class="cell-admin"><?= h($log['username'] ?: 'Ismeretlen'); ?></td>
                                        <td class="cell-action">
                                            <span class="log-pill <?= $pillClass; ?>"><?= $pillText; ?></span>
                                            <span class="cell-action-text"><?= h($actionText); ?></span>
                                        </td>
                                        <td class="cell-ip"><?= h($log['ip_address']); ?></td>
                                        <td class="cell-ua" title="<?= h($uaFull); ?>">
                                            <?= h($uaShort); ?>
                                        </td>
                                        <td class="cell-date"><?= h($log['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
