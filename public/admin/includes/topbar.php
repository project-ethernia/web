<header class="admin-header glass" style="width: 100%; flex-shrink: 0;">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon"><?= $topbar_icon ?? 'dashboard' ?></span>
                <div>
                    <h1 style="margin: 0 0 0.3rem 0;"><?= $topbar_title ?? 'Vezérlőpult' ?></h1>
                    <p class="subtitle" id="greeting-subtitle" style="margin: 0;"><?= $topbar_subtitle ?? '' ?></p>
                </div>
            </div>
        </header>
        <div class="admin-content" style="width: 100%; display: flex; flex-direction: column; gap: 2rem; padding-top: 1rem;">
            <?php displayFlash(); ?>