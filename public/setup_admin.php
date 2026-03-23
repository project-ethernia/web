<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database.php';

$adminUser = 'sx';
$adminPass = 'ethernia123';

$hashedPassword = password_hash($adminPass, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)");
    $stmt->execute([
        'username' => $adminUser,
        'password_hash' => $hashedPassword
    ]);
    
    echo "<h1>Siker!</h1>";
    echo "<p>Az admin fiók létrejött.</p>";
    echo "<p><strong>Felhasználónév:</strong> " . htmlspecialchars($adminUser) . "</p>";
    echo "<p><strong>Jelszó:</strong> " . htmlspecialchars($adminPass) . "</p>";
    echo "<p style='color:red; font-weight:bold;'>FONTOS: Azonnal töröld ezt a fájlt (setup_admin.php) a szerverről!</p>";
    echo "<a href='/admin/login.php'>Tovább a bejelentkezéshez</a>";

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "Ez az admin felhasználó már létezik az adatbázisban!";
    } else {
        echo "Hiba történt: " . $e->getMessage();
    }
}
?>