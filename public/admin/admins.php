<?php
$current_page = 'admins';
require_once __DIR__ . '/includes/core.php';

// Jogosultság ellenőrzése
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

$stmt = $pdo->query("SELECT id, username, role, last_login, is_active, two_factor_secret FROM admins ORDER BY id ASC");
$adminList = $stmt->fetchAll();

$page_title = 'Adminisztrátorok | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/admins.css'];
$topbar_icon = 'shield_person';
$topbar_title = 'Adminisztrátorok & Stáb';
$topbar_subtitle = 'Csapattagok és jogosultságok élő kezelése';
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
                                    <button class="btn-sm btn-warning" title="2FA Visszaállítása" onclick="doAdminAction('reset_2fa', <?= $a['id'] ?>, 'Biztosan visszaállítod a 2FA-t?')">
                                        <span class="material-symbols-rounded">lock_reset</span>
                                    </button>
                                <?php endif; ?>
                                <?php if ($a['id'] !== $admin_id): ?>
                                    <button class="btn-sm btn-danger" title="Admin Törlése" onclick="doAdminAction('delete', <?= $a['id'] ?>, 'Biztosan eltávolítod ezt az admint a rendszerből?')">
                                        <span class="material-symbols-rounded">person_remove</span>
                                    </button>
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
            <form onsubmit="handleAddAdmin(event)" class="add-admin-form">
                
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