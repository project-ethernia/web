<?php
// public/admin/players.php
$current_page = 'players';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'manage_users') && !hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod a játékosok kezeléséhez!');
    header('Location: /admin/index.php');
    exit;
}

// Keresés és játékos lista (webes regisztrált felhasználók)
$search = trim($_GET['search'] ?? '');
$sql    = "SELECT id, username, email, created_at, last_ip, is_banned, is_muted FROM users";
$params = [];

if ($search !== '') {
    $sql   .= " WHERE username LIKE ? OR email LIKE ? OR last_ip LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

// Kiválasztott játékos profilja
$selectedUser = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['view']]);
    $selectedUser = $stmt->fetch();
}

$page_title       = 'Játékosok | ETHERNIA Admin';
$extra_css        = ['/admin/assets/css/players.css', '/admin/assets/css/users.css'];
$topbar_icon      = 'sports_esports';
$topbar_title     = 'Játékosok';
$topbar_subtitle  = 'Online játékosok, ban/kick/parancs küldés RCON-on át';
$extra_js         = ['/admin/assets/js/players.js'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<?php /* ── SZERVER STÁTUSZ SÁV ── */ ?>
<div class="server-status-bar glass" id="server-status-bar">
    <div class="status-item">
        <span class="status-dot" id="status-dot"></span>
        <span id="status-label">Kapcsolódás...</span>
    </div>
    <div class="status-item">
        <span class="material-symbols-rounded">group</span>
        <span id="status-players">– / –</span> játékos online
    </div>
    <div class="status-item">
        <button class="btn-sm btn-refresh" id="btn-refresh-status">
            <span class="material-symbols-rounded">refresh</span> Frissítés
        </button>
    </div>
</div>

<?php /* ── ONLINE JÁTÉKOSOK ── */ ?>
<div class="admin-panel glass">
    <div class="panel-header">
        <h2><span class="material-symbols-rounded">sensors</span> Online Játékosok (RCON)</h2>
    </div>
    <div id="online-players-grid" class="online-grid">
        <div class="online-loading">
            <span class="material-symbols-rounded spinning">sync</span> Szerver lekérdezése...
        </div>
    </div>
</div>

<?php /* ── GYORS PARANCS ── */ ?>
<div class="admin-panel glass">
    <div class="panel-header">
        <h2><span class="material-symbols-rounded">terminal</span> Parancs Küldése (RCON)</h2>
    </div>
    <div class="panel-body">
        <div class="rcon-form">
            <div class="rcon-input-wrap">
                <span class="rcon-prompt">$</span>
                <input type="text" id="rcon-input" class="admin-input rcon-input"
                       placeholder="Pl.: say Hello világ!  |  gamemode creative Notch  |  weather clear"
                       autocomplete="off">
                <button class="btn-action btn-claim" id="rcon-send-btn">
                    <span class="material-symbols-rounded">send</span> Küldés
                </button>
            </div>
            <div id="rcon-response" class="rcon-response" style="display:none;"></div>
        </div>
    </div>
</div>

<?php /* ── REGISZTRÁLT JÁTÉKOSOK LISTA ── */ ?>
<div class="admin-panel glass search-panel">
    <form method="GET" class="search-form">
        <span class="material-symbols-rounded search-icon">search</span>
        <input type="text" name="search" class="search-input"
               placeholder="Keresés felhasználónév, email vagy IP alapján..."
               value="<?= h($search) ?>">
        <button type="submit" class="btn-action btn-claim">Keresés</button>
        <?php if ($search): ?>
            <a href="/admin/players.php" class="btn-action btn-back">Mindenki</a>
        <?php endif; ?>
    </form>
</div>

<div class="split-layout">
    <div class="admin-panel glass list-panel">
        <div class="panel-header">
            <h2><span class="material-symbols-rounded">list</span> Regisztrált Felhasználók (Max 100)</h2>
        </div>
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
                                    <span style="font-size:0.75rem; color:var(--text-muted);"><?= h($u['email'] ?? '') ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="td-muted"><?= date('Y.m.d.', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div style="display:flex; gap:0.3rem;">
                                <?php if ($u['is_banned']): ?>
                                    <span class="status-badge error" title="Kitiltva"><span class="material-symbols-rounded">block</span></span>
                                <?php endif; ?>
                                <?php if ($u['is_muted']): ?>
                                    <span class="status-badge warning" title="Némítva"><span class="material-symbols-rounded">volume_off</span></span>
                                <?php endif; ?>
                                <?php if (!$u['is_banned'] && !$u['is_muted']): ?>
                                    <span class="status-badge success"><span class="material-symbols-rounded">check_circle</span></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <a href="?search=<?= urlencode($search) ?>&view=<?= $u['id'] ?>"
                               class="btn-sm btn-open">Kezelés</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usersList)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">Nincs találat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= h($selectedUser['email'] ?? 'Nem megadott') ?></span>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Utolsó IP</span>
                        <span class="info-value"><?= h($selectedUser['last_ip'] ?? 'Ismeretlen') ?></span>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Regisztráció</span>
                        <span class="info-value"><?= date('Y. m. d. H:i', strtotime($selectedUser['created_at'])) ?></span>
                    </div>
                </div>

                <hr class="control-divider">
                <h4 style="color:var(--text-muted); font-size:0.85rem; text-transform:uppercase; margin-bottom:1rem;">
                    RCON Műveletek
                </h4>

                <div class="rcon-actions" data-username="<?= h($selectedUser['username']) ?>">
                    <button class="btn-punish" data-action="kick">
                        <span class="material-symbols-rounded">logout</span>
                        <div><strong>Kick</strong><span>Kirúgás a szerverről</span></div>
                    </button>
                    <button class="btn-punish <?= $selectedUser['is_banned'] ? 'active-punishment' : '' ?>"
                            data-action="<?= $selectedUser['is_banned'] ? 'unban' : 'ban' ?>">
                        <span class="material-symbols-rounded">block</span>
                        <div>
                            <strong><?= $selectedUser['is_banned'] ? 'Unban' : 'Ban' ?></strong>
                            <span><?= $selectedUser['is_banned'] ? 'Tiltás feloldása (MC+Web)' : 'Kitiltás (MC+Web)' ?></span>
                        </div>
                    </button>
                    <button class="btn-punish" data-action="msg">
                        <span class="material-symbols-rounded">chat</span>
                        <div><strong>Privát Üzenet</strong><span>Üzenet küldése játék közben</span></div>
                    </button>
                </div>

                <div id="rcon-result-<?= $selectedUser['id'] ?>" class="rcon-inline-result" style="display:none;"></div>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-panel glass profile-panel empty-profile">
            <span class="material-symbols-rounded">person_search</span>
            <p>Válassz játékost a listából a kezeléshez!</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>