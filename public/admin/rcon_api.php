<?php
// public/admin/rcon_api.php
require_once __DIR__ . '/includes/core.php';
header('Content-Type: application/json; charset=utf-8');

// Csak POST vagy GET, és csak admin
if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// --- Tiltott parancsok (ezeket csak gombbal lehet küldeni, nem szabad szabad szövegként) ---
const BLOCKED_COMMANDS = ['stop', 'restart', 'reload', 'op', 'deop'];

switch ($action) {

    // GET /admin/rcon_api.php?action=status
    case 'status':
        echo json_encode(['ok' => true, 'data' => rcon()->getStatus()]);
        break;

    // GET /admin/rcon_api.php?action=players
    case 'players':
        echo json_encode(['ok' => true, 'players' => rcon()->getOnlinePlayers()]);
        break;

    // POST /admin/rcon_api.php?action=command   body: command=say Hello
    case 'command':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'POST required']); break;
        }
        $cmd   = trim($_POST['command'] ?? '');
        $first = strtolower(explode(' ', $cmd)[0]);

        if (in_array($first, BLOCKED_COMMANDS)) {
            echo json_encode(['ok' => false, 'error' => 'Ez a parancs le van tiltva webes felületről.']); break;
        }
        if ($cmd === '') {
            echo json_encode(['ok' => false, 'error' => 'Üres parancs']); break;
        }

        $result = rcon()->command($cmd);
        log_admin_action($pdo, $admin_id, $admin_name, "RCON parancs: " . $cmd);

        echo json_encode([
            'ok'       => $result !== false,
            'response' => $result !== false ? $result : 'RCON kapcsolat sikertelen.'
        ]);
        break;

    // POST – Ban a szerveren (Minecraft ban + web ban egyszerre)
    case 'ban':
        $target  = trim($_POST['username'] ?? '');
        $reason  = trim($_POST['reason']   ?? 'Admin döntés alapján.');
        if (!$target) { echo json_encode(['ok' => false, 'error' => 'Hiányzó játékos név']); break; }

        rcon()->command("ban $target $reason");
        // Web ban is (users táblában)
        $pdo->prepare("UPDATE users SET is_banned = 1 WHERE username = ?")->execute([$target]);
        log_admin_action($pdo, $admin_id, $admin_name, "Kitiltva (MC+Web): $target – Ok: $reason");

        echo json_encode(['ok' => true, 'response' => "$target kitiltva a szerveren és a weboldalon."]);
        break;

    // POST – Unban
    case 'unban':
        $target = trim($_POST['username'] ?? '');
        if (!$target) { echo json_encode(['ok' => false, 'error' => 'Hiányzó játékos név']); break; }

        rcon()->command("pardon $target");
        $pdo->prepare("UPDATE users SET is_banned = 0 WHERE username = ?")->execute([$target]);
        log_admin_action($pdo, $admin_id, $admin_name, "Tiltás feloldva (MC+Web): $target");

        echo json_encode(['ok' => true, 'response' => "$target tiltása feloldva."]);
        break;

    // POST – Kick
    case 'kick':
        $target = trim($_POST['username'] ?? '');
        $reason = trim($_POST['reason']   ?? 'Admin kirúgta.');
        if (!$target) { echo json_encode(['ok' => false, 'error' => 'Hiányzó játékos név']); break; }

        $result = rcon()->command("kick $target $reason");
        log_admin_action($pdo, $admin_id, $admin_name, "Kick: $target – Ok: $reason");

        echo json_encode(['ok' => $result !== false, 'response' => $result ?: 'Sikertelen.']);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Ismeretlen action']);
}