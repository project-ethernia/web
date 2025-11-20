<?php
session_start();

/*
 * LiteBans Web Dashboard
 * -----------------------
 * Egyszerű, egyfájlos admin felület LiteBans-hez.
 * FUNKCIONALITÁS:
 *  - Bejelentkezés (hardcode-olt staff felhasználók)
 *  - Áttekintés (statisztikák)
 *  - Bannok listázása, szűrése staff szerint
 *  - Staff aktivitás (ki hány bann-t adott)
 *
 * FONTOS:
 *  - MINDENKÉPPEN módosítsd az alábbi beállításokat a saját környezetednek megfelelően!
 */

/* === ADATBÁZIS BEÁLLÍTÁSOK (LiteBans MySQL) === */
$db_host = "localhost";
$db_port = 3306;
$db_name = "litebans";
$db_user = "litebans_user";
$db_pass = "super_secret";

/* === DASHBOARD BEJELENTKEZÉS (FELHASZNÁLÓK) ===
 * Itt tudsz staff felhasználókat beállítani.
 * Kulcs: felhasználónév
 * Érték: jelszó (egyszerűség kedvéért most plain-text, de ajánlott password_hash használata)
 */
$dashboard_users = [
    "owner"   => "jelszo123",
    "admin1"  => "adminpass",
    "helper"  => "helperpass"
];

// Ha szeretnél securebb megoldást:
// $dashboard_users = [
//     "owner" => password_hash("jelszo123", PASSWORD_DEFAULT),
// ];
// és lentebb password_verify-vel ellenőrizd.

/* === SEGÉD FÜGGVÉNYEK === */
function db_connect() {
    global $db_host, $db_port, $db_name, $db_user, $db_pass;
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Adatbázis kapcsolat sikertelen: " . htmlspecialchars($e->getMessage()));
    }
}

