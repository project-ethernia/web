<?php
session_start();

/* --- HIBAKIÍRÁS FEJLESZTÉSHEZ --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- jogosultság ellenőrzés --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

if (empty($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'owner') {
    http_response_code(403);
    echo "Nincs jogosultságod az adminok kezeléséhez.";
    exit;
}

$currentUserId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

/* --- DB beállítások: ÁLLÍTSD BE UGYANÚGY, MINT A TÖBBI FÁJLBAN --- */
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
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            )
        );
    }
    return $pdo;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* --- LOG FUNKCIÓ BEHÚZÁSA --- */
require_once __DIR__ . '/log.php';

/* ---------------- AJAX: add / toggle_active / reset_password ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $pdo    = get_pdo_admin();
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        // jelenlegi admin adatok logoláshoz
        $currentAdminId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
        $currentAdminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

        if ($action === 'add_admin') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $role     = isset($_POST['role']) ? $_POST['role'] : 'admin';

            if ($username === '' || $password === '') {
                throw new Exception('Felhasználónév és jelszó szükséges.');
            }

            if (!in_array($role, array('owner', 'admin', 'mod'), true)) {
                $role = 'admin';
            }

            // van-e már ilyen felhasználónév?
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = :u LIMIT 1");
            $stmt->execute(array(':u' => $username));
            if ($stmt->fetch()) {
                throw new Exception('Ez a felhasználónév már foglalt.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, password_hash, role, is_active)
                VALUES (:u, :h, :r, 1)
            ");
            $stmt->execute(array(
                ':u' => $username,
                ':h' => $hash,
                ':r' => $role,
            ));

            $id = (int)$pdo->lastInsertId();

            // LOG: új admin létrehozása
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentAdminName,
                    "Új admin felhasználó létrehozása: '{$username}' ({$role})",
                    [
                        'new_admin_id' => $id,
                        'new_role'     => $role,
                    ]
                );
            } catch (Throwable $e) {
                // ha a logolás hibázik, ne álljon le az app
            }

            echo json_encode(array(
                'ok'   => true,
                'id'   => $id,
                'user' => array(
                    'id'         => $id,
                    'username'   => $username,
                    'role'       => $role,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => null,
                ),
            ));
            exit;
        }

        if ($action === 'toggle_active') {
            $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $isActive  = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó admin ID.');
            }

            // ne engedd magad inaktiválni
            if ($id === $currentAdminId && $isActive === 0) {
                throw new Exception('Nem inaktiválhatod saját magad.');
            }

            // owner-t csak akkor piszkáljuk, ha ő maga a current user
            $stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = :id");
            $stmt->execute(array(':id' => $id));
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('Admin nem található.');
            }

            $targetName = $row['username'];
            if ($row['role'] === 'owner' && $id !== $currentAdminId) {
                throw new Exception('Másik owner fiókját nem inaktiválhatod.');
            }

            $stmt = $pdo->prepare("UPDATE admin_users SET is_active = :a WHERE id = :id");
            $stmt->execute(array(
                ':a'  => $isActive,
                ':id' => $id,
            ));

            $stateText = $isActive ? 'aktiválva' : 'inaktiválva';

            // LOG: admin aktív / inaktív
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentAdminName,
                    "Admin fiók {$stateText}: '{$targetName}'",
                    [
                        'target_admin_id' => $id,
                        'target_username' => $targetName,
                        'is_active'       => $isActive,
                        'state'           => $stateText,
                    ]
                );
            } catch (Throwable $e) {
                // logolás hibája ne ölje meg a választ
            }

            echo json_encode(array(
                'ok'        => true,
                'id'        => $id,
                'is_active' => $isActive,
            ));
            exit;
        }

        if ($action === 'reset_password') {
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($id <= 0 || $password === '') {
                throw new Exception('Hiányzó ID vagy jelszó.');
            }

            // Cél admin neve a loghoz
            $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('Admin nem található.');
            }
            $targetName = $row['username'];

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :h WHERE id = :id");
            $stmt->execute(array(
                ':h'  => $hash,
                ':id' => $id,
            ));

            // LOG: jelszócsere
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentAdminName,
                    "Admin jelszó módosítása: '{$targetName}'",
                    [
                        'target_admin_id' => $id,
                        'target_username' => $targetName,
                    ]
                );
            } catch (Throwable $e) {
                // log hiba ignorálva
            }

            echo json_encode(array('ok' => true, 'id' => $id));
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(array('ok' => false, 'error' => $e->getMessage()));
        exit;
    }
}

/* ---------------- GET: admin lista ---------------- */

