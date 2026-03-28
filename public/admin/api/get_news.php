<?php
require_once __DIR__ . '/../includes/core.php';
header('Content-Type: application/json');

if (!hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod.']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT n.*, a.username as author_name FROM news n LEFT JOIN admins a ON n.author_id = a.id";
$params = [];

if ($q !== '') {
    $sql .= " WHERE n.title LIKE ? OR n.category LIKE ?";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= " ORDER BY n.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'success', 'data' => $news]);