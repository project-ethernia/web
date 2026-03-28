<?php
require_once __DIR__ . '/../includes/core.php';
header('Content-Type: application/json');

if (!hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'add' || $action === 'edit') {
    $id = (int)($data['id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $category = trim($data['category'] ?? '');
    $content = trim($data['content'] ?? '');
    $is_published = (int)($data['is_published'] ?? 0);

    if (!$title || !$content) {
        echo json_encode(['status' => 'error', 'message' => 'A cím és a tartalom kitöltése kötelező!']);
        exit;
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO news (title, category, content, is_published, author_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $content, $is_published, $admin_id]);
        echo json_encode(['status' => 'success', 'message' => 'Hír sikeresen közzétéve!']);
    } else {
        $stmt = $pdo->prepare("UPDATE news SET title=?, category=?, content=?, is_published=? WHERE id=?");
        $stmt->execute([$title, $category, $content, $is_published, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Hír sikeresen frissítve!']);
    }
} elseif ($action === 'delete') {
    $id = (int)($data['id'] ?? 0);
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    echo json_encode(['status' => 'success', 'message' => 'Hír véglegesen törölve.']);
} elseif ($action === 'toggle') {
    $id = (int)($data['id'] ?? 0);
    $state = (int)($data['state'] ?? 0);
    $pdo->prepare("UPDATE news SET is_published = ? WHERE id = ?")->execute([$state, $id]);
    echo json_encode(['status' => 'success', 'message' => 'Láthatóság módosítva.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ismeretlen művelet.']);
}