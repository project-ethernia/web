<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$timeout_duration = 3600; // 1 óra
if (empty($_SESSION['is_user']) || $_SESSION['is_user'] !== true) {
    header('Location: /auth/login.php');
    exit;
}
if (!isset($_SESSION['login_time'])) $_SESSION['login_time'] = time();
$elapsed_time = time() - $_SESSION['login_time'];
if ($elapsed_time >= $timeout_duration) {
    session_unset(); session_destroy(); header('Location: /auth/login.php?error=timeout'); exit;
}
$remaining_time = $timeout_duration - $elapsed_time;

require_once __DIR__ . '/database.php';

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$user_id = $_SESSION['user_id'];
$currentUser = $_SESSION['user_username'];
$action = $_GET['action'] ?? 'list';
$msg = '';

// KÉPFELTÖLTŐ FUNKCIÓ (Már csak a chatnél használjuk!)
function uploadImage($fileArray) {
    if (isset($fileArray) && $fileArray['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($ext), $allowedExts)) {
            $uploadDir = __DIR__ . '/uploads/tickets/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid('img_', true) . '.' . $ext;
            if (move_uploaded_file($fileArray['tmp_name'], $uploadDir . $filename)) {
                return '/uploads/tickets/' . $filename;
            }
        }
    }
    return null;
}

// ÚJ TICKET LÉTREHOZÁSA (Itt kivettük a képfeltöltést)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if ($subject && $category && $message) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, category) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $subject, $category]);
            $ticket_id = $pdo->lastInsertId();

            // Első üzenetnél nincs csatolmány (NULL)
            $stmt2 = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment) VALUES (?, ?, ?, NULL)");
            $stmt2->execute([$ticket_id, $user_id, $message]);

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

// VÁLASZ KÜLDÉSE (A chatben már van képfeltöltés)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reply' && isset($_GET['id'])) {
    $ticket_id = (int)$_GET['id'];
    $message = trim($_POST['message'] ?? '');
    
    $check = $pdo->prepare("SELECT id, status FROM tickets WHERE id = ? AND user_id = ?");
    $check->execute([$ticket_id, $user_id]);
    $ticket = $check->fetch();

    if ($ticket && $ticket['status'] !== 'closed' && $message) {
        $attachment = uploadImage($_FILES['attachment'] ?? null);
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ticket_id, $user_id, $message, $attachment]);
        $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);
        
        header("Location: /support.php?action=view&id=" . $ticket_id);
        exit;
    }
}
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
<body>

