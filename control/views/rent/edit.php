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
    'notes',
    'location',
    'price',
    'deposit_fee',
    'key_money',
    'guarantee_fee',
    'deduction_fee',
    'floor_plan',
    'floor_area',
    'age',
    'structure',
    'direction',
    'floor_info',
    'distance_to_station',
    'available_lines',
    'room_facilities',
    'internet',
    'furnished',
    'balcony_garden',
    'parking',
    'pets_allowed',
    'instrument_allowed',
    'contract_type',
    'contract_period',
    'renewal_fee',
    'insurance_required',
    'management_contact',
    'shops',
    'schools',
    'hospitals',
    'public_facilities',
    'shared_spaces',
    'sonota',
    'sort',
];

$defaults = array_fill_keys($fields, '');
$defaults['status'] = 1;
$defaults['sort'] = 0;
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
        $values['sort'] = (int)($values['sort'] !== '' ? $values['sort'] : 0);

        $now = date('Y-m-d H:i:s');

        if ($id) {
            $setParts = [];
            foreach ($fields as $field) {
                $setParts[] = "`{$field}` = :{$field}";
            }
            $sql = "UPDATE `rent_properties` SET " . implode(', ', $setParts) . ", `lastUpdateDate` = :lastUpdateDate WHERE `id` = :id";
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
            $sql = "INSERT INTO `rent_properties` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
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
        $stmt = $pdo->prepare('SELECT * FROM `rent_properties` WHERE `id` = :id');
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
  <div><label>備考（短文） <input type="text" name="notes" value="<?php echo h((string)$values['notes']); ?>"></label></div>
  <div><label>所在地 <input type="text" name="location" value="<?php echo h((string)$values['location']); ?>"></label></div>
  <div><label>賃料 <input type="text" name="price" value="<?php echo h((string)$values['price']); ?>"></label></div>
  <div><label>敷金 <input type="text" name="deposit_fee" value="<?php echo h((string)$values['deposit_fee']); ?>"></label></div>
  <div><label>礼金 <input type="text" name="key_money" value="<?php echo h((string)$values['key_money']); ?>"></label></div>
  <div><label>保証料 <input type="text" name="guarantee_fee" value="<?php echo h((string)$values['guarantee_fee']); ?>"></label></div>
  <div><label>敷引き <input type="text" name="deduction_fee" value="<?php echo h((string)$values['deduction_fee']); ?>"></label></div>
  <div><label>間取り <input type="text" name="floor_plan" value="<?php echo h((string)$values['floor_plan']); ?>"></label></div>
  <div><label>専有面積 <input type="text" name="floor_area" value="<?php echo h((string)$values['floor_area']); ?>"></label></div>
  <div><label>築年数 <input type="text" name="age" value="<?php echo h((string)$values['age']); ?>"></label></div>
  <div><label>構造 <input type="text" name="structure" value="<?php echo h((string)$values['structure']); ?>"></label></div>
  <div><label>方位 <input type="text" name="direction" value="<?php echo h((string)$values['direction']); ?>"></label></div>
  <div><label>階数情報 <input type="text" name="floor_info" value="<?php echo h((string)$values['floor_info']); ?>"></label></div>
  <div><label>最寄駅距離 <input type="text" name="distance_to_station" value="<?php echo h((string)$values['distance_to_station']); ?>"></label></div>
  <div><label>利用可能路線 <input type="text" name="available_lines" value="<?php echo h((string)$values['available_lines']); ?>"></label></div>
  <div><label>室内設備 <input type="text" name="room_facilities" value="<?php echo h((string)$values['room_facilities']); ?>"></label></div>
  <div><label>インターネット <input type="text" name="internet" value="<?php echo h((string)$values['internet']); ?>"></label></div>
  <div><label>家具・家電付き <input type="text" name="furnished" value="<?php echo h((string)$values['furnished']); ?>"></label></div>
  <div><label>バルコニー・庭 <input type="text" name="balcony_garden" value="<?php echo h((string)$values['balcony_garden']); ?>"></label></div>
  <div><label>駐車場 <input type="text" name="parking" value="<?php echo h((string)$values['parking']); ?>"></label></div>
  <div><label>ペット可否 <input type="text" name="pets_allowed" value="<?php echo h((string)$values['pets_allowed']); ?>"></label></div>
  <div><label>楽器可否 <input type="text" name="instrument_allowed" value="<?php echo h((string)$values['instrument_allowed']); ?>"></label></div>
  <div><label>契約種別 <input type="text" name="contract_type" value="<?php echo h((string)$values['contract_type']); ?>"></label></div>
  <div><label>契約期間 <input type="text" name="contract_period" value="<?php echo h((string)$values['contract_period']); ?>"></label></div>
  <div><label>更新料 <input type="text" name="renewal_fee" value="<?php echo h((string)$values['renewal_fee']); ?>"></label></div>
  <div><label>保険加入 <input type="text" name="insurance_required" value="<?php echo h((string)$values['insurance_required']); ?>"></label></div>
  <div><label>管理会社連絡先 <input type="text" name="management_contact" value="<?php echo h((string)$values['management_contact']); ?>"></label></div>
  <div><label>近隣施設 <input type="text" name="shops" value="<?php echo h((string)$values['shops']); ?>"></label></div>
  <div><label>教育施設 <input type="text" name="schools" value="<?php echo h((string)$values['schools']); ?>"></label></div>
  <div><label>医療機関 <input type="text" name="hospitals" value="<?php echo h((string)$values['hospitals']); ?>"></label></div>
  <div><label>公共施設 <input type="text" name="public_facilities" value="<?php echo h((string)$values['public_facilities']); ?>"></label></div>
  <div>
    <label>共有部
      <textarea name="shared_spaces" rows="3"><?php echo h((string)$values['shared_spaces']); ?></textarea>
    </label>
  </div>
  <div><label>その他 <input type="text" name="sonota" value="<?php echo h((string)$values['sonota']); ?>"></label></div>
  <div><label>並び順 <input type="number" name="sort" value="<?php echo h((string)$values['sort']); ?>"></label></div>
  <div>
    <button type="submit">保存</button>
  </div>
</form>

