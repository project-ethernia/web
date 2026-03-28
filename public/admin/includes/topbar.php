<header class="admin-topbar glass">
    <div class="topbar-left">
        <div class="topbar-icon">
            <span class="material-symbols-rounded"><?= $topbar_icon ?? 'dashboard' ?></span>
        </div>
        <div class="topbar-title">
            <h1><?= $topbar_title ?? 'Vezérlőpult' ?></h1>
            <p class="topbar-subtitle" id="greeting-subtitle"><?= $topbar_subtitle ?? '' ?></p>
        </div>
    </div>
</header>
<div class="admin-content">
    <?php displayFlash(); ?>