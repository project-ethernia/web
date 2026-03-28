<?php
$current_page = 'dashboard';
require_once __DIR__ . '/includes/core.php';

// --- VALÓS ADATOK (Amik már be vannak kötve) ---
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$openTicketsCount = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status != 'closed'")->fetchColumn();
$recentTickets = $pdo->query("SELECT t.id, t.subject, t.status, u.username FROM tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.status != 'closed' ORDER BY t.updated_at DESC LIMIT 4")->fetchAll();

// --- SZIMULÁLT ADATOK (Mock data a lenyűgöző dizájnhoz - később bekötheted!) ---
$onlinePlayers = rand(45, 120); // Ezt majd Pterodactyl API-ból vagy RCON-ból húzhatod
$serverStats = [
    'tps' => 19.8,
    'ram_used' => 6.4,
    'ram_total' => 16.0,
    'cpu' => rand(15, 45)
];

// Napi belépési grafikon adatai (Szimulált)
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $chartLabels[] = date('m.d', strtotime("-$i days"));
    $chartData[] = rand(150, 400); // Napi egyedi belépők
}

// Gazdasági Anomáliák (Szimulált)
$economyAlerts = [
    ['player' => 'GazdagPistike', 'issue' => '+50,000 Coin 1 perc alatt', 'level' => 'critical'],
    ['player' => 'KereskedoGyuri', 'issue' => 'Gyanús item duplikáció (Gyémánt)', 'level' => 'high'],
    ['player' => 'TrollBéla', 'issue' => 'Túl gyors piac (Market) tranzakciók', 'level' => 'warning']
];

// Legutóbbi kitiltások (Szimulált)
$recentBans = [
    ['player' => 'X_Hacker_X', 'reason' => 'Kliens módosítás (Fly)', 'admin' => 'CONSOLE', 'time' => '10 perce'],
    ['player' => 'ToxicPeti', 'reason' => 'Súlyos káromkodás', 'admin' => 'AdminX', 'time' => '1 órája'],
    ['player' => 'Spammer01', 'reason' => 'Chat flood', 'admin' => 'AdminY', 'time' => '3 órája']
];

$page_title = 'Vezérlőpult | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/dashboard.css'];
$extra_scripts_head = ['https://cdn.jsdelivr.net/npm/chart.js'];
$topbar_icon = 'dashboard';
$topbar_title = 'Vezérlőpult';
$topbar_subtitle = 'Rendszer áttekintése és élő statisztikák';
$extra_js = ['/admin/assets/js/dashboard.js'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="server-hardware-bar glass">
    <div class="hw-stat">
        <div class="hw-icon online"><span class="material-symbols-rounded">router</span></div>
        <div class="hw-info">
            <span class="hw-label">Szerver Állapot</span>
            <span class="hw-value text-success">ONLINE</span>
        </div>
    </div>
    <div class="hw-stat">
        <div class="hw-icon"><span class="material-symbols-rounded">speed</span></div>
        <div class="hw-info">
            <span class="hw-label">TPS (Teljesítmény)</span>
            <span class="hw-value" id="live-tps"><?= $serverStats['tps'] ?> <small>/ 20.0</small></span>
        </div>
    </div>
    <div class="hw-stat">
        <div class="hw-icon"><span class="material-symbols-rounded">memory</span></div>
        <div class="hw-info">
            <span class="hw-label">RAM Használat</span>
            <span class="hw-value" id="live-ram"><?= $serverStats['ram_used'] ?> <small>GB / <?= $serverStats['ram_total'] ?> GB</small></span>
        </div>
        <div class="hw-bar-bg"><div class="hw-bar-fill" style="width: <?= ($serverStats['ram_used'] / $serverStats['ram_total']) * 100 ?>%; background: var(--admin-info);"></div></div>
    </div>
    <div class="hw-stat">
        <div class="hw-icon"><span class="material-symbols-rounded">dns</span></div>
        <div class="hw-info">
            <span class="hw-label">CPU Használat</span>
            <span class="hw-value" id="live-cpu"><?= $serverStats['cpu'] ?>%</span>
        </div>
        <div class="hw-bar-bg"><div class="hw-bar-fill" id="live-cpu-bar" style="width: <?= $serverStats['cpu'] ?>%; background: var(--admin-warning);"></div></div>
    </div>
</div>

<div class="dashboard-stats-grid">
    <div class="stat-card glass">
        <div class="stat-icon-wrapper" style="color: var(--admin-success); background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);"><span class="material-symbols-rounded">person_play</span></div>
        <div class="stat-info"><h3>Jelenleg Online</h3><div class="stat-value" id="live-players"><?= $onlinePlayers ?></div></div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon-wrapper" style="color: var(--admin-info); background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3);"><span class="material-symbols-rounded">group</span></div>
        <div class="stat-info"><h3>Összes Regisztrált</h3><div class="stat-value"><?= number_format($totalUsers, 0, '', ' ') ?></div></div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon-wrapper" style="color: var(--admin-warning); background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3);"><span class="material-symbols-rounded">support_agent</span></div>
        <div class="stat-info"><h3>Nyitott Ticketek</h3><div class="stat-value"><?= $openTicketsCount ?></div></div>
    </div>
</div>

<div class="bento-grid">
    
    <div class="panel glass bento-graph">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">show_chart</span> Játékos Aktivitás (Elmúlt 7 nap belépései)</h3>
        </div>
        <div class="panel-body" style="position: relative; height: 300px; padding: 1rem;">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    <div class="panel glass bento-tickets">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">forum</span> Legutóbbi Ticketek</h3>
            <a href="/admin/tickets.php" class="btn-sm btn-open" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">Összes</a>
        </div>
        <div class="panel-body list-body">
            <?php if(empty($recentTickets)): ?>
                <div class="empty-state"><span class="material-symbols-rounded">done_all</span><p>Nincs nyitott ticket!</p></div>
            <?php else: ?>
                <?php foreach($recentTickets as $t): ?>
                    <div class="list-item">
                        <div class="item-icon"><img src="https://minotar.net/helm/<?= h($t['username']) ?>/32.png" alt="head" style="border-radius: 6px;"></div>
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
            <h3 style="color: #fca5a5;"><span class="material-symbols-rounded">troubleshoot</span> Gazdasági Riasztások</h3>
        </div>
        <div class="panel-body list-body">
            <?php foreach($economyAlerts as $alert): ?>
                <div class="list-item alert-<?= $alert['level'] ?>">
                    <div class="item-icon"><span class="material-symbols-rounded">warning</span></div>
                    <div class="item-content">
                        <strong style="color: #fff;"><?= h($alert['player']) ?></strong>
                        <span style="color: #cbd5e1;"><?= h($alert['issue']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel glass bento-bans">
        <div class="panel-header">
            <h3><span class="material-symbols-rounded">gavel</span> Legutóbbi Büntetések</h3>
            <a href="#" class="btn-sm btn-open" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">Banlista</a>
        </div>
        <div class="panel-body list-body">
            <?php foreach($recentBans as $ban): ?>
                <div class="list-item">
                    <div class="item-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--admin-red);"><span class="material-symbols-rounded">block</span></div>
                    <div class="item-content">
                        <strong><?= h($ban['player']) ?></strong>
                        <span style="color: var(--admin-red); font-weight: 600;"><?= h($ban['reason']) ?></span>
                    </div>
                    <div class="item-action" style="text-align: right; font-size: 0.75rem; color: var(--text-muted);">
                        <?= h($ban['admin']) ?><br><?= $ban['time'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartData = <?= json_encode($chartData) ?>;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>