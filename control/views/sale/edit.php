<?php
require_once __DIR__ . '/../../lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function post_value(string $key): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

$fields = [
    'status',
    'name',
    'cate',
    'catchCopy',
    'location',
    'price',
    'transaction_type',
    'floor_plan',
    'land_area',
    'building_area',
    'age',
    'construction_date',
    'shinchiku',
    'structure',
    'floors',
    'direction',
    'balcony_garden',
    'parking',
    'distance_to_station',
    'available_lines',
    'shops',
    'schools',
    'hospitals',
    'parks_facilities',
    'land_rights',
    'urban_planning',
    'zoning',
    'building_coverage_ratio',
    'floor_area_ratio',
    'road_conditions',
    'legal_restrictions',
    'kitchen_bath_toilet',
    'heating_cooling',
    'internet_tv',
    'security',
    'management_fee',
    'repair_fund',
    'management_type',
    'management_company',
    'current_status',
    'building_confirmation_number',
    'handover_time',
    'pets_allowed',
    'renovation_history',
    'school_district',
    'sonota',
    'notes',
    'sort',
    'osusume',
];

$defaults = array_fill_keys($fields, '');
$defaults['status'] = 1;
$defaults['shinchiku'] = 0;
$defaults['sort'] = 0;
$defaults['osusume'] = 0;
$values = $defaults;
$message = '';
$error = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: $id;

        foreach ($fields as $field) {
            $values[$field] = post_value($field);
        }

        $values['status'] = (int)($values['status'] !== '' ? $values['status'] : 1);
        $values['shinchiku'] = (int)($values['shinchiku'] !== '' ? $values['shinchiku'] : 0);
        $values['sort'] = (int)($values['sort'] !== '' ? $values['sort'] : 0);
        $values['osusume'] = (int)($values['osusume'] !== '' ? $values['osusume'] : 0);

        $now = date('Y-m-d H:i:s');

        if ($id) {
            $setParts = [];
            foreach ($fields as $field) {
                $setParts[] = "`{$field}` = :{$field}";
            }
            $sql = "UPDATE `sale_properties` SET " . implode(', ', $setParts) . ", `lastUpdateDate` = :lastUpdateDate WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $params = $values;
            $params['lastUpdateDate'] = $now;
            $params['id'] = $id;
            $stmt->execute($params);
            $message = '更新しました。';
        } else {
            $columns = array_merge($fields, ['firstAddDate', 'lastUpdateDate']);
            $placeholders = array_map(function ($col) {
                return ":{$col}";
            }, $columns);
            $sql = "INSERT INTO `sale_properties` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $params = $values;
            $params['firstAddDate'] = $now;
            $params['lastUpdateDate'] = $now;
            $stmt->execute($params);
            $id = (int)$pdo->lastInsertId();
            $message = '登録しました。';
        }
    }

    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM `sale_properties` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $values = array_merge($values, $row);
        }
    }
} catch (Throwable $e) {
    $error = 'エラーが発生しました。' . $e->getMessage();
}
?>

<?php if ($message): ?>
  <p><?php echo h($message); ?></p>
<?php endif; ?>
<?php if ($error): ?>
  <p><?php echo h($error); ?></p>
<?php endif; ?>

