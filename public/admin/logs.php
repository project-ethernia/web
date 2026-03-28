<?php
$current_page = 'logs';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod a rendszernaplók megtekintéséhez!');
    header('Location: /admin/index.php');
    exit;
}

$page_title = 'Műveletnapló | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/logs.css', '/admin/assets/css/admins.css']; 
$topbar_icon = 'manage_search';
$topbar_title = 'Műveletnapló (Audit Log)';
$topbar_subtitle = 'A rendszerben történt összes adminisztrátori tevékenység élő nézete';
$extra_js = ['/admin/assets/js/logs.js'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="admin-panel glass" style="padding: 1.5rem; margin-bottom: 1rem;">
    <div style="display: flex; gap: 1rem; align-items: center;">
        <div style="flex: 1; position: relative;">
            <span class="material-symbols-rounded" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">search</span>
            <input type="text" id="log-search" class="admin-input" placeholder="Élő keresés admin név vagy esemény alapján..." style="padding-left: 3rem;">
        </div>
    </div>
</div>

<div class="admin-panel glass">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Adminisztrátor</th>
                <th>Esemény (Akció)</th>
                <th>IP Cím</th>
                <th>Időpont</th>
                <th>Technikai infó</th>
            </tr>
        </thead>
        <tbody id="logs-tbody">
            </tbody>
    </table>
</div>

<div id="log-modal" class="modal-overlay">
    <div class="modal-container glass">
        <button class="modal-close"><span class="material-symbols-rounded">close</span></button>
        <h3 class="modal-title" style="margin-top: 0;">Napló Részletek</h3>
        <div class="log-details">
            <div class="detail-item">
                <label>Eszköz / Böngésző (User-Agent)</label>
                <div class="detail-value" id="log-ua"></div>
            </div>
            <div class="detail-item">
                <label>További kontextus adatok (JSON)</label>
                <div class="detail-value" id="log-context" style="white-space: pre-wrap; font-family: monospace;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="log-close-btn" class="btn-action btn-back">Bezárás</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>