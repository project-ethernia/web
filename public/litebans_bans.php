<?php
// LiteBans MySQL connection (update this to match your LiteBans config)
$db_host = "localhost";
$db_port = 3306;
$db_name = "litebans";
$db_user = "litebans_user";
$db_pass = "super_secret";

// Connect to MySQL
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Query latest 20 bans from LiteBans
$sql = "
    SELECT uuid, name, reason, banned_by_name, time, until, active
    FROM litebans_bans
    ORDER BY time DESC
    LIMIT 20
";

$stmt = $pdo->query($sql);
$bans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang=\"hu\">
<head>
    <meta charset=\"UTF-8\">
    <title>Ban Lista</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background: #222; }
        tr:nth-child(even) { background: #1b1b1b; }
        .inactive { opacity: 0.6; }
    </style>
</head>
<body>
    <h1>Ban Lista</h1>
    <table>
        <tr>
            <th>Játékos</th>
            <th>UUID</th>
            <th>Indok</th>
            <th>Bannolta</th>
            <th>Bannolva ekkor</th>
            <th>Lejár</th>
            <th>Aktív</th>
        </tr>
        <?php foreach ($bans as $ban): ?>
            <?php
                $bannedAt = date('Y-m-d H:i:s', $ban['time'] / 1000);
                $expires = $ban['until'] == -1 ? "Végleges" : date('Y-m-d H:i:s', $ban['until'] / 1000);
                $rowClass = $ban['active'] ? '' : 'inactive';
            ?>
            <tr class=\"<?php echo $rowClass; ?>\">
                <td><?php echo htmlspecialchars($ban['name']); ?></td>
                <td><?php echo htmlspecialchars($ban['uuid']); ?></td>
                <td><?php echo htmlspecialchars($ban['reason']); ?></td>
                <td><?php echo htmlspecialchars($ban['banned_by_name']); ?></td>
                <td><?php echo $bannedAt; ?></td>
                <td><?php echo $expires; ?></td>
                <td><?php echo $ban['active'] ? 'Igen' : 'Nem'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>