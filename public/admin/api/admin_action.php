<?php
require_once __DIR__ . '/../includes/core.php';
header('Content-Type: application/json');

// Csak megfelelő jogosultsággal engedjük be!
if (!hasPermission($admin_role, 'manage_admins') && !hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod az adminisztrátorok kezeléséhez!']);
    exit;
}

// A JavaScript JSON-ben küldi az adatokat
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

// 1. ÚJ ADMIN HOZZÁADÁSA
if ($action === 'add') {
    $new_user = trim($data['username'] ?? '');
    $new_pass = $data['password'] ?? '';
    $new_role = $data['role'] ?? 'support';

    if (!$new_user || !$new_pass) {
        echo json_encode(['status' => 'error', 'message' => 'Minden mező kitöltése kötelező!']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$new_user]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ez a felhasználónév már létezik a rendszerben!']);
        exit;
    }

    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$new_user, $hash, $new_role]);
    
    if (function_exists('log_admin_action')) {
        log_admin_action($pdo, $admin_id, $admin_name, "Új adminisztrátor hozzáadva: " . $new_user . " (" . $new_role . ")");
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Új csapattag sikeresen hozzáadva: ' . h($new_user)]);
    exit;
}

// 2. ADMIN TÖRLÉSE
if ($action === 'delete') {
    $target_id = (int)($data['id'] ?? 0);
    
    if ($target_id === $admin_id) {
        echo json_encode(['status' => 'error', 'message' => 'Saját magadat nem törölheted!']);
        exit;
    }
    if ($target_id) {
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$target_id]);
        if (function_exists('log_admin_action')) {
            log_admin_action($pdo, $admin_id, $admin_name, "Adminisztrátor törölve. ID: " . $target_id);
        }
        echo json_encode(['status' => 'success', 'message' => 'Adminisztrátor sikeresen eltávolítva.']);
        exit;
    }
}

// 3. 2FA VISSZAÁLLÍTÁSA
if ($action === 'reset_2fa') {
    $target_id = (int)($data['id'] ?? 0);
    if ($target_id) {
        $pdo->prepare("UPDATE admins SET two_factor_secret = NULL WHERE id = ?")->execute([$target_id]);
        if (function_exists('log_admin_action')) {
            log_admin_action($pdo, $admin_id, $admin_name, "2FA visszaállítva. ID: " . $target_id);
        }
        echo json_encode(['status' => 'success', 'message' => 'A 2FA hitelesítés sikeresen visszaállítva.']);
        exit;
    }
}

// Ha ide eljutott, akkor valami nem stimmel a kéréssel
echo json_encode(['status' => 'error', 'message' => 'Érvénytelen vagy ismeretlen művelet.']);