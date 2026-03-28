<?php
$current_page = 'news';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod!');
    header('Location: /admin/index.php');
    exit;
}

$page_title = 'Hírek Kezelése | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/news.css'];
$extra_js = ['/admin/assets/js/news.js'];
$topbar_icon = 'newspaper';
$topbar_title = 'Hírek & Bejelentések';
$topbar_subtitle = 'Weboldal főoldali híreinek élő szerkesztése';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="split-layout">
    <div class="list-panel">
        <div class="search-panel glass" style="margin-bottom: 1.5rem; padding: 1.5rem; display: flex; align-items: center; border-radius: 12px;">
            <span class="material-symbols-rounded" style="margin-right: 1rem; color: var(--text-muted);">search</span>
            <input type="text" id="news-search" class="admin-input" placeholder="Élő keresés a hírek között..." style="border: none; background: transparent; padding: 0; box-shadow: none; font-size: 1.1rem; flex: 1; outline: none;">
        </div>

        <div class="glass" style="border-radius: 12px; overflow: hidden;">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Cím & Kategória</th><th>Szerző</th><th>Láthatóság</th><th>Műveletek</th></tr></thead>
                <tbody id="news-tbody"></tbody>
            </table>
        </div>
    </div>

    <div class="form-panel glass" style="border-radius: 12px;">
        <div class="panel-header"><h2><span class="material-symbols-rounded">add_circle</span> Új hír írása</h2></div>
        <div class="panel-body">
            <form id="news-form" class="add-news-form">
                <input type="hidden" id="news-action" name="action" value="add">
                <input type="hidden" id="news-id" name="id" value="">
                
                <div class="input-group">
                    <label>Hír Címe</label>
                    <input type="text" id="news-title" name="title" class="admin-input" required>
                </div>
                
                <div class="input-group">
                    <label>Kategória</label>
                    <select id="news-category" name="category" class="admin-input" required>
                        <option value="Karbantartás">Karbantartás</option>
                        <option value="Frissítés">Frissítés</option>
                        <option value="Bejelentés">Bejelentés</option>
                        <option value="Esemény">Esemény</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Tartalom (HTML engedélyezett)</label>
                    <textarea id="news-content" name="content" class="admin-input" rows="8" required></textarea>
                </div>

                <label class="checkbox-container" style="margin-top: 1rem;">
                    <input type="checkbox" id="news-published" name="is_published" value="1" checked>
                    <span class="checkmark"></span>
                    Azonnali közzététel a weboldalon
                </label>

                <button type="submit" class="btn-action btn-claim" style="width: 100%; margin-top: 1.5rem;">Közzététel / Mentés</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>