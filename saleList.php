<?php
require_once __DIR__ . '/control/lib/db.php';
$googleConfig = require __DIR__ . '/control/config/google.php';
$mapsApiKey = trim((string)($googleConfig['maps_js_api_key'] ?? ''));

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$rows = [];
    $error = '';

try {
    $pdo = getPDO();
    $sql = 'SELECT id, name, cate, price, location, lat, lng,
        (SELECT file_path
         FROM property_images
         WHERE property_type = \'sale\'
           AND status = 1
           AND property_id = sale_properties.id
          ORDER BY sort ASC, id ASC
          LIMIT 1) AS image_path
        FROM `sale_properties`
        WHERE `status` = 1
        ORDER BY CAST(sort AS UNSIGNED) ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
     $error = 'エラーが発生しました：' . $e->getMessage();}
?>
<!doctype html>
<html lang="ja">
    <head>
    <meta charset="UTF-8">
    <title>売り物件一覧 松永不動産</title>
    <style>
        <?php require __DIR__ . '/inc/siteHeaderFooterCss.php'; ?>
        :root {
            --card-radius: 12px;
            --card-gap: 16px;
            --card-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Monda, Helvetica, Arial, Sans-Serif, serif;
            background: #ffffff;
            color: #1b1b1b;
        }

        .site-main {
            width: 90%;
            margin: 0 auto;
        }

        .page-shell {
            padding: 5%;
        }

        .osusume-map {
            width: 100%;
            height: 540px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin: 0 0 20px;
            background: #d9d3cb;
        }

        .map-title {
            margin: 100px 0 12px;
            font-size: 20px;
            letter-spacing: 0.08em;
            text-align: center;
        }

        .osusume-title {
            margin: 0 0 12px;
            font-size: 22px;
            letter-spacing: 0.08em;
            text-align: center;
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
            :root {
                --card-gap: 8px;
            }

            .site-main {
                width: 100%;
            }

            .page-shell {
                padding: 1%;
            }

            .osusume-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .osusume-card {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                aspect-ratio: auto;
            }

            .osusume-image {
                width: 100%;
                min-height: 120px;
            }
        }
    </style>
    </head>

    <body>
    <?php
$siteHeroTitle = '物件一覧';
$siteNavActive = 'sale';
require __DIR__ . '/inc/siteHeader.php';
?>
<main class="site-main">
    <div class="page-shell">
    <?php if ($error): ?>
        <p><?php echo h($error); ?></p>
    <?php elseif (!$rows): ?>
        
    <?php else: ?>
        <?php if ($mapsApiKey !== ''): ?>
        <h2 class="osusume-title">物件マップ</h2>
        <div id="sale-map" class="osusume-map"></div>
        <?php endif; ?>
<h2 class="osusume-title" style="margin-top:100px">物件検索</h2>
        <div class="osusume-grid">
            <?php $index = 0; ?>
            <?php foreach ($rows as $row): ?>
                <?php
                    $imagePath = trim((string)($row['image_path'] ?? ''));
                    $cate = trim((string)($row['cate'] ?? ''));
                    $fallbackImageUrl = $cate === '土地' ? '/image/sale-rentland.webp' : '/image/salehouse.webp';
                    $imageUrl = $imagePath !== '' ? '/control/' . ltrim($imagePath, '/') : $fallbackImageUrl;
                    $style = "background-image: url('" . h($imageUrl) . "');";
                    $detailUrl = 'https://matsu-f.com/saleDetail.php?id=' . urlencode((string)$row['id']);
                    $isHidden = $index >= 20;
                    $index++;
                ?>
                <a class="osusume-card<?php echo $isHidden ? ' is-hidden' : ''; ?>" href="<?php echo h($detailUrl); ?>" target="_top">
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
    <?php if ($rows && $mapsApiKey !== ''): ?>
    <?php
        $markerRows = array_values(array_filter($rows, function ($row) {
            return isset($row['lat'], $row['lng']) && $row['lat'] !== '' && $row['lng'] !== '';
        }));
        $markers = array_map(function ($row) {
            return [
                'id' => (int)$row['id'],
                'name' => (string)$row['name'],
                'location' => (string)$row['location'],
                'lat' => (float)$row['lat'],
                'lng' => (float)$row['lng'],
            ];
        }, $markerRows);
    ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo h($mapsApiKey); ?>"></script>
    <script>
        (function () {
            var mapEl = document.getElementById('sale-map');
            if (!mapEl) {
                return;
            }
            var markers = <?php echo json_encode($markers, JSON_UNESCAPED_UNICODE); ?>;
            if (!markers.length) {
                mapEl.style.display = 'none';
                return;
            }
            var map = new google.maps.Map(mapEl, {
                center: { lat: markers[0].lat, lng: markers[0].lng },
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: false
            });
            var bounds = new google.maps.LatLngBounds();
            markers.forEach(function (item) {
                var pos = { lat: item.lat, lng: item.lng };
                var labelText = item.name || item.location || '';
                var marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: labelText,
                    label: labelText
                });
                marker.addListener('click', function () {
                    var detailUrl = 'https://matsu-f.com/saleDetail.php?id=' + encodeURIComponent(String(item.id));
                    if (window.top) {
                        window.top.location.href = detailUrl;
                        return;
                    }
                    window.location.href = detailUrl;
                });
                bounds.extend(pos);
            });
            if (markers.length > 1) {
                map.fitBounds(bounds);
            }
        })();
    </script>
    <?php endif; ?>
    </div>
    </main>
<?php
$siteFooterMaxWidth = '1200px';
require __DIR__ . '/inc/siteFooter.php';
?>
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

