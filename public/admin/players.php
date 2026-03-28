<?php
$current_page = 'players';
require_once __DIR__ . '/includes/core.php';

if (!hasPermission($admin_role, 'all')) {
    setFlash('error', 'Nincs jogosultságod az élő szerver kezeléséhez!');
    header('Location: /admin/index.php');
    exit;
}

$page_title = 'Szerver Konzol | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/players.css'];
$extra_js = ['/admin/assets/js/players.js'];
$topbar_icon = 'terminal';
$topbar_title = 'Élő Szerver Konzol (RCON)';
$topbar_subtitle = 'Parancsok küldése és válaszok fogadása valós időben';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="split-layout" style="display: flex; gap: 2rem;">
    
    <div class="admin-panel glass" style="flex: 2; display: flex; flex-direction: column;">
        <div class="panel-header">
            <h2><span class="material-symbols-rounded">code_blocks</span> Rendszer Terminál</h2>
        </div>
        <div class="panel-body">
            
            <div id="rcon-terminal-output" class="terminal-box">
                <div class="terminal-line" style="color: var(--text-muted);">Kapcsolódás előkészítve a <strong>play.ethernia.hu</strong> szerverhez...</div>
                <div class="terminal-line" style="color: var(--text-muted); margin-bottom: 1rem;">Írj be egy parancsot a lentebb található mezőbe! (A "/" jel nem szükséges)</div>
            </div>

            <form id="rcon-form" class="rcon-form-inline">
                <span class="prompt-icon">></span>
                <input type="text" id="rcon-input" class="admin-input" placeholder="pl: list, say Hello, op Notch..." autocomplete="off" style="font-family: monospace; font-size: 1.1rem; flex: 1;">
                <button type="submit" class="btn-action btn-claim" style="margin: 0;">
                    <span class="material-symbols-rounded">send</span> Küldés
                </button>
            </form>

        </div>
    </div>

    <div class="admin-panel glass" style="flex: 1; height: fit-content;">
        <div class="panel-header">
            <h2><span class="material-symbols-rounded">offline_bolt</span> Gyors Akciók</h2>
        </div>
        <div class="panel-body" style="display: flex; flex-direction: column; gap: 1rem;">
            
            <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5;">Kattints a gombokra, hogy azonnal lefuttasd őket a bal oldali terminálban!</p>
            
            <button class="btn-action btn-back" style="justify-content: flex-start;" onclick="document.getElementById('rcon-input').value = 'list'; document.getElementById('rcon-form').dispatchEvent(new Event('submit'));">
                <span class="material-symbols-rounded">group</span> Online Játékosok Lekérése (/list)
            </button>
            
            <button class="btn-action btn-back" style="justify-content: flex-start;" onclick="document.getElementById('rcon-input').value = 'tps'; document.getElementById('rcon-form').dispatchEvent(new Event('submit'));">
                <span class="material-symbols-rounded">speed</span> Szerver TPS Lekérése (/tps)
            </button>
            
            <button class="btn-action btn-back" style="justify-content: flex-start;" onclick="document.getElementById('rcon-input').value = 'save-all'; document.getElementById('rcon-form').dispatchEvent(new Event('submit'));">
                <span class="material-symbols-rounded">save</span> Világ Mentése (/save-all)
            </button>
            
            <hr style="border-top: 1px solid var(--admin-border); margin: 0.5rem 0; width: 100%;">
            
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--admin-red); border-radius: 8px; padding: 1rem; color: #fff; font-size: 0.85rem;">
                <strong style="color: var(--admin-red); display: block; margin-bottom: 0.3rem;"><span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">warning</span> Figyelem!</strong>
                A konzolból kiadott parancsok azonnal végrehajtódnak a szerveren, és minden művelet naplózásra kerül a Te azonosítóddal!
            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>