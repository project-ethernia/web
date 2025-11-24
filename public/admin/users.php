<?php
session_start();

/* --- HIBÁK FEJLESZTÉSHEZ --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- jogosultság ellenőrzés --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$role = $_SESSION['admin_role'] ?? 'admin';
if (!in_array($role, ['owner', 'admin'], true)) {
    http_response_code(403);
    echo "Nincs jogosultságod a felhasználók kezeléséhez.";
    exit;
}

$currentAdminId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentAdminName = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

/* --- KÖZPONTI DB + LOG --- */
require_once __DIR__ . '/../database.php'; // itt van a get_pdo()
require_once __DIR__ . '/log.php';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ---------------- AJAX: e-mail csere / jelszó csere / törlés ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $pdo    = get_pdo();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'change_email') {
            $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';

            if ($id <= 0 || $email === '') {
                throw new Exception('Hiányzó ID vagy e-mail.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Érvénytelen e-mail cím.');
            }

            // user + név lekérdezés loghoz
            $stmt = $pdo->prepare("SELECT username FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $u = $stmt->fetch();
            if (!$u) {
                throw new Exception('Felhasználó nem található.');
            }
            $username = $u['username'];

            // e-mail egyediség
            $stmt = $pdo->prepare("SELECT id FROM web_users WHERE email = :e AND id != :id LIMIT 1");
            $stmt->execute([':e' => $email, ':id' => $id]);
            if ($stmt->fetch()) {
                throw new Exception('Ez az e-mail cím már használatban van.');
            }

            $stmt = $pdo->prepare("UPDATE web_users SET email = :e WHERE id = :id");
            $stmt->execute([':e' => $email, ':id' => $id]);

            // LOG
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentAdminName,
                    "Felhasználói e-mail módosítása: '{$username}'",
                    [
                        'user_id'   => $id,
                        'new_email' => $email,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true, 'email' => $email]);
            exit;
        }

        if ($action === 'change_password') {
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = $_POST['password'] ?? '';

            if ($id <= 0 || $password === '') {
                throw new Exception('Hiányzó ID vagy jelszó.');
            }

            if (mb_strlen($password, 'UTF-8') < 8) {
                throw new Exception('A jelszó legalább 8 karakter legyen.');
            }

            $stmt = $pdo->prepare("SELECT username FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $u = $stmt->fetch();
            if (!$u) {
                throw new Exception('Felhasználó nem található.');
            }
            $username = $u['username'];

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE web_users SET password_hash = :h WHERE id = :id");
            $stmt->execute([':h' => $hash, ':id' => $id]);

            // LOG
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentAdminName,
                    "Felhasználói jelszó módosítása: '{$username}'",
                    [
                        'user_id' => $id,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'delete_user') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó felhasználó ID.');
            }

            $stmt = $pdo->prepare("SELECT username, email FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $u = $stmt->fetch();
            if (!$u) {
                throw new Exception('Felhasználó nem található.');
            }
            $username = $u['username'];
            $email    = $u['email'];

            $stmt = $pdo->prepare("DELETE FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // LOG
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentAdminName,
                    "Felhasználói fiók törlése: '{$username}'",
                    [
                        'user_id' => $id,
                        'email'   => $email,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true]);
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/* ---------------- GET: felhasználó lista ---------------- */

$pdo = get_pdo();

// egyszerű lista, ha akarsz, később csinálunk keresőt / paginate-et
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql    = "SELECT id, username, email, created_at, last_login_at FROM web_users";
$params = [];

if ($q !== '') {
    $sql .= " WHERE username LIKE :q OR email LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

$sql .= " ORDER BY created_at DESC";

$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Felhasználók</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/css/users.css?v=<?= time(); ?>">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php
      $activePage      = 'users';
      $currentUsername = $currentAdminName;
      require __DIR__ . '/_sidebar.php';
    ?>

    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Regisztrált felhasználók</h1>
          <p class="admin-subtitle">
            Itt látod az összes webes fiókot. E-mail / jelszó módosítás, fiók törlés egy helyen.
          </p>
        </div>
      </header>

      <section class="admin-section">
        <div class="users-header-row">
          <p class="hint">
            Összes felhasználó: <strong><?= count($users); ?></strong>
          </p>

          <form class="user-search" method="get" action="/admin/users.php">
            <input
              type="text"
              name="q"
              placeholder="Keresés név vagy e-mail alapján..."
              value="<?= h($q); ?>"
            >
            <button type="submit">Keresés</button>
          </form>
        </div>

        <?php if (empty($users)): ?>
          <div class="admin-empty">
            <p>Még nincs egyetlen regisztrált felhasználó sem.</p>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table">
              <thead>
              <tr>
                <th>ID</th>
                <th>Felhasználónév</th>
                <th>E‑mail</th>
                <th>Regisztráció</th>
                <th>Utolsó belépés</th>
                <th>Műveletek</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($users as $u): ?>
                <tr
                  data-id="<?= (int)$u['id']; ?>"
                  data-username="<?= h($u['username']); ?>"
                  data-email="<?= h($u['email']); ?>"
                >
                  <td><?= (int)$u['id']; ?></td>
                  <td class="cell-username"><?= h($u['username']); ?></td>
                  <td class="cell-email"><?= h($u['email']); ?></td>
                  <td class="cell-date">
                    <?= $u['created_at'] ? h($u['created_at']) : 'ismeretlen'; ?>
                  </td>
                  <td class="cell-date">
                    <?= $u['last_login_at'] ? h($u['last_login_at']) : '–'; ?>
                  </td>
                  <td class="cell-actions">
                    <button type="button" class="btn btn-sm btn-secondary btn-user-email">
                      E‑mail módosítás
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary btn-user-password">
                      Jelszó módosítás
                    </button>
                    <button type="button" class="btn btn-sm btn-danger btn-user-delete">
                      Fiók törlése
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

  <!-- MODAL: E‑MAIL MÓDOSÍTÁS -->
  <div class="modal" id="user-email-modal" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
      <button type="button" class="modal-close" data-close="user-email-modal">×</button>

      <form class="modal-content" id="user-email-form">
        <h2>E‑mail módosítása</h2>
        <p class="modal-user-label">
          Felhasználó: <strong id="user-email-name"></strong>
        </p>

        <input type="hidden" name="id" id="user-email-id">
        <input type="hidden" name="action" value="change_email">

        <div class="form-group" id="group-email">
          <label for="user-email-new">Új e‑mail cím</label>
          <input type="email" id="user-email-new" name="email" required>
        </div>

        <p class="form-error" id="user-email-error" hidden></p>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" data-close="user-email-modal">Mégse</button>
          <button type="submit" class="btn btn-primary">Mentés</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: JELSZÓ MÓDOSÍTÁS -->
  <div class="modal" id="user-password-modal" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
      <button type="button" class="modal-close" data-close="user-password-modal">×</button>

      <form class="modal-content" id="user-password-form">
        <h2>Jelszó módosítása</h2>
        <p class="modal-user-label">
          Felhasználó: <strong id="user-password-name"></strong>
        </p>

        <input type="hidden" name="id" id="user-password-id">
        <input type="hidden" name="action" value="change_password">

        <div class="form-group" id="group-pass1">
          <label for="user-password-new">Új jelszó</label>
          <input type="password" id="user-password-new" name="password" required>
        </div>

        <div class="form-group" id="group-pass2">
          <label for="user-password-confirm">Új jelszó ismét</label>
          <input type="password" id="user-password-confirm" required>
        </div>

        <p class="form-error" id="user-password-error" hidden></p>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" data-close="user-password-modal">Mégse</button>
          <button type="submit" class="btn btn-primary">Mentés</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: FIÓK TÖRLÉSE -->
  <div class="modal" id="user-delete-modal" aria-hidden="true">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true">
      <button type="button" class="modal-close" data-close="user-delete-modal">×</button>

      <form class="modal-content" id="user-delete-form">
        <h2>Fiók törlése</h2>
        <p class="modal-user-label">
          Biztosan törlöd ezt a fiókot?<br>
          <strong id="user-delete-name"></strong> (<span id="user-delete-email"></span>)
        </p>

        <input type="hidden" name="id" id="user-delete-id">
        <input type="hidden" name="action" value="delete_user">

        <p class="form-error" id="user-delete-error" hidden></p>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" data-close="user-delete-modal">Mégse</button>
          <button type="submit" class="btn btn-danger">Igen, töröld</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/admin/assets/js/users.js?v=1"></script>
</body>
</html>
