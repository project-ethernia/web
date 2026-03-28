<?php
require_once __DIR__ . '/../includes/core.php';
// Tegyük fel, hogy itt van az RCON osztályod, ha nem így hívják, cseréld ki a helyes névre!
require_once __DIR__ . '/../includes/rcon.php'; 

header('Content-Type: application/json');

if (!hasPermission($admin_role, 'all')) {
    echo json_encode(['status' => 'error', 'message' => 'Nincs jogosultságod a szerver konzolhoz!']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$command = trim($data['command'] ?? '');

if (!$command) {
    echo json_encode(['status' => 'error', 'message' => 'Üres parancs.']);
    exit;
}

$rcon_host = 'play.ethernia.hu'; 
$rcon_port = 25575; 
$rcon_pass = 'ethernianetwork123'; 

try {
    $rcon = new Thedudeguy\Rcon($rcon_host, $rcon_port, $rcon_pass, 3);
    if ($rcon->connect()) {
        $response = $rcon->sendCommand($command);
        
        // Logoljuk, hogy ki mit írt a szerver konzolba
        if (function_exists('log_admin_action')) {
            log_admin_action($pdo, $admin_id, $admin_name, "RCON parancs kiadása: /" . $command);
        }
        
        echo json_encode(['status' => 'success', 'response' => $response ?: 'Parancs elküldve, nincs visszatérő üzenet.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nem sikerült csatlakozni az RCON szerverhez. Ellenőrizd a portot és a jelszót!']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'RCON hiba történt: ' . $e->getMessage()]);
}