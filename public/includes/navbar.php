<?php
// Biztonsági ellenőrzés: ha esetleg valamelyik fájlban elfelejtenénk megadni, ne dobjon hibát
$current_page = $current_page ?? '';
$remaining_time = $remaining_time ?? 3600;
$currentUser = $currentUser ?? 'Játékos';
?>
<nav class="navbar">
    <div class="navbar-inner glass">
        
        <div class="nav-side nav-left">
            <div class="session-timer glass" title="Automatikus kijelentkezés">
                <span class="material-symbols-rounded">timer</span>
                <span id="countdown-timer" data-seconds="<?= $remaining_time ?>">--:--</span>
            </div>
        </div>
        
        <ul class="nav-links">
            <li><a href="/" class="<?= $current_page === 'home' ? 'active' : '' ?>">Főoldal</a></li>
            <li><a href="/news.php" class="<?= $current_page === 'news' ? 'active' : '' ?>">Hírek</a></li>
            <li><a href="#" class="<?= $current_page === 'webshop' ? 'active' : '' ?>">Webshop</a></li>
            <li><a href="#" class="<?= $current_page === 'rules' ? 'active' : '' ?>">Szabályzat</a></li>
            <li><a href="#" class="<?= $current_page === 'stats' ? 'active' : '' ?>">Statisztikák</a></li>
            <li><a href="#" class="<?= $current_page === 'contact' ? 'active' : '' ?>">Kapcsolat</a></li>
        </ul>

        <div class="nav-side nav-right">
            <a href="/profile.php" class="user-badge glass hover-lift" style="text-decoration: none;">
                <img src="https://minotar.net/helm/<?= htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8'); ?>/32.png" alt="Skin">
                <span><?= htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <a href="/auth/logout.php" class="btn btn-logout">Kijelentkezés</a>
        </div>
        
    </div>
</nav>