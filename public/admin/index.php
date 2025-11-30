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

$newsStats  = ['total' => 0, 'visible' => 0];
$adminStats = ['total' => 0, 'active' => 0];
$selfInfo   = ['created_at' => null, 'last_login' => null, 'role' => $currentRole];
$logSummary = [
    'total'       => 0,
    'last'        => [],
    'last_failed' => []
];
$recentLogs = [];

try {
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN is_visible = 1 THEN 1 ELSE 0 END) AS visible
        FROM news
    ");
    $row = $stmt->fetch();
    if ($row) {
        $newsStats = $row;
    }

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active
        FROM admin_users
    ");
    $row = $stmt->fetch();
    if ($row) {
        $adminStats = $row;
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
        SELECT created_at, COALESCE(username, 'Ismeretlen') AS username, action, ip_address
        FROM admin_logs
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentLogs = $stmt->fetchAll();

    if (!empty($recentLogs)) {
        $logSummary['last'] = $recentLogs[0];

        foreach ($recentLogs as $logRow) {
            if (stripos($logRow['action'], 'Sikertelen admin bejelentkezés') !== false) {
                $logSummary['last_failed'] = $logRow;
                break;
            }
        }
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

    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1 class="admin-title">Admin áttekintés</h1>
                <p class="admin-subtitle">
                    Üdv, <?= h($currentUsername); ?>! Itt látod röviden, milyen állapotban van az ETHERNIA rendszer.
                </p>
            </div>
        </header>

        <section class="admin-section admin-section-top">
            <div class="dashboard-row">
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
                        Összes admin fiók:
                        <strong><?= (int)$adminStats['total']; ?></strong>
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
                    <div class="dash-meta-block">
                        <div class="dash-meta-item">
                            <span class="dash-meta-label">Létrehozva</span>
                            <span class="dash-meta-value">
                                <?= !empty($selfInfo['created_at']) ? h($selfInfo['created_at']) : 'ismeretlen'; ?>
                            </span>
                        </div>
                        <div class="dash-meta-item">
                            <span class="dash-meta-label">Utolsó belépés</span>
                            <span class="dash-meta-value">
                                <?= !empty($selfInfo['last_login']) ? h($selfInfo['last_login']) : 'még nincs adat'; ?>
                            </span>
                        </div>
                    </div>
                    <p class="dash-tip">
                        A jelszavadat egy másik tulaj / owner tudja módosítani az Adminok menüben.
                    </p>
                </article>
            </div>
        </section>

        <section class="admin-section admin-section-bottom">
            <div class="dashboard-row dashboard-row-bottom">
                <article class="dash-card dash-card-wide">
                    <div class="dash-card-header dash-card-header-row">
                        <h2>Rendszer összefoglaló</h2>
                        <span class="dash-pill">Napló</span>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-label">Összes naplózott esemény</span>
                            <span class="summary-value"><?= (int)$logSummary['total']; ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Utolsó esemény</span>
                            <?php if (!empty($logSummary['last'])): ?>
                                <span class="summary-value">
                                    <?= h($logSummary['last']['username']); ?> – <?= h($logSummary['last']['action']); ?>
                                </span>
                                <span class="summary-meta">
                                    <?= h($logSummary['last']['created_at']); ?> · IP: <?= h($logSummary['last']['ip_address']); ?>
                                </span>
                            <?php else: ?>
                                <span class="summary-value">Nincs még naplózott esemény.</span>
                            <?php endif; ?>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Utolsó sikertelen belépés</span>
                            <?php if (!empty($logSummary['last_failed'])): ?>
                                <span class="summary-value">
                                    IP: <?= h($logSummary['last_failed']['ip_address']); ?>
                                </span>
                                <span class="summary-meta">
                                    <?= h($logSummary['last_failed']['created_at']); ?>
                                </span>
                            <?php else: ?>
                                <span class="summary-value">Nem található sikertelen admin belépés.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($recentLogs)): ?>
                        <div class="timeline">
                            <div class="timeline-header">
                                <span class="timeline-title">Legutóbbi műveletek</span>
                                <a href="/admin/logs.php" class="dash-link secondary">Teljes napló megnyitása →</a>
                            </div>
                            <ul class="timeline-list">
                                <?php foreach ($recentLogs as $log): ?>
                                    <li class="timeline-item">
                                        <div class="timeline-main">
                                            <span class="timeline-user"><?= h($log['username']); ?></span>
                                            <span class="timeline-action"><?= h($log['action']); ?></span>
                                        </div>
                                        <div class="timeline-meta">
                                            <span><?= h($log['created_at']); ?></span>
                                            <span>IP: <?= h($log['ip_address']); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </section>
    </main>
</div>
</body>
</html>
