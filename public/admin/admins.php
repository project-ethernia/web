<?php
$current_page = 'admins';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'manage_admins') && !hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod az adminisztrátorok kezeléséhez!');
    header('Location: /admin/index.php');
    exit;
}

$ADMIN_ROLES = [
    'support' => ['name' => 'Support', 'color' => '#22c55e', 'icon' => 'support_agent'],
    'moderator' => ['name' => 'Moderátor', 'color' => '#3b82f6', 'icon' => 'gavel'],
    'admin' => ['name' => 'Adminisztrátor', 'color' => '#a855f7', 'icon' => 'shield_person'],
    'superadmin' => ['name' => 'Super Admin', 'color' => '#ef4444', 'icon' => 'local_police']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $new_user = trim($_POST['username'] ?? '');
    $new_pass = $_POST['password'] ?? '';
    $new_role = $_POST['role'] ?? 'support';

    if ($new_user && $new_pass) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$new_user]);
        if ($stmt->fetch()) {
            setFlash('error', 'Ez a felhasználónév már létezik az adminok között!');
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$new_user, $hash, $new_role]);
            log_admin_action($pdo, $admin_id, $admin_name, "Új adminisztrátor hozzáadva: " . $new_user . " (" . $new_role . ")");
            setFlash('success', 'Új csapattag sikeresen hozzáadva: ' . h($new_user));
        }
    } else {
        setFlash('error', 'Minden mező kitöltése kötelező!');
    }
    header('Location: /admin/admins.php');
    exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $target_id = (int)$_GET['delete'];
    if ($target_id === $admin_id) {
        setFlash('error', 'Saját magadat nem törölheted!');
    } else {
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$target_id]);
        log_admin_action($pdo, $admin_id, $admin_name, "Adminisztrátor törölve. ID: " . $target_id);
        setFlash('success', 'Adminisztrátor sikeresen eltávolítva a rendszerből.');
    }
    header('Location: /admin/admins.php');
    exit;
}

if (isset($_GET['reset_2fa']) && is_numeric($_GET['reset_2fa'])) {
    $pdo->prepare("UPDATE admins SET two_factor_secret = NULL WHERE id = ?")->execute([(int)$_GET['reset_2fa']]);
    log_admin_action($pdo, $admin_id, $admin_name, "2FA visszaállítva. ID: " . (int)$_GET['reset_2fa']);
    setFlash('success', 'A 2FA hitelesítés sikeresen visszaállítva a felhasználónak.');
    header('Location: /admin/admins.php');
    exit;
}

$stmt = $pdo->query("SELECT id, username, role, last_login, is_active, two_factor_secret FROM admins ORDER BY id ASC");
$adminList = $stmt->fetchAll();

$page_title = 'Adminisztrátorok | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/admins.css'];
$topbar_icon = 'shield_person';
$topbar_title = 'Adminisztrátorok & Stáb';
$topbar_subtitle = 'Csapattagok és jogosultságok kezelése';
$extra_js = ['/admin/assets/js/admins.js'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="split-layout">
    <div class="admin-panel glass list-panel">
        <div class="panel-header">
            <h2><span class="material-symbols-rounded">groups</span> Jelenlegi Csapattagok</h2>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Azonosító</th>
                    <th>Felhasználó</th>
                    <th>Rang (Szerepkör)</th>
                    <th>2FA Állapot</th>
                    <th>Utolsó Belépés</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($adminList as $a): ?>
                    <?php 
                        $roleData = $ADMIN_ROLES[$a['role']] ?? ['name' => $a['role'], 'color' => '#fff', 'icon' => 'person'];
                        $has2FA = !empty($a['two_factor_secret']);
                    ?>
                    <tr class="hover-row">
                        <td class="td-id">#<?= str_pad($a['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="admin-user-cell">
                                <img src="https://minotar.net/helm/<?= h($a['username']) ?>/32.png" alt="Avatar" class="admin-avatar">
                                <strong><?= h($a['username']) ?></strong>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge" style="--role-color: <?= $roleData['color'] ?>;">
                                <span class="material-symbols-rounded"><?= $roleData['icon'] ?></span>
                                <?= h($roleData['name']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($has2FA): ?>
                                <span class="status-2fa success" title="2FA Bekapcsolva"><span class="material-symbols-rounded">shield</span> Védett</span>
                            <?php else: ?>
                                <span class="status-2fa warning" title="Nincs 2FA védelem"><span class="material-symbols-rounded">gpp_maybe</span> Nincs</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-muted">
                            <?= $a['last_login'] ? date('Y.m.d. H:i', strtotime($a['last_login'])) : 'Még nem lépett be' ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($has2FA && hasPermission($admin_role, 'all')): ?>
                                    <a href="?reset_2fa=<?= $a['id'] ?>" class="btn-sm btn-warning" title="2FA Visszaállítása" onclick="ethConfirm(event, 'Biztosan visszaállítod a 2FA-t?', this.href);">
                                        <span class="material-symbols-rounded">lock_reset</span>
                                    </a>
                                <?php endif; ?>
                                <?php if ($a['id'] !== $admin_id): ?>
                                    <a href="?delete=<?= $a['id'] ?>" class="btn-sm btn-danger" title="Admin Törlése" onclick="ethConfirm(event, 'Biztosan eltávolítod ezt az admint?', this.href);">
                                        <span class="material-symbols-rounded">person_remove</span>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-sm btn-disabled" title="Saját magadat nem törölheted"><span class="material-symbols-rounded">person_remove</span></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-panel glass form-panel">
        <div class="panel-header">
            <h2><span class="material-symbols-rounded">person_add</span> Új Tag Hozzáadása</h2>
        </div>
        <div class="panel-body">
            <form method="POST" action="/admin/admins.php" class="add-admin-form">
                <input type="hidden" name="action" value="add_admin">
                
                <div class="input-group">
                    <label>Felhasználónév (Minecraft név)</label>
                    <input type="text" name="username" class="admin-input" required autocomplete="off" placeholder="Pl.: Notch">
                </div>

                <div class="input-group">
                    <label>Ideiglenes Jelszó</label>
                    <input type="password" name="password" class="admin-input" required autocomplete="new-password" placeholder="Belépéshez szükséges">
                </div>

                <div class="input-group">
                    <label>Jogosultsági Szint</label>
                    <div class="role-grid">
                        <?php foreach ($ADMIN_ROLES as $key => $data): ?>
                            <label class="role-card" style="--role-color: <?= $data['color'] ?>;">
                                <input type="radio" name="role" value="<?= $key ?>" required <?= $key === 'support' ? 'checked' : '' ?>>
                                <div class="role-content">
                                    <span class="material-symbols-rounded"><?= $data['icon'] ?></span>
                                    <span><?= h($data['name']) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn-action btn-claim" style="width: 100%; margin-top: 1rem;">
                    <span class="material-symbols-rounded">add_circle</span>
                    Hozzáadás
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>