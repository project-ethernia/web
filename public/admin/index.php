<?php
$current_page = 'dashboard';
require_once __DIR__ . '/includes/core.php';

// --- VALÓS ADATBÁZIS LEKÉRDEZÉSEK ---
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$openTicketsCount = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status != 'closed'")->fetchColumn();

// Legutóbbi ticketek
$recentTickets = $pdo->query("SELECT t.id, t.subject, t.status, u.username FROM tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.status != 'closed' ORDER BY t.updated_at DESC LIMIT 4")->fetchAll();

// Legújabb regisztrált játékosok
$latestUsers = $pdo->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 4")->fetchAll();

// Legutóbbi admin események (admin_logs táblából)
$recentLogs = $pdo->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 4")->fetchAll();

// Napi aktivitás grafikon adatai (adsaadaddasasddassdaasdasddasValós admin_logs statisztika az elmúlt 7 napra)
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('m.d', strtotime("-$i days"));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_logs WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $chartData[] = (int)$stmt->fetchColumn();
}

$page_title = 'Vezérlőpult | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/dashboard.css'];
$extra_scripts_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
$topbar_icon = 'dashboard';
$topbar_title = 'Vezérlőpult';
$topbar_subtitle = 'Rendszer áttekintése és élő szerver statisztikák';
$extra_js = ['/admin/assets/js/dashboard.js'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="server-hardware-bar glass">
    <div class="hw-stat">
        <div class="hw-icon" id="status-icon"><span class="material-symbols-rounded">router</span></div>
        <div class="hw-info">
            <span class="hw-label">Szerver Állapot</span>
            <span class="hw-value" id="live-status"><span style="color: var(--text-muted); font-size: 0.9rem;">Betöltés...</span></span>
        </div>
    </div>
    <div class="hw-stat">
        <div class="hw-icon" style="color: #3b82f6;"><span class="material-symbols-rounded">network_ping</span></div>
        <div class="hw-info">
            <span class="hw-label">Késleltetés (Ping)</span>
            <span class="hw-value" id="live-ping">- <small>ms</small></span>
        </div>
    </div>
    <div class="hw-stat">
        <div class="hw-icon" style="color: #a855f7;"><span class="material-symbols-rounded">info</span></div>
        <div class="hw-info">
            <span class="hw-label">Szerver Verzió</span>
            <span class="hw-value" id="live-version" style="font-size: 1rem;">-</span>
        </div>
    </div>
    <div class="hw-stat">
        <div class="hw-icon" style="color: #f59e0b;"><span class="material-symbols-rounded">dns</span></div>
        <div class="hw-info">
            <span class="hw-label">IP Cím</span>
            <span class="hw-value" style="font-size: 1rem;">play.ethernia.hu</span>
        </div>
    </div>
</div>

<div class="dashboard-stats-grid">
    <div class="stat-card glass">
        <div class="stat-icon-wrapper" style="color: var(--admin-success); background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);"><span class="material-symbols-rounded">person_play</span></div>
        <div class="stat-info"><h3>Jelenleg Online</h3><div class="stat-value" id="live-players">- <small id="live-max-players" style="font-size: 0.8rem; color: var(--text-muted);">/ -</small></div></div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon-wrapper" style="color: var(--admin-info); background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);"><span class="material-symbols-rounded">group</span></div>
        <div class="stat-info"><h3>Regisztrált Játékosok</h3><div class="stat-value"><?= number_format($totalUsers, 0, '', ' ') ?></div></div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon-wrapper" style="color: var(--admin-warning); background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);"><span class="material-symbols-rounded">support_agent</span></div>
        <div class="stat-info"><h3>Nyitott Ticketek</h3><div class="stat-value"><?= $openTicketsCount ?></div></div>
    </div>
</div>

<div class="bento-grid">
    
    <div class="panel glass bento-graph">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">show_chart</span> Rendszer Aktivitás (Elmúlt 7 nap)</h3>
        </div>
        <div class="panel-body" style="position: relative; height: 300px; padding: 1rem;">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    <div class="panel glass bento-tickets">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">forum</span> Nyitott Ticketek</h3>
            <a href="/admin/tickets.php" class="btn-sm btn-open" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">Összes</a>
        </div>
        <div class="panel-body list-body">
            <?php if(empty($recentTickets)): ?>
                <div class="empty-state"><span class="material-symbols-rounded">done_all</span><p>Nincs nyitott ticket!</p></div>
            <?php else: ?>
                <?php foreach($recentTickets as $t): ?>
                    <div class="list-item">
                        <div class="item-icon"><img src="https://minotar.net/helm/<?= h($t['username'] ?? 'MHF_Steve') ?>/40.png" alt="head"></div>
                        <div class="item-content">
                            <strong><?= h($t['subject']) ?></strong>
                            <span><?= h($t['username']) ?></span>
                        </div>
                        <div class="item-action">
                            <span class="badge <?= $t['status'] === 'open' ? 'success' : 'warning' ?>"><?= strtoupper($t['status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel glass bento-economy">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">person_add</span> Legújabb Játékosok</h3>
            <a href="/admin/users.php" class="btn-sm btn-open" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">Összes</a>
        </div>
        <div class="panel-body list-body">
            <?php foreach($latestUsers as $user): ?>
                <div class="list-item">
                    <div class="item-icon"><img src="https://minotar.net/helm/<?= h($user['username']) ?>/40.png" alt="head"></div>
                    <div class="item-content">
                        <strong style="color: #fff;"><?= h($user['username']) ?></strong>
                        <span style="color: #cbd5e1;"><?= date('Y. m. d. H:i', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel glass bento-bans">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">history</span> Legutóbbi Események</h3>
            <a href="/admin/logs.php" class="btn-sm btn-open" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">Logok</a>
        </div>
        <div class="panel-body list-body">
            <?php if(empty($recentLogs)): ?>
                <div class="empty-state"><p>Nincs rögzített esemény.</p></div>
            <?php else: ?>
                <?php foreach($recentLogs as $log): ?>
                    <div class="list-item">
                        <div class="item-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--admin-info); border: 1px solid rgba(59, 130, 246, 0.3);"><span class="material-symbols-rounded">bolt</span></div>
                        <div class="item-content">
                            <strong><?= h($log['username'] ?? 'Rendszer') ?></strong>
                            <span style="color: #94a3b8; font-size: 0.8rem;"><?= h($log['action']) ?></span>
                        </div>
                        <div class="item-action" style="text-align: right; font-size: 0.75rem; color: var(--text-muted);">
                            <?= date('m.d H:i', strtotime($log['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartData = <?= json_encode($chartData) ?>;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>