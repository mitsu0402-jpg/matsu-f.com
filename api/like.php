<?php
require_once __DIR__ . '/../control/lib/db.php';

header('Content-Type: application/json; charset=UTF-8');

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$contentType = isset($_REQUEST['content_type']) ? trim((string)$_REQUEST['content_type']) : '';
$contentId = isset($_REQUEST['content_id']) ? (int)$_REQUEST['content_id'] : 0;
$pagePath = isset($_REQUEST['page_path']) ? trim((string)$_REQUEST['page_path']) : '';

if ($contentType === '' || $pagePath === '') {
    json_response(['ok' => false, 'error' => 'missing_params'], 400);
}

try {
    $pdo = getPDO();

    if ($method === 'POST') {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $likeDate = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d');

        try {
            $stmt = $pdo->prepare('INSERT INTO property_likes (content_type, content_id, page_path, like_date, ip, user_agent, created_at)
                VALUES (:content_type, :content_id, :page_path, :like_date, :ip, :user_agent, NOW())');
            $stmt->execute([
                ':content_type' => $contentType,
                ':content_id' => $contentId,
                ':page_path' => $pagePath,
                ':like_date' => $likeDate,
                ':ip' => $ip,
                ':user_agent' => $userAgent,
            ]);
            $liked = true;
        } catch (PDOException $e) {
            // Duplicate for same day/IP -> treat as already liked
            $liked = false;
        }
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM property_likes WHERE content_type = :content_type AND content_id = :content_id AND page_path = :page_path');
    $countStmt->execute([
        ':content_type' => $contentType,
        ':content_id' => $contentId,
        ':page_path' => $pagePath,
    ]);
    $count = (int)$countStmt->fetchColumn();

    json_response([
        'ok' => true,
        'liked' => $method === 'POST' ? $liked : null,
        'count' => $count,
    ]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'server_error'], 500);
}
