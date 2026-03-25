<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Admin jogosultság ellenőrzése (igazítsd a saját rendszeredhez, ha máshogy van!)
// require_once __DIR__ . '/_auth.php'; 
if (empty($_SESSION['is_user'])) {
    header('Location: /auth/login.php');
    exit;
}
// Itt egy extra ellenőrzés kéne, hogy tényleg admin-e, de egyelőre feltételezzük, hogy az admin mappát csak ők érik el.

require_once __DIR__ . '/../database.php';

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_username'];
$action = $_GET['action'] ?? 'list';
$msg = '';

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatTicketId($id) { return sprintf("#%03d-%03d", floor($id / 1000), $id % 1000); }
function formatHungarianDate($datetime) {
    $months = ['', 'Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'];
    $ts = strtotime($datetime);
    return date('Y.', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('d - H:i', $ts);
}

// ====================================================================================
// ADMIN TICKET MŰVELETEK
// ====================================================================================
if (isset($_GET['do']) && isset($_GET['id'])) {
    $do = $_GET['do'];
    $ticket_id = (int)$_GET['id'];
    
    $botMsg = "";
    $newStatus = null;
    
    if ($do === 'claim') {
        $pdo->prepare("UPDATE tickets SET claimed_by = ? WHERE id = ?")->execute([$admin_id, $ticket_id]);
        $botMsg = "[SYSTEM] **" . h($admin_name) . "** adminisztrátor csatlakozott, és megkezdte a hibajegy feldolgozását.";
    } elseif ($do === 'unclaim') {
        $pdo->prepare("UPDATE tickets SET claimed_by = NULL WHERE id = ?")->execute([$ticket_id]);
        $botMsg = "[SYSTEM] **" . h($admin_name) . "** adminisztrátor lemondott a hibajegyről. Egy másik kolléga hamarosan átveszi.";
    } elseif ($do === 'pause') {
        $newStatus = 'paused';
        $botMsg = "[SYSTEM] A hibajegy **szüneteltetve** lett. Kérjük, várj türelemmel a további intézkedésig.";
    } elseif ($do === 'unpause') {
        $newStatus = 'open';
        $botMsg = "[SYSTEM] A hibajegy szüneteltetése feloldva.";
    } elseif ($do === 'close') {
        $newStatus = 'closed';
        $botMsg = "[SYSTEM] A hibajegyet az adminisztrátor **lezárta**.";
    }

    if ($newStatus) {
        $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $ticket_id]);
    }
    if ($botMsg) {
        $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin) VALUES (?, ?, ?, 1)")
            ->execute([$ticket_id, $admin_id, $botMsg]);
    }
    header("Location: /admin/tickets.php?action=view&id=" . $ticket_id);
    exit;
}

