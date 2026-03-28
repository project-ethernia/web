<?php
require_once __DIR__ . '/../includes/core.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { 
    echo json_encode(['status' => 'error', 'message' => 'Érvénytelen azonosító.']); 
    exit; 
}

$stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { 
    echo json_encode(['status' => 'error', 'message' => 'Játékos nem található.']); 
    exit; 
}

// Ideiglenes mock adatok a gyönyörű UI-hoz (Később bekötheted a gazdaság/rang tábláidba!)
$user['coins'] = rand(500, 15000);
$user['rank'] = ($user['id'] == 1) ? 'Tulajdonos' : 'Játékos';
$user['status'] = 'Aktív'; 

echo json_encode(['status' => 'success', 'data' => $user]);