<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../database.php';

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_username'];
$action = $_GET['action'] ?? 'list';
$msg = '';

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatTicketId($id) { return sprintf("#%03d-%03d", floor($id / 1000), $id % 1000); }
function formatHungarianDate($datetime) {
    $months = ['', 'Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'];
    $ts = strtotime($datetime);
    return date('Y.', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('d - H:i', $ts);
}

// ==================================================================================
// ÚJ: ADMIN REAL-TIME AJAX SZINKRONIZÁLÓ
// ==================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'sync' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $ticket_id = (int)$_GET['id'];
    $last_id = (int)($_GET['last_id'] ?? 0);
    $typing = (int)($_GET['typing'] ?? 0);

    if ($typing) {
        $pdo->prepare("UPDATE tickets SET admin_typing_at = NOW() WHERE id = ?")->execute([$ticket_id]);
    }

    $stmt = $pdo->prepare("SELECT user_typing_at FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $user_typing_at = $stmt->fetchColumn();
    $is_user_typing = ($user_typing_at && strtotime($user_typing_at) >= time() - 3);

    $msgStmt = $pdo->prepare("SELECT tm.*, u.username, a.username as admin_username FROM ticket_messages tm LEFT JOIN users u ON tm.sender_id = u.id LEFT JOIN admins a ON tm.sender_id = a.id WHERE tm.ticket_id = ? AND tm.id > ? ORDER BY tm.id ASC");
    $msgStmt->execute([$ticket_id, $last_id]);
    $messages = $msgStmt->fetchAll();

    $html = '';
    $new_last_id = $last_id;

    foreach ($messages as $m) {
        $new_last_id = $m['id'];
        $isSystem = (strpos($m['message'], '[SYSTEM]') === 0);
        $isMine = (!$isSystem && $m['is_admin'] == 1 && $m['sender_id'] == $admin_id);

        if ($isSystem) {
            $cleanMessage = h(trim(substr($m['message'], 8)));
            $html .= '<div class="system-msg-simple" data-id="'.$m['id'].'"><span class="material-symbols-rounded">info</span>' . nl2br($cleanMessage) . '</div>';
        } else {
            $wrapperClass = $isMine ? 'mine' : 'player';
            $avatarUrl = 'https://minotar.net/helm/' . h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']) . '/32.png';
            $authorName = h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']);
            $badge = $m['is_admin'] ? 'STAFF' : 'JÁTÉKOS';
            $cleanMessage = h($m['message']);
            $dateStr = formatHungarianDate($m['created_at']);

            $html .= '<div class="chat-bubble-wrapper ' . $wrapperClass . '" data-id="'.$m['id'].'">';
            $html .= '<img src="' . $avatarUrl . '" alt="Avatar" class="chat-avatar">';
            $html .= '<div class="chat-content">';
            $html .= '<div class="chat-meta"><span class="chat-author">' . $authorName;
            if ($badge) $html .= ' <span class="role-badge role-'.$badge.'">' . $badge . '</span>';
            $html .= '</span><span class="chat-time">' . $dateStr . '</span></div>';
            if ($cleanMessage !== '') $html .= '<div class="chat-text">' . nl2br($cleanMessage) . '</div>';
            if ($m['attachment']) {
                $html .= '<div class="chat-attachment" ' . ($cleanMessage === '' ? 'style="margin-top: 0;"' : '') . '><a href="' . h($m['attachment']) . '" target="_blank"><img src="' . h($m['attachment']) . '"></a></div>';
            }
            $html .= '</div></div>';
        }
    }

    echo json_encode(['html' => $html, 'last_id' => $new_last_id, 'other_typing' => $is_user_typing]);
    exit;
}
// ==================================================================================

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
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$ticket_id, $admin_id, $message, $attachment]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/tickets.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    
    <main class="admin-main">
        <header class="admin-header glass">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon">support_agent</span>
                <div>
                    <h1>Ügyfélszolgálat (Tickets)</h1>
                    <p class="subtitle">Hibajegyek kezelése és játékos támogatás</p>
                </div>
            </div>
            <div class="admin-user-info">
                Bejelentkezve mint: <strong class="text-red"><?= h($admin_name) ?></strong>
            </div>
        </header>

        <div class="admin-content">
            
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

                $msgStmt = $pdo->prepare("SELECT tm.*, u.username, a.username as admin_username FROM ticket_messages tm LEFT JOIN users u ON tm.sender_id = u.id LEFT JOIN admins a ON tm.sender_id = a.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
                $msgStmt->execute([$ticket_id]);
                $messages = $msgStmt->fetchAll();

                $statusClass = 'status-' . $ticket['status'];
                $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
                ?>

                <div class="admin-ticket-layout">
                    
                    <div class="chat-container glass">
                        <div class="chat-header">
                            <div class="chat-title-area">
                                <h2><span class="text-muted"><?= formatTicketId($ticket['id']) ?></span> <?= h($ticket['subject']) ?></h2>
                                <span class="badge tag-category"><?= h($ticket['category']) ?></span>
                            </div>
                            <span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$ticket['status']] ?></span>
                        </div>

                        <div class="chat-messages" id="chat-messages">
                            <?php foreach ($messages as $m): ?>
                                <?php 
                                    $isSystem = (strpos($m['message'], '[SYSTEM]') === 0);
                                    
                                    if ($isSystem): 
                                        $cleanMessage = trim(substr($m['message'], 8));
                                ?>
                                    <div class="system-msg-simple" data-id="<?= $m['id'] ?>">
                                        <span class="material-symbols-rounded">info</span>
                                        <?= nl2br(h($cleanMessage)) ?>
                                    </div>
                                <?php else: 
                                        $isMine = (!$isSystem && $m['is_admin'] == 1 && $m['sender_id'] == $admin_id); 
                                        $wrapperClass = $isMine ? 'mine' : 'player';
                                        $avatarUrl = 'https://minotar.net/helm/' . h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']) . '/32.png';
                                        $authorName = h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']);
                                        $badge = $m['is_admin'] ? 'STAFF' : 'JÁTÉKOS';
                                        $cleanMessage = h($m['message']);
                                ?>
                                    <div class="chat-bubble-wrapper <?= $wrapperClass ?>" data-id="<?= $m['id'] ?>">
                                        <img src="<?= $avatarUrl ?>" alt="Avatar" class="chat-avatar">
                                        <div class="chat-content">
                                            <div class="chat-meta">
                                                <span class="chat-author"><?= $authorName ?> <?= $badge ? '<span class="role-badge role-'.$badge.'">'.$badge.'</span>' : '' ?></span>
                                                <span class="chat-time"><?= formatHungarianDate($m['created_at']) ?></span>
                                            </div>
                                            <?php if ($cleanMessage !== ''): ?>
                                                <div class="chat-text"><?= nl2br($cleanMessage) ?></div>
                                            <?php endif; ?>
                                            <?php if ($m['attachment']): ?>
                                                <div class="chat-attachment" <?= $cleanMessage === '' ? 'style="margin-top: 0;"' : '' ?>>
                                                    <a href="<?= $m['attachment'] ?>" target="_blank"><img src="<?= $m['attachment'] ?>"></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <div class="typing-indicator" id="typing-indicator">
                                <span class="typing-text">A játékos éppen ír</span>
                                <div class="typing-dots"><span></span><span></span><span></span></div>
                            </div>
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
                                <a href="?do=unclaim&id=<?= $ticket_id ?>" class="btn-action btn-warning"><span class="material-symbols-rounded">waving_hand</span> Lemondok róla</a>
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
                                <a href="?do=close&id=<?= $ticket_id ?>" class="btn-action btn-danger"><span class="material-symbols-rounded">lock</span> Jegy Lezárása</a>
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
    </main>
</div>

<script src="/admin/assets/js/tickets.js?v=<?= time(); ?>"></script>
</body>
</html>