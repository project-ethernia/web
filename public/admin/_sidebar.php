<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminName = isset($_SESSION['admin_username']) && $_SESSION['admin_username'] !== ''
    ? $_SESSION['admin_username']
    : 'Ismeretlen';

$adminRole = isset($_SESSION['admin_role']) && $_SESSION['admin_role'] !== ''
    ? strtoupper($_SESSION['admin_role'])
    : 'ADMIN';

$currentPage = basename($_SERVER['SCRIPT_NAME']);

function h_sidebar($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_active_page($currentPage, array $files) {
    return in_array($currentPage, $files, true) ? ' nav-item-active' : '';
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-top">
        <div class="sidebar-profile">
            <div class="profile-avatar">
                <span><?= strtoupper(substr($adminName, 0, 1)); ?></span>
            </div>
            <div class="profile-text">
                <div class="profile-label">ETHERNIA ADMIN</div>
                <div class="profile-name"><?= h_sidebar($adminName); ?></div>
                <div class="profile-role"><?= h_sidebar($adminRole); ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-label">Áttekintés</div>
                <a href="/admin/index.php"
                   class="nav-item<?= is_active_page($currentPage, ['index.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-dashboard-line"></i></span>
                    <span class="nav-item-text">Főoldal</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Tartalom</div>
                <a href="/admin/news.php"
                   class="nav-item<?= is_active_page($currentPage, ['news.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-newspaper-line"></i></span>
                    <span class="nav-item-text">Hírek kezelése</span>
                </a>
                <a href="/admin/admins.php"
                   class="nav-item<?= is_active_page($currentPage, ['admins.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-key-2-line"></i></span>
                    <span class="nav-item-text">Hozzáférés</span>
                </a>
                <a href="/admin/activity.php"
                   class="nav-item<?= is_active_page($currentPage, ['activity.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-history-line"></i></span>
                    <span class="nav-item-text">Tevékenység napló</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Játékosok</div>
                <a href="/admin/players.php"
                   class="nav-item<?= is_active_page($currentPage, ['players.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-team-line"></i></span>
                    <span class="nav-item-text">Játékosok kezelése</span>
                    <span class="nav-pill nav-pill-soft">Beta</span>
                </a>
                <a href="/admin/users.php"
                   class="nav-item<?= is_active_page($currentPage, ['users.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-user-3-line"></i></span>
                    <span class="nav-item-text">Játékos fiókok</span>
                </a>
                <a href="/admin/modlog.php"
                   class="nav-item<?= is_active_page($currentPage, ['modlog.php']); ?>">
                    <span class="nav-item-icon"><i class="ri-shield-user-line"></i></span>
                    <span class="nav-item-text">Discord büntetések</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-label">Bolt</div>
                <a href="#"
                   class="nav-item nav-item-disabled">
                    <span class="nav-item-icon"><i class="ri-shopping-bag-3-line"></i></span>
                    <span class="nav-item-text">Bolt</span>
                    <span class="nav-pill nav-pill-soft">Hamarosan</span>
                </a>
            </div>
        </nav>
    </div>

    <div class="sidebar-bottom">
        <div class="sidebar-support">
            <a href="#" class="nav-item nav-item-ghost">
                <span class="nav-item-icon"><i class="ri-settings-3-line"></i></span>
                <span class="nav-item-text">Beállítások</span>
            </a>
            <a href="#" class="nav-item nav-item-ghost">
                <span class="nav-item-icon"><i class="ri-question-line"></i></span>
                <span class="nav-item-text">Segítség</span>
            </a>
        </div>

        <form action="/auth/logout.php" method="post" class="sidebar-logout-form">
            <button type="submit" class="nav-item nav-item-danger">
                <span class="nav-item-icon"><i class="ri-logout-box-r-line"></i></span>
                <span class="nav-item-text">Kijelentkezés</span>
            </button>
        </form>
    </div>
</aside>