try {
    $pdo = get_pdo_admin();

    $stmt = $pdo->query("
        SELECT id, username, role, is_active, created_at, last_login
        FROM admin_users
        ORDER BY role = 'owner' DESC, username ASC
    ");
    $admins = $stmt->fetchAll();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Adminok kezelése</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/css/admins.css?v=<?= time(); ?>">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php
      $activePage = 'admins';
      require __DIR__ . '/_sidebar.php';
    ?>

    <!-- FŐ TARTALOM -->
    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Admin felhasználók</h1>
          <p class="admin-subtitle">
            Itt tudsz új adminokat felvenni, meglévőket inaktiválni vagy jelszót cserélni.
          </p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-add-admin">
          + Új admin
        </button>
      </header>

      <section class="admin-section">
        <?php if (empty($admins)): ?>
          <div class="admin-empty">
            <p>Még nincs egyetlen admin felhasználó sem.</p>
            <button type="button" class="btn btn-primary" id="btn-add-admin-empty">
              + Hozz létre egy admin fiókot
            </button>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Felhasználónév</th>
                  <th>Rang</th>
                  <th>Aktív</th>
                  <th>Létrehozva</th>
                  <th>Utolsó belépés</th>
                  <th>Műveletek</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($admins as $a): ?>
                  <tr
                    data-id="<?php echo (int)$a['id']; ?>"
                    data-username="<?php echo h($a['username']); ?>"
                    data-role="<?php echo h($a['role']); ?>"
                    data-is_active="<?php echo (int)$a['is_active']; ?>"
                    data-created_at="<?php echo h($a['created_at']); ?>"
                    data-last_login="<?php echo h($a['last_login']); ?>"
                  >
                    <td class="cell-id"><?php echo (int)$a['id']; ?></td>
                    <td class="cell-username">
                      <?php echo h($a['username']); ?>
                      <?php if ($a['id'] === $currentUserId): ?>
                        <span class="self-pill">te</span>
                      <?php endif; ?>
                    </td>
                    <td class="cell-role">
                      <?php
                        $role  = $a['role'];
                        $class = 'role-pill';
                        if ($role === 'owner') {
                            $class .= ' role-owner';
                        } elseif ($role === 'admin') {
                            $class .= ' role-admin';
                        } elseif ($role === 'mod') {
                            $class .= ' role-mod';
                        }
                      ?>
                      <span class="<?php echo $class; ?>">
                        <?php echo strtoupper(h($role)); ?>
                      </span>
                    </td>
                    <td class="cell-active">
                      <?php $active = (int)$a['is_active'] === 1; ?>
                      <button
                        type="button"
                        class="visibility-toggle <?php echo $active ? 'is-on' : 'is-off'; ?>"
                        data-id="<?php echo (int)$a['id']; ?>"
                        data-visible="<?php echo $active ? '1' : '0'; ?>"
                        aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
                        title="<?php echo $active ? 'Aktív – kattints az inaktiváláshoz' : 'Inaktív – kattints az aktiváláshoz'; ?>"
                      >
                        <span class="toggle-knob"></span>
                      </button>
                    </td>
                    <td class="cell-date">
                      <?php echo h($a['created_at']); ?>
                    </td>
                    <td class="cell-date">
                      <?php echo h($a['last_login'] ? $a['last_login'] : '–'); ?>
                    </td>
                    <td class="cell-actions">
                      <button type="button" class="btn btn-sm btn-secondary btn-reset-pw">
                        Jelszó csere
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

  <!-- MODAL: ÚJ ADMIN LÉTREHOZÁSA -->
  <div class="modal" id="admin-modal" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
      <button type="button" class="modal-close" aria-label="Bezárás">×</button>

      <form class="modal-content" id="admin-form">
        <h2>Új admin felhasználó</h2>

        <input type="hidden" name="action" value="add_admin">

        <div class="form-group">
          <label for="admin-username">Felhasználónév</label>
          <input type="text" id="admin-username" name="username" required>
        </div>

        <div class="form-group">
          <label for="admin-password">Jelszó</label>
          <input type="password" id="admin-password" name="password" required>
        </div>

        <div class="form-group">
          <label for="admin-role">Szerep</label>
          <select id="admin-role" name="role">
            <option value="admin">admin</option>
            <option value="mod">mod</option>
            <option value="owner">owner</option>
          </select>
          <p class="form-hint">
            Owner: teljes jogkör • admin: általános admin • mod: korlátozott (későbbre).
          </p>
        </div>

        <p class="form-meta">
          Az új admin azonnal be tud majd lépni a /admin/login felületen.
        </p>

        <div class="modal-actions">
          <p class="form-error" id="admin-error" hidden></p>
          <div class="actions-right">
            <button type="button" class="btn btn-secondary" id="admin-cancel">Mégse</button>
            <button type="submit" class="btn btn-primary">Létrehozás</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="/admin/assets/css/admins.js?v=<?= time(); ?>"></script>
</body>
</html>
