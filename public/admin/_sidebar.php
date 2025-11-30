<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$adminName = isset($_SESSION['admin_username']) ? (string)$_SESSION['admin_username'] : 'Ismeretlen';
$adminRole = isset($_SESSION['admin_role']) ? (string)$_SESSION['admin_role'] : 'Nincs rang';

$currentFile = basename($_SERVER['SCRIPT_NAME']);

function sb_is_active($files, $currentFile)
{
    if (!is_array($files)) {
        $files = [$files];
    }
    return in_array($currentFile, $files, true);
}

function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$initial = strtoupper(substr($adminName, 0, 1));
?>
<aside class="admin-sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <span class="sidebar-avatar-initial"><?php echo e($initial); ?></span>
            </div>
            <div class="sidebar-profile-text">
                <div class="sidebar-profile-label">ETHERNIA ADMIN</div>
                <div class="sidebar-profile-name"><?php echo e($adminName); ?></div>
                <div class="sidebar-profile-role"><?php echo e($adminRole); ?></div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">
            <div class="nav-section-title">Áttekintés</div>

            <a href="/admin/index.php"
               class="nav-item<?php echo sb_is_active('index.php', $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                        <rect x="13" y="3" width="8" height="5" rx="2"></rect>
                        <rect x="13" y="10" width="8" height="11" rx="2"></rect>
                        <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                    </svg>
                </span>
                <span class="nav-label">Főoldal</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Tartalom</div>

            <a href="/admin/news.php"
               class="nav-item<?php echo sb_is_active('news.php', $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="4" y="4" width="16" height="14" rx="2"></rect>
                        <path d="M8 8h8"></path>
                        <path d="M8 11h6"></path>
                        <path d="M8 14h4"></path>
                    </svg>
                </span>
                <span class="nav-label">Hírek kezelése</span>
            </a>

            <a href="/admin/access.php"
               class="nav-item<?php echo sb_is_active('access.php', $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="8" cy="8" r="3"></circle>
                        <circle cx="16" cy="8" r="3"></circle>
                        <path d="M4 19c0-2.2 1.8-4 4-4"></path>
                        <path d="M16 15c2.2 0 4 1.8 4 4"></path>
                    </svg>
                </span>
                <span class="nav-label">Hozzáférés</span>
            </a>

            <a href="/admin/activity.php"
               class="nav-item<?php echo sb_is_active(array('activity.php', 'log.php'), $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <polyline points="4 14 8 9 12 15 16 10 20 14"></polyline>
                        <path d="M4 4v16"></path>
                    </svg>
                </span>
                <span class="nav-label">Tevékenység napló</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Játékosok</div>

            <a href="/admin/players.php"
               class="nav-item<?php echo sb_is_active('players.php', $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="8" r="3"></circle>
                        <path d="M4 19c0-2.5 2-4.5 5-4.5"></path>
                        <circle cx="17" cy="9" r="2.5"></circle>
                        <path d="M17 14.5c2.2 0 4 1.6 4 3.5"></path>
                    </svg>
                </span>
                <span class="nav-label">
                    Játékosok kezelése
                    <span class="nav-pill">BETA</span>
                </span>
            </a>

            <a href="/admin/users.php"
               class="nav-item<?php echo sb_is_active('users.php', $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="8" r="3"></circle>
                        <path d="M6 19c0-3 2.7-5 6-5s6 2 6 5"></path>
                    </svg>
                </span>
                <span class="nav-label">Játékos fiókok</span>
            </a>

            <a href="/admin/modlog.php"
               class="nav-item<?php echo sb_is_active('modlog.php', $currentFile) ? ' active' : ''; ?>">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                        <path d="M7 9h10"></path>
                        <path d="M7 13h5"></path>
                    </svg>
                </span>
                <span class="nav-label">Discord büntetések</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Bolt</div>

            <a href="#"
               class="nav-item nav-item-disabled">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 5h2l1 11h8l1-8H7"></path>
                        <circle cx="9" cy="19" r="1.5"></circle>
                        <circle cx="17" cy="19" r="1.5"></circle>
                    </svg>
                </span>
                <span class="nav-label">
                    Bolt
                    <span class="nav-pill nav-pill-muted">Hamarosan</span>
                </span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-footer-links">
            <a href="#" class="nav-item nav-item-compact">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2"></path>
                        <path d="M12 20v2"></path>
                        <path d="M4 12h2"></path>
                        <path d="M18 12h2"></path>
                        <path d="M5.6 5.6l1.4 1.4"></path>
                        <path d="M17 17l1.4 1.4"></path>
                        <path d="M18.4 5.6L17 7"></path>
                        <path d="M7 17l-1.4 1.4"></path>
                    </svg>
                </span>
                <span class="nav-label">Beállítások</span>
            </a>

            <a href="#" class="nav-item nav-item-compact">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 8v4"></path>
                        <circle cx="12" cy="16" r="1"></circle>
                    </svg>
                </span>
                <span class="nav-label">Segítség</span>
            </a>

            <a href="/admin/logout.php" class="nav-item nav-item-compact nav-item-logout">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 5h-3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3"></path>
                        <path d="M16 17l5-5-5-5"></path>
                        <path d="M11 12h10"></path>
                    </svg>
                </span>
                <span class="nav-label">Kijelentkezés</span>
            </a>
        </div>
    </div>
</aside>
