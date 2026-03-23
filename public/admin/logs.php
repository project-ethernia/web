<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!in_array($_SESSION['admin_role'], ['owner', 'admin'], true)) {
    http_response_code(403);
    echo 'Nincs jogosultságod a napló megtekintéséhez.';
    exit;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT id, admin_id, username, action, context, ip_address, user_agent, created_at FROM admin_logs";
$params = [];

if ($q !== '') {
    $sql .= " WHERE username LIKE :q OR action LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

$sql .= " ORDER BY created_at DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$totalLogs = count($logs);

$currentNav = 'logs';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA Admin - Műveletnapló</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@100..700&display=block">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/logs.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
    <header class="admin-header glass-panel">
        <div class="header-text">
            <h1 class="admin-title">Műveletnapló</h1>
            <p class="admin-subtitle">Rendszeresemények, módosítások és biztonsági naplózás.</p>
        </div>
        <div class="header-actions">
            <div class="stat-pill glass-panel">
                <span>Összesen: <strong><?= (int)$totalLogs; ?></strong> bejegyzés</span>
            </div>
        </div>
    </header>

    <section class="admin-content glass-panel" style="margin-bottom: 2rem; padding: 1.5rem;">
        <form class="logs-filters" method="get">
            <div class="search-box">
                <span class="material-symbols-rounded search-icon">search</span>
                <input type="text" name="q" placeholder="Keresés admin vagy esemény alapján..." value="<?= h($q); ?>">
                <button type="submit" class="btn btn-glow-red">Szűrés</button>
            </div>
        </form>
    </section>

    <section class="admin-content glass-panel">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Adminisztrátor</th>
                        <th>Esemény</th>
                        <th>IP Cím</th>
                        <th>Időpont</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center">Nincs találat a naplóban.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $actionText = $log['action'];
                            $lower = mb_strtolower($actionText, 'UTF-8');
                            $pillClass = 'badge-default';
                            $pillText = 'EGYÉB';

                            if (strpos($lower, 'sikeres') !== false) { $pillClass = 'badge-mod'; $pillText = 'LOGIN'; }
                            elseif (strpos($lower, 'sikertelen') !== false) { $pillClass = 'badge-danger'; $pillText = 'HIBA'; }
                            elseif (strpos($lower, 'hír') !== false) { $pillClass = 'badge-info'; $pillText = 'HÍR'; }
                            elseif (strpos($lower, 'admin') !== false) { $pillClass = 'badge-owner'; $pillText = 'ADMIN'; }
                            ?>
                            <tr class="log-row" data-context='<?= h($log['context'] ?: '{}'); ?>' data-ua="<?= h($log['user_agent'] ?: 'Nincs adat'); ?>">
                                <td class="cell-order">#<?= (int)$log['id']; ?></td>
                                <td class="cell-title">
                                    <div style="font-weight: 600; color: #fff;"><?= h($log['username'] ?: 'Ismeretlen'); ?></div>
                                </td>
                                <td>
                                    <div class="log-action-cell">
                                        <span class="badge <?= $pillClass; ?>"><?= $pillText; ?></span>
                                        <span class="action-desc"><?= h($actionText); ?></span>
                                    </div>
                                </td>
                                <td class="cell-date"><?= h($log['ip_address']); ?></td>
                                <td class="cell-date"><?= h($log['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>>
</div>

<div class="modal-overlay" id="log-modal">
    <div class="modal-container glass-panel">
        <button type="button" class="modal-close">&times;</button>
        <div class="modal-form">
            <h2 class="modal-title">Bejegyzés részletei</h2>
            <div class="log-details">
                <div class="detail-item">
                    <label>Böngésző (User Agent):</label>
                    <div id="log-ua" class="detail-value glass-panel"></div>
                </div>
                <div class="detail-item">
                    <label>Nyers Context (JSON):</label>
                    <pre id="log-context" class="detail-value glass-panel"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="log-close-btn">Bezárás</button>
            </div>
        </div>
    </div>
</div>

<script src="/admin/assets/js/logs.js?v=<?= time(); ?>"></script>
</body>
</html>