<?php
require_once __DIR__ . '/../includes/core.php';
header('Content-Type: application/json');

if (!hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod.']);
    exit;
}

// 1. Megvizsgáljuk, hogy JSON kérés jött-e (Toggle/Törlés esetén) vagy hagyományos Form (Mentés/Kép)
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if (strpos($contentType, 'application/json') !== false) {
    // JSON feldolgozása (Toggle és Delete funkciók)
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $id = (int)($data['id'] ?? 0);
    $state = (int)($data['state'] ?? 0);
} else {
    // FORM feldolgozása (Hozzáadás, Szerkesztés, Képfeltöltés)
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $snippet = trim($_POST['snippet'] ?? ''); // RÖVID SZÖVEG
    $content = trim($_POST['content'] ?? ''); // HOSSZÚ SZÖVEG
    $is_published = isset($_POST['is_published']) ? 1 : 0;
}

// --- AKCIÓK KEZELÉSE ---

if ($action === 'add' || $action === 'edit') {
    if (!$title || !$content || !$snippet) {
        echo json_encode(['status' => 'error', 'message' => 'Minden szöveges mező kitöltése kötelező!']);
        exit;
    }

    // Képfeltöltés kezelése
    $image_query_part = "";
    $image_param = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../assets/img/news/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = '/assets/img/news/' . $filename;
            $image_query_part = ", image_url = ?";
            $image_param = $image_path;
        }
    }

    if ($action === 'add') {
        $sql = "INSERT INTO news (title, category, snippet, content, is_published, author_id" . ($image_param ? ", image_url" : "") . ") VALUES (?, ?, ?, ?, ?, ?" . ($image_param ? ", ?" : "") . ")";
        $params = [$title, $category, $snippet, $content, $is_published, $admin_id];
        if ($image_param) $params[] = $image_param;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'message' => 'Hír sikeresen közzétéve!']);
    } else {
        $sql = "UPDATE news SET title=?, category=?, snippet=?, content=?, is_published=?" . $image_query_part . " WHERE id=?";
        $params = [$title, $category, $snippet, $content, $is_published];
        if ($image_param) $params[] = $image_param;
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'message' => 'Hír sikeresen frissítve!']);
    }

} elseif ($action === 'delete') {
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    echo json_encode(['status' => 'success', 'message' => 'Hír véglegesen törölve.']);

} elseif ($action === 'toggle') {
    $pdo->prepare("UPDATE news SET is_published = ? WHERE id = ?")->execute([$state, $id]);
    echo json_encode(['status' => 'success', 'message' => 'Láthatóság sikeresen módosítva.']);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Ismeretlen művelet.']);
}