// ====================================================================================
// ADMIN VÁLASZ KÜLDÉSE (Base64)
// ====================================================================================
function uploadImageAsBase64($fileArray) {
    if (isset($fileArray) && $fileArray['error'] === UPLOAD_ERR_OK) {
        $tmpName = $fileArray['tmp_name'];
        if ($fileArray['size'] > 5 * 1024 * 1024) return null; 
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) && getimagesize($tmpName) !== false) {
            return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($tmpName));
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reply' && isset($_GET['id'])) {
    $ticket_id = (int)$_GET['id'];
    $message = trim($_POST['message'] ?? '');
    $attachment = uploadImageAsBase64($_FILES['attachment'] ?? null);
    
    if ($message !== '' || $attachment !== null) {
        // AZ IS_ADMIN = 1 KRITIKUS! ETTŐL LESZ STAFF JELVÉNYE A PUBLIKUS OLDALON!
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$ticket_id, $admin_id, $message, $attachment]);
        // Átállítjuk a státuszt, hogy a játékos lássa: válaszoltak!
        $pdo->prepare("UPDATE tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);
        header("Location: /admin/tickets.php?action=view&id=" . $ticket_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Ticket Kezelő | Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/tickets.css?v=<?= time(); ?>">
</head>
<body>

<div class="admin-layout">
    <main class="admin-main">
        <header class="admin-header">
            <h1>Ügyfélszolgálat (Tickets)</h1>
            <div class="admin-user-info">Bejelentkezve mint: <strong><?= h($admin_name) ?></strong></div>
        </header>

        <div class="admin-content">
            
            <?php if ($action === 'list'): ?>
                <?php
                // Lekérjük az összes ticketet, a készítő nevével és az admin nevével (aki lefoglalta)
                $stmt = $pdo->query("
                    SELECT t.*, u.username as creator_name, a.username as admin_name 
                    FROM tickets t 
                    LEFT JOIN users u ON t.user_id = u.id 
                    LEFT JOIN users a ON t.claimed_by = a.id 
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
                                <tr>
                                    <td><strong><?= formatTicketId($t['id']) ?></strong></td>
                                    <td>
                                        <div style="font-size: 0.8rem; color: #a855f7; text-transform: uppercase; font-weight: 700;"><?= h($t['category']) ?></div>
                                        <div style="font-weight: 600;"><?= h($t['subject']) ?></div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <img src="https://minotar.net/helm/<?= h($t['creator_name']) ?>/24.png" style="border-radius: 4px;">
                                            <?= h($t['creator_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($t['claimed_by']): ?>
                                            <span class="badge badge-claimed"><?= h($t['admin_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-unclaimed">Nincs felelős</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$t['status']] ?></span></td>
                                    <td style="font-size: 0.85rem; color: #94a3b8;"><?= formatHungarianDate($t['updated_at']) ?></td>
                                    <td><a href="?action=view&id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">Megnyitás</a></td>
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

                if (!$ticket) die('Ticket nem található!');

                $msgStmt = $pdo->prepare("SELECT tm.*, u.username FROM ticket_messages tm JOIN users u ON tm.sender_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
                $msgStmt->execute([$ticket_id]);
                $messages = $msgStmt->fetchAll();

                $statusClass = 'status-' . $ticket['status'];
                $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
                ?>

                <div class="admin-ticket-layout">
                    
                    <div class="chat-container glass" style="flex: 1;">
                        <div class="chat-header">
                            <div class="chat-title-area">
                                <h2><span style="color: #94a3b8;"><?= formatTicketId($ticket['id']) ?></span> <?= h($ticket['subject']) ?></h2>
                                <span class="badge" style="background: rgba(168,85,247,0.2); color: #c084fc;"><?= h($ticket['category']) ?></span>
                            </div>
                            <span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$ticket['status']] ?></span>
                        </div>

                        <div class="chat-messages" id="chat-messages">
                            <?php foreach ($messages as $m): ?>
                                <?php 
                                    $isSystem = (strpos($m['message'], '[SYSTEM]') === 0);
                                    // ADMIN NÉZET: A mi üzenetünk kerül jobb oldalra (mine)!
                                    $isMine = (!$isSystem && $m['sender_id'] == $admin_id); 
                                    $wrapperClass = $isSystem ? 'admin system-msg' : ($isMine ? 'mine' : 'admin');
                                    
                                    if ($isSystem) {
                                        $avatarUrl = '/assets/img/etherniareborn.png';
                                        $authorName = 'ETHERNIA BOT';
                                        $badge = 'SYSTEM';
                                        $cleanMessage = trim(substr($m['message'], 8));
                                    } else {
                                        $avatarUrl = 'https://minotar.net/helm/' . h($m['username']) . '/32.png';
                                        $authorName = h($m['username']);
                                        $badge = $m['is_admin'] ? 'STAFF' : ($m['sender_id'] == $ticket['user_id'] ? 'JÁTÉKOS' : '');
                                        $cleanMessage = h($m['message']);
                                    }
                                ?>
                                <div class="chat-bubble-wrapper <?= $wrapperClass ?>">
                                    <img src="<?= $avatarUrl ?>" alt="Avatar" class="chat-avatar" <?= $isSystem ? 'style="object-fit: contain; background: rgba(0,0,0,0.5);"' : '' ?>>
                                    <div class="chat-content">
                                        <div class="chat-meta">
                                            <span class="chat-author"><?= $authorName ?> <?= $badge ? '<span class="admin-badge">'.$badge.'</span>' : '' ?></span>
                                            <span class="chat-time"><?= formatHungarianDate($m['created_at']) ?></span>
                                        </div>
                                        <?php if ($cleanMessage !== ''): ?>
                                            <div class="chat-text"><?= nl2br($cleanMessage) ?></div>
                                        <?php endif; ?>
                                        <?php if ($m['attachment']): ?>
                                            <div class="chat-attachment">
                                                <a href="<?= $m['attachment'] ?>" target="_blank"><img src="<?= $m['attachment'] ?>"></a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($ticket['status'] !== 'closed'): ?>
                            <div class="chat-input-area">
                                <div id="image-preview-container" class="image-preview-container" style="display: none;">
                                    <img id="image-preview" src="">
                                    <button type="button" id="remove-image-btn" class="remove-image-btn"><span class="material-symbols-rounded">close</span></button>
                                </div>
                                <form method="POST" action="?action=reply&id=<?= $ticket_id ?>" enctype="multipart/form-data" class="chat-form">
                                    <label class="chat-upload-btn" title="Kép csatolása">
                                        <span class="material-symbols-rounded">image</span>
                                        <input type="file" name="attachment" accept="image/*" style="display: none;" id="chat-file-input">
                                    </label>
                                    <textarea name="message" placeholder="Admin válasz küldése (Enter)..." class="chat-textarea"></textarea>
                                    <button type="submit" class="chat-send-btn"><span class="material-symbols-rounded">send</span></button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="chat-closed-alert">Ez a hibajegy le lett zárva. Csak újranyitás után lehet válaszolni.</div>
                        <?php endif; ?>
                    </div>

                    <div class="admin-controls glass" style="width: 320px;">
                        <h3>Műveletek</h3>
                        <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1.5rem;">Játékos: <strong><?= h($ticket['creator_name']) ?></strong></p>

                        <div class="control-actions">
                            <?php if ($ticket['claimed_by'] === null): ?>
                                <a href="?do=claim&id=<?= $ticket_id ?>" class="btn btn-action btn-claim"><span class="material-symbols-rounded">pan_tool</span> Magamra vállalom</a>
                            <?php elseif ($ticket['claimed_by'] == $admin_id): ?>
                                <a href="?do=unclaim&id=<?= $ticket_id ?>" class="btn btn-action btn-warning"><span class="material-symbols-rounded">waving_hand</span> Lemondok róla</a>
                            <?php else: ?>
                                <div class="profile-alert warning" style="padding: 0.8rem; font-size: 0.8rem;">Ezt a jegyet már egy másik admin lefoglalta.</div>
                            <?php endif; ?>

                            <hr class="control-divider">

                            <?php if ($ticket['status'] !== 'paused' && $ticket['status'] !== 'closed'): ?>
                                <a href="?do=pause&id=<?= $ticket_id ?>" class="btn btn-action btn-warning"><span class="material-symbols-rounded">pause_circle</span> Szüneteltetés</a>
                            <?php elseif ($ticket['status'] === 'paused'): ?>
                                <a href="?do=unpause&id=<?= $ticket_id ?>" class="btn btn-action btn-claim"><span class="material-symbols-rounded">play_circle</span> Folytatás (Feloldás)</a>
                            <?php endif; ?>

                            <?php if ($ticket['status'] !== 'closed'): ?>
                                <a href="?do=close&id=<?= $ticket_id ?>" class="btn btn-action btn-danger"><span class="material-symbols-rounded">lock</span> Jegy Lezárása</a>
                            <?php else: ?>
                                <a href="?do=unpause&id=<?= $ticket_id ?>" class="btn btn-action btn-claim"><span class="material-symbols-rounded">lock_open</span> Jegy Újranyitása</a>
                            <?php endif; ?>
                            
                            <hr class="control-divider">
                            <a href="/admin/tickets.php" class="btn btn-action" style="border-color: #64748b; color: #cbd5e1;"><span class="material-symbols-rounded">arrow_back</span> Vissza a listához</a>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="/assets/js/support.js?v=<?= time(); ?>"></script>
</body>
</html>