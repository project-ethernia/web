<?php
session_start();

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../database.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = get_pdo();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$type   = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(user_tag LIKE :q OR moderator_tag LIKE :q OR user_id LIKE :q OR moderator_id LIKE :q OR reason LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

if ($type !== '' && $type !== 'all') {
    $where[] = 'action_type = :t';
    $params[':t'] = $type;
}

if ($status === 'active') {
    $where[] = 'revoked = 0';
} elseif ($status === 'revoked') {
    $where[] = 'revoked = 1';
}

$sql = '
    SELECT
        id,
        guild_id,
        user_id,
        user_tag,
        moderator_id,
        moderator_tag,
        action_type,
        reason,
        duration_seconds,
        created_at,
        revoked,
        related_action_id
    FROM moderation_actions
';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY id DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalCount = count($rows);

function formatDuration(?int $seconds): string
{
    if ($seconds === null || $seconds <= 0) {
        return 'Állandó';
    }

    $m = intdiv($seconds, 60);
    $s = $seconds % 60;

    if ($m < 60) {
        return $m . ' perc' . ($s ? ' ' . $s . ' mp' : '');
    }

    $h = intdiv($m, 60);
    $m = $m % 60;

    if ($h < 24) {
        return $h . ' óra' . ($m ? ' ' . $m . ' perc' : '');
    }

    $d = intdiv($h, 24);
    $h = $h % 24;

    return $d . ' nap' . ($h ? ' ' . $h . ' óra' : '');
}

function formatActionLabel(string $action): string
{
    $upper = strtoupper($action);

    switch ($upper) {
        case 'BAN':
            return 'BAN';
        case 'UNBAN':
            return 'UNBAN';
        case 'MUTE':
            return 'MUTE';
        case 'UNMUTE':
            return 'UNMUTE';
        case 'KICK':
            return 'KICK';
        case 'WARN':
            return 'WARN';
        case 'UNWARN':
            return 'UNWARN';
        default:
            return $upper;
    }
}

function formatStatus(bool $revoked): array
{
    if ($revoked) {
        return ['Lejárva / visszavonva', 'status-pill status-pill-off'];
    }
    return ['Aktív / lezárt', 'status-pill status-pill-on'];
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
<div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-page-header">
            <div class="admin-page-header-main">
                <h1 class="admin-page-title">Moderációs napló</h1>
                <p class="admin-page-subtitle">
                    Discord moderációs műveletek (ban, mute, kick, warn, stb.) naplózása.
                </p>
            </div>
            <div class="admin-page-header-meta">
                <span class="badge-pill">
                    Összesen <?= (int)$totalCount; ?> bejegyzés
                </span>
            </div>
        </header>

        <section class="admin-card modlog-card">
            <form class="modlog-filters" method="get" action="/admin/modlog.php">
                <div class="modlog-filters-left">
                    <div class="form-group-inline">
                        <label for="q">Keresés</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            placeholder="Felhasználó, moderátor, indok…"
                            value="<?= h($search); ?>"
                        >
                    </div>

                    <div class="form-group-inline">
                        <label for="type">Akció típusa</label>
                        <select id="type" name="type">
                            <option value="all"<?= $type === 'all' ? ' selected' : ''; ?>>Mind</option>
                            <option value="WARN"<?= strtoupper($type) === 'WARN' ? ' selected' : ''; ?>>Warn</option>
                            <option value="UNWARN"<?= strtoupper($type) === 'UNWARN' ? ' selected' : ''; ?>>Unwarn</option>
                            <option value="MUTE"<?= strtoupper($type) === 'MUTE' ? ' selected' : ''; ?>>Mute</option>
                            <option value="UNMUTE"<?= strtoupper($type) === 'UNMUTE' ? ' selected' : ''; ?>>Unmute</option>
                            <option value="KICK"<?= strtoupper($type) === 'KICK' ? ' selected' : ''; ?>>Kick</option>
                            <option value="BAN"<?= strtoupper($type) === 'BAN' ? ' selected' : ''; ?>>Ban</option>
                            <option value="UNBAN"<?= strtoupper($type) === 'UNBAN' ? ' selected' : ''; ?>>Unban</option>
                        </select>
                    </div>

                    <div class="form-group-inline">
                        <label for="status">Státusz</label>
                        <select id="status" name="status">
                            <option value="all"<?= $status === 'all' ? ' selected' : ''; ?>>Mind</option>
                            <option value="active"<?= $status === 'active' ? ' selected' : ''; ?>>Csak aktív</option>
                            <option value="revoked"<?= $status === 'revoked' ? ' selected' : ''; ?>>Lejárt / visszavont</option>
                        </select>
                    </div>
                </div>

                <div class="modlog-filters-right">
                    <button type="submit" class="btn btn-primary">Szűrés</button>
                    <a href="/admin/modlog.php" class="btn btn-ghost">Szűrés törlése</a>
                </div>
            </form>

            <div class="modlog-table-wrapper">
                <table class="modlog-table">
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
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="8" class="modlog-empty">
                                Jelenleg nincs a szűrésnek megfelelő bejegyzés.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $idLabel = '#' . (int)$row['id'];

                            $dt = $row['created_at'];
                            $dateDisplay = '';
                            if ($dt !== null) {
                                $ts = strtotime($dt);
                                if ($ts !== false) {
                                    $dateDisplay = date('Y.m.d H:i', $ts);
                                }
                            }

                            $userTag = $row['user_tag'] ?: 'Ismeretlen';
                            $userId  = $row['user_id'] ?: 'N/A';

                            $modTag = $row['moderator_tag'] ?: 'Ismeretlen';
                            $modId  = $row['moderator_id'] ?: 'N/A';

                            $actionLabel   = formatActionLabel((string)$row['action_type']);
                            $durationLabel = formatDuration($row['duration_seconds'] !== null ? (int)$row['duration_seconds'] : null);

                            $reason = $row['reason'] ?: 'Nincs megadva indok';

                            $revoked = (bool)$row['revoked'];
                            [$statusLabel, $statusClass] = formatStatus($revoked);
                            ?>
                            <tr>
                                <td class="col-id">
                                    <span class="mono"><?= h($idLabel); ?></span>
                                </td>
                                <td class="col-date">
                                    <?= h($dateDisplay); ?>
                                </td>
                                <td class="col-user">
                                    <div class="modlog-user-main">
                                        <?= h($userTag); ?>
                                    </div>
                                    <div class="modlog-user-sub">
                                        ID: <?= h($userId); ?>
                                    </div>
                                </td>
                                <td class="col-mod">
                                    <div class="modlog-user-main">
                                        <?= h($modTag); ?>
                                    </div>
                                    <div class="modlog-user-sub">
                                        ID: <?= h($modId); ?>
                                    </div>
                                </td>
                                <td class="col-action">
                                    <span class="action-pill action-<?= strtolower($actionLabel); ?>">
                                        <?= h($actionLabel); ?>
                                    </span>
                                </td>
                                <td class="col-duration">
                                    <?= h($durationLabel); ?>
                                </td>
                                <td class="col-reason">
                                    <?= h($reason); ?>
                                </td>
                                <td class="col-status">
                                    <span class="<?= h($statusClass); ?>">
                                        <?= h($statusLabel); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
