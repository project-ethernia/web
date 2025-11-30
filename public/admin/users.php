<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- KÖZÖS DB + LOG --- */
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

/* --- jogosultság --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$currentUsername = !empty($_SESSION['admin_username'])
    ? $_SESSION['admin_username']
    : 'Ismeretlen';

$currentAdminId = !empty($_SESSION['admin_id'])
    ? (int)$_SESSION['admin_id']
    : 0;

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ---------------- AJAX: e‑mail csere / jelszó csere / törlés ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $pdo    = get_pdo();
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'change_email') {
            $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';

            if ($id <= 0 || $email === '') {
                throw new Exception('Hiányzó felhasználó vagy e‑mail.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Érvénytelen e‑mail cím.');
            }

            // meglévő felhasználói adatok a loghoz
            $stmt = $pdo->prepare("SELECT username, email FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('Felhasználó nem található.');
            }

            // ütközés ellenőrzése
            $stmt = $pdo->prepare("
                SELECT id FROM web_users
                WHERE email = :email AND id <> :id
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $email,
                ':id'    => $id,
            ]);
            if ($stmt->fetch()) {
                throw new Exception('Ezzel az e‑mail címmel már létezik fiók.');
            }

            $stmt = $pdo->prepare("
                UPDATE web_users
                SET email = :email
                WHERE id = :id
            ");
            $stmt->execute([
                ':email' => $email,
                ':id'    => $id,
            ]);

            // LOG
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentUsername,
                    "Felhasználói e‑mail módosítása: '{$user['username']}' ({$user['email']} → {$email})",
                    [
                        'user_id'      => $id,
                        'old_email'    => $user['email'],
                        'new_email'    => $email,
                    ]
                );
            } catch (Throwable $e) {
                // log hiba ignorálva
            }

            echo json_encode([
                'ok'    => true,
                'id'    => $id,
                'email' => $email,
            ]);
            exit;
        }

        if ($action === 'change_password') {
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($id <= 0 || $password === '') {
                throw new Exception('Hiányzó felhasználó vagy jelszó.');
            }

            if (strlen($password) < 6) {
                throw new Exception('A jelszó legyen legalább 6 karakter hosszú.');
            }

            $stmt = $pdo->prepare("SELECT username FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Felhasználó nem található.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE web_users
                SET password_hash = :h
                WHERE id = :id
            ");
            $stmt->execute([
                ':h'  => $hash,
                ':id' => $id,
            ]);

            // LOG
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentUsername,
                    "Felhasználói jelszó módosítása: '{$user['username']}'",
                    [
                        'user_id' => $id,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true, 'id' => $id]);
            exit;
        }

        if ($action === 'delete_user') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó felhasználó ID.');
            }

            $stmt = $pdo->prepare("SELECT username, email FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Felhasználó nem található.');
            }

            $stmt = $pdo->prepare("DELETE FROM web_users WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // LOG
            try {
                log_admin_action(
                    $pdo,
                    $currentAdminId,
                    $currentUsername,
                    "Felhasználó törlése: '{$user['username']}' ({$user['email']})",
                    [
                        'user_id' => $id,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode(['ok' => true, 'id' => $id]);
            exit;
        }

        throw new Exception('Ismeretlen művelet.');
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'ok'    => false,
            'error' => $e->getMessage(),
        ]);
        exit;
    }
}

/* ---------------- GET: lista oldal render ---------------- */

