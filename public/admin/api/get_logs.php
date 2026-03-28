<?php
require_once __DIR__ . '/../includes/core.php';

// JSON válasz a böngészőnek
header('Content-Type: application/json');

// Jogosultság ellenőrzés
if (!hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod a rendszernaplók megtekintéséhez.']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM admin_logs";
$params = [];

// Ha a felhasználó gépelt valamit a keresőbe
if ($q !== '') {
    $sql .= " WHERE username LIKE ? OR action LIKE ?";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

$sql .= " ORDER BY created_at DESC LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Visszaküldjük a tiszta adatokat JSON formátumban
echo json_encode(['status' => 'success', 'data' => $logs]);