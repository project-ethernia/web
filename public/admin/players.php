<?php
session_start();

/* --- HIBÁK (fejlesztéshez) --- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

/* --- KÖZPONTI DB KAPCSOLAT BEHÚZÁSA --- */
/* database.php a public rootban van, ezért egy szinttel feljebb lépünk */
require_once __DIR__ . '/../database.php'; // itt jön létre a $pdo

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* --- Lekérdezés: játékosok --- */
/*
   A táblát majd később kialakítjuk.
   Itt egy példa, hogy ne dobjon hibát:

   CREATE TABLE admin_players (
       id INT AUTO_INCREMENT PRIMARY KEY,
       username VARCHAR(32),
       registered_at DATETIME,
       last_login DATETIME,
       last_ip VARCHAR(64),
       rank VARCHAR(32),
       is_banned TINYINT(1)
   );
*/

$stmt = $pdo->query("SELECT * FROM admin_players ORDER BY id DESC");
$players = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>ETHERNIA Admin - Játékosok</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Közös stílusok -->
  <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="/admin/assets/css/players.css?v=<?php echo time(); ?>">
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
            <p>Még nincs egyetlen játékos sem az <strong>admin_players</strong> táblában.</p>
          </div>
        <?php else: ?>

          <div class="admin-table-wrapper">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Játékos</th>
                  <th>Regisztráció</th>
                  <th>Utolsó belépés</th>
                  <th>IP cím</th>
                  <th>Rang</th>
                  <th>Ban / Mute</th>
                  <th>Műveletek</th>
                </tr>
              </thead>

              <tbody>
                <?php foreach ($players as $p): ?>
                  <tr>
                    <td><?php echo (int)$p['id']; ?></td>

                    <td><?php echo h($p['username']); ?></td>

                    <td>
                      <?php echo $p['registered_at'] ? h($p['registered_at']) : 'ismeretlen'; ?>
                    </td>

                    <td>
                      <?php echo $p['last_login'] ? h($p['last_login']) : '—'; ?>
                    </td>

                    <td><?php echo h($p['last_ip']); ?></td>

                    <td><?php echo h($p['rank'] ?: '—'); ?></td>

                    <td>
                      <?php if (!empty($p['is_banned'])): ?>
                        <span style="color:#f87171;font-weight:600;">Tiltva</span>
                      <?php else: ?>
                        <span style="opacity:0.7;">OK</span>
                      <?php endif; ?>
                    </td>

                    <td class="cell-actions">
                      <button class="btn btn-secondary btn-sm">Mute</button>
                      <button class="btn btn-danger btn-sm">
                        <?php echo !empty($p['is_banned']) ? 'Unban' : 'Ban'; ?>
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
</body>
</html>
