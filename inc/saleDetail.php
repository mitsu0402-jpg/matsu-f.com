<?php
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../control/lib/db.php';
$googleConfig = require __DIR__ . '/../control/config/google.php';
$mapsApiKey = trim((string)($googleConfig['maps_js_api_key'] ?? ''));

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_bool(?string $value, string $yes, string $no): string
{
    if ($value === null || $value === '') {
        return '';
    }
    return $value === '1' ? $yes : $no;
}

function short_text(string $value, int $maxLength): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $maxLength) {
            return $value;
        }
        return mb_substr($value, 0, $maxLength, 'UTF-8') . '…';
    }
    if (strlen($value) <= $maxLength) {
        return $value;
    }
    return substr($value, 0, $maxLength) . '...';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$row = [];
$images = [];
$error = '';

try {
    if (!$id) {
        throw new RuntimeException('Property ID is required.');
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM `sale_properties` WHERE `id` = :id AND `status` = 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException('Property not found.');
    }

    $imgStmt = $pdo->prepare("SELECT file_path FROM property_images WHERE property_type = 'sale' AND status = 1 AND property_id = :id ORDER BY sort ASC, id ASC");
    $imgStmt->execute(['id' => $id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    try {
        $logStmt = $pdo->prepare('INSERT INTO page_views_log (page_path, page_title, ip, referrer, user_agent, property_type, property_id, created_at)
            VALUES (:page_path, :page_title, :ip, :referrer, :user_agent, :property_type, :property_id, NOW())');
        $logStmt->execute([
            ':page_path' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            ':page_title' => (string)($row['name'] ?? ''),
            ':ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ':referrer' => (string)($_SERVER['HTTP_REFERER'] ?? ''),
            ':user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ':property_type' => 'sale',
            ':property_id' => $id,
        ]);
    } catch (Throwable $e) {
        // Ignore logging errors to avoid breaking page rendering.
    }
} catch (Throwable $e) {
    $error = 'Error: ' . $e->getMessage();
}

$imageUrls = array_values(array_filter(array_map(function ($path) {
    $path = trim((string)$path);
    return $path !== '' ? '/control/' . ltrim($path, '/') : '';
}, $images)));

$displayPrice = isset($row['price']) && $row['price'] !== '' ? number_format((int)$row['price']) : '';
$displayLocation = (string)($row['location'] ?? '');
$displayTransaction = (string)($row['transaction_type'] ?? '');
$catchCopy = short_text((string)($row['catchCopy'] ?? ''), 20);
$description = trim((string)($row['setsumei'] ?? ''));
$optionalItems = [
    '種別' => (string)($row['cate'] ?? ''),
    '間取り' => (string)($row['floor_plan'] ?? ''),
    '土地面積' => (string)($row['land_area'] ?? ''),
    '建物面積' => (string)($row['building_area'] ?? ''),
    '築年数' => (string)($row['age'] ?? ''),
    '建築年月' => (string)($row['construction_date'] ?? ''),
    '新築/中古' => format_bool(isset($row['shinchiku']) ? (string)$row['shinchiku'] : '', '新築', '中古'),
    '構造' => (string)($row['structure'] ?? ''),
    '階数' => (string)($row['floors'] ?? ''),
    '方位' => (string)($row['direction'] ?? ''),
    'バルコニー・庭' => format_bool(isset($row['balcony_garden']) ? (string)$row['balcony_garden'] : '', '有り', '無し'),
    '駐車場' => format_bool(isset($row['parking']) ? (string)$row['parking'] : '', '有り', '無し'),
    '最寄駅距離' => (string)($row['distance_to_station'] ?? ''),
    '利用可能路線' => (string)($row['available_lines'] ?? ''),
    '近隣施設' => (string)($row['shops'] ?? ''),
    '教育施設' => (string)($row['schools'] ?? ''),
    '医療機関' => (string)($row['hospitals'] ?? ''),
    '公園・公共施設' => (string)($row['parks_facilities'] ?? ''),
    '土地権利' => (string)($row['land_rights'] ?? ''),
    '都市計画' => (string)($row['urban_planning'] ?? ''),
    '用途地域' => (string)($row['zoning'] ?? ''),
    '建ぺい率' => (string)($row['building_coverage_ratio'] ?? ''),
    '容積率' => (string)($row['floor_area_ratio'] ?? ''),
    '接道状況' => (string)($row['road_conditions'] ?? ''),
    '法令制限' => (string)($row['legal_restrictions'] ?? ''),
    'キッチン/バス/トイレ' => (string)($row['kitchen_bath_toilet'] ?? ''),
    '給湯・冷暖房' => (string)($row['heating_cooling'] ?? ''),
    'インターネット/TV' => (string)($row['internet_tv'] ?? ''),
    'セキュリティ' => (string)($row['security'] ?? ''),
    '管理費' => (string)($row['management_fee'] ?? ''),
    '修繕積立金' => (string)($row['repair_fund'] ?? ''),
    '管理形態' => (string)($row['management_type'] ?? ''),
    '管理会社' => (string)($row['management_company'] ?? ''),
    '現況' => (string)($row['current_status'] ?? ''),
    '建築確認番号' => (string)($row['building_confirmation_number'] ?? ''),
    '引渡し時期' => (string)($row['handover_time'] ?? ''),
    'ペット可否' => format_bool(isset($row['pets_allowed']) ? (string)$row['pets_allowed'] : '', '可', '不可'),
    'リフォーム履歴' => (string)($row['renovation_history'] ?? ''),
    '学校区' => (string)($row['school_district'] ?? ''),
];
$optionalItems = array_filter($optionalItems, function ($value) {
    return $value !== '' && $value !== null;
});
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <?php
    $pageName = trim((string)($row['name'] ?? ''));
    $pageTitle = $pageName !== '' ? $pageName . '　売り物件詳細　松永不動産' : '売り物件詳細　松永不動産';
    ?>
    <title><?php echo h($pageTitle); ?></title>
    <style>
        :root {
            --accent: #d7665b;
            --bg: #ffffff;
            --panel: #ffffff;
            --shadow: 0 16px 32px rgba(0, 0, 0, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Yu Mincho", "Hiragino Mincho ProN", "Hiragino Mincho Pro", "Noto Serif JP", serif;
            background: var(--bg);
            color: #1b1b1b;
        }

        .detail-shell {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            gap: 16px;
            padding: 0 16px 40px;
        }

        .image-hero {
            width: 100%;
            aspect-ratio: 5 / 3;
            background: #15C0D0;
            background-size: cover;
            background-position: center;
            border-radius: 10px 10px 0 0;
        }

        .hero-wrap {
            position: relative;
        }

        .hero-title {
            margin: 0 0 10px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #1b1b1b;
        }

        .hero-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.85);
            color: #3b2f28;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
        }

        .hero-arrow:disabled {
            opacity: 0.4;
            cursor: default;
        }

        .hero-arrow.left {
            left: 12px;
        }

        .hero-arrow.right {
            right: 12px;
        }

        .hero-counter {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.55);
            color: #ffffff;
            font-size: 12px;
            letter-spacing: 0.04em;
        }

        .like-button {
            align-self: flex-end;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            border: none;
            background: rgba(255, 255, 255, 0.92);
            color: #7a4b2a;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
        }

        .like-button.is-disabled {
            opacity: 0.6;
            cursor: default;
        }

        .detail-panel {
            background: var(--panel);
            border-radius: 0 0 10px 10px;
            box-shadow: var(--shadow);
        }

        .panel-row {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 16px;
            padding: 16px;
            align-items: end;
        }

        .thumb-grid {
            display: grid;
            gap: 8px;
        }

        .thumb-grid.is-three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .thumb-grid.is-four {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .image-thumb {
            background: #e9e1da;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .image-thumb.is-active {
            border-color: var(--accent);
        }

        .image-thumb img {
            width: 100%;
            height: 70px;
            object-fit: cover;
            display: block;
        }

        .info-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 8px;
            font-size: 14px;
            align-self: stretch;
        }

        .info-title {
            font-size: 18px;
            font-weight: 600;
            margin-top: 5px;
        }

        .catch-copy {
            margin-top: 6px;
            font-size: 14px;
            color: #4b3f35;
            line-height: 1.4;
        }

        .info-row {
            display: flex;
            gap: 8px;
            align-items: baseline;
        }

        .info-row.is-split {
            justify-content: space-between;
        }

        .info-left {
            display: flex;
            gap: 8px;
            align-items: baseline;
        }

        .info-label {
            min-width: 64px;
            color: #5a4f47;
        }

        .info-price {
            margin-top: 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--accent);
            text-align: right;
            margin-bottom: 5px;
        }

        .info-price .tax-label {
            font-size: 18px;
            font-weight: 600;
        }

        .map-section {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 16px;
        }

        .map-title {
            margin: 0 0 12px;
            font-size: 16px;
            font-weight: 600;
        }

        .map-canvas {
            width: 100%;
            height: 360px;
            border-radius: 10px;
            background: #e0f3f5;
        }

        .desc-section {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 16px;
        }

        .desc-title {
            margin: 0 0 12px;
            font-size: 16px;
            font-weight: 600;
        }

        .desc-body {
            font-size: 14px;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .optional-section {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 16px;
        }

        .optional-title {
            margin: 0 0 12px;
            font-size: 16px;
            font-weight: 600;
        }

        .optional-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 16px;
            font-size: 14px;
        }

        .optional-item {
            display: flex;
            gap: 8px;
        }

        .optional-label {
            min-width: 110px;
            color: #5a4f47;
        }

        @media (max-width: 900px) {
            .panel-row {
                grid-template-columns: 1fr;
            }

            .optional-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php if ($error): ?>
    <p><?php echo h($error); ?></p>
<?php else: ?>
    <div class="detail-shell">
        <?php
        $heroUrl = $imageUrls[0] ?? '';
        $heroStyle = $heroUrl !== '' ? "background-image: url('" . h($heroUrl) . "');" : '';
        ?>
        <div class="hero-title"><?php echo h((string)($row['name'] ?? '')); ?></div>
        <?php $likePagePath = '/saledetail/?id=' . urlencode((string)$id); ?>
        <div style="display:flex; justify-content:flex-end; margin: 0 0 8px;">
            <button
                class="like-button"
                id="sale-like-btn"
                type="button"
                data-type="sale"
                data-id="<?php echo h((string)$id); ?>"
                data-path="<?php echo h($likePagePath); ?>"
                aria-label="いいね">
                いいね！ <span id="sale-like-count">0</span>
            </button>
        </div>
        <div class="hero-wrap">
            <div class="image-hero" id="sale-hero" style="<?php echo $heroStyle; ?>"></div>
            <button class="hero-arrow left" id="sale-prev" type="button" aria-label="Previous image">&lsaquo;</button>
            <button class="hero-arrow right" id="sale-next" type="button" aria-label="Next image">&rsaquo;</button>
            <div class="hero-counter" id="sale-counter"></div>
        </div>
        <section class="detail-panel">
            <div class="panel-row">
                <div id="sale-thumbs">
                    <div class="thumb-grid is-three">
                        <?php if ($imageUrls): ?>
                            <?php foreach (array_slice($imageUrls, 0, 3) as $index => $url): ?>
                                <div class="image-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>" data-image="<?php echo h($url); ?>" data-index="<?php echo $index; ?>">
                                    <img src="<?php echo h($url); ?>" alt="">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for ($i = 0; $i < 3; $i++): ?>
                                <div class="image-thumb"></div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                    <div class="thumb-grid is-four" style="margin-top:8px;">
                        <?php if ($imageUrls): ?>
                            <?php foreach (array_slice($imageUrls, 3, 4) as $index => $url): ?>
                                <?php $realIndex = $index + 3; ?>
                                <div class="image-thumb" data-image="<?php echo h($url); ?>" data-index="<?php echo $realIndex; ?>">
                                    <img src="<?php echo h($url); ?>" alt="">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <div class="image-thumb"></div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-card">
                    <div>
                        <?php if ($catchCopy !== ''): ?>
                            <div class="catch-copy"><?php echo h($catchCopy); ?></div>
                        <?php endif; ?>
                        <div class="info-title">物件名：<?php echo h((string)$row['name']); ?></div>
                        <?php if ($displayLocation !== ''): ?>
                            <div class="info-row">
                                <div class="info-label">所在地：</div>
                                <div><?php echo h($displayLocation); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($displayTransaction !== '' || $displayPrice !== ''): ?>
                        <div class="info-row is-split">
                            <div class="info-left">
                                <?php if ($displayTransaction !== ''): ?>
                                    <div class="info-label">取引態様：</div>
                                    <div><?php echo h($displayTransaction); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($displayPrice !== ''): ?>
                                <div class="info-price"><span class="tax-label">税込</span> <?php echo h($displayPrice); ?> 万円</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php if ($description !== ''): ?>
            <section class="desc-section">
                <h2 class="desc-title">物件説明</h2>
                <div class="desc-body"><?php echo h($description); ?></div>
            </section>
        <?php endif; ?>
        <?php if ($mapsApiKey !== '' && !empty($row['lat']) && !empty($row['lng'])): ?>
            <section class="map-section">
                <h2 class="map-title">物件マップ</h2>
                <div id="sale-map" class="map-canvas"></div>
            </section>
        <?php endif; ?>
        <?php if ($optionalItems): ?>
            <section class="optional-section">
                <h2 class="optional-title">任意項目</h2>
                <div class="optional-grid">
                    <?php foreach ($optionalItems as $label => $value): ?>
                        <div class="optional-item">
                            <div class="optional-label"><?php echo h($label); ?></div>
                            <div><?php echo h((string)$value); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$error): ?>
