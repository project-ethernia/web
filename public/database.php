<?php
// database.php

// --- DB KONFIG ---
$DB_HOST = "localhost";
$DB_NAME = "ethernia_web";
$DB_USER = "ethernia";
$DB_PASS = "LrKqjfTKc3Q5H6e1Ohuo";

/**
 * Globális PDO példány lekérése.
 * Minden include-olt fájl ezt hívja: $pdo = get_pdo();
 */
function get_pdo(): PDO
{
    static $pdo = null;

    // ha már van kapcsolat, azt adjuk vissza
    if ($pdo !== null) {
        return $pdo;
    }

    // szükségünk van a globális konfig változókra
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";

    try {
        $pdo = new PDO(
            $dsn,
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {

        error_log("DATABASE ERROR: " . $e->getMessage());
        http_response_code(500);
        echo "Adatbázis hiba. (PDO)";
        exit;
    }

    return $pdo;
}

$pdo = get_pdo();
