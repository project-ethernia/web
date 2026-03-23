<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../database.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') {
        echo json_encode(['ok' => false, 'error' => 'Üres üzenet']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO admin_chat (admin_id, message) VALUES (:aid, :msg)");
    $stmt->execute(['aid' => $adminId, 'msg' => $msg]);
    
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'GET') {
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    $stmt = $pdo->prepare("
        SELECT c.id, c.message, c.created_at, a.username, a.role
        FROM admin_chat c
        JOIN admins a ON c.admin_id = a.id
        WHERE c.id > :last_id
        ORDER BY c.id ASC
        LIMIT 50
    ");
    $stmt->execute(['last_id' => $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $msg['is_me'] = ($msg['username'] === $currentUser);
        $msg['time'] = date('H:i', strtotime($msg['created_at']));
        $msg['avatar'] = "https://minotar.net/helm/" . urlencode($msg['username']) . "/32.png";
    }
    
    echo json_encode(['ok' => true, 'messages' => $messages]);
    exit;
}