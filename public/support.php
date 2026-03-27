<?php
session_start();
require_once __DIR__ . '/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$currentUser = $_SESSION['username'] ?? 'Játékos';
$current_page = 'support';
$page_title = 'Ügyfélszolgálat | Ethernia';
$extra_css = ['/assets/css/support.css'];

$SUPPORT_CATEGORIES = [
    'Játékbeli hiba (Bug)' => ['color' => '#ef4444', 'icon' => 'bug_report'],
    'Játékos jelentés' => ['color' => '#f59e0b', 'icon' => 'gavel'],
    'Vásárlás / Pénzügy' => ['color' => '#22c55e', 'icon' => 'shopping_cart'],
    'Egyéb kérdés' => ['color' => '#3b82f6', 'icon' => 'help_center']
];

$stmt = $pdo->prepare("SELECT is_muted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_muted = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT category FROM tickets WHERE user_id = ? AND status != 'closed'");
$stmt->execute([$user_id]);
$active_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$can_open_new = count($active_categories) < count($SUPPORT_CATEGORIES);
$action = $_GET['action'] ?? 'list';

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatHungarianDate($datetime) {
    $ts = strtotime($datetime);
    return date('Y. m. d. - H:i', $ts);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if ($is_muted) {
        header('Location: /support.php');
        exit;
    }
    $category = $_POST['category'] ?? 'Egyéb kérdés';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (in_array($category, $active_categories)) {
        header('Location: /support.php?error=already_open');
        exit;
    }
    
    if ($subject && $message) {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, category, subject, status) VALUES (?, ?, ?, 'open')");
        $stmt->execute([$user_id, $category, $subject]);
        $ticket_id = $pdo->lastInsertId();
        
        $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin) VALUES (?, ?, ?, 0)")
            ->execute([$ticket_id, $user_id, $message]);
            
        header("Location: /support.php?action=view&id=" . $ticket_id);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reply' && isset($_GET['id'])) {
    $ticket_id = (int)$_GET['id'];
    $message = trim($_POST['message'] ?? '');
    $attachment = uploadImageAsBase64($_FILES['attachment'] ?? null);
    
    $stmt = $pdo->prepare("SELECT id, status FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch();
    
    if ($ticket && $ticket['status'] !== 'closed' && ($message !== '' || $attachment !== null)) {
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment, is_admin) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$ticket_id, $user_id, $message, $attachment]);
        $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);
    }
    header("Location: /support.php?action=view&id=" . $ticket_id);
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="support-container <?= $action === 'view' ? 'view-ticket-mode' : '' ?>">
    <?php if ($action !== 'view'): ?>
    <div class="support-header">
        <h1>Ügyfélszolgálat</h1>
        <p>Problémád akadt a szerveren? Nyiss egy hibajegyet, és az adminok hamarosan segítenek!</p>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'already_open'): ?>
        <div class="alert-box error"><span class="material-symbols-rounded">error</span> Ebben a kategóriában már van aktív hibajegyed!</div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        $tickets = $stmt->fetchAll();
        ?>
        <div class="glass support-panel">
            <div class="panel-header">
                <h2><span class="material-symbols-rounded">forum</span> Hibajegyeim</h2>
                <?php if (!$is_muted && $can_open_new): ?>
                    <a href="?action=new" class="btn-primary"><span class="material-symbols-rounded">add</span> Új Hibajegy</a>
                <?php elseif (!$can_open_new): ?>
                    <span class="btn-primary" style="background: rgba(255,255,255,0.1); color: #94a3b8; cursor: not-allowed;"><span class="material-symbols-rounded">block</span> Elérted a limitet</span>
                <?php endif; ?>
            </div>
            
            <?php if ($is_muted): ?>
                <div class="alert-box error" style="margin: 1rem;">
                    <span class="material-symbols-rounded">volume_off</span>
                    A fiókod némítva lett az ügyfélszolgálati rendszerben. Nem nyithatsz új hibajegyeket.
                </div>
            <?php endif; ?>

            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <span class="material-symbols-rounded">support_agent</span>
                    <p>Még nem nyitottál egyetlen hibajegyet sem.</p>
                </div>
            <?php else: ?>
                <div class="ticket-list">
                    <?php foreach ($tickets as $t): ?>
                        <?php 
                            $statusClass = 'status-' . $t['status'];
                            $statusTexts = ['open' => 'FELDOLGOZÁS ALATT', 'answered' => 'VÁLASZOLTAK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
                        ?>
                        <a href="?action=view&id=<?= $t['id'] ?>" class="ticket-item">
                            <div class="ticket-info">
                                <span class="ticket-cat"><?= h($t['category']) ?></span>
                                <strong class="ticket-subject"><?= h($t['subject']) ?></strong>
                                <span class="ticket-date"><?= formatHungarianDate($t['updated_at']) ?></span>
                            </div>
                            <span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$t['status']] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($action === 'new'): ?>
        <?php if ($is_muted || !$can_open_new) { header('Location: /support.php'); exit; } ?>
        <div class="glass support-panel form-panel">
            <div class="panel-header">
                <h2><span class="material-symbols-rounded">create</span> Új Hibajegy Nyitása</h2>
                <a href="/support.php" class="btn-back"><span class="material-symbols-rounded">arrow_back</span> Mégse</a>
            </div>
            <form method="POST" action="?action=create" class="new-ticket-form">
                <div class="input-group">
                    <label>Kategória (Válassz egyet)</label>
                    <div class="category-grid">
                        <?php foreach ($SUPPORT_CATEGORIES as $cat_name => $cat_data): ?>
                            <?php $is_active = in_array($cat_name, $active_categories); ?>
                            <label class="category-card <?= $is_active ? 'disabled-card' : '' ?>" style="--cat-color: <?= $cat_data['color'] ?>;">
                                <?php if ($is_active): ?>
                                    <span class="material-symbols-rounded locked-badge" title="Már van aktív jegyed itt!">lock</span>
                                <?php endif; ?>
                                <input type="radio" name="category" value="<?= h($cat_name) ?>" <?= $is_active ? 'disabled' : 'required' ?>>
                                <div class="category-content">
                                    <span class="material-symbols-rounded"><?= $cat_data['icon'] ?></span>
                                    <span><?= h($cat_name) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="input-group">
                    <label>Tárgy (Rövid összefoglaló)</label>
                    <input type="text" name="subject" class="eth-input" required placeholder="Pl.: Eltűnt az itemem a ládából">
                </div>
                <div class="input-group">
                    <label>Részletes leírás</label>
                    <textarea name="message" class="eth-input" rows="6" required placeholder="Írd le a problémádat minél pontosabban..."></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 1rem;"><span class="material-symbols-rounded">send</span> Hibajegy Beküldése</button>
            </form>
        </div>

    <?php elseif ($action === 'view' && isset($_GET['id'])): ?>
        <?php
        $ticket_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
        $ticket = $stmt->fetch();

        if (!$ticket) die('<div class="glass support-panel"><h2 style="padding:2rem;">Hiba! Jegy nem található.</h2></div>');

        $msgStmt = $pdo->prepare("SELECT tm.*, a.username as admin_username FROM ticket_messages tm LEFT JOIN admins a ON tm.sender_id = a.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
        $msgStmt->execute([$ticket_id]);
        $messages = $msgStmt->fetchAll();

        $statusClass = 'status-' . $ticket['status'];
        $statusTexts = ['open' => 'FELDOLGOZÁS ALATT', 'answered' => 'VÁLASZOLTAK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
        ?>
        <div class="chat-container glass">
            <div class="chat-header">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <a href="/support.php" class="btn-back"><span class="material-symbols-rounded">arrow_back</span></a>
                    <h2 style="margin:0; font-size:1.3rem;"><?= h($ticket['subject']) ?></h2>
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
                        <div class="system-msg-simple"><span class="material-symbols-rounded">info</span> <?= nl2br(h($cleanMessage)) ?></div>
                    <?php else: 
                            $isMine = ($m['is_admin'] == 0 && $m['sender_id'] == $user_id);
                            $wrapperClass = $isMine ? 'mine' : 'admin';
                            $avatarUrl = 'https://minotar.net/helm/' . h($isMine ? $currentUser : ($m['admin_username'] ?? 'Admin')) . '/32.png';
                            $authorName = h($isMine ? $currentUser : ($m['admin_username'] ?? 'Ethernia Stáb'));
                            $cleanMessage = h($m['message']);
                    ?>
                        <div class="chat-bubble-wrapper <?= $wrapperClass ?>">
                            <img src="<?= $avatarUrl ?>" alt="Avatar" class="chat-avatar">
                            <div class="chat-content">
                                <div class="chat-meta">
                                    <span class="chat-author">
                                        <?= $authorName ?>
                                        <?= !$isMine ? '<span class="role-badge role-STAFF">STAFF</span>' : '' ?>
                                    </span>
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
            </div>

            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="chat-input-area">
                    <form method="POST" action="?action=reply&id=<?= $ticket_id ?>" enctype="multipart/form-data" class="chat-form">
                        <label class="chat-upload-btn">
                            <span class="material-symbols-rounded">image</span>
                            <input type="file" name="attachment" accept="image/*" style="display: none;">
                        </label>
                        <textarea name="message" placeholder="Írd le a válaszod ide (Enter a küldés)..." class="chat-textarea"></textarea>
                        <button type="submit" class="chat-send-btn"><span class="material-symbols-rounded">send</span></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="chat-closed-alert">
                    <span class="material-symbols-rounded">lock</span> Ez a hibajegy le lett zárva. Nem tudsz már válaszolni.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>