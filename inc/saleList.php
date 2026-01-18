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
    $error = 'エラーが発生しました。' . $e->getMessage();
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
            font-size: 22px;
            letter-spacing: 0.08em;
            text-align: center;
        }

        .osusume-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
        }

        .osusume-card.is-hidden {
            display: none;
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

        @media (max-width: 900px) {
            .osusume-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .osusume-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
    </head>


    <body>
    <?php if ($error): ?>
        <p><?php echo h($error); ?></p>
    <?php elseif (!$rows): ?>
        
    <?php else: ?>
        <h2 class="osusume-title">売り物件一覧</h2>
        <div class="osusume-grid">
            <?php $index = 0; ?>
            <?php foreach ($rows as $row): ?>
                <?php
                    $imagePath = trim((string)($row['image_path'] ?? ''));
                    $imageUrl = $imagePath !== '' ? '/control/' . ltrim($imagePath, '/') : '';
                    $style = $imageUrl !== '' ? "background-image: url('" . h($imageUrl) . "');" : '';
                    $isHidden = $index >= 20;
                    $index++;
                ?>
                <article class="osusume-card<?php echo $isHidden ? ' is-hidden' : ''; ?>">
                    <div class="osusume-image" style="<?php echo $style; ?>"></div>
                    <div class="osusume-body">
                        <div class="osusume-name"><?php echo h((string)$row['name']); ?></div>
                        <div class="osusume-price"><?php echo number_format((int)$row['price']); ?> 万円</div>
                        <div class="osusume-location"><?php echo h((string)$row['location']); ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <script>
        (function () {
            var hiddenCards = Array.prototype.slice.call(
                document.querySelectorAll('.osusume-card.is-hidden')
            );
            if (!hiddenCards.length) {
                return;
            }
            var batchSize = 8;
            var threshold = 200;

            function revealNextBatch() {
                var count = Math.min(batchSize, hiddenCards.length);
                for (var i = 0; i < count; i++) {
                    var card = hiddenCards.shift();
                    if (card) {
                        card.classList.remove('is-hidden');
                    }
                }
            }

            function maybeReveal() {
                var scrollY = window.scrollY || window.pageYOffset;
                var viewportBottom = scrollY + window.innerHeight;
                var docHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
                if (viewportBottom >= docHeight - threshold) {
                    revealNextBatch();
                }
            }

            window.addEventListener('scroll', maybeReveal, { passive: true });
            window.addEventListener('load', maybeReveal);
        })();
    </script>
    </body>
</html>
