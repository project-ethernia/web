<?php
$current_page = 'admins';
require_once __DIR__ . '/includes/core.php';

// JOGOSULTSÁG ELLENŐRZÉS! 
if (!hasPermission($admin_role, 'manage_admins')) {
    header('Location: /admin/index.php?error=no_permission');
    exit;
}

$msg = '';
$msgType = '';

// --- MŰVELETEK: Új admin hozzáadása ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $new_user = trim($_POST['username'] ?? '');
    $new_pass = $_POST['password'] ?? '';
    $new_role = $_POST['role'] ?? 'support';

    if ($new_user && $new_pass && isset($ADMIN_ROLES[$new_role])) {
        try {
            // Megnézzük, létezik-e már a név
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$new_user]);
            if ($stmt->fetch()) {
                $msg = "Ez a felhasználónév már foglalt!";
                $msgType = "error";
            } else {
                // JELSZÓ HASH GENERÁLÁSA!
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                
                // Atombiztos Insert (a failed_logins-t is 0-ra állítjuk biztos ami biztos)
                $insert = $pdo->prepare("INSERT INTO admins (username, password_hash, role, failed_logins) VALUES (?, ?, ?, 0)");
                $insert->execute([$new_user, $hash, $new_role]);
                
                $msg = "Új csapattag sikeresen hozzáadva: " . h($new_user);
                $msgType = "success";
            }
        } catch (PDOException $e) {
            // HA BÁRMI HIBA VAN A DB-VEL, ITT FOGJA KIÍRNI, NEM DOB EL SEHOVA!
            $msg = "Adatbázis hiba történt: " . $e->getMessage();
            $msgType = "error";
        }
    } else {
        $msg = "Kérjük tölts ki minden mezőt helyesen!";
        $msgType = "error";
    }
}

// --- MŰVELETEK: Admin törlése ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $target_id = (int)$_GET['delete'];
    
    if ($target_id === $admin_id) {
        $msg = "Saját magadat nem törölheted!";
        $msgType = "error";
    } elseif ($target_id === 1) {
        $msg = "A Fő Tulajdonost nem lehet törölni!";
        $msgType = "error";
    } else {
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$target_id]);
        $msg = "Adminisztrátor sikeresen eltávolítva a rendszerből.";
        $msgType = "success";
    }
}

// Kilistázzuk az összes admint
$stmt = $pdo->query("SELECT id, username, role, failed_logins, lockout_time, last_ip FROM admins ORDER BY id ASC");
$all_admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Személyzet Kezelése | ETHERNIA Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Poppins:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:wght@300..700&display=block">
    <link rel="stylesheet" href="/assets/css/globals.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/sidebar.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/admin/assets/css/admins.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <header class="admin-header glass">
            <div class="header-left">
                <span class="material-symbols-rounded header-icon">shield_person</span>
                <div>
                    <h1>Személyzet & Jogosultságok</h1>
                    <p class="subtitle">Csapattagok kezelése, rangok beállítása</p>
                </div>
            </div>
            <div class="admin-user-info">
                Bejelentkezve mint: <strong class="text-red"><?= h($admin_name) ?></strong>
            </div>
        </header>

        <div class="admin-content">
            
            <?php if ($msg): ?>
                <div class="alert-box <?= $msgType ?>">
                    <span class="material-symbols-rounded"><?= $msgType === 'success' ? 'check_circle' : 'error' ?></span>
                    <?= h($msg) ?>
                </div>
            <?php endif; ?>

            <div class="split-layout">
                
                <div class="admin-panel glass list-panel">
                    <div class="panel-header">
                        <h2><span class="material-symbols-rounded">group</span> Aktív Csapattagok</h2>
                    </div>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Felhasználónév</th>
                                <th>Jogosultság (Rang)</th>
                                <th>Utolsó IP Cím</th>
                                <th>Státusz</th>
                                <th>Művelet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_admins as $a): ?>
                                <?php 
                                    $role_key = $a['role'];
                                    $role_info = $ADMIN_ROLES[$role_key] ?? ['name' => 'Ismeretlen', 'color' => '#64748b'];
                                    
                                    $is_locked = ($a['lockout_time'] && strtotime($a['lockout_time']) > time());
                                ?>
                                <tr class="hover-row">
                                    <td class="td-id"><strong>#<?= str_pad($a['id'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td>
                                        <div class="player-cell">
                                            <img src="https://minotar.net/helm/<?= h($a['username']) ?>/32.png" class="player-head">
                                            <?= h($a['username']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge" style="background: <?= $role_info['color'] ?>30; color: <?= $role_info['color'] ?>; border: 1px solid <?= $role_info['color'] ?>50;">
                                            <?= h($role_info['name']) ?>
                                        </span>
                                    </td>
                                    <td class="td-muted"><?= $a['last_ip'] ? h($a['last_ip']) : 'Soha nem lépett be' ?></td>
                                    <td>
                                        <?php if ($is_locked): ?>
                                            <span class="status-badge error"><span class="material-symbols-rounded">lock</span> ZÁROLVA</span>
                                        <?php else: ?>
                                            <span class="status-badge success"><span class="material-symbols-rounded">check_circle</span> AKTÍV</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['id'] !== $admin_id && $a['id'] !== 1): ?>
                                            <a href="?delete=<?= $a['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Biztosan ki akarod rúgni <?= h($a['username']) ?>-t a stábból?');">
                                                <span class="material-symbols-rounded">person_remove</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-panel glass form-panel">
                    <div class="panel-header">
                        <h2><span class="material-symbols-rounded">person_add</span> Új Admin Felvevése</h2>
                    </div>
                    <div class="panel-body">
                        <form method="POST" action="/admin/admins.php" class="add-admin-form">
                            <input type="hidden" name="action" value="add_admin">
                            
                            <div class="input-group">
                                <label>Minecraft / Felhasználónév</label>
                                <input type="text" name="username" class="admin-input" required autocomplete="off" placeholder="Pl.: Herobrine">
                            </div>
                            
                            <div class="input-group">
                                <label>Bejelentkezési Jelszó</label>
                                <input type="password" name="password" class="admin-input" required placeholder="Erős jelszó...">
                                <small class="input-hint">A rendszer automatikusan titkosítja (Hash) mentés előtt!</small>
                            </div>

                            <div class="input-group">
                                <label>Kiosztott Szerepkör</label>
                                <div class="role-grid">
                                    <?php foreach ($ADMIN_ROLES as $key => $data): ?>
                                        <?php
                                            $icon = 'shield';
                                            if($key === 'superadmin') $icon = 'local_police';
                                            if($key === 'admin') $icon = 'admin_panel_settings';
                                            if($key === 'moderator') $icon = 'gavel';
                                            if($key === 'support') $icon = 'support_agent';
                                        ?>
                                        <label class="role-card" style="--role-color: <?= $data['color'] ?>;">
                                            <input type="radio" name="role" value="<?= $key ?>" required <?= $key === 'support' ? 'checked' : '' ?>>
                                            <div class="role-content">
                                                <span class="material-symbols-rounded"><?= $icon ?></span>
                                                <span><?= h($data['name']) ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn-action btn-claim" style="width: 100%; margin-top: 1rem;">
                                <span class="material-symbols-rounded">add_moderator</span>
                                Csapattag Hozzáadása
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
</body>
</html>