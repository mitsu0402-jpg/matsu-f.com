<?php
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../control/lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$rows = [];
$error = '';

try {
    $pdo = getPDO();
    $sql = 'SELECT id, name, cate, price, location,
        (SELECT file_path
         FROM property_images
         WHERE property_type = \'sale\'
           AND status = 1
           AND property_id = sale_properties.id
         ORDER BY sort ASC, id ASC
         LIMIT 1) AS image_path
        FROM `sale_properties`
        WHERE `osusume` = 1
        ORDER BY lastUpdateDate DESC, id DESC';
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
            --card-size: 240px;
            --card-radius: 12px;
            --card-gap: 16px;
            --card-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Yu Mincho", "Hiragino Mincho ProN", "Hiragino Mincho Pro", "Noto Serif JP", serif;
            background: #f6f4f1;
            color: #1b1b1b;
        }

        .osusume-carousel {
            overflow: hidden;
            width: 100%;
        }

        .osusume-title {
            margin: 0 0 12px;
            font-size: 22px;
            letter-spacing: 0.08em;
            text-align: center;
        }

        .osusume-track {
            display: flex;
            gap: var(--card-gap);
            padding-bottom: 4px;
        }

        .osusume-card {
            display: grid;
            grid-template-rows: 1fr auto;
            width: var(--card-size);
            flex: 0 0 var(--card-size);
            aspect-ratio: 1 / 1;
            background: #fff;
            border-radius: var(--card-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            scroll-snap-align: start;
            scroll-snap-stop: always;
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
            font-size: 15px;
            font-weight: 600;
            line-height: 1.3;
        }

        .osusume-price {
            font-size: 14px;
            color: #7a4b2a;
        }

        .osusume-location {
            font-size: 12px;
            color: #545454;
        }

        @media (max-width: 600px) {
            :root {
                --card-size: 180px;
            }
        }
    </style>
</head>

<body>
<?php if ($error): ?>
    <p><?php echo h($error); ?></p>
<?php elseif (!$rows): ?>
    <p>該当する物件がありません。</p>
<?php else: ?>
    <h2 class="osusume-title">おすすめ物件</h2>
    <div class="osusume-carousel" id="osusume-carousel">
        <div class="osusume-track">
        <?php foreach ($rows as $row): ?>
            <?php
                $imagePath = trim((string)($row['image_path'] ?? ''));
                $imageUrl = $imagePath !== '' ? '/control/' . ltrim($imagePath, '/') : '';
                $style = $imageUrl !== '' ? "background-image: url('" . h($imageUrl) . "');" : '';
            ?>
            <article class="osusume-card">
                <div class="osusume-image" style="<?php echo $style; ?>"></div>
                <div class="osusume-body">
                    <div class="osusume-name"><?php echo h((string)$row['name']); ?></div>
                    <div class="osusume-location"><?php echo h((string)$row['location']); ?></div>
                    <div class="osusume-price"><?php echo number_format((int)$row['price']); ?> 円</div>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    function setupCarousel() {
        var carousel = document.getElementById('osusume-carousel');
        if (!carousel) return;

        var track = carousel.querySelector('.osusume-track');
        var cards = carousel.querySelectorAll('.osusume-card');
        if (!track || cards.length < 2) return;

        var gapValue = getComputedStyle(track).gap || '0';
        var gap = parseFloat(gapValue) || 0;
        var step = cards[0].getBoundingClientRect().width + gap;
        if (!step) return;

        track.style.transition = 'transform 700ms ease';
        var index = 0;
        var intervalMs = 4500;

        setInterval(function () {
            index = (index + 1) % cards.length;
            if (index === 0) {
                track.style.transition = 'none';
                track.style.transform = 'translateX(0)';
                track.offsetHeight;
                track.style.transition = 'transform 700ms ease';
                return;
            }
            track.style.transform = 'translateX(' + (-step * index) + 'px)';
        }, intervalMs);
    }

    if (document.readyState === 'complete') {
        setupCarousel();
    } else {
        window.addEventListener('load', setupCarousel);
    }
})();
</script>
</body>
</html>