<script>
    (function () {
        var hero = document.getElementById('sale-hero');
        var thumbWrap = document.getElementById('sale-thumbs');
        var prevBtn = document.getElementById('sale-prev');
        var nextBtn = document.getElementById('sale-next');
        var counter = document.getElementById('sale-counter');
        if (!hero || !thumbWrap || !prevBtn || !nextBtn || !counter) {
            return;
        }
        var thumbs = Array.prototype.slice.call(thumbWrap.querySelectorAll('.image-thumb[data-image]'));
        if (!thumbs.length) {
            prevBtn.disabled = true;
            nextBtn.disabled = true;
            return;
        }
        var currentIndex = 0;

        function setActive(index) {
            var clamped = (index + thumbs.length) % thumbs.length;
            var target = thumbs[clamped];
            if (!target) {
                return;
            }
            var url = target.getAttribute('data-image');
            if (!url) {
                return;
            }
            hero.style.backgroundImage = "url('" + url.replace(/'/g, "\\'") + "')";
            var active = thumbWrap.querySelector('.image-thumb.is-active');
            if (active) {
                active.classList.remove('is-active');
            }
            target.classList.add('is-active');
            currentIndex = clamped;
            counter.textContent = (clamped + 1) + '/' + thumbs.length;
        }

        thumbs.forEach(function (thumb, idx) {
            var dataIndex = parseInt(thumb.getAttribute('data-index') || '', 10);
            if (!Number.isNaN(dataIndex)) {
                idx = dataIndex;
            }
            thumb.setAttribute('data-order', String(idx));
        });
        thumbs.sort(function (a, b) {
            return parseInt(a.getAttribute('data-order'), 10) - parseInt(b.getAttribute('data-order'), 10);
        });

        thumbWrap.addEventListener('click', function (event) {
            var target = event.target.closest('.image-thumb');
            if (!target) {
                return;
            }
            var url = target.getAttribute('data-image');
            if (!url) {
                return;
            }
            hero.style.backgroundImage = "url('" + url.replace(/'/g, "\\'") + "')";
            var active = thumbWrap.querySelector('.image-thumb.is-active');
            if (active) {
                active.classList.remove('is-active');
            }
            target.classList.add('is-active');
            currentIndex = thumbs.indexOf(target);
        });

        prevBtn.addEventListener('click', function () {
            setActive(currentIndex - 1);
        });

        nextBtn.addEventListener('click', function () {
            setActive(currentIndex + 1);
        });

        setActive(currentIndex);

        var startX = 0;
        var startY = 0;
        var isTouching = false;
        var swipeThreshold = 40;

        hero.addEventListener('touchstart', function (event) {
            if (!window.matchMedia || !window.matchMedia('(max-width: 900px)').matches) {
                return;
            }
            var touch = event.touches[0];
            if (!touch) {
                return;
            }
            isTouching = true;
            startX = touch.clientX;
            startY = touch.clientY;
        }, { passive: true });

        hero.addEventListener('touchend', function (event) {
            if (!isTouching) {
                return;
            }
            isTouching = false;
            var touch = event.changedTouches[0];
            if (!touch) {
                return;
            }
            var diffX = touch.clientX - startX;
            var diffY = touch.clientY - startY;
            if (Math.abs(diffX) < swipeThreshold || Math.abs(diffY) > Math.abs(diffX)) {
                return;
            }
            if (diffX > 0) {
                setActive(currentIndex - 1);
            } else {
                setActive(currentIndex + 1);
            }
        }, { passive: true });
    })();
</script>
<?php endif; ?>

<?php if (!$error && $mapsApiKey !== '' && !empty($row['lat']) && !empty($row['lng'])): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo h($mapsApiKey); ?>"></script>
<script>
    (function () {
        var mapEl = document.getElementById('sale-map');
        if (!mapEl || !window.google || !google.maps) {
            return;
        }
        var lat = parseFloat('<?php echo h((string)$row['lat']); ?>');
        var lng = parseFloat('<?php echo h((string)$row['lng']); ?>');
        if (!lat || !lng) {
            return;
        }
        var center = { lat: lat, lng: lng };
        var map = new google.maps.Map(mapEl, {
            center: center,
            zoom: 14,
            mapTypeControl: false,
            streetViewControl: false
        });
        new google.maps.Marker({
            position: center,
            map: map
        });
    })();
</script>
<?php endif; ?>

<script>
    (function () {
        var btn = document.getElementById('sale-like-btn');
        var countEl = document.getElementById('sale-like-count');
        if (!btn || !countEl) {
            return;
        }
        var payload = new URLSearchParams({
            content_type: btn.getAttribute('data-type') || '',
            content_id: btn.getAttribute('data-id') || '0',
            page_path: btn.getAttribute('data-path') || ''
        });

        function updateCount() {
            fetch('/api/like.php?' + payload.toString())
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && typeof data.count === 'number') {
                        countEl.textContent = String(data.count);
                    }
                })
                .catch(function () {});
        }

        btn.addEventListener('click', function () {
            if (btn.classList.contains('is-disabled')) {
                return;
            }
            fetch('/api/like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: payload.toString()
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && typeof data.count === 'number') {
                        countEl.textContent = String(data.count);
                    }
                    if (data && data.liked === false) {
                        btn.classList.add('is-disabled');
                    }
                })
                .catch(function () {});
        });

        updateCount();
    })();
</script>
<script>
    (function () {
        var params = new URLSearchParams(window.location.search);
        var itemId = params.get('id') || '';
        var body = new URLSearchParams();
        body.set('page_path', window.location.pathname || '/');
        body.set('item_id', itemId);

        if (navigator.sendBeacon) {
            navigator.sendBeacon('/api/page-view.php', body);
            return;
        }

        fetch('/api/page-view.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: body.toString()
        }).catch(function () {});
    })();
</script>
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
            parent.postMessage({ type: 'matsu-sale-detail-height', height: height }, 'https://matsu-f.com');
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