function is_logged_in() {
    return isset($_SESSION['dashboard_user']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: ?page=login");
        exit;
    }
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* === LOGIN LOGIKA === */
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    global $dashboard_users;

    if (isset($dashboard_users[$user]) && $dashboard_users[$user] === $pass) {
        $_SESSION['dashboard_user'] = $user;
        header("Location: ?page=overview");
        exit;
    } else {
        $login_error = "Hibás felhasználónév vagy jelszó!";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ?page=login");
    exit;
}

/* === OLDAL VÁLASZTÓ === */
$page = $_GET['page'] ?? (is_logged_in() ? 'overview' : 'login');

/* === HTML KEZDET === */
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>LiteBans Web Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0b1020;
            color: #f5f5f5;
        }
        a { color: #4FC3F7; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 240px;
            background: #050814;
            border-right: 1px solid #171b2b;
            padding: 20px;
            box-sizing: border-box;
        }
        .sidebar h1 {
            font-size: 20px;
            margin: 0 0 20px 0;
        }
        .nav-link {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 5px;
            color: #d0d4ff;
        }
        .nav-link.active {
            background: linear-gradient(90deg, #3949AB, #1E88E5);
            color: #fff;
        }
        .nav-link span {
            font-size: 14px;
        }
        .user-info {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #171b2b;
            font-size: 14px;
            color: #9ea4cc;
        }
        .user-info a {
            color: #EF5350;
        }
        .content {
            flex: 1;
            padding: 20px 30px;
            box-sizing: border-box;
        }
        .page-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .page-subtitle {
            font-size: 14px;
            color: #9ea4cc;
            margin-bottom: 20px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .card {
            background: #12172b;
            border-radius: 16px;
            padding: 16px 18px;
            border: 1px solid #1e2440;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }
        .card-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ea4cc;
            margin-bottom: 6px;
        }
        .card-value {
            font-size: 24px;
            font-weight: 600;
        }
        .card-hint {
            font-size: 12px;
            color: #9ea4cc;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 14px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #0f1424;
            border-bottom: 1px solid #20263e;
            font-weight: 500;
            color: #c0c5ff;
        }
        tbody tr:nth-child(even) {
            background: #0f1424;
        }
        tbody tr:nth-child(odd) {
            background: #090d1a;
        }
        .tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.09em;
        }
        .tag-active {
            background: rgba(76, 175, 80, 0.1);
            color: #81C784;
        }
        .tag-inactive {
            background: rgba(239, 83, 80, 0.1);
            color: #EF9A9A;
        }
        .tag-perm {
            background: rgba(255, 193, 7, 0.1);
            color: #FFD54F;
        }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
            align-items: center;
        }
        .input, .select {
            padding: 7px 9px;
            border-radius: 8px;
            border: 1px solid #303857;
            background: #050814;
            color: #f5f5f5;
            outline: none;
            font-size: 13px;
        }
        .input::placeholder {
            color: #6f7699;
        }
        .btn {
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
            background: linear-gradient(90deg, #3949AB, #1E88E5);
            color: #fff;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid #3949AB;
            color: #d0d4ff;
        }
        .login-wrapper {
            max-width: 360px;
            margin: 80px auto;
            background: #12172b;
            border-radius: 18px;
            padding: 24px 26px;
            border: 1px solid #1e2440;
            box-shadow: 0 10px 32px rgba(0, 0, 0, 0.5);
        }
        .login-wrapper h2 {
            margin-top: 0;
        }
        .login-error {
            background: rgba(239, 83, 80, 0.1);
            border: 1px solid rgba(239, 83, 80, 0.7);
            color: #FFCDD2;
            padding: 8px 10px;
            font-size: 13px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .text-muted {
            color: #9ea4cc;
            font-size: 12px;
        }
        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: #050814;
            border: 1px solid #2a3150;
            color: #9ea4cc;
        }
    </style>
</head>
<body>
<?php if (!is_logged_in() && $page === 'login'): ?>

    <div class="login-wrapper">
        <h2>LiteBans Web Dashboard</h2>
        <p class="text-muted">Jelentkezz be a staff panel eléréséhez.</p>

        <?php if (!empty($login_error)): ?>
            <div class="login-error"><?php echo h($login_error); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="login">
            <div style="margin-bottom:10px;">
                <label for="username">Felhasználónév</label><br>
                <input class="input" style="width:100%;" type="text" id="username" name="username" required>
            </div>
            <div style="margin-bottom:14px;">
                <label for="password">Jelszó</label><br>
                <input class="input" style="width:100%;" type="password" id="password" name="password" required>
            </div>
            <button class="btn" type="submit" style="width:100%;">Bejelentkezés</button>
        </form>
        <p class="text-muted" style="margin-top:10px;">
            Tipp: a staff felhasználók a fájl tetején, a <code>$dashboard_users</code> tömbben vannak beállítva.
        </p>
    </div>

<?php else: ?>
    <?php require_login(); ?>
    <?php $pdo = db_connect(); ?>

    <div class="layout">
        <div class="sidebar">
            <h1>LiteBans Dashboard</h1>
            <a class="nav-link <?php echo $page === 'overview' ? 'active' : ''; ?>" href="?page=overview">
                <span>Áttekintés</span>
            </a>
            <a class="nav-link <?php echo $page === 'bans' ? 'active' : ''; ?>" href="?page=bans">
                <span>Bannok</span>
            </a>
            <a class="nav-link <?php echo $page === 'staff' ? 'active' : ''; ?>" href="?page=staff">
                <span>Staff aktivitás</span>
            </a>
            <div class="user-info">
                Bejelentkezve: <strong><?php echo h($_SESSION['dashboard_user']); ?></strong><br>
                <a href="?action=logout">Kijelentkezés</a>
            </div>
            <div class="user-info" style="margin-top:10px;">
                <span class="pill">Béta verzió</span><br>
                <span style="font-size:12px;">Testreszabhatod a kinézetet és funkciókat a kód módosításával.</span>
            </div>
        </div>
        <div class="content">
            <?php if ($page === 'overview'): ?>
                <?php
                // Összes ban
                $total_bans = (int)$pdo->query("SELECT COUNT(*) FROM litebans_bans")->fetchColumn();
                // Aktív banok
                $active_bans = (int)$pdo->query("SELECT COUNT(*) FROM litebans_bans WHERE active = 1")->fetchColumn();
                // Mute-ok száma
                $total_mutes = (int)$pdo->query("SELECT COUNT(*) FROM litebans_mutes")->fetchColumn();
                $active_mutes = (int)$pdo->query("SELECT COUNT(*) FROM litebans_mutes WHERE active = 1")->fetchColumn();
                // Utolsó 10 ban
                $stmt_recent = $pdo->query("
                    SELECT name, uuid, reason, banned_by_name, time, until, active
                    FROM litebans_bans
                    ORDER BY time DESC
                    LIMIT 10
                ");
                $recent_bans = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

                // Top 5 staff bannok szerint
                $stmt_staff = $pdo->query("
                    SELECT banned_by_name AS staff, COUNT(*) AS cnt
                    FROM litebans_bans
                    GROUP BY banned_by_name
                    ORDER BY cnt DESC
                    LIMIT 5
                ");
                $top_staff = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="page-title">Áttekintés</div>
                <div class="page-subtitle">Gyors statisztikák a LiteBans adatbázisból.</div>

                <div class="card-grid">
                    <div class="card">
                        <div class="card-title">Összes ban</div>
                        <div class="card-value"><?php echo $total_bans; ?></div>
                        <div class="card-hint">Ebből aktív: <?php echo $active_bans; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-title">Összes mute</div>
                        <div class="card-value"><?php echo $total_mutes; ?></div>
                        <div class="card-hint">Ebből aktív: <?php echo $active_mutes; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-title">Aktív büntetések összesen</div>
                        <div class="card-value"><?php echo $active_bans + $active_mutes; ?></div>
                        <div class="card-hint">Ban + mute együtt</div>
                    </div>
                    <div class="card">
                        <div class="card-title">Top staff (ban szerint)</div>
                        <?php if ($top_staff): ?>
                            <ul style="list-style:none;padding-left:0;margin:0;">
                                <?php foreach ($top_staff as $row): ?>
                                    <li style="font-size:13px;">
                                        <strong><?php echo h($row['staff']); ?></strong>
                                        – <?php echo (int)$row['cnt']; ?> ban
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="card-hint">Még nincs adat.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <h3>Legutóbbi bannok</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Játékos</th>
                            <th>Indok</th>
                            <th>Staff</th>
                            <th>Időpont</th>
                            <th>Lejár</th>
                            <th>Státusz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recent_bans): ?>
                            <tr><td colspan="6">Nincs megjeleníthető ban.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_bans as $ban): ?>
                                <?php
                                $time = date('Y-m-d H:i', $ban['time'] / 1000);
                                $until = ($ban['until'] == -1)
                                    ? '<span class="tag tag-perm">Végleges</span>'
                                    : date('Y-m-d H:i', $ban['until'] / 1000);
                                $statusTag = $ban['active']
                                    ? '<span class="tag tag-active">Aktív</span>'
                                    : '<span class="tag tag-inactive">Lejárt</span>';
                                ?>
                                <tr>
                                    <td><?php echo h($ban['name']); ?></td>
                                    <td><?php echo h($ban['reason']); ?></td>
                                    <td><?php echo h($ban['banned_by_name']); ?></td>
                                    <td><?php echo $time; ?></td>
                                    <td><?php echo $until; ?></td>
                                    <td><?php echo $statusTag; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($page === 'bans'): ?>
                <?php
                // Szűrők
                $filter_staff = $_GET['staff'] ?? '';
                $filter_player = $_GET['player'] ?? '';
                $filter_active = $_GET['active'] ?? '';

                $params = [];
                $where = [];

                if ($filter_staff !== '') {
                    $where[] = "banned_by_name = :staff";
                    $params[':staff'] = $filter_staff;
                }
                if ($filter_player !== '') {
                    $where[] = "name LIKE :player";
                    $params[':player'] = '%' . $filter_player . '%';
                }
                if ($filter_active !== '') {
                    if ($filter_active === '1') {
                        $where[] = "active = 1";
                    } elseif ($filter_active === '0') {
                        $where[] = "active = 0";
                    }
                }

                $where_sql = $where ? ("WHERE " . implode(" AND ", $where)) : "";
                $sql_bans = "
                    SELECT name, uuid, reason, banned_by_name, time, until, active
                    FROM litebans_bans
                    $where_sql
                    ORDER BY time DESC
                    LIMIT 100
                ";
                $stmt_bans = $pdo->prepare($sql_bans);
                foreach ($params as $k => $v) {
                    $stmt_bans->bindValue($k, $v);
                }
                $stmt_bans->execute();
                $bans = $stmt_bans->fetchAll(PDO::FETCH_ASSOC);

                // Staff lista a selecthez
                $staff_list = $pdo->query("
                    SELECT DISTINCT banned_by_name AS staff
                    FROM litebans_bans
                    ORDER BY staff ASC
                ")->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <div class="page-title">Bannok</div>
                <div class="page-subtitle">Részletes lista, szűrhető staff és játékos szerint.</div>

                <form method="get" class="filter-bar">
                    <input type="hidden" name="page" value="bans">
                    <input class="input" type="text" name="player" placeholder="Játékos neve..."
                           value="<?php echo h($filter_player); ?>">
                    <select class="select" name="staff">
                        <option value="">Összes staff</option>
                        <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo h($staff); ?>" <?php echo ($filter_staff === $staff ? 'selected' : ''); ?>>
                                <?php echo h($staff); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="select" name="active">
                        <option value="">Aktív / inaktív</option>
                        <option value="1" <?php echo ($filter_active === '1' ? 'selected' : ''); ?>>Csak aktív</option>
                        <option value="0" <?php echo ($filter_active === '0' ? 'selected' : ''); ?>>Csak inaktív</option>
                    </select>
                    <button class="btn btn-outline" type="submit">Szűrés</button>
                    <a class="btn btn-outline" href="?page=bans">Szűrők törlése</a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Játékos</th>
                            <th>UUID</th>
                            <th>Indok</th>
                            <th>Staff</th>
                            <th>Időpont</th>
                            <th>Lejár</th>
                            <th>Státusz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$bans): ?>
                            <tr><td colspan="7">Nincs találat a megadott szűrőkre.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bans as $ban): ?>
                                <?php
                                $time = date('Y-m-d H:i', $ban['time'] / 1000);
                                $until = ($ban['until'] == -1)
                                    ? '<span class="tag tag-perm">Végleges</span>'
                                    : date('Y-m-d H:i', $ban['until'] / 1000);
                                $statusTag = $ban['active']
                                    ? '<span class="tag tag-active">Aktív</span>'
                                    : '<span class="tag tag-inactive">Lejárt</span>';
                                ?>
                                <tr>
                                    <td><?php echo h($ban['name']); ?></td>
                                    <td><?php echo h($ban['uuid']); ?></td>
                                    <td><?php echo h($ban['reason']); ?></td>
                                    <td><?php echo h($ban['banned_by_name']); ?></td>
                                    <td><?php echo $time; ?></td>
                                    <td><?php echo $until; ?></td>
                                    <td><?php echo $statusTag; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($page === 'staff'): ?>
                <?php
                // Staff aktivitás bannok alapján
                $staff_stats = $pdo->query("
                    SELECT banned_by_name AS staff,
                           COUNT(*) AS total_bans,
                           SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active_bans
                    FROM litebans_bans
                    GROUP BY banned_by_name
                    ORDER BY total_bans DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="page-title">Staff aktivitás</div>
                <div class="page-subtitle">Ki mennyi bann-t adott, és mennyi aktív közülük.</div>

                <table>
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Összes ban</th>
                            <th>Aktív ban</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$staff_stats): ?>
                            <tr><td colspan="3">Még nincs adat a bannokról.</td></tr>
                        <?php else: ?>
                            <?php foreach ($staff_stats as $row): ?>
                                <tr>
                                    <td><?php echo h($row['staff']); ?></td>
                                    <td><?php echo (int)$row['total_bans']; ?></td>
                                    <td><?php echo (int)$row['active_bans']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="text-muted" style="margin-top:10px;">
                    Ha szeretnéd, ugyanígy bővíthető a panel mute / warn statisztikákkal,
                    vagy Discord webhook logokkal.
                </p>

            <?php else: ?>
                <div class="page-title">Ismeretlen oldal</div>
                <p>Nem létező oldal: <?php echo h($page); ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
