<?php
require_once __DIR__ . '/../lib/db.php';

try {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT NOW() AS now_time');
    $row = $stmt->fetch();
    header('Content-Type: text/plain; charset=UTF-8');
    echo "DB OK\n";
    echo "NOW(): " . $row['now_time'] . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "DB ERROR\n";
    echo $e->getMessage();
}
