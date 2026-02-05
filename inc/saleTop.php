<?php
require_once __DIR__ . '/../control/lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$rows = [];
$error = '';

try {
    $pdo = getPDO();
    try {
        $referrer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $pagePath = '';
        if ($referrer !== '') {
            $parts = parse_url($referrer);
            if (!empty($parts['path'])) {
                $pagePath = $parts['path'];
                if (!empty($parts['query'])) {
                    $pagePath .= '?' . $parts['query'];
                }
            }
        }
        if ($pagePath === '') {
            $pagePath = '/index.php';
        }

        $logStmt = $pdo->prepare('INSERT INTO page_views_log (page_path, page_title, ip, referrer, user_agent, property_type, property_id, created_at)
            VALUES (:page_path, :page_title, :ip, :referrer, :user_agent, :property_type, :property_id, NOW())');
        $logStmt->execute([
            ':page_path' => $pagePath,
            ':page_title' => 'トップページ',
            ':ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ':referrer' => $referrer,
            ':user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ':property_type' => 'index',
            ':property_id' => 0,
        ]);
    } catch (Throwable $e) {
        // Ignore logging errors to avoid breaking page rendering.
    }
    $sql = 'SELECT id, name, cate, price, location, sort,
        (SELECT file_path
         FROM property_images
         WHERE property_type = \'sale\'
           AND status = 1
           AND property_id = sale_properties.id
         ORDER BY sort ASC, id ASC
         LIMIT 1) AS image_path
        FROM `sale_properties`
        WHERE `status` = 1
        ORDER BY `sort` DESC, id DESC
        LIMIT 6';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'エラーが発生しました：' . $e->getMessage();
}
?>
<!doctype html>
<html lang="ja">
    <head>
    <meta charset="UTF-8">
    <style>
        :root {
            --card-radius: 12px;
            --card-gap: 16px;
            --card-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Yu Mincho", "Hiragino Mincho ProN", "Hiragino Mincho Pro", "Noto Serif JP", serif;
            background: #ffffff;
            color: #1b1b1b;
        }

        .osusume-title {
            margin: 0 0 12px;
            font-size: 24px;
            letter-spacing: 0.08em;
            text-align: center;
        }

        .osusume-title-link {
            color: inherit;
            text-decoration: none;
        }

        .osusume-title-link:hover {
            text-decoration: underline;
        }

        .osusume-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: var(--card-gap);
        }

        .osusume-card {
            display: grid;
            grid-template-rows: 1fr auto;
            aspect-ratio: 1 / 1;
            background: #fff;
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin: 2%;
            transition: transform 200ms ease, box-shadow 200ms ease;
            text-decoration: none;
            color: inherit;
        }

        .osusume-card:hover {
            transform: scale(1.03);
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.24);
        }

        .osusume-image {
            background-size: cover;
            background-position: center;
            background-color: #d9d3cb;
        }

        .osusume-body {
            display: grid;
            gap: 4px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.92);
        }

        .osusume-name {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.3;
        }

        .osusume-price {
            font-size: 15px;
            color: #7a4b2a;
        }

        .osusume-location {
            font-size: 13px;
            color: #545454;
        }

        @media (max-width: 900px) {
            .osusume-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .osusume-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
        }
    </style>
    </head>

    <body>
    <?php if ($error): ?>
        <p><?php echo h($error); ?></p>
    <?php elseif (!$rows): ?>
        <p>現在表示できる物件がありません。</p>
    <?php else: ?>
         <div class="osusume-grid">
            <?php foreach ($rows as $row): ?>
                <?php
                    $imagePath = trim((string)($row['image_path'] ?? ''));
                    $cate = trim((string)($row['cate'] ?? ''));
                    $fallbackImageUrl = $cate === '土地' ? '/image/sale-rentland.webp' : '/image/salehouse.webp';
                    $imageUrl = $imagePath !== '' ? '/control/' . ltrim($imagePath, '/') : $fallbackImageUrl;
                    $style = "background-image: url('" . h($imageUrl) . "');";
                    $detailUrl = 'https://matsu-f.com/saleDetail.php?id=' . urlencode((string)$row['id']);
                ?>
                <a class="osusume-card" href="<?php echo h($detailUrl); ?>" target="_blank" rel="noopener">
                    <div class="osusume-image" style="<?php echo $style; ?>"></div>
                    <div class="osusume-body">
                        <div class="osusume-name"><?php echo h((string)$row['name']); ?></div>
                        <div class="osusume-location"><?php echo h((string)$row['location']); ?></div>
                        <div class="osusume-price"><?php echo number_format((int)$row['price']); ?> 万円</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <script>
        (function () {
            function getContentHeight() {
                var body = document.body;
                var doc = document.documentElement;
                if (!body || !doc) {
                    return 0;
                }
                var bodyHeight = Math.ceil(body.getBoundingClientRect().height);
                var docHeight = Math.ceil(doc.getBoundingClientRect().height);
                var offsetHeight = Math.max(body.offsetHeight, doc.offsetHeight);
                return Math.max(bodyHeight, docHeight, offsetHeight);
            }

            function sendHeight() {
                var height = getContentHeight();
                if (!height) {
                    return;
                }
                parent.postMessage({ type: 'matsu-sale-height', height: height }, 'https://matsu-f.com');
            }

            window.addEventListener('DOMContentLoaded', sendHeight);
            window.addEventListener('load', sendHeight);
            window.addEventListener('resize', sendHeight);
            if (window.ResizeObserver) {
                new ResizeObserver(sendHeight).observe(document.body);
            }
            setTimeout(sendHeight, 300);
            setTimeout(sendHeight, 1000);
        })();
    </script>
    </body>
</html>
