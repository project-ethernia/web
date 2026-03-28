<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_api();
}

$context = $_REQUEST['context'] ?? 'player';

if ($context === 'admin') {
    if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) { echo json_encode(['success' => false, 'error' => 'Unauthorized admin']); exit; }
    $is_admin = true; $current_user_id = $_SESSION['admin_id'] ?? 0; $current_username = $_SESSION['admin_username'] ?? '';
} else {
    if (empty($_SESSION['user_id'])) { echo json_encode(['success' => false, 'error' => 'Unauthorized player']); exit; }
    $is_admin = false; $current_user_id = $_SESSION['user_id'] ?? 0; $current_username = $_SESSION['username'] ?? '';
}

$action = $_REQUEST['action'] ?? '';
$ticket_id = (int)($_REQUEST['ticket_id'] ?? 0);

if (!$ticket_id) { echo json_encode(['success' => false, 'error' => 'Missing ticket ID']); exit; }

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket || (!$is_admin && $ticket['user_id'] != $current_user_id)) { echo json_encode(['success' => false, 'error' => 'Access denied']); exit; }

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

if ($action === 'sync') {
    $last_id = (int)($_GET['last_id'] ?? 0);
    $msgStmt = $pdo->prepare("SELECT tm.*, a.username as admin_username, u.username as user_username FROM ticket_messages tm LEFT JOIN admins a ON tm.sender_id = a.id AND tm.is_admin = 1 LEFT JOIN users u ON tm.sender_id = u.id AND tm.is_admin = 0 WHERE tm.ticket_id = ? AND tm.id > ? ORDER BY tm.id ASC");
    $msgStmt->execute([$ticket_id, $last_id]);
    $messagesRaw = $msgStmt->fetchAll();
    
    $messages = [];
    $new_last_id = $last_id;
    foreach ($messagesRaw as $m) {
        $isSystem = (strpos($m['message'], '[SYSTEM]') === 0);
        $isMine = false;
        if (!$isSystem) {
            if ($is_admin && $m['is_admin'] == 1 && $m['sender_id'] == $current_user_id) $isMine = true;
            if (!$is_admin && $m['is_admin'] == 0 && $m['sender_id'] == $current_user_id) $isMine = true;
        }
        $authorName = $m['is_admin'] == 1 ? ($m['admin_username'] ?? 'Stáb') : ($m['user_username'] ?? 'Játékos');
        
        $messages[] = [
            'id' => (int)$m['id'], 'is_system' => $isSystem, 'is_mine' => $isMine, 'is_admin' => (int)$m['is_admin'] === 1,
            'message' => htmlspecialchars($isSystem ? trim(substr($m['message'], 8)) : $m['message'], ENT_QUOTES, 'UTF-8'),
            'attachment' => $m['attachment'] ? htmlspecialchars($m['attachment'], ENT_QUOTES, 'UTF-8') : null,
            'author' => htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'),
            'avatar' => 'https://minotar.net/helm/' . urlencode($authorName) . '/32.png',
            'created_at' => date('Y. m. d. - H:i', strtotime($m['created_at']))
        ];
        $new_last_id = $m['id'];
    }

    $typing_column = $is_admin ? 'user_typing_at' : 'admin_typing_at';
    $stmt = $pdo->prepare("SELECT {$typing_column} FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $other_typing_at = $stmt->fetchColumn();
    
    echo json_encode(['success' => true, 'messages' => $messages, 'last_id' => $new_last_id, 'other_typing' => ($other_typing_at && strtotime($other_typing_at) >= time() - 3), 'ticket_status' => $ticket['status']]);
    exit;
}

if ($action === 'typing') {
    $column = $is_admin ? 'admin_typing_at' : 'user_typing_at';
    $pdo->prepare("UPDATE tickets SET {$column} = NOW() WHERE id = ?")->execute([$ticket_id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'send') {
    if ($ticket['status'] === 'closed') { echo json_encode(['success' => false, 'error' => 'Ticket is closed']); exit; }

    $now = time();
    $session_key = 'last_msg_time_' . $ticket_id;
    if (isset($_SESSION[$session_key]) && ($now - $_SESSION[$session_key]) < 2) { echo json_encode(['success' => false, 'error' => 'cooldown']); exit; }

    $message = trim($_POST['message'] ?? '');
    $attachment = uploadImageAsBase64($_FILES['attachment'] ?? null);

    if ($message === '' && $attachment === null) { echo json_encode(['success' => false, 'error' => 'Empty message']); exit; }

    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, attachment, is_admin) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ticket_id, $current_user_id, $message, $attachment, $is_admin ? 1 : 0]);

    $new_status = $is_admin ? 'answered' : 'open';
    $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$new_status, $ticket_id]);
    $_SESSION[$session_key] = $now;

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);