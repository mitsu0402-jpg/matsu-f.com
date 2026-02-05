<?php
require_once __DIR__ . '/../control/lib/db.php';

header('Content-Type: application/json; charset=UTF-8');

$pagePath = trim((string)($_POST['page_path'] ?? $_GET['page_path'] ?? ''));
$itemId = trim((string)($_POST['item_id'] ?? $_GET['item_id'] ?? ''));
$pageTitle = trim((string)($_POST['page_title'] ?? $_GET['page_title'] ?? ''));
$propertyType = trim((string)($_POST['property_type'] ?? $_GET['property_type'] ?? ''));
$propertyId = (int)($_POST['property_id'] ?? $_GET['property_id'] ?? 0);
if ($propertyType === '') {
    $propertyType = 'blog';
}

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
    $logStmt = $pdo->prepare(
        'INSERT INTO page_views_log (page_path, page_title, ip, referrer, user_agent, property_type, property_id, created_at)
         VALUES (:page_path, :page_title, :ip, :referrer, :user_agent, :property_type, :property_id, NOW())'
    );
    $logStmt->execute([
        ':page_path' => $pagePath,
        ':page_title' => $pageTitle,
        ':ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ':referrer' => (string)($_SERVER['HTTP_REFERER'] ?? ''),
        ':user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ':property_type' => $propertyType,
        ':property_id' => $propertyId > 0 ? $propertyId : 0,
    ]);

    // Optional aggregate table (if exists).
    try {
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
    } catch (Throwable $e) {
        // Ignore when page_views table does not exist.
    }

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
