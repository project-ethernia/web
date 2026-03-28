<?php
$current_page = 'users';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod a játékosok kezeléséhez!');
    header('Location: /admin/index.php');
    exit;
}

$page_title = 'Játékosok Kezelése | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/users.css'];
$extra_js = ['/admin/assets/js/users.js'];
$topbar_icon = 'group';
$topbar_title = 'Játékosok (Users)';
$topbar_subtitle = 'Regisztrált felhasználók élő keresése és profil kezelése';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="split-layout">
    
    <div class="list-panel">
        <div class="search-panel glass" style="margin-bottom: 1.5rem; padding: 1.5rem; display: flex; align-items: center; border-radius: 12px;">
            <span class="material-symbols-rounded" style="margin-right: 1rem; color: var(--text-muted); font-size: 1.8rem;">search</span>
            <input type="text" id="user-search" class="admin-input" placeholder="Élő keresés név vagy ID alapján..." style="border: none; background: transparent; padding: 0; box-shadow: none; font-size: 1.1rem; flex: 1; outline: none;">
        </div>

        <div class="glass" style="border-radius: 12px; overflow: hidden;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Játékos</th>
                        <th>Regisztráció</th>
                        <th>Művelet</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    </tbody>
            </table>
        </div>
    </div>

    <div class="profile-panel glass" id="profile-panel" style="border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;">
        <div class="empty-profile" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 2rem; color: var(--text-muted); text-align: center;">
            <span class="material-symbols-rounded" style="font-size: 4rem; opacity: 0.3; margin-bottom: 1rem;">person_search</span>
            <h3 style="color: #fff; font-family: var(--font-heading); margin-bottom: 0.5rem;">Válassz ki egy játékost</h3>
            <p>Kattints a "Megnyitás" gombra a bal oldali listában a részletes profil és a büntetési opciók betöltéséhez.</p>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>