<?php $current_page = 'support'; ?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container" style="padding-top: 8rem; min-height: 80vh;">
    <div class="section-header">
        <h1 class="section-title">Ügyfélszolgálat</h1>
        <div class="title-line"></div>
        <p class="section-subtitle">Hibabejelentés, csalók jelentése vagy egyéb problémák.</p>
    </div>

    <?php if ($msg): ?>
        <div class="profile-alert error glass"><span class="material-symbols-rounded">error</span><?= h($msg) ?></div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        $tickets = $stmt->fetchAll();
        ?>
        <div class="support-toolbar">
            <a href="/support.php?action=new" class="btn btn-auth" style="margin-bottom: 2rem;">ÚJ HIBAJEGY NYITÁSA</a>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="empty-state glass">
                <span class="material-symbols-rounded">support_agent</span>
                <p>Még nem nyitottál egyetlen hibajegyet sem.</p>
            </div>
        <?php else: ?>
            <div class="ticket-list">
                <?php foreach ($tickets as $t): ?>
                    <?php 
                        $statusClass = $t['status'] === 'closed' ? 'status-closed' : ($t['status'] === 'answered' ? 'status-answered' : 'status-open');
                        $statusText = $t['status'] === 'closed' ? 'LEZÁRVA' : ($t['status'] === 'answered' ? 'MEGVÁLASZOLVA' : 'NYITOTT');
                    ?>
                    <a href="/support.php?action=view&id=<?= $t['id'] ?>" class="ticket-card glass hover-lift">
                        <div class="ticket-card-header">
                            <span class="ticket-cat"><?= h($t['category']) ?></span>
                            <span class="ticket-status <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <h3 class="ticket-subject"><?= h($t['subject']) ?></h3>
                        <div class="ticket-date">Utolsó frissítés: <?= date('Y. m. d. H:i', strtotime($t['updated_at'])) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($action === 'new'): ?>
        <div class="glass support-form-container" style="position: relative;">
            
            <a href="/support.php" class="modal-close" aria-label="Bezárás" style="top: 1.5rem; right: 1.5rem; position: absolute;">
                <span class="material-symbols-rounded">close</span>
            </a>
            
            <form method="POST" action="/support.php?action=create" class="password-form" style="padding-top: 1rem;">
                <div class="form-row">
                    <div class="input-group">
                        <label>Kategória</label>
                        <select name="category" required class="support-select">
                            <option value="">Válassz kategóriát...</option>
                            <option value="Hibabejelentés">Hibabejelentés (Bug)</option>
                            <option value="Játékos Jelentése">Játékos Jelentése (Csaló/Toxikus)</option>
                            <option value="Vásárlási Probléma">Vásárlási Probléma</option>
                            <option value="Egyéb Kérdés">Egyéb Kérdés</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Tárgy (Röviden)</label>
                        <input type="text" name="subject" required placeholder="Pl.: Eltűnt az itemem" class="support-input">
                    </div>
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
            $msgStmt = $pdo->prepare("
                SELECT tm.*, u.username 
                FROM ticket_messages tm 
                JOIN users u ON tm.sender_id = u.id 
                WHERE tm.ticket_id = ? 
                ORDER BY tm.created_at ASC
            ");
            $msgStmt->execute([$ticket_id]);
            $messages = $msgStmt->fetchAll();
            
            $statusClass = $ticket['status'] === 'closed' ? 'status-closed' : ($ticket['status'] === 'answered' ? 'status-answered' : 'status-open');
            $statusText = $ticket['status'] === 'closed' ? 'LEZÁRVA' : ($ticket['status'] === 'answered' ? 'MEGVÁLASZOLVA' : 'NYITOTT');
        ?>

            <div class="chat-container glass" style="position: relative;">
                
                <div class="chat-header">
                    <div class="chat-title-area">
                        <h2>#<?= $ticket['id'] ?> - <?= h($ticket['subject']) ?></h2>
                        <span class="badge tag-default"><?= h($ticket['category']) ?></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="ticket-status <?= $statusClass ?>" style="font-size: 0.8rem;"><?= $statusText ?></span>
                        <a href="/support.php" class="modal-close" style="position: static; color: var(--text-muted); transition: var(--transition);">
                            <span class="material-symbols-rounded">close</span>
                        </a>
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <?php foreach ($messages as $m): ?>
                        <?php $isMine = ($m['sender_id'] == $user_id); ?>
                        <div class="chat-bubble-wrapper <?= $isMine ? 'mine' : 'admin' ?>">
                            <img src="https://minotar.net/helm/<?= h($m['username']) ?>/32.png" alt="Skin" class="chat-avatar">
                            <div class="chat-content">
                                <div class="chat-meta">
                                    <span class="chat-author"><?= h($m['username']) ?> <?= $m['is_admin'] ? '<span class="admin-badge">STAFF</span>' : '' ?></span>
                                    <span class="chat-time"><?= date('H:i - m. d.', strtotime($m['created_at'])) ?></span>
                                </div>
                                <div class="chat-text">
                                    <?= nl2br(h($m['message'])) ?>
                                </div>
                                <?php if ($m['attachment']): ?>
                                    <div class="chat-attachment">
                                        <a href="<?= h($m['attachment']) ?>" target="_blank">
                                            <img src="<?= h($m['attachment']) ?>" alt="Csatolmány">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($ticket['status'] !== 'closed'): ?>
                    <div class="chat-input-area">
                        <form method="POST" action="/support.php?action=reply&id=<?= $ticket_id ?>" enctype="multipart/form-data" class="chat-form">
                            <label class="chat-upload-btn" title="Kép csatolása">
                                <span class="material-symbols-rounded">image</span>
                                <input type="file" name="attachment" accept="image/*" style="display: none;" id="chat-file-input">
                            </label>
                            <textarea name="message" placeholder="Írj egy választ (Küldés: Enter, Új sor: Shift+Enter)..." required class="chat-textarea"></textarea>
                            <button type="submit" class="chat-send-btn"><span class="material-symbols-rounded">send</span></button>
                        </form>
                        <div id="file-name-display" style="font-size: 0.75rem; color: var(--eth-primary); margin-top: 0.5rem; text-align: left;"></div>
                    </div>
                <?php else: ?>
                    <div class="chat-closed-alert">
                        <span class="material-symbols-rounded">lock</span> Ez a hibajegy le lett zárva. Nem küldhetsz több üzenetet.
                    </div>
                <?php endif; ?>
            </div>
            
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const chatMsgs = document.getElementById("chat-messages");
                    if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;

                    const fileInput = document.getElementById("chat-file-input");
                    const fileDisplay = document.getElementById("file-name-display");
                    if (fileInput && fileDisplay) {
                        fileInput.addEventListener("change", function() {
                            if (this.files && this.files.length > 0) {
                                fileDisplay.textContent = "Csatolva: " + this.files[0].name;
                            } else {
                                fileDisplay.textContent = "";
                            }
                        });
                    }

                    const chatTextarea = document.querySelector(".chat-textarea");
                    if (chatTextarea) {
                        chatTextarea.addEventListener("keydown", function(e) {
                            if (e.key === "Enter" && !e.shiftKey) {
                                e.preventDefault(); 
                                if (this.value.trim() !== '') {
                                    this.closest("form").submit();
                                }
                            }
                        });
                    }
                });
            </script>
        <?php } ?>
    <?php endif; ?>
</main>

<footer class="footer">
    <p class="copyright">&copy; <span id="year"></span> ETHERNIA.</p>
</footer>

<script src="/assets/js/index.js?v=<?= time(); ?>"></script>
</body>
</html>