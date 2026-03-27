<?php
$current_page = 'tickets';
require_once __DIR__ . '/includes/core.php';

$action = $_GET['action'] ?? 'list';

function formatTicketId($id) { return sprintf("#%03d-%03d", floor($id / 1000), $id % 1000); }
function formatHungarianDate($datetime) {
    $ts = strtotime($datetime);
    return date('Y. m. d. - H:i', $ts);
}

if (isset($_GET['do']) && isset($_GET['id'])) {
    $do = $_GET['do'];
    $ticket_id = (int)$_GET['id'];
    
    $botMsg = "";
    $newStatus = null;
    $logMessage = "";
    
    if ($do === 'claim') {
        $pdo->prepare("UPDATE tickets SET claimed_by = ? WHERE id = ?")->execute([$admin_id, $ticket_id]);
        $botMsg = "[SYSTEM] **" . h($admin_name) . "** adminisztrátor csatlakozott, és megkezdte a hibajegy feldolgozását.";
        $logMessage = "Magára vállalta a #" . $ticket_id . " azonosítójú hibajegyet.";
        setFlash('success', 'Hibajegy magadra vállalva.');
    } elseif ($do === 'unclaim') {
        $pdo->prepare("UPDATE tickets SET claimed_by = NULL WHERE id = ?")->execute([$ticket_id]);
        $botMsg = "[SYSTEM] **" . h($admin_name) . "** adminisztrátor lemondott a hibajegyről. Egy másik kolléga hamarosan átveszi.";
        $logMessage = "Lemondott a #" . $ticket_id . " azonosítójú hibajegyről.";
        setFlash('warning', 'Hibajegyről lemondva.');
    } elseif ($do === 'pause') {
        $newStatus = 'paused';
        $botMsg = "[SYSTEM] A hibajegy **szüneteltetve** lett. Kérjük, várj türelemmel a további intézkedésig.";
        $logMessage = "Szüneteltette a #" . $ticket_id . " azonosítójú hibajegyet.";
        setFlash('warning', 'Hibajegy szüneteltetve.');
    } elseif ($do === 'unpause') {
        $newStatus = 'open';
        $botMsg = "[SYSTEM] A hibajegy szüneteltetése feloldva.";
        $logMessage = "Feloldotta a #" . $ticket_id . " azonosítójú hibajegy szüneteltetését.";
        setFlash('success', 'Hibajegy feloldva.');
    } elseif ($do === 'close') {
        $newStatus = 'closed';
        $botMsg = "[SYSTEM] A hibajegyet az adminisztrátor **lezárta**.";
        $logMessage = "Lezárta a #" . $ticket_id . " azonosítójú hibajegyet.";
        setFlash('error', 'Hibajegy véglegesen lezárva.');
    }

    if ($newStatus) {
        $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $ticket_id]);
    }
    if ($botMsg) {
        $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin) VALUES (?, ?, ?, 1)")
            ->execute([$ticket_id, $admin_id, $botMsg]);
    }

    if ($logMessage !== "") {
        log_admin_action($pdo, $admin_id, $admin_name, $logMessage);
    }

    header("Location: /admin/tickets.php?action=view&id=" . $ticket_id);
    exit;
}

