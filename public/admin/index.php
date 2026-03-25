<?php
require_once __DIR__ . '/includes/core.php';

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status != 'closed'")->fetchColumn();
$totalNews = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
$totalPlayers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); // Placeholder

// --- GRAFIKON ADATOK (Elmúlt 7 nap admin aktivitása) ---
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('m.d', strtotime("-$i days")); // Pl: 03.25
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_logs WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $chartData[] = (int)$stmt->fetchColumn();
}

// --- LEGUTÓBBI 5 ESEMÉNY ---
$recentLogs = $pdo->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Vezérlőpult | ETHERNIA Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/dashboard.css?v=<?= time(); ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php $current_page = 'dashboard'; require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header glass">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon">dashboard</span>
                <div>
                    <h1>Vezérlőpult</h1>
                    <p class="subtitle" id="greeting-subtitle">Üdvözlünk az Ethernia rendszerében!</p>
                </div>
            </div>
            <div class="admin-user-info">
                Bejelentkezve mint: <strong class="text-red"><?= h($admin_name) ?></strong>
            </div>
        </header>

        <div class="admin-content">
            
            <div class="dashboard-stats-grid">
                <div class="stat-card glass">
                    <div class="stat-icon-wrapper" style="color: #3b82f6; background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);"><span class="material-symbols-rounded">group</span></div>
                    <div class="stat-info"><h3>Regisztrált Felhasználók</h3><div class="stat-value"><?= number_format($totalUsers, 0, '', ' ') ?></div></div>
                </div>
                <div class="stat-card glass">
                    <div class="stat-icon-wrapper" style="color: #22c55e; background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);"><span class="material-symbols-rounded">sports_esports</span></div>
                    <div class="stat-info"><h3>Játékos Karakterek</h3><div class="stat-value"><?= number_format($totalPlayers, 0, '', ' ') ?></div></div>
                </div>
                <div class="stat-card glass">
                    <div class="stat-icon-wrapper" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);"><span class="material-symbols-rounded">support_agent</span></div>
                    <div class="stat-info"><h3>Nyitott Hibajegyek</h3><div class="stat-value"><?= $openTickets ?></div></div>
                </div>
                <div class="stat-card glass">
                    <div class="stat-icon-wrapper" style="color: #a855f7; background: rgba(168, 85, 247, 0.1); border-color: rgba(168, 85, 247, 0.3);"><span class="material-symbols-rounded">newspaper</span></div>
                    <div class="stat-info"><h3>Közzétett Hírek</h3><div class="stat-value"><?= $totalNews ?></div></div>
                </div>
            </div>

            <div class="dashboard-panels">
                
                <div class="panel glass" style="flex: 2;">
                    <div class="panel-header">
                        <h3><span class="material-symbols-rounded">monitoring</span> Rendszer Aktivitás (Elmúlt 7 nap)</h3>
                    </div>
                    <div class="panel-body" style="position: relative; height: 300px; padding: 1rem;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>

                <div class="panel glass" style="flex: 1;">
                    <div class="panel-header">
                        <h3><span class="material-symbols-rounded">history</span> Legutóbbi Események</h3>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <?php if(empty($recentLogs)): ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem;">Még nincs rögzített esemény.</p>
                        <?php else: ?>
                            <div class="recent-logs">
                                <?php foreach($recentLogs as $l): ?>
                                    <div class="log-item">
                                        <div class="log-icon"><span class="material-symbols-rounded">bolt</span></div>
                                        <div class="log-text">
                                            <strong><?= h($l['username']) ?></strong>: <?= h($l['action']) ?>
                                            <span class="log-time"><?= date('m.d H:i', strtotime($l['created_at'])) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script>
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartData = <?= json_encode($chartData) ?>;
</script>
<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
<script src="/admin/assets/js/dashboard.js?v=<?= time(); ?>"></script>
</body>
</html>