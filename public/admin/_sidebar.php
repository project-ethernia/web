<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminName = $_SESSION['admin_username'] ?? 'Ismeretlen';
$adminRole = $_SESSION['admin_role'] ?? 'ADMIN';

$currentNav = $currentNav ?? '';
?>
<aside class="admin-sidebar glass-panel-sidebar" id="admin-sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-user-card">
            <div class="sidebar-avatar">
                <img src="https://minotar.net/helm/<?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>/44.png" alt="Skin" style="border-radius: 10px; width: 44px; height: 44px; image-rendering: pixelated;">
            </div>
            <div class="sidebar-user-meta">
                <div class="sidebar-user-label">Vezérlőpult</div>
                <div class="sidebar-user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="sidebar-user-role"><?= htmlspecialchars(strtoupper($adminRole), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-label">Áttekintés</div>
                <a href="/admin/index.php" class="sidebar-link ripple-btn<?= $currentNav === 'dashboard' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">dashboard</span>
                    <span class="sidebar-link-text">Főoldal</span>
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-label">Tartalom</div>
                <a href="/admin/news.php" class="sidebar-link ripple-btn<?= $currentNav === 'news' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">newspaper</span>
                    <span class="sidebar-link-text">Hírek kezelése</span>
                </a>
                <a href="/admin/admins.php" class="sidebar-link ripple-btn<?= $currentNav === 'admins' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">admin_panel_settings</span>
                    <span class="sidebar-link-text">Hozzáférés</span>
                </a>
                <a href="/admin/logs.php" class="sidebar-link ripple-btn<?= $currentNav === 'logs' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">history</span>
                    <span class="sidebar-link-text">Tevékenység napló</span>
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-label">Játékosok</div>
                <a href="/admin/players.php" class="sidebar-link ripple-btn<?= $currentNav === 'players' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">groups</span>
                    <span class="sidebar-link-text">
                        Játékosok kezelése
                        <span class="sidebar-pill pill-danger">BETA</span>
                    </span>
                </a>
                <a href="/admin/users.php" class="sidebar-link ripple-btn<?= $currentNav === 'users' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">person</span>
                    <span class="sidebar-link-text">Játékos fiókok</span>
                </a>
                <a href="/admin/modlog.php" class="sidebar-link ripple-btn<?= $currentNav === 'modlog' ? ' is-active' : ''; ?>">
                    <span class="material-symbols-rounded sidebar-icon">gavel</span>
                    <span class="sidebar-link-text">Discord büntetések</span>
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-label">Bolt</div>
                <a href="#" class="sidebar-link sidebar-link-disabled">
                    <span class="material-symbols-rounded sidebar-icon">shopping_bag</span>
                    <span class="sidebar-link-text">
                        Bolt
                        <span class="sidebar-pill sidebar-pill-muted">Hamarosan</span>
                    </span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="#" class="sidebar-footer-link ripple-btn">
                <span class="material-symbols-rounded sidebar-footer-icon">settings</span>
                <span>Beállítások</span>
            </a>
            <a href="#" class="sidebar-footer-link ripple-btn">
                <span class="material-symbols-rounded sidebar-footer-icon">help</span>
                <span>Segítség</span>
            </a>
            <a href="/admin/logout.php" class="sidebar-footer-link sidebar-footer-link-danger ripple-btn">
                <span class="material-symbols-rounded sidebar-footer-icon">logout</span>
                <span>Kijelentkezés</span>
            </a>
        </div>
    </div>
</aside>