<?php
// Az aktív menüpont megállapítása a jelenlegi URL alapján
$current_url = $_SERVER['REQUEST_URI'];
function is_active($path) {
    global $current_url;
    return strpos($current_url, $path) !== false ? 'active' : '';
}
?>
<aside class="admin-sidebar glass" id="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo-wrapper">
            <span class="material-symbols-rounded logo-icon">admin_panel_settings</span>
            <span class="logo-text">ETHERNIA</span>
        </div>
        <button class="toggle-btn" id="sidebar-toggle" title="Menü összecsukása/kinyitása">
            <span class="material-symbols-rounded">menu_open</span>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Rendszer</div>
        <a href="/admin/index.php" class="nav-item <?= $current_url === '/admin/' || $current_url === '/admin/index.php' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">dashboard</span>
            <span class="nav-text">Vezérlőpult</span>
        </a>
        <a href="/admin/tickets.php" class="nav-item <?= is_active('/admin/tickets.php') ?>">
            <span class="material-symbols-rounded">support_agent</span>
            <span class="nav-text">Ügyfélszolgálat</span>
        </a>
        
        <div class="nav-section">Játékosok</div>
        <a href="/admin/users.php" class="nav-item <?= is_active('/admin/users.php') ?>">
            <span class="material-symbols-rounded">group</span>
            <span class="nav-text">Felhasználók</span>
        </a>
        <a href="/admin/players.php" class="nav-item <?= is_active('/admin/players.php') ?>">
            <span class="material-symbols-rounded">sports_esports</span>
            <span class="nav-text">Játékos Karakterek</span>
        </a>

        <div class="nav-section">Tartalom</div>
        <a href="/admin/news.php" class="nav-item <?= is_active('/admin/news.php') ?>">
            <span class="material-symbols-rounded">newspaper</span>
            <span class="nav-text">Hírek & Cikkek</span>
        </a>
        
        <div class="nav-section">Biztonság</div>
        <a href="/admin/admins.php" class="nav-item <?= is_active('/admin/admins.php') ?>">
            <span class="material-symbols-rounded">shield_person</span>
            <span class="nav-text">Adminisztrátorok</span>
        </a>
        <a href="/admin/logs.php" class="nav-item <?= is_active('/admin/logs.php') ?>">
            <span class="material-symbols-rounded">manage_search</span>
            <span class="nav-text">Rendszernaplók</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/admin/auth/logout.php" class="nav-item logout-btn">
            <span class="material-symbols-rounded">logout</span>
            <span class="nav-text">Kijelentkezés</span>
        </a>
    </div>
</aside>