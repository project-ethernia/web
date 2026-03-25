<?php
$current_page = 'logs';
require_once __DIR__ . '/includes/core.php';

// Jogosultság ellenőrzés
if (!hasPermission($admin_role, 'all')) {
    header('Location: /admin/index.php?error=no_permission');
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM admin_logs";
$params = [];

if ($q !== '') {
    $sql .= " WHERE username LIKE ? OR action LIKE ?";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

$sql .= " ORDER BY created_at DESC LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$totalLogs = count($logs);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Műveletnapló | ETHERNIA Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/admins.css?v=<?= time(); ?>"> </head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header glass">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon">manage_search</span>
                <div>
                    <h1>Műveletnapló (Audit Log)</h1>
                    <p class="subtitle">A rendszerben történt összes adminisztrátori tevékenység</p>
                </div>
            </div>
            <div class="admin-user-info">
                Bejelentkezve mint: <strong class="text-red"><?= h($admin_name) ?></strong>
            </div>
        </header>

        <div class="admin-content">
            
            <div class="admin-panel glass" style="padding: 1.5rem; margin-bottom: 1rem;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <span class="material-symbols-rounded" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">search</span>
                        <input type="text" name="q" class="admin-input" placeholder="Keresés admin név vagy esemény alapján..." value="<?= h($q) ?>" style="padding-left: 3rem;">
                    </div>
                    <button type="submit" class="btn-action btn-claim" style="margin: 0; padding: 1rem 2rem;">Keresés</button>
                    <?php if($q): ?>
                        <a href="/admin/logs.php" class="btn-action btn-danger" style="margin: 0;">Törlés</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="admin-panel glass">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Adminisztrátor</th>
                            <th>Esemény (Akció)</th>
                            <th>IP Cím</th>
                            <th>Időpont</th>
                            <th>Technikai infó</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs találat a naplóban.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover-row">
                                    <td class="td-id">#<?= str_pad($log['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div class="player-cell">
                                            <img src="https://minotar.net/helm/<?= h($log['username'] ?: 'Steve') ?>/24.png" class="player-head">
                                            <strong><?= h($log['username'] ?: 'Rendszer') ?></strong>
                                        </div>
                                    </td>
                                    <td style="color: #cbd5e1;"><?= h($log['action']) ?></td>
                                    <td class="td-muted"><?= h($log['ip_address']) ?></td>
                                    <td class="td-muted"><?= date('Y.m.d H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted); cursor: help;" title="<?= h($log['user_agent']) ?>">
                                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">devices</span> Info
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </main>
</div>

<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
</body>
</html>