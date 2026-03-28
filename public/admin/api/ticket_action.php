<?php
require_once __DIR__ . '/../includes/core.php';

// JSON válasz beállítása
header('Content-Type: application/json');

// Csak POST kéréseket fogadunk
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Érvénytelen kérés.']);
    exit;
}

// A JavaScriptből érkező JSON adatok feldolgozása
$data = json_decode(file_get_contents('php://input'), true);
$do = $data['action'] ?? '';
$ticket_id = (int)($data['id'] ?? 0);

if (!$ticket_id || !$do) {
    echo json_encode(['status' => 'error', 'message' => 'Hiányzó adatok.']);
    exit;
}

$botMsg = ""; 
$newStatus = null; 
$logMessage = ""; 
$message = "";

// Különböző akciók kezelése
if ($do === 'claim') {
    $pdo->prepare("UPDATE tickets SET claimed_by = ? WHERE id = ?")->execute([$admin_id, $ticket_id]);
    $botMsg = "[SYSTEM] **" . h($admin_name) . "** adminisztrátor csatlakozott, és megkezdte a hibajegy feldolgozását.";
    $logMessage = "Magára vállalta a #" . $ticket_id . " azonosítójú hibajegyet.";
    $message = 'Hibajegy sikeresen magadra vállalva!';
} elseif ($do === 'unclaim') {
    $pdo->prepare("UPDATE tickets SET claimed_by = NULL WHERE id = ?")->execute([$ticket_id]);
    $botMsg = "[SYSTEM] **" . h($admin_name) . "** adminisztrátor lemondott a hibajegyről. Egy másik kolléga hamarosan átveszi.";
    $logMessage = "Lemondott a #" . $ticket_id . " azonosítójú hibajegyről.";
    $message = 'Sikeresen lemondtál a hibajegyről.';
} elseif ($do === 'pause') {
    $newStatus = 'paused';
    $botMsg = "[SYSTEM] A hibajegy **szüneteltetve** lett. Kérjük, várj türelemmel a további intézkedésig.";
    $logMessage = "Szüneteltette a #" . $ticket_id . " azonosítójú hibajegyet.";
    $message = 'Hibajegy sikeresen szüneteltetve.';
} elseif ($do === 'unpause') {
    $newStatus = 'open';
    $botMsg = "[SYSTEM] A hibajegy szüneteltetése feloldva.";
    $logMessage = "Feloldotta a #" . $ticket_id . " azonosítójú hibajegy szüneteltetését.";
    $message = 'Hibajegy szüneteltetése feloldva.';
} elseif ($do === 'close') {
    $newStatus = 'closed';
    $botMsg = "[SYSTEM] A hibajegyet az adminisztrátor **lezárta**.";
    $logMessage = "Lezárta a #" . $ticket_id . " azonosítójú hibajegyet.";
    $message = 'Hibajegy véglegesen lezárva.';
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ismeretlen művelet.']);
    exit;
}

// Adatbázis frissítések
if ($newStatus) {
    $pdo->prepare("UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $ticket_id]);
}
if ($botMsg) {
    $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin) VALUES (?, ?, ?, 1)")->execute([$ticket_id, $admin_id, $botMsg]);
}
if ($logMessage !== "") {
    if (function_exists('log_admin_action')) {
        log_admin_action($pdo, $admin_id, $admin_name, $logMessage);
    }
}

// Válasz visszaküldése a JavaScriptnek
echo json_encode(['status' => 'success', 'message' => $message]);