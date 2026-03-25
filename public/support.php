<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$timeout_duration = 3600;
if (empty($_SESSION['is_user']) || $_SESSION['is_user'] !== true) {
    header('Location: /auth/login.php');
    exit;
}
if (!isset($_SESSION['login_time'])) $_SESSION['login_time'] = time();
$elapsed_time = time() - $_SESSION['login_time'];
if ($elapsed_time >= $timeout_duration) {
    session_unset(); session_destroy(); header('Location: /auth/login.php?error=timeout'); exit;
}

require_once __DIR__ . '/database.php';

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatTicketId($id) { return sprintf("#%03d-%03d", floor($id / 1000), $id % 1000); }
function formatHungarianDate($datetime) {
    $months = ['', 'Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'];
    $ts = strtotime($datetime);
    return date('Y.', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('d - H:i', $ts);
}

$user_id = $_SESSION['user_id'];
$currentUser = $_SESSION['user_username'];
$action = $_GET['action'] ?? 'list';
$msg = '';

// ==================================================================================
// ÚJ: REAL-TIME AJAX SZINKRONIZÁLÓ (Háttérben fut a JS által)
// ==================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'sync' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $ticket_id = (int)$_GET['id'];
    $last_id = (int)($_GET['last_id'] ?? 0);
    $typing = (int)($_GET['typing'] ?? 0);

    // 1. Frissítjük, hogy mi épp gépelünk-e
    if ($typing) {
        $pdo->prepare("UPDATE tickets SET user_typing_at = NOW() WHERE id = ?")->execute([$ticket_id]);
    }

    // 2. Ellenőrizzük, hogy az admin gépel-e (az elmúlt 3 másodpercben)
    $stmt = $pdo->prepare("SELECT admin_typing_at FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $admin_typing_at = $stmt->fetchColumn();
    $is_admin_typing = ($admin_typing_at && strtotime($admin_typing_at) >= time() - 3);

    // 3. Lekérjük az ÚJ üzeneteket
    $msgStmt = $pdo->prepare("SELECT tm.*, u.username, a.username as admin_username FROM ticket_messages tm LEFT JOIN users u ON tm.sender_id = u.id LEFT JOIN admins a ON tm.sender_id = a.id WHERE tm.ticket_id = ? AND tm.id > ? ORDER BY tm.id ASC");
    $msgStmt->execute([$ticket_id, $last_id]);
    $messages = $msgStmt->fetchAll();

    $html = '';
    $new_last_id = $last_id;

    foreach ($messages as $m) {
        $new_last_id = $m['id'];
        $isSystem = (strpos($m['message'], '[SYSTEM]') === 0);
        $isMine = (!$isSystem && $m['is_admin'] == 0 && $m['sender_id'] == $user_id);

        if ($isSystem) {
            $cleanMessage = h(trim(substr($m['message'], 8)));
            $html .= '<div class="system-msg-simple" data-id="'.$m['id'].'"><span class="material-symbols-rounded">info</span>' . nl2br($cleanMessage) . '</div>';
        } else {
            $wrapperClass = $isMine ? 'mine' : 'admin';
            $avatarUrl = 'https://minotar.net/helm/' . h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']) . '/32.png';
            $authorName = h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']);
            $badge = $m['is_admin'] ? 'STAFF' : '';
            $cleanMessage = h($m['message']);
            $dateStr = formatHungarianDate($m['created_at']);

            $html .= '<div class="chat-bubble-wrapper ' . $wrapperClass . '" data-id="'.$m['id'].'">';
            $html .= '<img src="' . $avatarUrl . '" alt="Avatar" class="chat-avatar">';
            $html .= '<div class="chat-content">';
            $html .= '<div class="chat-meta"><span class="chat-author">' . $authorName;
            if ($badge) $html .= ' <span class="admin-badge">' . $badge . '</span>';
            $html .= '</span><span class="chat-time">' . $dateStr . '</span></div>';
            if ($cleanMessage !== '') $html .= '<div class="chat-text">' . nl2br($cleanMessage) . '</div>';
            if ($m['attachment']) {
                $html .= '<div class="chat-attachment" ' . ($cleanMessage === '' ? 'style="margin-top: 0;"' : '') . '><a href="' . h($m['attachment']) . '" target="_blank"><img src="' . h($m['attachment']) . '"></a></div>';
            }
            $html .= '</div></div>';
        }
    }

    echo json_encode(['html' => $html, 'last_id' => $new_last_id, 'other_typing' => $is_admin_typing]);
    exit;
}
// ==================================================================================

$activeCheck = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
$activeCheck->execute([$user_id]);
$hasActiveTicket = $activeCheck->fetchColumn() > 0;

$stmtActive = $pdo->prepare("SELECT category FROM tickets WHERE user_id = ? AND status != 'closed'");
$stmtActive->execute([$user_id]);
$activeCategories = $stmtActive->fetchAll(PDO::FETCH_COLUMN);
$allCategoriesFull = (count($activeCategories) >= 4);

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
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (in_array($category, $activeCategories)) {
        $msg = "Ebben a kategóriában már van egy folyamatban lévő hibajegyed!";
    } elseif ($subject && $category && $message) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, category) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $subject, $category]);
            $ticket_id = $pdo->lastInsertId();

            $stmt2 = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment) VALUES (?, ?, ?, NULL)");
            $stmt2->execute([$ticket_id, $user_id, $message]);

            $botMessage = "[SYSTEM] Sikeresen létrehoztál egy hibajegyet a(z) **" . $category . "** kategóriában. Kérjük várj türelmesen, és egy csapattagunk hamarosan elkezdi intézni az ügyedet.";
            $stmt3 = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment) VALUES (?, ?, ?, NULL)");
            $stmt3->execute([$ticket_id, $user_id, $botMessage]);

            $pdo->commit();
            header("Location: /support.php?action=view&id=" . $ticket_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Hiba történt a küldés során!";
        }
    } else {
        $msg = "Kérlek, tölts ki minden kötelező mezőt!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reply' && isset($_GET['id'])) {
    $ticket_id = (int)$_GET['id'];
    $message = trim($_POST['message'] ?? '');
    
    $check = $pdo->prepare("SELECT id, status FROM tickets WHERE id = ? AND user_id = ?");
    $check->execute([$ticket_id, $user_id]);
    $ticket = $check->fetch();

    if ($ticket && ($ticket['status'] === 'open' || $ticket['status'] === 'answered')) {
        $attachment = uploadImageAsBase64($_FILES['attachment'] ?? null);
        if ($message !== '' || $attachment !== null) {
            $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $user_id, $message, $attachment]);
            $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);
            header("Location: /support.php?action=view&id=" . $ticket_id);
            exit;
        }
    }
}

