<?php
$current_page = 'users';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'manage_users') && !hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod a felhasználók kezeléséhez!');
    header('Location: /admin/index.php');
    exit;
}

if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $action = $_GET['action'];

    $stmt = $pdo->prepare("SELECT username, is_banned, is_muted FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $user = $stmt->fetch();

    if ($user) {
        if ($action === 'toggle_ban') {
            $new_status = $user['is_banned'] ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ?")->execute([$new_status, $target_id]);
            $logAction = $new_status ? "Kitiltotta (Ban) a weboldalról: " : "Feloldotta a kitiltást (Unban): ";
            log_admin_action($pdo, $admin_id, $admin_name, $logAction . $user['username']);
            setFlash('success', $new_status ? "Játékos kitiltva!" : "Tiltás feloldva!");
        } elseif ($action === 'toggle_mute') {
            $new_status = $user['is_muted'] ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_muted = ? WHERE id = ?")->execute([$new_status, $target_id]);
            $logAction = $new_status ? "Némította a Ticket rendszerből: " : "Feloldotta a némítást: ";
            log_admin_action($pdo, $admin_id, $admin_name, $logAction . $user['username']);
            setFlash('success', $new_status ? "Játékos némítva a hibajegyeknél!" : "Némítás feloldva!");
        }
    }
    
    $search_query = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
    header("Location: /admin/users.php?view={$target_id}{$search_query}");
    exit;
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT id, username, email, created_at, last_ip, is_banned, is_muted FROM users";
$params = [];

if ($search !== '') {
    $sql .= " WHERE username LIKE ? OR email LIKE ? OR last_ip LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$selectedUser = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['view']]);
    $selectedUser = $stmt->fetch();
}

$page_title = 'Felhasználók Kezelése | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/users.css'];
$topbar_icon = 'group';
$topbar_title = 'Felhasználók & Játékosok';
$topbar_subtitle = 'Regisztrált fiókok keresése, kezelése és büntetése';
$extra_js = ['/admin/assets/js/users.js'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="admin-panel glass search-panel">
    <form method="GET" class="search-form">
        <span class="material-symbols-rounded search-icon">search</span>
        <input type="text" name="search" class="search-input" placeholder="Keresés név, email vagy IP cím alapján..." value="<?= h($search) ?>">
        <button type="submit" class="btn-action btn-claim">Keresés</button>
        <?php if($search): ?>
            <a href="/admin/users.php" class="btn-action btn-back">Mindenki</a>
        <?php endif; ?>
    </form>
</div>

<div class="split-layout">
    <div class="admin-panel glass list-panel">
        <div class="panel-header">
            <h2><span class="material-symbols-rounded">list</span> Találatok (Max 100)</h2>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Felhasználó</th>
                        <th>Regisztrált</th>
                        <th>Státusz</th>
                        <th>Művelet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $u): ?>
                        <tr class="hover-row <?= ($selectedUser && $selectedUser['id'] === $u['id']) ? 'active-row' : '' ?>">
                            <td class="td-id">#<?= $u['id'] ?></td>
                            <td>
                                <div class="player-cell">
                                    <img src="https://minotar.net/helm/<?= h($u['username']) ?>/24.png" class="player-head">
                                    <div style="display:flex; flex-direction:column;">
                                        <strong><?= h($u['username']) ?></strong>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?= h($u['email'] ?? 'Nincs email') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="td-muted"><?= date('Y.m.d.', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div style="display:flex; gap:0.3rem;">
                                    <?php if($u['is_banned']): ?>
                                        <span class="status-badge error" title="Weboldalról kitiltva"><span class="material-symbols-rounded">block</span></span>
                                    <?php endif; ?>
                                    <?php if($u['is_muted']): ?>
                                        <span class="status-badge warning" title="Ticketekből némítva"><span class="material-symbols-rounded">volume_off</span></span>
                                    <?php endif; ?>
                                    <?php if(!$u['is_banned'] && !$u['is_muted']): ?>
                                        <span class="status-badge success"><span class="material-symbols-rounded">check_circle</span></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <a href="?search=<?= urlencode($search) ?>&view=<?= $u['id'] ?>" class="btn-sm btn-open">Kezelés</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($usersList)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--text-muted);">Nincs találat a keresésre.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($selectedUser): ?>
        <div class="admin-panel glass profile-panel">
            <div class="panel-header">
                <h2><span class="material-symbols-rounded">account_box</span> Játékos Profilja</h2>
            </div>
            <div class="panel-body">
                <div class="profile-header">
                    <img src="https://minotar.net/helm/<?= h($selectedUser['username']) ?>/64.png" class="profile-avatar">
                    <div>
                        <h3 class="profile-name"><?= h($selectedUser['username']) ?></h3>
                        <span class="profile-id">ID: #<?= $selectedUser['id'] ?></span>
                    </div>
                </div>

                <div class="profile-info-grid">
                    <div class="info-box">
                        <span class="info-label">Email cím</span>
                        <span class="info-value"><?= h($selectedUser['email'] ?? 'Nem megadott') ?></span>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Utolsó IP Cím</span>
                        <span class="info-value"><?= h($selectedUser['last_ip'] ?? 'Ismeretlen') ?></span>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Regisztráció ideje</span>
                        <span class="info-value"><?= date('Y. M d. H:i', strtotime($selectedUser['created_at'])) ?></span>
                    </div>
                </div>

                <hr class="control-divider">
                <h4 style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; margin-bottom: 1rem;">Büntetések és Korlátozások</h4>

                <div class="punishment-actions">
                    <a href="?action=toggle_ban&id=<?= $selectedUser['id'] ?>&view=<?= $selectedUser['id'] ?>&search=<?= urlencode($search) ?>" 
                       class="btn-punish <?= $selectedUser['is_banned'] ? 'active-punishment' : '' ?>"
                       onclick="ethConfirm(event, 'Biztosan meg akarod változtatni a Web Ban státuszt?', this.href);">
                        <span class="material-symbols-rounded">block</span>
                        <div>
                            <strong><?= $selectedUser['is_banned'] ? 'Web Ban Feloldása' : 'Kitiltás a Weboldalról' ?></strong>
                            <span><?= $selectedUser['is_banned'] ? 'A játékos újra be tud lépni.' : 'Nem fog tudni bejelentkezni az oldalra.' ?></span>
                        </div>
                    </a>

                    <a href="?action=toggle_mute&id=<?= $selectedUser['id'] ?>&view=<?= $selectedUser['id'] ?>&search=<?= urlencode($search) ?>" 
                       class="btn-punish <?= $selectedUser['is_muted'] ? 'active-warning' : '' ?>"
                       onclick="ethConfirm(event, 'Biztosan meg akarod változtatni a Némítás státuszt?', this.href);">
                        <span class="material-symbols-rounded">volume_off</span>
                        <div>
                            <strong><?= $selectedUser['is_muted'] ? 'Némítás Feloldása' : 'Némítás a Ticketekből' ?></strong>
                            <span><?= $selectedUser['is_muted'] ? 'Újra nyithat jegyeket.' : 'Nem nyithat új hibajegyet (Spam ellen).' ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-panel glass profile-panel empty-profile">
            <span class="material-symbols-rounded">person_search</span>
            <p>Válassz ki egy játékost a bal oldali listából a kezeléshez!</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>