<?php
// /admin/_sidebar.php

// h() helper, ha valamelyik oldalon még nem lenne definiálva
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// aktív menü (dashboard, news, admins, players, logs, stb.)
if (!isset($activePage)) {
    $activePage = '';
}

// jelenlegi felhasználó neve
if (!isset($currentUsername)) {
    $currentUsername = 'Ismeretlen';
}

function admin_nav_item_class($page, $activePage) {
    $base = 'nav-item';
    if ($page === $activePage) {
        $base .= ' active';
    }
    return $base;
}
?>

<aside class="admin-sidebar">
  <div class="sidebar-logo">
    <span class="logo-main">ETHERNIA</span>
    <span class="logo-sub">Admin Panel</span>
  </div>

  <nav class="sidebar-nav">
    <!-- Főoldal / Dashboard -->
    <a href="/admin/index.php" class="<?php echo admin_nav_item_class('dashboard', $activePage); ?>">
      <span class="nav-icon">🏠</span>
      <span class="nav-label">FŐOLDAL</span>
    </a>

    <!-- Hírek -->
    <a href="/admin/news.php" class="<?php echo admin_nav_item_class('news', $activePage); ?>">
      <span class="nav-icon">📰</span>
      <span class="nav-label">HÍREK KEZELÉSE</span>
    </a>

    <!-- Adminok -->
    <a href="/admin/admins.php" class="<?php echo admin_nav_item_class('admins', $activePage); ?>">
      <span class="nav-icon">🛡️</span>
      <span class="nav-label">HOZZÁFÉRÉS</span>
    </a>

    <!-- Napló -->
    <a href="/admin/logs.php" class="<?php echo admin_nav_item_class('logs', $activePage); ?>">
      <span class="nav-icon">📜</span>
      <span class="nav-label">TEVÉKENYSÉG NAPLÓ</span>
    </a>

    <!-- Játékosok -->
    <a href="/admin/players.php" class="<?php echo admin_nav_item_class('players', $activePage); ?>">
      <span class="nav-icon">👥</span>
      <span class="nav-label">JÁTÉKOSOK KEZELÉSE</span>
      <span class="nav-pill">BETA</span>
    </a>

    <a href="/admin/users.php" class="<?php echo admin_nav_item_class('users', $activePage); ?>">
      <span class="nav-icon"></span>
      <span class="nav-label">JÁTÉKOS FIÓKOK KEZELÉSE</span>
    </a>

    <div class="nav-separator"></div>

    <!-- Későbbi menük -->
    <button class="nav-item nav-item-disabled" type="button" disabled>
      <span class="nav-icon">💎</span>
      <span class="nav-label">Bolt</span>
      <span class="nav-pill">Hamarosan</span>
    </button>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <span class="user-label">Bejelentkezve</span>
      <span class="user-name"><?php echo h($currentUsername); ?></span>
    </div>

    <form method="post" action="/admin/logout.php" class="sidebar-logout">
      <button type="submit" class="btn-logout">Kijelentkezés</button>
    </form>
  </div>
</aside>