$isAppMode = ($action === 'view' && isset($_GET['id']));
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Support | ETHERNIA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/index.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/assets/css/support.css?v=<?= time(); ?>">
</head>
<body class="<?= $isAppMode ? 'chat-app-mode' : '' ?>">

<?php $current_page = 'support'; ?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container" <?= !$isAppMode ? 'style="padding-top: 8rem; min-height: 80vh;"' : '' ?>>
    
    <?php if (!$isAppMode): ?>
        <div class="section-header">
            <h1 class="section-title">Ügyfélszolgálat</h1>
            <div class="title-line"></div>
            <p class="section-subtitle">Hibabejelentés, csalók jelentése vagy egyéb problémák.</p>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="profile-alert error glass"><span class="material-symbols-rounded">error</span><?= h($msg) ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        $tickets = $stmt->fetchAll();
        ?>
        <?php if ($allCategoriesFull): ?>
            <div class="profile-alert warning glass" style="margin-bottom: 2rem;">
                <span class="material-symbols-rounded">info</span>
                Minden kategóriában van már egy nyitott hibajegyed! Újat csak valamelyik lezárása után nyithatsz.
            </div>
        <?php else: ?>
            <div class="support-toolbar">
                <a href="/support.php?action=new" class="btn btn-auth" style="margin-bottom: 2rem;">ÚJ HIBAJEGY NYITÁSA</a>
            </div>
        <?php endif; ?>

        <?php if (empty($tickets)): ?>
            <div class="empty-state glass">
                <span class="material-symbols-rounded">support_agent</span>
                <p>Még nem nyitottál egyetlen hibajegyet sem.</p>
            </div>
        <?php else: ?>
            <div class="ticket-list">
                <?php foreach ($tickets as $t): ?>
                    <?php 
                        $statusClass = 'status-' . $t['status'];
                        $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
                    ?>
                    <a href="/support.php?action=view&id=<?= $t['id'] ?>" class="ticket-card glass hover-lift">
                        <div class="ticket-card-header">
                            <span class="ticket-cat"><?= h($t['category']) ?></span>
                            <span class="ticket-status <?= $statusClass ?>"><?= $statusTexts[$t['status']] ?></span>
                        </div>
                        <h3 class="ticket-subject"><span style="color: var(--text-muted); font-size: 0.9rem; margin-right: 0.5rem;"><?= formatTicketId($t['id']) ?></span> <?= h($t['subject']) ?></h3>
                        <div class="ticket-date">Utolsó frissítés: <?= formatHungarianDate($t['updated_at']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($action === 'new' && !$allCategoriesFull): ?>
        <div class="glass support-form-container" style="position: relative;">
            <a href="/support.php" class="modal-close" aria-label="Bezárás" style="top: 1.5rem; right: 1.5rem; position: absolute;">
                <span class="material-symbols-rounded">close</span>
            </a>
            
            <form method="POST" action="/support.php?action=create" class="password-form" style="padding-top: 1rem;">
                <div class="input-group" style="grid-column: 1 / -1;">
                    <label>Válassz Kategóriát (Kategóriánként max. 1 nyitott jegy)</label>
                    <div class="category-grid">
                        <?php 
                        $catBugOpen = in_array('Hibabejelentés', $activeCategories);
                        $catReportOpen = in_array('Játékos Jelentése', $activeCategories);
                        $catShopOpen = in_array('Vásárlási Probléma', $activeCategories);
                        $catOtherOpen = in_array('Egyéb Kérdés', $activeCategories);
                        ?>
                        <label class="cat-card cat-bug <?= $catBugOpen ? 'disabled-cat' : '' ?>">
                            <input type="radio" name="category" value="Hibabejelentés" <?= $catBugOpen ? 'disabled' : 'required' ?>>
                            <div class="cat-content">
                                <span class="material-symbols-rounded"><?= $catBugOpen ? 'lock' : 'bug_report' ?></span>
                                <span>Hiba (Bug)</span>
                                <?php if($catBugOpen): ?><span class="cat-locked-text">Nyitva</span><?php endif; ?>
                            </div>
                        </label>
                        <label class="cat-card cat-report <?= $catReportOpen ? 'disabled-cat' : '' ?>">
                            <input type="radio" name="category" value="Játékos Jelentése" <?= $catReportOpen ? 'disabled' : 'required' ?>>
                            <div class="cat-content">
                                <span class="material-symbols-rounded"><?= $catReportOpen ? 'lock' : 'person_alert' ?></span>
                                <span>Csaló / Toxikus</span>
                                <?php if($catReportOpen): ?><span class="cat-locked-text">Nyitva</span><?php endif; ?>
                            </div>
                        </label>
                        <label class="cat-card cat-shop <?= $catShopOpen ? 'disabled-cat' : '' ?>">
                            <input type="radio" name="category" value="Vásárlási Probléma" <?= $catShopOpen ? 'disabled' : 'required' ?>>
                            <div class="cat-content">
                                <span class="material-symbols-rounded"><?= $catShopOpen ? 'lock' : 'shopping_cart' ?></span>
                                <span>Webshop</span>
                                <?php if($catShopOpen): ?><span class="cat-locked-text">Nyitva</span><?php endif; ?>
                            </div>
                        </label>
                        <label class="cat-card cat-other <?= $catOtherOpen ? 'disabled-cat' : '' ?>">
                            <input type="radio" name="category" value="Egyéb Kérdés" <?= $catOtherOpen ? 'disabled' : 'required' ?>>
                            <div class="cat-content">
                                <span class="material-symbols-rounded"><?= $catOtherOpen ? 'lock' : 'help' ?></span>
                                <span>Egyéb</span>
                                <?php if($catOtherOpen): ?><span class="cat-locked-text">Nyitva</span><?php endif; ?>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="input-group">
                    <label>Tárgy (Röviden)</label>
                    <input type="text" name="subject" required placeholder="Pl.: Eltűnt az itemem" class="support-input">
                </div>

                <div class="input-group">
                    <label>Probléma Részletes Leírása</label>
                    <textarea name="message" required rows="6" placeholder="Írd le minél pontosabban a problémát..." class="support-textarea"></textarea>
                </div>

                <div style="text-align: center; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-auth" style="font-weight: 600;">HIBAJEGY BEKÜLDÉSE</button>
                </div>
            </form>
        </div>

    <?php elseif ($action === 'view' && isset($_GET['id'])): ?>
        <?php
        $ticket_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            echo "<div class='profile-alert error glass'>Hibajegy nem található, vagy nincs hozzá jogosultságod!</div>";
        } else {
            $msgStmt = $pdo->prepare("SELECT tm.*, u.username, a.username as admin_username FROM ticket_messages tm LEFT JOIN users u ON tm.sender_id = u.id LEFT JOIN admins a ON tm.sender_id = a.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
            $msgStmt->execute([$ticket_id]);
            $messages = $msgStmt->fetchAll();
            
            $statusClass = 'status-' . $ticket['status'];
            $statusTexts = ['open' => 'NYITOTT', 'answered' => 'VÁLASZOLTUNK', 'paused' => 'SZÜNETELTETVE', 'closed' => 'LEZÁRVA'];
        ?>

            <div class="chat-container glass" style="position: relative;">
                
                <div class="chat-header">
                    <div class="chat-title-area">
                        <h2><span style="color: var(--text-muted); font-size: 1rem; margin-right: 0.5rem;"><?= formatTicketId($ticket['id']) ?></span> <?= h($ticket['subject']) ?></h2>
                        <span class="badge tag-default"><?= h($ticket['category']) ?></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="ticket-status <?= $statusClass ?>" style="font-size: 0.8rem;"><?= $statusTexts[$ticket['status']] ?></span>
                        <a href="/support.php" class="modal-close" style="position: static; color: var(--text-muted); transition: var(--transition);">
                            <span class="material-symbols-rounded">close</span>
                        </a>
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($messages as $m): ?>
                        <?php 
                            $isSystem = (strpos($m['message'], '[SYSTEM]') === 0);
                            $isMine = (!$isSystem && $m['is_admin'] == 0 && $m['sender_id'] == $user_id); 
                            
                            // JAVÍTÁS: A rendszerüzenet egy egyszerű középre zárt doboz
                            if ($isSystem): 
                                $cleanMessage = trim(substr($m['message'], 8));
                        ?>
                            <div class="system-msg-simple" data-id="<?= $m['id'] ?>">
                                <span class="material-symbols-rounded">info</span>
                                <?= nl2br(h($cleanMessage)) ?>
                            </div>
                        <?php else: 
                                $wrapperClass = $isMine ? 'mine' : 'admin';
                                $avatarUrl = 'https://minotar.net/helm/' . h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']) . '/32.png';
                                $authorName = h($m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Admin') : $m['username']);
                                $badge = $m['is_admin'] ? 'STAFF' : '';
                                $cleanMessage = h($m['message']);
                        ?>
                            <div class="chat-bubble-wrapper <?= $wrapperClass ?>" data-id="<?= $m['id'] ?>">
                                <img src="<?= $avatarUrl ?>" alt="Avatar" class="chat-avatar">
                                <div class="chat-content">
                                    <div class="chat-meta">
                                        <span class="chat-author"><?= $authorName ?> <?= $badge ? '<span class="admin-badge">'.$badge.'</span>' : '' ?></span>
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
                        <span class="typing-text">Egy Staff tag éppen ír</span>
                        <div class="typing-dots"><span></span><span></span><span></span></div>
                    </div>
                </div>

                <?php if ($ticket['status'] === 'open' || $ticket['status'] === 'answered'): ?>
                    <div class="chat-input-area">
                        <div id="image-preview-container" class="image-preview-container" style="display: none;">
                            <img id="image-preview" src="">
                            <button type="button" id="remove-image-btn" class="remove-image-btn"><span class="material-symbols-rounded">close</span></button>
                        </div>

                        <form method="POST" action="/support.php?action=reply&id=<?= $ticket_id ?>" enctype="multipart/form-data" class="chat-form">
                            <label class="chat-upload-btn" title="Kép csatolása">
                                <span class="material-symbols-rounded">image</span>
                                <input type="file" name="attachment" accept="image/*" style="display: none;" id="chat-file-input">
                            </label>
                            <textarea name="message" placeholder="Írj egy választ (Küldés: Enter, Új sor: Shift+Enter)..." class="chat-textarea"></textarea>
                            <button type="submit" class="chat-send-btn"><span class="material-symbols-rounded">send</span></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-closed-alert">
                        <span class="material-symbols-rounded">lock</span> Ez a hibajegy szüneteltetve van, vagy le lett zárva.
                    </div>
                <?php endif; ?>
            </div>
        <?php } ?>
    <?php endif; ?>
</main>

<footer class="footer">
    <p class="copyright">&copy; <span id="year"></span> ETHERNIA.</p>
</footer>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
<script src="/assets/js/support.js?v=<?= time(); ?>"></script>
</body>
</html>