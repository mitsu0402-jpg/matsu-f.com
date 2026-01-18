<?php
require_once __DIR__ . '/../control/lib/db.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = getPDO();
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
    if (!$limit || $limit < 1 || $limit > 50) {
        $limit = 10;
    }

    $stmt = $pdo->prepare('SELECT id, title, body, link_url, created_at FROM notices WHERE status = 1 ORDER BY created_at DESC, id DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        'ok' => true,
        'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'failed_to_load_notices',
    ], JSON_UNESCAPED_UNICODE);
}
