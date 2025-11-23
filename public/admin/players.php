<?php
session_start();

/* --- HIBÁK --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$currentUsername = isset($_SESSION['admin_username'])
    ? $_SESSION['admin_username']
    : 'Ismeretlen';

// hogy a menü is jó helyen világítson:
$activePage = 'players';


/* --- jogosultság ellenőrzés --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$role = !empty($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'mod';
if (!in_array($role, ['owner', 'admin', 'mod'], true)) {
    http_response_code(403);
    echo "Nincs jogosultságod a játékosok kezeléséhez.";
    exit;
}

$currentAdminId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentAdminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

/* --- DB beállítások --- */
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

function get_pdo_admin() {
    static $pdo = null;
    global $DB_DSN, $DB_USER, $DB_PASS;

    if ($pdo === null) {
        $pdo = new PDO(
            $DB_DSN,
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* --- log funkció behúzása --- */
require_once __DIR__ . '/log.php';

/* ---------------- AJAX: ban / unban / mute / unmute ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Hiányzó játékos ID.']);
        exit;
    }

    try {
        $pdo = get_pdo_admin();

        // céljátékos info logoláshoz
        $stmt = $pdo->prepare("SELECT username, is_banned, is_muted FROM admin_players WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $player = $stmt->fetch();

        if (!$player) {
            throw new Exception('Játékos nem található.');
        }

        $username   = $player['username'];
        $isBanned   = (int)$player['is_banned'];
        $isMuted    = (int)$player['is_muted'];
        $newBanned  = $isBanned;
        $newMuted   = $isMuted;
        $logText    = '';

        if ($action === 'ban') {
            $newBanned = 1;
            $logText   = "Játékos bannolása: '{$username}'";
        } elseif ($action === 'unban') {
            $newBanned = 0;
            $logText   = "Játékos unbannolása: '{$username}'";
        } elseif ($action === 'mute') {
            $newMuted = 1;
            $logText  = "Játékos némítása: '{$username}'";
        } elseif ($action === 'unmute') {
            $newMuted = 0;
            $logText  = "Játékos némításának feloldása: '{$username}'";
        } else {
            throw new Exception('Ismeretlen művelet.');
        }

        $stmt = $pdo->prepare("
            UPDATE admin_players
            SET is_banned = :banned,
                is_muted  = :muted
            WHERE id = :id
        ");
        $stmt->execute([
            ':banned' => $newBanned,
            ':muted'  => $newMuted,
            ':id'     => $id,
        ]);

        // log
        try {
            log_admin_action(
                $pdo,
                $currentAdminId,
                $currentAdminName,
                $logText,
                [
                    'player_id'   => $id,
                    'player_name' => $username,
                    'is_banned'   => $newBanned,
                    'is_muted'    => $newMuted,
                ]
            );
        } catch (Throwable $e) {
            // log hiba: ignore
        }

        echo json_encode([
            'ok'        => true,
            'id'        => $id,
            'is_banned' => $newBanned,
            'is_muted'  => $newMuted,
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/* ---------------- GET: játékos lista ---------------- */

$players = [];

try {
    $pdo = get_pdo_admin();

    // egyszerű lista – később ide jöhet keresés / szűrés
    $stmt = $pdo->query("
        SELECT id, username, registered_at, last_login_at, last_ip,
               rank, is_banned, is_muted
        FROM admin_players
        ORDER BY username ASC
    ");
    $players = $stmt->fetchAll();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Játékosok</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet"
        href="/admin/assets/css/players.css?v=<?php echo time(); ?>">
</head>
<body class="admin-body">
  <div class="admin-layout">
  
    <?php
      $activePage = 'players';
      require __DIR__ . '/_sidebar.php';
    ?>

    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Játékosok</h1>
          <p class="admin-subtitle">
            Webes összefoglaló a játékosokról – regisztráció, utolsó belépés, IP, rang, ban/mute státusz.
          </p>
        </div>
      </header>

      <section class="admin-section">
        <?php if (empty($players)): ?>
          <div class="admin-empty">
            <p>Még nincs egyetlen játékos sem az <code>admin_players</code> táblában.</p>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table players-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Játékos</th>
                  <th>Regisztráció</th>
                  <th>Utolsó login</th>
                  <th>IP</th>
                  <th>Rang</th>
                  <th>Ban</th>
                  <th>Mute</th>
                  <th>Műveletek</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($players as $p): ?>
                  <?php
                    $isBanned = (int)$p['is_banned'] === 1;
                    $isMuted  = (int)$p['is_muted'] === 1;
                  ?>
                  <tr
                    data-id="<?php echo (int)$p['id']; ?>"
                    data-name="<?php echo h($p['username']); ?>"
                    data-banned="<?php echo $isBanned ? '1' : '0'; ?>"
                    data-muted="<?php echo $isMuted ? '1' : '0'; ?>"
                  >
                    <td class="cell-id"><?php echo (int)$p['id']; ?></td>
                    <td class="cell-username"><?php echo h($p['username']); ?></td>
                    <td class="cell-date">
                      <?php echo $p['registered_at'] ? h($p['registered_at']) : '–'; ?>
                    </td>
                    <td class="cell-date">
                      <?php echo $p['last_login_at'] ? h($p['last_login_at']) : '–'; ?>
                    </td>
                    <td class="cell-ip">
                      <?php echo $p['last_ip'] ? h($p['last_ip']) : '–'; ?>
                    </td>
                    <td class="cell-rank">
                      <?php echo $p['rank'] ? h($p['rank']) : 'nincs'; ?>
                    </td>
                    <td class="cell-status">
                      <span class="status-pill <?php echo $isBanned ? 'status-banned' : 'status-ok'; ?>">
                        <?php echo $isBanned ? 'BANNOLVA' : 'OK'; ?>
                      </span>
                    </td>
                    <td class="cell-status">
                      <span class="status-pill <?php echo $isMuted ? 'status-muted' : 'status-ok'; ?>">
                        <?php echo $isMuted ? 'MUTE' : 'OK'; ?>
                      </span>
                    </td>
                    <td class="cell-actions">
                      <button
                        type="button"
                        class="btn btn-sm btn-secondary btn-ban-toggle"
                        data-action="<?php echo $isBanned ? 'unban' : 'ban'; ?>"
                      >
                        <?php echo $isBanned ? 'Unban' : 'Ban'; ?>
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-secondary btn-mute-toggle"
                        data-action="<?php echo $isMuted ? 'unmute' : 'mute'; ?>"
                      >
                        <?php echo $isMuted ? 'Unmute' : 'Mute'; ?>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>

  <script src="/admin/assets/js/players.js?v=<?php echo time(); ?>"></script>
</body>
</html>
