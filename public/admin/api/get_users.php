<?php
require_once __DIR__ . '/../includes/core.php';
header('Content-Type: application/json');

if (!hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod.']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT id, username, created_at FROM users";
$params = [];

if ($q !== '') {
    $sql .= " WHERE username LIKE ? OR id = ?";
    $params[] = '%' . $q . '%';
    $params[] = (int)$q;
}

$sql .= " ORDER BY id DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'success', 'data' => $users]);