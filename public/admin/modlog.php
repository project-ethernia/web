<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../database.php';
// ha van külön admin-auth, itt be tudod vonni:
// require_once __DIR__ . '/_require_admin.php';

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function format_duration(?int $seconds): string
{
    if ($seconds === null || $seconds <= 0) {
        return 'Állandó';
    }

    $out = [];

    $days = intdiv($seconds, 86400);
    $seconds %= 86400;

    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;

    $mins = intdiv($seconds, 60);

    if ($days > 0) {
        $out[] = $days . ' nap';
    }
    if ($hours > 0) {
        $out[] = $hours . ' óra';
    }
    if ($mins > 0) {
        $out[] = $mins . ' perc';
    }

    return $out ? implode(' ', $out) : '≤ 1 perc';
}

function format_action_label(string $type): array
{
    $t = strtolower($type);
    $label = strtoupper($type);
    $cls = 'badge-log badge-log-other';

    if (in_array($t, ['ban', 'tempban', 'softban'], true)) {
        $label = 'BAN';
        $cls = 'badge-log badge-log-ban';
    } elseif (in_array($t, ['mute', 'tempmute'], true)) {
        $label = 'MUTE';
        $cls = 'badge-log badge-log-mute';
    } elseif (in_array($t, ['kick'], true)) {
        $label = 'KICK';
        $cls = 'badge-log badge-log-kick';
    } elseif (in_array($t, ['warn'], true)) {
        $label = 'WARN';
        $cls = 'badge-log badge-log-warn';
    } elseif (in_array($t, ['unban', 'unmute', 'unwarn'], true)) {
        $label = strtoupper($type);
        $cls = 'badge-log badge-log-revoke';
    }

    return [$label, $cls];
}

function format_status_label(int $revoked): array
{
    if ($revoked) {
        return ['Visszavonva', 'badge-status badge-status-revoked'];
    }
    return ['Aktív / lezárt', 'badge-status badge-status-active'];
}

try {
    $pdo = get_pdo();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$actionFilter = isset($_GET['action']) ? trim($_GET['action']) : 'all';
$revokedFilter = isset($_GET['revoked']) ? trim($_GET['revoked']) : 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($actionFilter !== 'all' && $actionFilter !== '') {
    $where[] = 'action_type = :action_type';
    $params[':action_type'] = $actionFilter;
}

if ($revokedFilter === 'active') {
    $where[] = 'revoked = 0';
} elseif ($revokedFilter === 'revoked') {
    $where[] = 'revoked = 1';
}

if ($q !== '') {
    $where[] = '(user_tag LIKE :q
                 OR user_id LIKE :q
                 OR moderator_tag LIKE :q
                 OR moderator_id LIKE :q
                 OR reason LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) FROM moderation_actions {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = (int)max(1, ceil($totalRows / $perPage));

$listSql = "
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
    {$whereSql}
    ORDER BY created_at DESC, id DESC
    LIMIT :limit OFFSET :offset
";

$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$actionTypes = ['ban', 'tempban', 'softban', 'mute', 'tempmute', 'kick', 'warn', 'unban', 'unmute', 'unwarn'];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA – Moderációs napló</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

<?php
// ha van közös admin header/nav, itt tudod behúzni include‑dal
// include __DIR__ . '/_layout_header.php';
?>

<main class="admin-main">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Moderációs napló</h1>
            <p class="admin-page-subtitle">
                Discord moderációs műveletek (ban, mute, kick, warn, stb.) naplózása.
            </p>
        </div>
        <div class="admin-page-meta">
            <span class="pill-counter">
                Összesen <?= $totalRows; ?> bejegyzés
            </span>
        </div>
    </header>

    <section class="card">
        <div class="card-header card-header-flex">
            <form method="get" class="modlog-filters">
                <div class="filter-group">
                    <label for="q">Keresés</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="<?= h($q); ?>"
                        placeholder="Felhasználó, moderátor, indok..."
                    >
                </div>

                <div class="filter-group">
                    <label for="action">Akció típusa</label>
                    <select id="action" name="action">
                        <option value="all"<?= $actionFilter === 'all' ? ' selected' : ''; ?>>Mind</option>
                        <?php foreach ($actionTypes as $type): ?>
                            <option value="<?= h($type); ?>"<?= $actionFilter === $type ? ' selected' : ''; ?>>
                                <?= strtoupper($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="revoked">Státusz</label>
                    <select id="revoked" name="revoked">
                        <option value="all"<?= $revokedFilter === 'all' ? ' selected' : ''; ?>>Mind</option>
                        <option value="active"<?= $revokedFilter === 'active' ? ' selected' : ''; ?>>Aktív / nem visszavont</option>
                        <option value="revoked"<?= $revokedFilter === 'revoked' ? ' selected' : ''; ?>>Visszavont</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Szűrés</button>
                    <a href="modlog.php" class="btn btn-ghost">Szűrés törlése</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <?php if (empty($rows)): ?>
                <p class="table-empty">
                    Nincs a feltételeknek megfelelő moderációs bejegyzés.
                </p>
            <?php else: ?>
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
                        <?php foreach ($rows as $row): ?>
                            <?php
                            [$actionLabel, $actionClass] = format_action_label($row['action_type'] ?? '');
                            [$statusLabel, $statusClass] = format_status_label((int)$row['revoked']);
                            $createdAt = $row['created_at'] ?? null;
                            $createdDisplay = $createdAt ? date('Y.m.d H:i', strtotime($createdAt)) : '-';
                            $durationStr = format_duration(isset($row['duration_seconds']) ? (int)$row['duration_seconds'] : null);
                            $reason = trim($row['reason'] ?? '');
                            $shortReason = mb_strlen($reason) > 80 ? mb_substr($reason, 0, 77) . '…' : $reason;
                            ?>
                            <tr>
                                <td class="cell-id">
                                    #<?= (int)$row['id']; ?>
                                </td>
                                <td class="cell-datetime">
                                    <?= h($createdDisplay); ?>
                                </td>
                                <td class="cell-user">
                                    <div class="cell-main">
                                        <?= h($row['user_tag'] ?? 'ismeretlen'); ?>
                                    </div>
                                    <div class="cell-sub">
                                        ID: <?= h($row['user_id'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td class="cell-mod">
                                    <div class="cell-main">
                                        <?= h($row['moderator_tag'] ?? 'ismeretlen'); ?>
                                    </div>
                                    <div class="cell-sub">
                                        ID: <?= h($row['moderator_id'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td class="cell-action">
                                    <span class="<?= h($actionClass); ?>">
                                        <?= h($actionLabel); ?>
                                    </span>
                                </td>
                                <td class="cell-duration">
                                    <?= h($durationStr); ?>
                                </td>
                                <td class="cell-reason" title="<?= h($reason); ?>">
                                    <?= h($shortReason ?: '—'); ?>
                                </td>
                                <td class="cell-status">
                                    <span class="<?= h($statusClass); ?>">
                                        <?= h($statusLabel); ?>
                                    </span>
                                    <?php if (!empty($row['related_action_id'])): ?>
                                        <div class="cell-sub">
                                            Kapcsolódó ID: #<?= (int)$row['related_action_id']; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a class="page-link" href="?<?= http_build_query(['page' => $page - 1] + $_GET); ?>">&laquo; Előző</a>
                        <?php endif; ?>

                        <span class="page-current">
                            Oldal <?= $page; ?> / <?= $totalPages; ?>
                        </span>

                        <?php if ($page < $totalPages): ?>
                            <a class="page-link" href="?<?= http_build_query(['page' => $page + 1] + $_GET); ?>">Következő &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

</body>
</html>
