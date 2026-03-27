<header class="admin-header glass">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon"><?= $topbar_icon ?? 'dashboard' ?></span>
                <div>
                    <h1 style="margin: 0 0 0.3rem 0;"><?= $topbar_title ?? 'Vezérlőpult' ?></h1>
                    <p class="subtitle" id="greeting-subtitle" style="margin: 0;"><?= $topbar_subtitle ?? '' ?></p>
                </div>
            </div>
        </header>
        <div class="admin-content">
            <?php displayFlash(); ?>