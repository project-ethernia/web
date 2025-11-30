<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$adminUsername = isset($_SESSION['admin_username']) ? (string)$_SESSION['admin_username'] : 'Ismeretlen';
$adminRole     = isset($_SESSION['admin_role']) ? (string)$_SESSION['admin_role'] : 'Admin';

$adminUsernameEsc = htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8');
$adminRoleEsc     = htmlspecialchars(strtoupper($adminRole), ENT_QUOTES, 'UTF-8');

$currentPath = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
$currentFile = basename($currentPath);

function sidebar_active_class($currentFile, array $files)
{
    return in_array($currentFile, $files, true) ? ' sidebar-link-active' : '';
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <span class="sidebar-avatar-initial">
                <?php echo strtoupper(mb_substr($adminUsername, 0, 1, 'UTF-8')); ?>
            </span>
        </div>
        <div class="sidebar-profile-text">
            <div class="sidebar-profile-title">ETHERNIA ADMIN</div>
            <div class="sidebar-profile-name"><?php echo $adminUsernameEsc; ?></div>
            <div class="sidebar-profile-role"><?php echo $adminRoleEsc; ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-group">
            <div class="sidebar-group-label">Áttekintés</div>
            <a href="/admin/index.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['index.php']); ?>">
                <span class="sidebar-link-icon">🏠</span>
                <span class="sidebar-link-label">Főoldal</span>
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-label">Tartalom</div>
            <a href="/admin/news.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['news.php']); ?>">
                <span class="sidebar-link-icon">📰</span>
                <span class="sidebar-link-label">Hírek kezelése</span>
            </a>
            <a href="/admin/admins.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['admins.php']); ?>">
                <span class="sidebar-link-icon">🔑</span>
                <span class="sidebar-link-label">Hozzáférés</span>
            </a>
            <a href="/admin/activity.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['activity.php']); ?>">
                <span class="sidebar-link-icon">📊</span>
                <span class="sidebar-link-label">Tevékenység napló</span>
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-label">Játékosok</div>
            <a href="/admin/players.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['players.php']); ?>">
                <span class="sidebar-link-icon">🎮</span>
                <span class="sidebar-link-label">
                    Játékosok kezelése
                    <span class="sidebar-pill">BETA</span>
                </span>
            </a>
            <a href="/admin/users.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['users.php']); ?>">
                <span class="sidebar-link-icon">👤</span>
                <span class="sidebar-link-label">Játékos fiókok</span>
            </a>
            <a href="/admin/modlog.php" class="sidebar-link<?php echo sidebar_active_class($currentFile, ['modlog.php']); ?>">
                <span class="sidebar-link-icon">💬</span>
                <span class="sidebar-link-label">Discord büntetések</span>
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-label">Bolt</div>
            <div class="sidebar-link sidebar-link-disabled">
                <span class="sidebar-link-icon">🛒</span>
                <span class="sidebar-link-label">
                    Bolt
                    <span class="sidebar-pill sidebar-pill-muted">Hamarosan</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-footer-links">
            <a href="#" class="sidebar-footer-link">
                <span class="sidebar-footer-icon">⚙️</span>
                <span class="sidebar-footer-label">Beállítások</span>
            </a>
            <a href="#" class="sidebar-footer-link">
                <span class="sidebar-footer-icon">❓</span>
                <span class="sidebar-footer-label">Segítség</span>
            </a>
        </div>
        <a href="/admin/logout.php" class="sidebar-logout-link">
            <span class="sidebar-footer-icon sidebar-footer-icon-danger">⏻</span>
            <span class="sidebar-footer-label">Kijelentkezés</span>
        </a>
    </div>
</aside>
