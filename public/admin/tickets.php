<?php
$current_page = 'tickets';
require_once __DIR__ . '/includes/core.php';

$action = $_GET['action'] ?? 'list';

function formatTicketId($id) { return sprintf("#%03d-%03d", floor($id / 1000), $id % 1000); }
function formatHungarianDate($datetime) { return date('Y. m. d. - H:i', strtotime($datetime)); }

$page_title = 'Ügyfélszolgálat | ETHERNIA Admin';
$extra_css = ['/admin/assets/css/tickets.css'];

// Itt behúztuk az új, lefordított scriptet a régi chat.js mellé!
$extra_js = ['/admin/assets/js/chat.js', '/admin/assets/js/ticket_actions.js']; 

$topbar_icon = 'support_agent';
$topbar_title = 'Ügyfélszolgálat (Tickets)';
$topbar_subtitle = 'Hibajegyek kezelése és játékos támogatás';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="support-container <?= $action === 'view' ? 'view-ticket-mode' : '' ?>">
    <?php if ($action === 'list'): ?>
        <?php
        $stmt = $pdo->query("SELECT t.*, u.username as creator_name, a.username as admin_name FROM tickets t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN admins a ON t.claimed_by = a.id ORDER BY CASE t.status WHEN 'open' THEN 1 WHEN 'answered' THEN 2 WHEN 'paused' THEN 3 ELSE 4 END, t.updated_at DESC");
        $tickets = $stmt->fetchAll();
        ?>
        <div class="admin-panel glass">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>Kategória & Tárgy</th><th>Játékos</th><th>Felelős</th><th>Státusz</th><th>Frissítés</th><th>Művelet</th></tr></thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <?php 
                            $statusBadgeClass = ['open' => 'success', 'answered' => 'info', 'paused' => 'warning', 'closed' => 'error'];
                            $bClass = $statusBadgeClass[$t['status']] ?? 'default';
                            $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
                        ?>
                        <tr class="hover-row">
                            <td class="td-id"><strong><?= formatTicketId($t['id']) ?></strong></td>
                            <td>
                                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; margin-bottom: 0.2rem;"><?= h($t['category']) ?></div>
                                <div style="font-weight: 600; font-size: 1.05rem;"><?= h($t['subject']) ?></div>
                            </td>
                            <td><div class="player-cell"><img src="https://minotar.net/helm/<?= h($t['creator_name']) ?>/24.png" class="player-head"><?= h($t['creator_name']) ?></div></td>
                            <td><?= $t['claimed_by'] ? '<span class="badge info"><span class="material-symbols-rounded" style="font-size: 1.1rem;">person</span> '.h($t['admin_name']).'</span>' : '<span class="badge default">Nincs felelős</span>' ?></td>
                            <td><span class="badge <?= $bClass ?>"><?= $statusTexts[$t['status']] ?></span></td>
                            <td class="td-date" style="color: var(--text-muted); font-size: 0.85rem;"><?= formatHungarianDate($t['updated_at']) ?></td>
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
        if (!$ticket) die('<div class="admin-panel glass"><h2>Hiba! Jegy nem található.</h2></div>');

        $statusBadgeClass = ['open' => 'success', 'answered' => 'info', 'paused' => 'warning', 'closed' => 'error'];
        $bClass = $statusBadgeClass[$ticket['status']] ?? 'default';
        $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
        ?>

        <div class="admin-ticket-layout">
            <div class="chat-container glass">
                <div class="chat-header" style="padding: 1.5rem; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
                    <div class="chat-title-area">
                        <h2 style="margin: 0 0 0.5rem 0; font-size: 1.3rem; font-family: inherit;"><span class="text-muted" style="color: var(--text-muted); margin-right: 0.5rem;"><?= formatTicketId($ticket['id']) ?></span> <?= h($ticket['subject']) ?></h2>
                        <span class="badge default"><?= h($ticket['category']) ?></span>
                    </div>
                    <span class="badge <?= $bClass ?>"><?= $statusTexts[$ticket['status']] ?></span>
                </div>

                <input type="hidden" id="chat-ticket-id" value="<?= $ticket_id ?>">
                <input type="hidden" id="chat-context" value="admin">
                
                <div class="chat-messages" id="chat-messages">
                    <div class="typing-indicator" id="typing-indicator">
                        <span class="material-symbols-rounded">edit</span>
                        <span class="typing-text">A Játékos éppen ír</span>
                        <div class="typing-dots"><span></span><span></span><span></span></div>
                    </div>
                </div>

                <?php if ($ticket['status'] !== 'closed'): ?>
                    <div class="chat-input-area" style="padding: 1.5rem; border-top: 1px solid var(--admin-border);">
                        <div id="image-preview-container" class="image-preview-container" style="display: none;">
                            <img id="image-preview" src="">
                            <button type="button" id="remove-image-btn" class="remove-image-btn"><span class="material-symbols-rounded">close</span></button>
                        </div>
                        <form id="chat-form" class="chat-form" style="display: flex; gap: 1rem; align-items: flex-end;">
                            <label class="chat-upload-btn" title="Kép csatolása" style="cursor: pointer; padding: 0.8rem; background: rgba(0,0,0,0.3); border-radius: 8px; border: 1px solid var(--admin-border); color: var(--text-muted); transition: 0.3s;">
                                <span class="material-symbols-rounded">image</span>
                                <input type="file" id="chat-file-input" name="attachment" accept="image/*" style="display: none;">
                            </label>
                            <textarea id="chat-textarea" name="message" placeholder="Admin válasz küldése (Enter)..." class="eth-input" style="min-height: 50px; resize: vertical;"></textarea>
                            <button type="submit" class="btn-primary" id="chat-submit-btn"><span class="material-symbols-rounded">send</span></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-closed-alert" style="padding: 1.5rem; text-align: center; color: var(--admin-red); font-weight: bold; background: rgba(239, 68, 68, 0.1); border-top: 1px solid var(--admin-border);">
                        <span class="material-symbols-rounded" style="vertical-align: middle;">lock</span> Ez a hibajegy le lett zárva. Csak újranyitás után lehet válaszolni.
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-controls glass">
                <h3 style="margin-bottom: 1rem; color: #fff; text-transform: uppercase; font-size: 1.1rem; font-family: inherit;">Műveletek</h3>
                <p class="control-player" style="color: var(--text-muted); margin-bottom: 1.5rem;">Játékos: <strong style="color: #fff;"><?= h($ticket['creator_name']) ?></strong></p>

                <div class="control-actions" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php if ($ticket['claimed_by'] === null): ?>
                        <button onclick="doTicketAction('claim', <?= $ticket_id ?>)" class="btn-action btn-claim"><span class="material-symbols-rounded">pan_tool</span> Magamra vállalom</button>
                    <?php elseif ($ticket['claimed_by'] == $admin_id): ?>
                        <button onclick="doTicketAction('unclaim', <?= $ticket_id ?>, 'Biztosan lemondasz erről a jegyről?')" class="btn-action btn-warning"><span class="material-symbols-rounded">waving_hand</span> Lemondok róla</button>
                    <?php else: ?>
                        <div class="alert-box warning" style="padding: 1rem; font-size: 0.85rem; background: rgba(245, 158, 11, 0.15); border: 1px solid var(--admin-warning); border-radius: 8px; color: var(--admin-warning); text-align: center;">Ezt a jegyet már egy másik admin lefoglalta.</div>
                    <?php endif; ?>

                    <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 0.5rem 0;">

                    <?php if ($ticket['status'] !== 'paused' && $ticket['status'] !== 'closed'): ?>
                        <button onclick="doTicketAction('pause', <?= $ticket_id ?>)" class="btn-action btn-warning"><span class="material-symbols-rounded">pause_circle</span> Szüneteltetés</button>
                    <?php elseif ($ticket['status'] === 'paused'): ?>
                        <button onclick="doTicketAction('unpause', <?= $ticket_id ?>)" class="btn-action btn-claim"><span class="material-symbols-rounded">play_circle</span> Feloldás</button>
                    <?php endif; ?>

                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <button onclick="doTicketAction('close', <?= $ticket_id ?>, 'Biztosan véglegesen lezárod a jegyet?')" class="btn-action btn-danger"><span class="material-symbols-rounded">lock</span> Jegy Lezárása</button>
                    <?php else: ?>
                        <button onclick="doTicketAction('unpause', <?= $ticket_id ?>)" class="btn-action btn-claim"><span class="material-symbols-rounded">lock_open</span> Jegy Újranyitása</button>
                    <?php endif; ?>
                    
                    <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 0.5rem 0;">
                    <a href="/admin/tickets.php" class="btn-action btn-back"><span class="material-symbols-rounded">arrow_back</span> Vissza a listához</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>