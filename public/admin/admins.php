<?php
// /admin/admins.php
session_start();

/* --- jogosultság ellenőrzés --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

// csak owner férjen hozzá az admin-kezeléshez
if (empty($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'owner') {
    http_response_code(403);
    echo "Nincs jogosultságod az adminok kezeléséhez.";
    exit;
}

$currentUserId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

/* --- DB beállítások: állítsd be a SAJÁT adataidra --- */
$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'SAJAT_DB_USER';
$DB_PASS = 'SAJAT_DB_JELSZO';

function get_pdo_admin() {
    static $pdo = null;
    global $DB_DSN, $DB_USER, $DB_PASS;
    if ($pdo === null) {
        $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ---------------- AJAX: add / toggle / reset_pw ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $pdo    = get_pdo_admin();
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    try {
        if ($action === 'add_admin') {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $role     = isset($_POST['role']) ? $_POST['role'] : 'admin';

            if ($username === '' || $password === '') {
                throw new Exception('Felhasználónév és jelszó szükséges.');
            }

            if (!in_array($role, ['owner', 'admin', 'mod'], true)) {
                $role = 'admin';
            }

            // ellenőrizd, hogy a username szabad-e
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            if ($stmt->fetch()) {
                throw new Exception('Ez a felhasználónév már foglalt.');
            }

            // jelszó hash
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, password_hash, role, is_active)
                VALUES (:u, :h, :r, 1)
            ");
            $stmt->execute([
                ':u' => $username,
                ':h' => $hash,
                ':r' => $role,
            ]);

            $id = (int)$pdo->lastInsertId();

            echo json_encode([
                'ok'   => true,
                'id'   => $id,
                'user' => [
                    'id'         => $id,
                    'username'   => $username,
                    'role'       => $role,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => null,
                ],
            ]);
            exit;
        }

        if ($action === 'toggle_active') {
            $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $isActive  = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó admin ID.');
            }

            // ne engedd magad inaktiválni
            global $currentUserId;
            if ($id === $currentUserId && $isActive === 0) {
                throw new Exception('Nem inaktiválhatod saját magad.');
            }

            // owner-t csak akkor engedjük piszkálni, ha ő maga a current user (vagy akár teljesen tiltjuk)
            $stmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('Admin nem található.');
            }
            if ($row['role'] === 'owner' && $id !== $currentUserId) {
                throw new Exception('Másik owner fiókját nem inaktiválhatod.');
            }

            $stmt = $pdo->prepare("UPDATE admin_users SET is_active = :a WHERE id = :id");
            $stmt->execute([
                ':a'  => $isActive,
                ':id' => $id,
            ]);

            echo json_encode(['ok' => true, 'id' => $id, 'is_active' => $isActive]);
            exit;
        }

        if ($action === 'reset_password') {
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($id <= 0 || $password === '') {
                throw new Exception('Hiányzó ID vagy jelszó.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :h WHERE id = :id");
            $stmt->execute([
                ':h' => $hash,
                ':id' => $id,
            ]);

            echo json_encode(['ok' => true, 'id' => $id]);
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/* ---------------- GET: admin lista ---------------- */

$pdo = get_pdo_admin();

$stmt = $pdo->query("
    SELECT id, username, role, is_active, created_at, last_login
    FROM admin_users
    ORDER BY role = 'owner' DESC, username ASC
");
$admins = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Adminok kezelése</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="/admin/admins.css?v=1">
</head>
<body class="admin-body">
  <div class="admin-layout">
    <!-- SIDEBAR – hasonló, mint news.php-ben -->
    <aside class="admin-sidebar">
      <div class="sidebar-logo">
        <span class="logo-main">ETHERNIA</span>
        <span class="logo-sub">Admin</span>
      </div>

      <nav class="sidebar-nav">
        <a href="/admin/news.php" class="nav-item">
          <span class="nav-icon">📰</span>
          <span class="nav-label">Hírek</span>
        </a>

        <div class="nav-separator"></div>

        <button class="nav-item nav-item-disabled" type="button" disabled>
          <span class="nav-icon">💎</span>
          <span class="nav-label">Bolt / Rangok</span>
          <span class="nav-pill">Hamarosan</span>
        </button>
        <button class="nav-item nav-item-disabled" type="button" disabled>
          <span class="nav-icon">👥</span>
          <span class="nav-label">Játékosok</span>
          <span class="nav-pill">Hamarosan</span>
        </button>

        <a href="/admin/admins.php" class="nav-item active">
          <span class="nav-icon">🛡️</span>
          <span class="nav-label">Adminok</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <span class="user-label">Bejelentkezve</span>
          <span class="user-name"><?php echo h($currentUsername); ?></span>
        </div>
      </div>
    </aside>

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
            <p>Még nincs egyetlen admin felhasználó sem (rajtatok kívül SQL-ben). 🤔</p>
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
                  <th>Szerep</th>
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
                        $role = $a['role'];
                        $class = 'role-pill';
                        if ($role === 'owner') $class .= ' role-owner';
                        elseif ($role === 'admin') $class .= ' role-admin';
                        elseif ($role === 'mod') $class .= ' role-mod';
                      ?>
                      <span class="<?php echo $class; ?>">
                        <?php echo h($role); ?>
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
                      <?php echo h($a['last_login'] ?: '–'); ?>
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

  <script src="/admin/admins.js?v=1"></script>
</body>
</html>
