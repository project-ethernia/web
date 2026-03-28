<?php
// A nav-item osztály kapja meg az 'active' stílust, ha azon az oldalon vagyunk.
$current_page = $current_page ?? '';
?>
<aside class="admin-sidebar glass" id="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo-wrapper">
            <span class="material-symbols-rounded logo-icon">security</span>
            <span class="logo-text">ETHERNIA</span>
        </div>
        <button class="toggle-btn" id="sidebar-toggle">
            <span class="material-symbols-rounded">menu_open</span>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Főmenü</div>
        <a href="/admin/index.php" class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">dashboard</span>
            <span class="nav-text">Vezérlőpult</span>
        </a>
        
        <div class="nav-section">Rendszer & Szerver</div>
        <a href="/admin/users.php" class="nav-item <?= $current_page === 'users' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">group</span>
            <span class="nav-text">Játékosok Kezelése</span>
        </a>
        <a href="/admin/players.php" class="nav-item <?= $current_page === 'players' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">terminal</span>
            <span class="nav-text">Szerver Konzol (RCON)</span>
        </a>
        
        <div class="nav-section">Kommunikáció</div>
        <a href="/admin/tickets.php" class="nav-item <?= $current_page === 'tickets' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">forum</span>
            <span class="nav-text">Hibajegyek</span>
        </a>
        <a href="/admin/news.php" class="nav-item <?= $current_page === 'news' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">newspaper</span>
            <span class="nav-text">Hírek kezelése</span>
        </a>
        
        <div class="nav-section">Adminisztráció</div>
        <a href="/admin/admins.php" class="nav-item <?= $current_page === 'admins' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">shield_person</span>
            <span class="nav-text">Adminisztrátorok</span>
        </a>
        <a href="/admin/logs.php" class="nav-item <?= $current_page === 'logs' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">manage_search</span>
            <span class="nav-text">Műveletnapló</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/admin/auth/logout.php" class="logout-btn">
            <span class="material-symbols-rounded">logout</span>
            <span class="nav-text">Kijelentkezés</span>
        </a>
    </div>
</aside>