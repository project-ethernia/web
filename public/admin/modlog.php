<?php
session_start();

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../database.php';

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// SZŰRŐK
$search      = isset($_GET['q']) ? trim($_GET['q']) : '';
$actionType  = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$status      = isset($_GET['status']) ? trim($_GET['status']) : '';

$where   = [];
$params  = [];

// keresés (user_tag, moderator_tag, reason, user_id, moderator_id)
if ($search !== '') {
    $where[] = '(user_tag LIKE :q OR moderator_tag LIKE :q OR reason LIKE :q OR user_id LIKE :qnum OR moderator_id LIKE :qnum)';
    $params[':q']    = '%' . $search . '%';
    $params[':qnum'] = '%' . $search . '%';
}

// akció típusa
if ($actionType !== '' && $actionType !== 'all') {
    $where[] = 'action_type = :action_type';
    $params[':action_type'] = $actionType;
}

// státusz (aktív / visszavont / mind)
if ($status === 'active') {
    $where[] = 'revoked = 0';
} elseif ($status === 'revoked') {
    $where[] = 'revoked = 1';
}

$sql = "SELECT id, guild_id, user_id, user_tag, moderator_id, moderator_tag,
               action_type, reason, duration_seconds, created_at, revoked, related_action_id
        FROM moderation_actions";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 200';

$pdo = get_pdo();
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount = count($rows);

function formatActionLabel(string $type): string {
    $u = strtoupper($type);
    switch ($u) {
        case 'BAN':
        case 'UNBAN':
        case 'MUTE':
        case 'UNMUTE':
        case 'KICK':
        case 'WARN':
        case 'UNWARN':
            return $u;
        default:
            return $u ?: 'OTHER';
    }
}

function formatDuration(?int $seconds): string {
    if ($seconds === null || $seconds <= 0) {
        return 'Állandó';
    }
    $minutes = intdiv($seconds, 60);
    if ($minutes < 60) {
        return $minutes . ' perc';
    }
    $hours = intdiv($minutes, 60);
    if ($hours < 24) {
        return $hours . ' óra';
    }
    $days = intdiv($hours, 24);
    return $days . ' nap';
}

function formatDateTime(string $dt): string {
    $t = strtotime($dt);
    if ($t === false) {
        return h($dt);
    }
    return date('Y.m.d H:i', $t);
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA Admin – Moderációs napló</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/admin/assets/css/modlog.css?v=<?= time(); ?>">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Moderációs napló</h1>
                <p class="admin-page-subtitle">
                    Discord moderációs műveletek (ban, mute, kick, warn, stb.) naplózása.
                </p>
            </div>
            <div>
                <span class="pill-counter">
                    Összesen <?= (int)$totalCount ?> bejegyzés
                </span>
            </div>
        </header>

        <section class="card">
            <div class="card-header card-header-flex">
                <div>Szűrés</div>
            </div>
            <div class="card-body">
                <form method="GET" action="/admin/modlog.php" class="modlog-filters">
                    <div class="filter-group">
                        <label for="filter-q">Keresés</label>
                        <input
                            type="text"
                            id="filter-q"
                            name="q"
                            placeholder="Felhasználó, moderátor, indok, ID…"
                            value="<?= h($search); ?>"
                        >
                    </div>

                    <div class="filter-group">
                        <label for="filter-action-type">Akció típusa</label>
                        <select id="filter-action-type" name="action_type">
                            <option value="all" <?= ($actionType === '' || $actionType === 'all') ? 'selected' : ''; ?>>Mind</option>
                            <option value="ban"    <?= $actionType === 'ban'    ? 'selected' : ''; ?>>Ban</option>
                            <option value="unban"  <?= $actionType === 'unban'  ? 'selected' : ''; ?>>Unban</option>
                            <option value="mute"   <?= $actionType === 'mute'   ? 'selected' : ''; ?>>Mute</option>
                            <option value="unmute" <?= $actionType === 'unmute' ? 'selected' : ''; ?>>Unmute</option>
                            <option value="kick"   <?= $actionType === 'kick'   ? 'selected' : ''; ?>>Kick</option>
                            <option value="warn"   <?= $actionType === 'warn'   ? 'selected' : ''; ?>>Warn</option>
                            <option value="unwarn" <?= $actionType === 'unwarn' ? 'selected' : ''; ?>>Unwarn</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-status">Státusz</label>
                        <select id="filter-status" name="status">
                            <option value="all"     <?= ($status === '' || $status === 'all') ? 'selected' : ''; ?>>Mind</option>
                            <option value="active"  <?= $status === 'active'  ? 'selected' : ''; ?>>Aktív / lezáratlan</option>
                            <option value="revoked" <?= $status === 'revoked' ? 'selected' : ''; ?>>Visszavont / feloldott</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Szűrés</button>
                        <a href="/admin/modlog.php" class="btn btn-ghost">Szűrés törlése</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table modlog-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Időpont</th>
                            <th>Felhasználó</th>
                            <th>Moderátor</th>
                            <th>Akció</th>
                            <th>Időtartam</th>
                            <th>Indok</th>
                            <th>Státusz</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" class="table-empty">
                                    Nincs a szűrésnek megfelelő bejegyzés.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $label = formatActionLabel($row['action_type'] ?? '');
                                $durationText = formatDuration(
                                    $row['duration_seconds'] !== null ? (int)$row['duration_seconds'] : null
                                );
                                $isRevoked = !empty($row['revoked']);
                                ?>
                                <tr>
                                    <td class="cell-id">#<?= (int)$row['id']; ?></td>
                                    <td class="cell-datetime"><?= h(formatDateTime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="cell-main"><?= h($row['user_tag'] ?: 'Ismeretlen'); ?></div>
                                        <div class="cell-sub">ID: <?= h($row['user_id'] ?: '‑'); ?></div>
                                    </td>
                                    <td>
                                        <div class="cell-main"><?= h($row['moderator_tag'] ?: 'Ismeretlen'); ?></div>
                                        <div class="cell-sub">ID: <?= h($row['moderator_id'] ?: '‑'); ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = 'badge-log-other';
                                        switch (strtoupper($row['action_type'] ?? '')) {
                                            case 'BAN':    $badgeClass = 'badge-log-ban'; break;
                                            case 'UNBAN':  $badgeClass = 'badge-log-revoke'; break;
                                            case 'MUTE':   $badgeClass = 'badge-log-mute'; break;
                                            case 'UNMUTE': $badgeClass = 'badge-log-revoke'; break;
                                            case 'KICK':   $badgeClass = 'badge-log-kick'; break;
                                            case 'WARN':   $badgeClass = 'badge-log-warn'; break;
                                            case 'UNWARN': $badgeClass = 'badge-log-revoke'; break;
                                        }
                                        ?>
                                        <span class="badge-log <?= $badgeClass; ?>">
                                            <?= h($label); ?>
                                        </span>
                                    </td>
                                    <td><?= h($durationText); ?></td>
                                    <td class="cell-reason">
                                        <?= h($row['reason'] ?: '—'); ?>
                                    </td>
                                    <td>
                                        <?php if ($isRevoked): ?>
                                            <span class="badge-status badge-status-revoked">Feloldva / visszavonva</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-status-active">Aktív / lezárt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