<form method="post" action="">
  <input type="hidden" name="id" value="<?php echo h((string)$id); ?>">
  <div>
    <label>公開状態
      <select name="status">
        <option value="1" <?php echo ((int)$values['status'] === 1) ? 'selected' : ''; ?>>公開</option>
        <option value="0" <?php echo ((int)$values['status'] === 0) ? 'selected' : ''; ?>>下書き</option>
      </select>
    </label>
  </div>
  <div><label>物件名 <input type="text" name="name" value="<?php echo h((string)$values['name']); ?>"></label></div>
  <div><label>種別 <input type="text" name="cate" value="<?php echo h((string)$values['cate']); ?>"></label></div>
  <div><label>キャッチコピー <input type="text" name="catchCopy" value="<?php echo h((string)$values['catchCopy']); ?>"></label></div>
  <div><label>所在地 <input type="text" name="location" value="<?php echo h((string)$values['location']); ?>"></label></div>
  <div><label>価格 <input type="text" name="price" value="<?php echo h((string)$values['price']); ?>"></label></div>
  <div><label>取引態様 <input type="text" name="transaction_type" value="<?php echo h((string)$values['transaction_type']); ?>"></label></div>
  <div><label>間取り <input type="text" name="floor_plan" value="<?php echo h((string)$values['floor_plan']); ?>"></label></div>
  <div><label>土地面積 <input type="text" name="land_area" value="<?php echo h((string)$values['land_area']); ?>"></label></div>
  <div><label>建物面積 <input type="text" name="building_area" value="<?php echo h((string)$values['building_area']); ?>"></label></div>
  <div><label>築年数 <input type="text" name="age" value="<?php echo h((string)$values['age']); ?>"></label></div>
  <div><label>建築年月 <input type="text" name="construction_date" value="<?php echo h((string)$values['construction_date']); ?>"></label></div>
  <div>
    <label>新築
      <select name="shinchiku">
        <option value="1" <?php echo ((int)$values['shinchiku'] === 1) ? 'selected' : ''; ?>>新築</option>
        <option value="0" <?php echo ((int)$values['shinchiku'] === 0) ? 'selected' : ''; ?>>中古</option>
      </select>
    </label>
  </div>
  <div><label>構造 <input type="text" name="structure" value="<?php echo h((string)$values['structure']); ?>"></label></div>
  <div><label>階数 <input type="text" name="floors" value="<?php echo h((string)$values['floors']); ?>"></label></div>
  <div><label>方位 <input type="text" name="direction" value="<?php echo h((string)$values['direction']); ?>"></label></div>
  <div><label>バルコニー・庭 <input type="text" name="balcony_garden" value="<?php echo h((string)$values['balcony_garden']); ?>"></label></div>
  <div><label>駐車場 <input type="text" name="parking" value="<?php echo h((string)$values['parking']); ?>"></label></div>
  <div><label>最寄駅距離 <input type="text" name="distance_to_station" value="<?php echo h((string)$values['distance_to_station']); ?>"></label></div>
  <div><label>利用可能路線 <input type="text" name="available_lines" value="<?php echo h((string)$values['available_lines']); ?>"></label></div>
  <div><label>近隣施設 <input type="text" name="shops" value="<?php echo h((string)$values['shops']); ?>"></label></div>
  <div><label>教育施設 <input type="text" name="schools" value="<?php echo h((string)$values['schools']); ?>"></label></div>
  <div><label>医療機関 <input type="text" name="hospitals" value="<?php echo h((string)$values['hospitals']); ?>"></label></div>
  <div><label>公園・公共施設 <input type="text" name="parks_facilities" value="<?php echo h((string)$values['parks_facilities']); ?>"></label></div>
  <div><label>土地権利 <input type="text" name="land_rights" value="<?php echo h((string)$values['land_rights']); ?>"></label></div>
  <div><label>都市計画 <input type="text" name="urban_planning" value="<?php echo h((string)$values['urban_planning']); ?>"></label></div>
  <div><label>用途地域 <input type="text" name="zoning" value="<?php echo h((string)$values['zoning']); ?>"></label></div>
  <div><label>建ぺい率 <input type="text" name="building_coverage_ratio" value="<?php echo h((string)$values['building_coverage_ratio']); ?>"></label></div>
  <div><label>容積率 <input type="text" name="floor_area_ratio" value="<?php echo h((string)$values['floor_area_ratio']); ?>"></label></div>
  <div><label>接道状況 <input type="text" name="road_conditions" value="<?php echo h((string)$values['road_conditions']); ?>"></label></div>
  <div><label>法令制限 <input type="text" name="legal_restrictions" value="<?php echo h((string)$values['legal_restrictions']); ?>"></label></div>
  <div><label>キッチン/バス/トイレ <input type="text" name="kitchen_bath_toilet" value="<?php echo h((string)$values['kitchen_bath_toilet']); ?>"></label></div>
  <div><label>給湯・冷暖房 <input type="text" name="heating_cooling" value="<?php echo h((string)$values['heating_cooling']); ?>"></label></div>
  <div><label>インターネット/TV <input type="text" name="internet_tv" value="<?php echo h((string)$values['internet_tv']); ?>"></label></div>
  <div><label>セキュリティ <input type="text" name="security" value="<?php echo h((string)$values['security']); ?>"></label></div>
  <div><label>管理費 <input type="text" name="management_fee" value="<?php echo h((string)$values['management_fee']); ?>"></label></div>
  <div><label>修繕積立金 <input type="text" name="repair_fund" value="<?php echo h((string)$values['repair_fund']); ?>"></label></div>
  <div><label>管理形態 <input type="text" name="management_type" value="<?php echo h((string)$values['management_type']); ?>"></label></div>
  <div><label>管理会社 <input type="text" name="management_company" value="<?php echo h((string)$values['management_company']); ?>"></label></div>
  <div><label>現況 <input type="text" name="current_status" value="<?php echo h((string)$values['current_status']); ?>"></label></div>
  <div><label>建築確認番号 <input type="text" name="building_confirmation_number" value="<?php echo h((string)$values['building_confirmation_number']); ?>"></label></div>
  <div><label>引渡し時期 <input type="text" name="handover_time" value="<?php echo h((string)$values['handover_time']); ?>"></label></div>
  <div><label>ペット可否 <input type="text" name="pets_allowed" value="<?php echo h((string)$values['pets_allowed']); ?>"></label></div>
  <div>
    <label>リフォーム履歴
      <textarea name="renovation_history" rows="3"><?php echo h((string)$values['renovation_history']); ?></textarea>
    </label>
  </div>
  <div><label>学校区 <input type="text" name="school_district" value="<?php echo h((string)$values['school_district']); ?>"></label></div>
  <div><label>その他 <input type="text" name="sonota" value="<?php echo h((string)$values['sonota']); ?>"></label></div>
  <div>
    <label>備考
      <textarea name="notes" rows="4"><?php echo h((string)$values['notes']); ?></textarea>
    </label>
  </div>
  <div><label>並び順 <input type="number" name="sort" value="<?php echo h((string)$values['sort']); ?>"></label></div>
  <div>
    <label>おすすめ
      <select name="osusume">
        <option value="1" <?php echo ((int)$values['osusume'] === 1) ? 'selected' : ''; ?>>おすすめ</option>
        <option value="0" <?php echo ((int)$values['osusume'] === 0) ? 'selected' : ''; ?>>通常</option>
      </select>
    </label>
  </div>
  <div>
    <button type="submit">保存</button>
  </div>
</form>