$page_title = 'Ügyfélszolgálat | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/tickets.css'];
$extra_js = ['/assets/js/chat_engine.js']; // BEHÚZZUK A KÖZÖS MOTORT
$topbar_icon = 'support_agent';
$topbar_title = 'Ügyfélszolgálat (Tickets)';
$topbar_subtitle = 'Hibajegyek kezelése és játékos támogatás';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="support-container <?= $action === 'view' ? 'view-ticket-mode' : '' ?>">
    <?php if ($action === 'list'): ?>
        <?php
        $stmt = $pdo->query("
            SELECT t.*, u.username as creator_name, a.username as admin_name 
            FROM tickets t 
            LEFT JOIN users u ON t.user_id = u.id 
            LEFT JOIN admins a ON t.claimed_by = a.id 
            ORDER BY 
                CASE t.status WHEN 'open' THEN 1 WHEN 'answered' THEN 2 WHEN 'paused' THEN 3 ELSE 4 END, 
                t.updated_at DESC
        ");
        $tickets = $stmt->fetchAll();
        ?>
        <div class="admin-panel glass">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kategória & Tárgy</th>
                        <th>Játékos</th>
                        <th>Felelős Admin</th>
                        <th>Státusz</th>
                        <th>Utolsó frissítés</th>
                        <th>Művelet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <?php 
                            $statusClass = 'status-' . $t['status'];
                            $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
                        ?>
                        <tr class="hover-row">
                            <td class="td-id"><strong><?= formatTicketId($t['id']) ?></strong></td>
                            <td>
                                <div class="td-cat"><?= h($t['category']) ?></div>
                                <div class="td-subject"><?= h($t['subject']) ?></div>
                            </td>
                            <td>
                                <div class="player-cell">
                                    <img src="https://minotar.net/helm/<?= h($t['creator_name']) ?>/24.png" class="player-head">
                                    <?= h($t['creator_name']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if($t['claimed_by']): ?>
                                    <span class="badge badge-claimed"><span class="material-symbols-rounded">person</span> <?= h($t['admin_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-unclaimed">Nincs felelős</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$t['status']] ?></span></td>
                            <td class="td-date"><?= formatHungarianDate($t['updated_at']) ?></td>
                            <td><a href="?action=view&id=<?= $t['id'] ?>" class="btn-sm btn-open">Megnyitás</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($action === 'view' && isset($_GET['id'])): ?>
        <?php
        $ticket_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT t.*, u.username as creator_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();

        if (!$ticket) die('<div class="admin-panel glass"><h2>Hiba!</h2><p>Ticket nem található.</p></div>');

        $statusClass = 'status-' . $ticket['status'];
        $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
        ?>

        <div class="admin-ticket-layout">
            <div class="chat-container glass">
                <div class="chat-header">
                    <div class="chat-title-area">
                        <h2 style="margin: 0; font-size: 1.3rem;"><span class="text-muted"><?= formatTicketId($ticket['id']) ?></span> <?= h($ticket['subject']) ?></h2>
                        <span class="badge tag-category"><?= h($ticket['category']) ?></span>
                    </div>
                    <span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$ticket['status']] ?></span>
                </div>

                <input type="hidden" id="chat-ticket-id" value="<?= $ticket_id ?>">
                <input type="hidden" id="chat-context" value="admin"> <div class="chat-messages" id="chat-messages"></div>
                <div class="chat-messages" id="chat-messages">
                    <div class="typing-indicator" id="typing-indicator">
                        <span class="material-symbols-rounded">edit</span>
                        <span class="typing-text">A Játékos éppen ír</span>
                        <div class="typing-dots"><span></span><span></span><span></span></div>
                    </div>
                </div>

                <?php if ($ticket['status'] !== 'closed'): ?>
                    <div class="chat-input-area">
                        <div id="image-preview-container" class="image-preview-container" style="display: none;">
                            <img id="image-preview" src="">
                            <button type="button" id="remove-image-btn" class="remove-image-btn"><span class="material-symbols-rounded">close</span></button>
                        </div>
                        <form id="chat-form" class="chat-form">
                            <label class="chat-upload-btn" title="Kép csatolása">
                                <span class="material-symbols-rounded">image</span>
                                <input type="file" id="chat-file-input" name="attachment" accept="image/*" style="display: none;">
                            </label>
                            <textarea id="chat-textarea" name="message" placeholder="Admin válasz küldése (Enter)..." class="chat-textarea"></textarea>
                            <button type="submit" class="chat-send-btn" id="chat-submit-btn"><span class="material-symbols-rounded">send</span></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-closed-alert">
                        <span class="material-symbols-rounded">lock</span> Ez a hibajegy le lett zárva. Csak újranyitás után lehet válaszolni.
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-controls glass">
                <h3>Műveletek</h3>
                <p class="control-player">Játékos: <strong><?= h($ticket['creator_name']) ?></strong></p>

                <div class="control-actions">
                    <?php if ($ticket['claimed_by'] === null): ?>
                        <a href="?do=claim&id=<?= $ticket_id ?>" class="btn-action btn-claim"><span class="material-symbols-rounded">pan_tool</span> Magamra vállalom</a>
                    <?php elseif ($ticket['claimed_by'] == $admin_id): ?>
                        <a href="?do=unclaim&id=<?= $ticket_id ?>" class="btn-action btn-warning" onclick="ethConfirm(event, 'Biztosan lemondasz erről a jegyről?', this.href);"><span class="material-symbols-rounded">waving_hand</span> Lemondok róla</a>
                    <?php else: ?>
                        <div class="alert-box warning">Ezt a jegyet már egy másik admin lefoglalta.</div>
                    <?php endif; ?>

                    <hr class="control-divider">

                    <?php if ($ticket['status'] !== 'paused' && $ticket['status'] !== 'closed'): ?>
                        <a href="?do=pause&id=<?= $ticket_id ?>" class="btn-action btn-warning"><span class="material-symbols-rounded">pause_circle</span> Szüneteltetés</a>
                    <?php elseif ($ticket['status'] === 'paused'): ?>
                        <a href="?do=unpause&id=<?= $ticket_id ?>" class="btn-action btn-claim"><span class="material-symbols-rounded">play_circle</span> Folytatás (Feloldás)</a>
                    <?php endif; ?>

                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <a href="?do=close&id=<?= $ticket_id ?>" class="btn-action btn-danger" onclick="ethConfirm(event, 'Biztosan véglegesen lezárod a jegyet?', this.href);"><span class="material-symbols-rounded">lock</span> Jegy Lezárása</a>
                    <?php else: ?>
                        <a href="?do=unpause&id=<?= $ticket_id ?>" class="btn-action btn-claim"><span class="material-symbols-rounded">lock_open</span> Jegy Újranyitása</a>
                    <?php endif; ?>
                    
                    <hr class="control-divider">
                    <a href="/admin/tickets.php" class="btn-action btn-back"><span class="material-symbols-rounded">arrow_back</span> Vissza a listához</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>