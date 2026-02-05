<?php
require_once __DIR__ . '/../../lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dashboard_page_label(string $path): string
{
    if (preg_match('#^/\d{4}/\d{2}/\d{2}/#', $path) === 1) {
        return 'ブログ';
    }
    if (strpos($path, '/saleDetail.php') === 0) {
        return '売り物件詳細';
    }
    if (strpos($path, '/rentDetail.php') === 0) {
        return '賃貸物件詳細';
    }
    $map = [
        '/' => 'トップ',
        '/saleList.php' => '売り物件一覧',
        '/rentList.php' => '賃貸物件一覧',
        '/contact.php' => 'お問い合わせ',
    ];
    return $map[$path] ?? $path;
}

function dashboard_full_url(string $path): string
{
    if ($path === '') {
        return 'https://matsu-f.com/';
    }
    if (preg_match('~^https?://~i', $path) === 1) {
        return $path;
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return 'https://matsu-f.com' . $path;
}

function dashboard_short(string $text, int $limit = 20): string
{
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit, 'UTF-8') . '...';
    }
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

function normalize_page_path(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    $parts = parse_url($path);
    $cleanPath = (string)($parts['path'] ?? $path);
    if (strpos($cleanPath, '/inc/') === 0) {
        $cleanPath = substr($cleanPath, 4);
    }
    if ($cleanPath === '/index.php') {
        $cleanPath = '/';
    }
    $lowerPath = strtolower($cleanPath);
    if ($lowerPath === '/saledetail/' || $lowerPath === '/saledetail') {
        $cleanPath = '/saleDetail.php';
    } elseif ($lowerPath === '/rentdetail/' || $lowerPath === '/rentdetail') {
        $cleanPath = '/rentDetail.php';
    }
    $query = (string)($parts['query'] ?? '');
    if ($query !== '' && (strpos($cleanPath, '/saleDetail.php') === 0 || strpos($cleanPath, '/rentDetail.php') === 0)) {
        parse_str($query, $queryParams);
        $id = isset($queryParams['id']) ? (int)$queryParams['id'] : 0;
        if ($id > 0) {
            return $cleanPath . '?id=' . $id;
        }
    }
    return $cleanPath;
}

$rows = [];
$error = '';
$tz = new DateTimeZone('Asia/Tokyo');
$now = new DateTimeImmutable('now', $tz);
$rangeStart = $now->modify('-30 days');
$rangeEnd = $now;

try {
    $pdo = getPDO();
    $since = $rangeStart->format('Y-m-d H:i:s');
    $until = $rangeEnd->format('Y-m-d H:i:s');

    $summary = [];

    $accessStmt = $pdo->prepare(
        'SELECT page_path, property_type, property_id
         FROM page_views_log
         WHERE created_at >= :since AND created_at < :until'
    );
    $accessStmt->execute([':since' => $since, ':until' => $until]);
    foreach ($accessStmt->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $propertyType = trim((string)($log['property_type'] ?? ''));
        $propertyId = (int)($log['property_id'] ?? 0);
        if ($propertyType === 'sale' && $propertyId > 0) {
            $key = '/saleDetail.php?id=' . $propertyId;
        } elseif ($propertyType === 'rent' && $propertyId > 0) {
            $key = '/rentDetail.php?id=' . $propertyId;
        } else {
            $key = normalize_page_path((string)($log['page_path'] ?? ''));
        }
        if ($key === '') {
            continue;
        }
        if (!isset($summary[$key])) {
            $summary[$key] = ['page_path' => $key, 'access_count' => 0, 'like_count' => 0];
        }
        $summary[$key]['access_count']++;
    }

    try {
        $likeStmt = $pdo->prepare(
            'SELECT page_path, content_type, content_id
             FROM property_likes
             WHERE created_at >= :since AND created_at < :until'
        );
        $likeStmt->execute([':since' => $since, ':until' => $until]);
        foreach ($likeStmt->fetchAll(PDO::FETCH_ASSOC) as $like) {
            $contentType = trim((string)($like['content_type'] ?? ''));
            $contentId = (int)($like['content_id'] ?? 0);
            if ($contentType === 'sale' && $contentId > 0) {
                $key = '/saleDetail.php?id=' . $contentId;
            } elseif ($contentType === 'rent' && $contentId > 0) {
                $key = '/rentDetail.php?id=' . $contentId;
            } else {
                $key = normalize_page_path((string)($like['page_path'] ?? ''));
            }
            if ($key === '') {
                continue;
            }
            if (!isset($summary[$key])) {
                $summary[$key] = ['page_path' => $key, 'access_count' => 0, 'like_count' => 0];
            }
            $summary[$key]['like_count']++;
        }
    } catch (Throwable $e) {
        // property_likes が未作成でもアクセス集計は表示する
    }

    $rows = array_values($summary);
    usort($rows, function (array $a, array $b): int {
        if ((int)$a['access_count'] !== (int)$b['access_count']) {
            return (int)$b['access_count'] <=> (int)$a['access_count'];
        }
        if ((int)$a['like_count'] !== (int)$b['like_count']) {
            return (int)$b['like_count'] <=> (int)$a['like_count'];
        }
        return strcmp((string)$a['page_path'], (string)$b['page_path']);
    });
} catch (Throwable $e) {
    $error = '集計の取得に失敗しました。' . $e->getMessage();
}
?>

<section>
  <p>直近30日（<?php echo h($rangeStart->format('Y-m-d')); ?> 〜 <?php echo h($rangeEnd->format('Y-m-d')); ?>）のアクセス数・いいね数</p>
</section>

<?php if ($error): ?>
  <p><?php echo h($error); ?></p>
<?php endif; ?>

<section>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>ページ</th>
          <th>ページパス</th>
          <th>アクセス数</th>
          <th>いいね！数</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="4">データがありません。</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <?php $path = (string)($row['page_path'] ?? ''); ?>
            <tr>
              <td><?php echo h(dashboard_short(dashboard_page_label($path), 20)); ?></td>
              <td>
                <a href="<?php echo h(dashboard_full_url($path)); ?>" target="_blank" rel="noopener noreferrer">
                  <?php echo h(dashboard_short($path, 20)); ?>
                </a>
              </td>
              <td><?php echo h(number_format((int)($row['access_count'] ?? 0))); ?></td>
              <td><?php echo h(number_format((int)($row['like_count'] ?? 0))); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