try {
    $pdo = get_pdo();
    $stmt = $pdo->query("
        SELECT id, username, email, registered_at, last_login, last_ip
        FROM web_users
        ORDER BY id DESC
    ");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Felhasználók</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/users.css?v=<?= time(); ?>">
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300;400;500&display=swap">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>
<body class="admin-body">
  <div class="admin-layout">

    <?php
      $currentNav = 'users';
      require __DIR__ . '/_sidebar.php';
    ?>

    <div class="admin-main">
      <header class="admin-header">
        <div>
          <h1 class="admin-title">Felhasználók</h1>
          <p class="admin-subtitle">
            Regisztrált ETHERNIA fiókok – e‑mail, regisztráció dátuma, utolsó belépés és IP cím.
          </p>
        </div>
      </header>

      <section class="admin-section">
        <?php if (empty($users)): ?>
          <div class="admin-empty">
            <p>Még egyetlen felhasználó sem regisztrált.</p>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper users-table-wrapper">
            <table class="admin-table users-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Felhasználónév</th>
                  <th>E‑mail</th>
                  <th>Regisztráció</th>
                  <th>Utolsó belépés</th>
                  <th>IP cím</th>
                  <th>Műveletek</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr
                    data-id="<?php echo (int)$u['id']; ?>"
                    data-username="<?php echo h($u['username']); ?>"
                    data-email="<?php echo h($u['email']); ?>"
                  >
                    <td><?php echo (int)$u['id']; ?></td>
                    <td><?php echo h($u['username']); ?></td>
                    <td class="cell-email"><?php echo h($u['email']); ?></td>
                    <td><?php echo h($u['registered_at'] ?: 'ismeretlen'); ?></td>
                    <td><?php echo h($u['last_login'] ?: '—'); ?></td>
                    <td><?php echo h($u['last_ip'] ?: '—'); ?></td>
                    <td class="cell-actions">
                      <button
                        type="button"
                        class="btn btn-secondary btn-sm js-change-email"
                      >
                        E‑mail módosítás
                      </button>
                      <button
                        type="button"
                        class="btn btn-secondary btn-sm js-change-password"
                      >
                        Jelszó csere
                      </button>
                      <button
                        type="button"
                        class="btn btn-danger btn-sm js-delete-user"
                      >
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

      <!-- MODALOK -->
      <div class="modal" id="modal-change-email" aria-hidden="true">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog modal-dialog-narrow" role="dialog" aria-modal="true">
          <button type="button" class="modal-close" data-modal-close>×</button>
          <form class="modal-content" id="form-change-email">
            <h2>E‑mail módosítása</h2>
            <input type="hidden" name="id" id="email-user-id">

            <div class="form-group">
              <label>Felhasználó</label>
              <div id="email-username" style="font-size:0.85rem; opacity:0.85;"></div>
            </div>

            <div class="form-group">
              <label for="email-new">Új e‑mail cím</label>
              <input type="email" id="email-new" name="email" required>
            </div>

            <div class="modal-actions">
              <p class="form-error" id="email-error" hidden></p>
              <div class="actions-right">
                <button type="button" class="btn btn-secondary" data-modal-close>Mégse</button>
                <button type="submit" class="btn btn-primary">Mentés</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal" id="modal-change-password" aria-hidden="true">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog modal-dialog-narrow" role="dialog" aria-modal="true">
          <button type="button" class="modal-close" data-modal-close>×</button>
          <form class="modal-content" id="form-change-password">
            <h2>Jelszó csere</h2>
            <input type="hidden" name="id" id="pw-user-id">

            <div class="form-group">
              <label>Felhasználó</label>
              <div id="pw-username" style="font-size:0.85rem; opacity:0.85;"></div>
            </div>

            <div class="form-group">
              <label for="pw-new">Új jelszó</label>
              <input type="password" id="pw-new" name="password" required>
            </div>

            <div class="form-group">
              <label for="pw-new2">Új jelszó mégegyszer</label>
              <input type="password" id="pw-new2" required>
            </div>

            <div class="modal-actions">
              <p class="form-error" id="pw-error" hidden></p>
              <div class="actions-right">
                <button type="button" class="btn btn-secondary" data-modal-close>Mégse</button>
                <button type="submit" class="btn btn-primary">Mentés</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal" id="modal-delete-user" aria-hidden="true">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog modal-dialog-narrow" role="dialog" aria-modal="true">
          <button type="button" class="modal-close" data-modal-close>×</button>
          <form class="modal-content" id="form-delete-user">
            <h2>Fiók törlése</h2>
            <input type="hidden" name="id" id="del-user-id">

            <p style="font-size:0.9rem;">
              Biztosan törlöd ezt a felhasználót?
            </p>
            <p style="font-size:0.9rem; opacity:0.85;">
              <strong id="del-username"></strong> – <span id="del-email"></span>
            </p>

            <div class="modal-actions">
              <p class="form-error" id="del-error" hidden></p>
              <div class="actions-right">
                <button type="button" class="btn btn-secondary" data-modal-close>Mégse</button>
                <button type="submit" class="btn btn-danger">Törlés</button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>

  <script src="/admin/assets/js/users.js?v=<?= time(); ?>"></script>
</body>
</html>
