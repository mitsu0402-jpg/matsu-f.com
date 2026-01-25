<?php
require_once __DIR__ . '/../control/lib/db.php';

header('Content-Type: application/json; charset=UTF-8');

$pagePath = trim((string)($_POST['page_path'] ?? $_GET['page_path'] ?? ''));
$itemId = trim((string)($_POST['item_id'] ?? $_GET['item_id'] ?? ''));

if ($pagePath === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'missing_page_path',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($pagePath) > 255) {
    $pagePath = substr($pagePath, 0, 255);
}
if (strlen($itemId) > 50) {
    $itemId = substr($itemId, 0, 50);
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO page_views (page_path, item_id, view_count)
         VALUES (:page_path, :item_id, 1)
         ON DUPLICATE KEY UPDATE
             view_count = view_count + 1,
             updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':page_path' => $pagePath,
        ':item_id' => $itemId,
    ]);

    echo json_encode([
        'ok' => true,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $response = [
        'ok' => false,
        'error' => 'failed_to_save',
    ];
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
