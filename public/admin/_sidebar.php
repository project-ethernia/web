<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminName = !empty($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';
$currentAdminRole = !empty($_SESSION['admin_role']) ? strtoupper((string)$_SESSION['admin_role']) : 'ADMIN';

function sidebar_link(string $href, string $label, string $icon, string $currentPath, bool $disabled = false): void {
    $isActive = !$disabled && (stripos($currentPath, $href) !== false);
    $classes = 'sidebar-nav-link';
    if ($isActive) {
        $classes .= ' is-active';
    }
    if ($disabled) {
        $classes .= ' is-disabled';
    }
    ?>
    <a href="<?php echo $disabled ? '#' : htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"
       class="<?php echo $classes; ?>">
        <span class="sidebar-nav-icon"><?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="sidebar-nav-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($disabled): ?>
            <span class="sidebar-nav-pill">HAMAROSAN</span>
        <?php endif; ?>
    </a>
    <?php
}

$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="sidebar-shell">
    <div class="sidebar-panel">
        <header class="sidebar-header">
            <div class="sidebar-user-avatar">
                <span class="sidebar-user-avatar-initial">
                    <?php echo strtoupper(substr($currentAdminName, 0, 1)); ?>
                </span>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($currentAdminName, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="sidebar-user-role"><?php echo htmlspecialchars($currentAdminRole, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </header>

        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Áttekintés</div>
                <div class="sidebar-section-items">
                    <?php sidebar_link('/admin/index.php', 'Főoldal', '🏠', $currentPath); ?>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Tartalom</div>
                <div class="sidebar-section-items">
                    <?php
                    sidebar_link('/admin/news.php', 'Hírek kezelése', '📰', $currentPath);
                    sidebar_link('/admin/admins.php', 'Hozzáférés', '🔑', $currentPath);
                    sidebar_link('/admin/activity.php', 'Tevékenység napló', '📊', $currentPath);
                    ?>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Játékosok</div>
                <div class="sidebar-section-items">
                    <?php
                    sidebar_link('/admin/players.php', 'Játékosok kezelése', '👥', $currentPath);
                    sidebar_link('/admin/users.php', 'Játékos fiókok', '👤', $currentPath);
                    sidebar_link('/admin/modlog.php', 'Discord büntetések', '🛡️', $currentPath);
                    ?>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">Bolt</div>
                <div class="sidebar-section-items">
                    <?php sidebar_link('/admin/shop.php', 'Bolt', '🛒', $currentPath, true); ?>
                </div>
            </div>
        </nav>

        <footer class="sidebar-footer">
            <div class="sidebar-footer-group">
                <button type="button" class="sidebar-footer-link" disabled>
                    <span class="sidebar-footer-icon">⚙️</span>
                    <span class="sidebar-footer-label">Beállítások</span>
                </button>
                <button type="button" class="sidebar-footer-link" disabled>
                    <span class="sidebar-footer-icon">❓</span>
                    <span class="sidebar-footer-label">Segítség</span>
                </button>
            </div>
            <div class="sidebar-footer-group sidebar-footer-logout">
                <a href="/admin/logout.php" class="sidebar-footer-link sidebar-footer-link-logout">
                    <span class="sidebar-footer-icon">⏻</span>
                    <span class="sidebar-footer-label">Kijelentkezés</span>
                </a>
            </div>
        </footer>
    </div>
</aside>
