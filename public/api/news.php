<?php
// api/news.php
header('Content-Type: application/json; charset=utf-8');

$DB_DSN  = 'mysql:host=localhost;dbname=ethernia_web;charset=utf8mb4';
$DB_USER = 'ethernia';
$DB_PASS = 'LrKqjfTKc3Q5H6e1Ohuo';

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $stmt = $pdo->query("
        SELECT
          id,
          title,
          tag,
          date_display,
          short_text,
          full_text,
          order_index
        FROM news
        WHERE is_visible = 1
        ORDER BY order_index ASC, created_at DESC
        LIMIT 50
    ");

    $rows = $stmt->fetchAll();

    echo json_encode(array(
        'ok'   => true,
        'news' => $rows,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'ok'    => false,
        'error' => $e->getMessage(),
    ));
}
