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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$row = [];
$images = [];
$error = '';

try {
    if (!$id) {
        throw new RuntimeException('物件IDが指定されていません。');
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM `rent_properties` WHERE `id` = :id AND `status` = 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException('指定された物件が見つかりません。');
    }

    $imgStmt = $pdo->prepare("SELECT file_path FROM property_images WHERE property_type = 'rent' AND status = 1 AND property_id = :id ORDER BY sort ASC, id ASC");
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
            ':property_type' => 'rent',
            ':property_id' => $id,
        ]);
    } catch (Throwable $e) {
        // Ignore logging errors to avoid breaking page rendering.
    }
} catch (Throwable $e) {
    $error = 'エラーが発生しました：' . $e->getMessage();
}

$imageUrls = array_values(array_filter(array_map(function ($path) {
    $path = trim((string)$path);
    return $path !== '' ? '/control/' . ltrim($path, '/') : '';
}, $images)));

$detailItems = [
    '種別' => (string)($row['cate'] ?? ''),
    '家賃' => isset($row['price']) && $row['price'] !== '' ? '月額　' . number_format((int)$row['price']) . '円' : '',
    '所在地' => (string)($row['location'] ?? ''),
    '取引態様' => (string)($row['transaction_type'] ?? ''),
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
    'その他' => (string)($row['sonota'] ?? ''),
    '備考' => (string)($row['notes'] ?? ''),
];
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <?php
    $pageName = trim((string)($row['name'] ?? ''));
    $pageTitle = $pageName !== '' ? $pageName . '　貸し物件詳細　松永不動産' : '貸し物件詳細　松永不動産';
    ?>
    <title><?php echo h($pageTitle); ?></title>
    <style>
        :root {
            --accent: #7a4b2a;
            --line: #e5e1db;
            --bg: #f7f4f0;
            --card: #ffffff;
            --shadow: 0 16px 36px rgba(0, 0, 0, 0.14);
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
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            gap: 28px;
            padding: 0 16px 40px;
        }

        .detail-header {
            display: grid;
            gap: 10px;
        }

        .detail-title {
            font-size: 28px;
            letter-spacing: 0.06em;
            margin: 0;
        }

        .detail-catch {
            font-size: 16px;
            color: #5f4b3a;
        }

        .detail-main {
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
        }

        .image-card {
            background: var(--card);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .image-hero {
            width: 100%;
            aspect-ratio: 4 / 3;
            background-size: cover;
            background-position: center;
            background-color: #d9d3cb;
        }

        .hero-wrap {
            position: relative;
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

        .image-thumbs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 10px;
            padding: 12px;
            background: #fff;
        }

        .image-thumb {
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .image-thumb.is-active {
            border-color: var(--accent);
        }

        .image-thumb img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }

        .detail-card {
            background: var(--card);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .price {
            font-size: 24px;
            font-weight: 600;
            color: var(--accent);
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 14px;
        }

        .detail-table th,
        .detail-table td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        .detail-table th {
            width: 34%;
            color: #4b3f35;
            font-weight: 600;
            background: #faf8f6;
        }

        .detail-section {
            display: grid;
            gap: 12px;
        }

        .map-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            background: #d9d3cb;
            height: 420px;
        }

        @media (max-width: 900px) {
            .detail-main {
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
        <header class="detail-header">
            <h1 class="detail-title"><?php echo h((string)$row['name']); ?></h1>
            <?php if (!empty($row['catchCopy'])): ?>
                <div class="detail-catch"><?php echo h((string)$row['catchCopy']); ?></div>
            <?php endif; ?>
        </header>

        <section class="detail-main">
            <div class="image-card">
                <?php
                $heroUrl = $imageUrls[0] ?? '';
                $heroStyle = $heroUrl !== '' ? "background-image: url('" . h($heroUrl) . "');" : '';
                ?>
                <?php $likePagePath = '/rentdetail/?id=' . urlencode((string)$id); ?>
                <div style="display:flex; justify-content:flex-end; margin: 0 0 8px;">
                    <button
                        class="like-button"
                        id="rent-like-btn"
                        type="button"
                        data-type="rent"
                        data-id="<?php echo h((string)$id); ?>"
                        data-path="<?php echo h($likePagePath); ?>"
                        aria-label="いいね">
                        いいね！ <span id="rent-like-count">0</span>
                    </button>
                </div>
                <div class="hero-wrap">
                    <div class="image-hero" id="rent-hero" style="<?php echo $heroStyle; ?>"></div>
                    <button class="hero-arrow left" id="rent-prev" type="button" aria-label="Previous image">&lsaquo;</button>
                    <button class="hero-arrow right" id="rent-next" type="button" aria-label="Next image">&rsaquo;</button>
                    <div class="hero-counter" id="rent-counter"></div>
                </div>
                <?php if ($imageUrls): ?>
                    <div class="image-thumbs" id="rent-thumbs">
                        <?php foreach ($imageUrls as $index => $url): ?>
                            <div class="image-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>" data-image="<?php echo h($url); ?>">
                                <img src="<?php echo h($url); ?>" alt="">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-card detail-section">
                <?php if (!empty($row['price'])): ?>
                    <div class="price">家賃　月額<?php echo number_format((int)$row['price']); ?>円</div>
                <?php endif; ?>
                <?php if (!empty($row['location'])): ?>
                    <div><?php echo h((string)$row['location']); ?></div>
                <?php endif; ?>
                <table class="detail-table">
                    <tbody>
                    <?php foreach ($detailItems as $label => $value): ?>
                        <?php if ($value !== ''): ?>
                            <tr>
                                <th><?php echo h($label); ?></th>
                                <td><?php echo h((string)$value); ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($mapsApiKey !== '' && !empty($row['lat']) && !empty($row['lng'])): ?>
            <div class="detail-section">
                <div id="sale-map" class="map-card"></div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    (function () {
        var btn = document.getElementById('rent-like-btn');
        var countEl = document.getElementById('rent-like-count');
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

<?php if (!$error && $imageUrls): ?>
<script>
    (function () {
        var hero = document.getElementById('rent-hero');
        var thumbWrap = document.getElementById('rent-thumbs');
        var prevBtn = document.getElementById('rent-prev');
        var nextBtn = document.getElementById('rent-next');
        var counter = document.getElementById('rent-counter');
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
            counter.textContent = (currentIndex + 1) + '/' + thumbs.length;
        });

        prevBtn.addEventListener('click', function () {
            setActive(currentIndex - 1);
        });

        nextBtn.addEventListener('click', function () {
            setActive(currentIndex + 1);
        });

        setActive(currentIndex);
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
            parent.postMessage({ type: 'matsu-rent-detail-height', height: height }, 'https://matsu-f.com');
            parent.postMessage({ type: 'matsu-rent-height', height: height }, 'https://matsu-f.com');
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
