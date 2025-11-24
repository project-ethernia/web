<?php
session_start();

/* --- HIBÁK (fejlesztéshez) --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* --- KÖZÖS ADATBÁZIS KAPCSOLAT --- */
require_once __DIR__ . '/../database.php'; // <-- EZ A LÉNYEG

/* --- jogosultság --- */
if (empty($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin/login.php');
    exit;
}

$currentUsername = isset($_SESSION['admin_username'])
    ? $_SESSION['admin_username']
    : 'Ismeretlen';

$currentUserId = isset($_SESSION['admin_id'])
    ? (int)$_SESSION['admin_id']
    : 0;

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

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

  <link rel="stylesheet" href="/admin/assets/css/users.css?v=<?= time(); ?>">
</head>

<body class="admin-body">
  <div class="admin-layout">

    <?php
      $activePage = 'users';
      require __DIR__ . '/_sidebar.php';
    ?>

    <div class="admin-main">

      <header class="admin-header">
        <div>
          <h1 class="admin-title">Felhasználók</h1>
          <p class="admin-subtitle">
            Regisztrált ETHERNIA fiókok listája – e-mail cím, regisztráció dátuma, utolsó belépés és IP cím.
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
                    <td><?php echo h($u['email']); ?></td>
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

      <!-- Itt majd lesznek a modalok js-hez (email/jelszó/törlés) -->
      <div id="user-modals-root"></div>

    </div>
  </div>

  <script src="/admin/assets/js/users.js?v=<?= time(); ?>"></script>
</body>
</html>
