<?php
$DB_HOST = "localhost";
$DB_NAME = "ethernia_web";
$DB_USER = "ethernia";
$DB_PASS = "LrKqjfTKc3Q5H6e1Ohuo";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {
    http_response_code(500);
    echo "Adatbázis hiba. (PDO)";
    error_log("DATABASE ERROR: " . $e->getMessage());
    exit;
}
