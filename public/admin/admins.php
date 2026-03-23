<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'owner') {
    http_response_code(403);
    echo "Nincs jogosultságod az adminok kezeléséhez.";
    exit;
}

$currentUserId   = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$currentUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Ismeretlen';

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

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

            $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            if ($stmt->fetch()) {
                throw new Exception('Ez a felhasználónév már foglalt.');
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('
                INSERT INTO admins (username, password_hash, role, is_active)
                VALUES (:u, :h, :r, 1)
            ');
            $stmt->execute([
                ':u' => $username,
                ':h' => $hash,
                ':r' => $role,
            ]);

            $id = (int)$pdo->lastInsertId();

            try {
                log_admin_action(
                    $pdo,
                    $currentUserId,
                    $currentUsername,
                    "Új admin felhasználó létrehozása: '{$username}' ({$role})",
                    [
                        'new_admin_id' => $id,
                        'new_role'     => $role,
                    ]
                );
            } catch (Throwable $e) {}

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
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

            if ($id <= 0) {
                throw new Exception('Hiányzó admin ID.');
            }

            if ($id === $currentUserId && $isActive === 0) {
                throw new Exception('Nem inaktiválhatod saját magad.');
            }

            $stmt = $pdo->prepare('SELECT username, role FROM admins WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('Admin nem található.');
            }

            $targetName = $row['username'];

            if ($row['role'] === 'owner' && $id !== $currentUserId) {
                throw new Exception('Másik owner fiókját nem inaktiválhatod.');
            }

            $stmt = $pdo->prepare('UPDATE admins SET is_active = :a WHERE id = :id');
            $stmt->execute([
                ':a'  => $isActive,
                ':id' => $id,
            ]);

            $stateText = $isActive ? 'aktiválva' : 'inaktiválva';

            try {
                log_admin_action(
                    $pdo,
                    $currentUserId,
                    $currentUsername,
                    "Admin fiók {$stateText}: '{$targetName}'",
                    [
                        'target_admin_id' => $id,
                        'target_username' => $targetName,
                        'is_active'       => $isActive,
                        'state'           => $stateText,
                    ]
                );
            } catch (Throwable $e) {}

            echo json_encode([
                'ok'        => true,
                'id'        => $id,
                'is_active' => $isActive,
            ]);
            exit;
        }

        if ($action === 'reset_password') {
            $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($id <= 0 || $password === '') {
                throw new Exception('Hiányzó ID vagy jelszó.');
            }

            $stmt = $pdo->prepare('SELECT username FROM admins WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('Admin nem található.');
            }
            $targetName = $row['username'];

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('UPDATE admins SET password_hash = :h WHERE id = :id');
            $stmt->execute([
                ':h'  => $hash,
                ':id' => $id,
            ]);

            try {
                log_admin_action(
                    $pdo,
                    $currentUserId,
                    $currentUsername,
                    "Admin jelszó módosítása: '{$targetName}'",
                    [
                        'target_admin_id' => $id,
                        'target_username' => $targetName,
                    ]
                );
            } catch (Throwable $e) {}

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

try {
    $stmt = $pdo->query('
        SELECT id, username, role, is_active, created_at, last_login
        FROM admins
        ORDER BY role = "owner" DESC, username ASC
    ');
    $admins = $stmt->fetchAll();
} catch (Exception $e) {
    die('Adatbázis hiba: ' . $e->getMessage());
}

$currentNav = 'admins';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>ETHERNIA Admin – Hozzáférés kezelése</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/admin/assets/css/base.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/news.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/admins.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-header glass-panel">
            <div class="header-text">
                <h1 class="admin-title">Hozzáférés</h1>
                <p class="admin-subtitle">Itt tudsz új adminokat felvenni, inaktiválni vagy jelszót cserélni.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-glow-red" id="btn-add-admin">+ Új admin</button>
            </div>
        </header>

        <section class="admin-content glass-panel">
            <?php if (empty($admins)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🛡️</div>
                    <h3>Nincs megjeleníthető admin.</h3>
                    <p>Hozd létre az első további adminisztrátort!</p>
                    <button type="button" class="btn btn-glow-red" id="btn-add-admin-empty">+ Hozz létre egy fiókot</button>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Felhasználónév</th>
                                <th>Rang</th>
                                <th>Aktív</th>
                                <th>Létrehozva</th>
                                <th>Utolsó belépés</th>
                                <th class="text-right">Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <?php
                                $role = $a['role'];
                                $roleClass = 'badge-default';
                                if ($role === 'owner') $roleClass = 'badge-event';
                                elseif ($role === 'admin') $roleClass = 'badge-info';
                                elseif ($role === 'mod') $roleClass = 'badge-test';
                                $active = (int)$a['is_active'] === 1;
                                ?>
                                <tr data-id="<?= (int)$a['id']; ?>"
                                    data-username="<?= h($a['username']); ?>"
                                    data-role="<?= h($a['role']); ?>"
                                    data-is_active="<?= (int)$a['is_active']; ?>">
                                    
                                    <td class="cell-order">#<?= (int)$a['id']; ?></td>
                                    <td class="cell-title">
                                        <?= h($a['username']); ?>
                                        <?php if ((int)$a['id'] === $currentUserId): ?>
                                            <span class="badge badge-success" style="margin-left:5px;font-size:0.65rem;">TE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= $roleClass; ?>"><?= strtoupper(h($role)); ?></span></td>
                                    <td>
                                        <button type="button" class="toggle-btn <?= $active ? 'active' : ''; ?>" data-id="<?= (int)$a['id']; ?>" data-visible="<?= $active ? '1' : '0'; ?>">
                                            <div class="toggle-circle"></div>
                                        </button>
                                    </td>
                                    <td class="cell-date"><?= h($a['created_at']); ?></td>
                                    <td class="cell-date"><?= h($a['last_login'] ? $a['last_login'] : 'Még nem lépett be'); ?></td>
                                    <td class="text-right cell-actions">
                                        <button type="button" class="btn btn-outline btn-sm btn-reset-pw">Jelszó csere</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<div class="modal-overlay" id="admin-modal">
    <div class="modal-container glass-panel">
        <button type="button" class="modal-close" aria-label="Bezárás">&times;</button>
        <form id="admin-form" class="modal-form">
            <h2 class="modal-title">Új admin létrehozása</h2>
            
            <input type="hidden" name="action" value="add_admin">

            <div class="form-group">
                <label for="admin-username">Felhasználónév</label>
                <input type="text" id="admin-username" name="username" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="admin-password">Jelszó</label>
                <input type="password" id="admin-password" name="password" required>
            </div>

            <div class="form-group">
                <label for="admin-role">Jogosultsági szint</label>
                <select id="admin-role" name="role">
                    <option value="admin">Admin (Általános)</option>
                    <option value="mod">Moderátor (Korlátozott)</option>
                    <option value="owner">Tulajdonos (Minden jog)</option>
                </select>
            </div>

            <div class="modal-footer">
                <div class="meta-info">Az új admin azonnal be tud jelentkezni.</div>
                <div class="action-buttons">
                    <span class="error-text" id="admin-error" hidden></span>
                    <button type="button" class="btn btn-outline" id="admin-cancel">Mégse</button>
                    <button type="submit" class="btn btn-glow-red">Létrehozás</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="/admin/assets/js/admins.js?v=<?= time(); ?>"></script>
</body>
</html>