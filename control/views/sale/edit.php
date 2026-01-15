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

function post_bool(string $key): string
{
    if (!isset($_POST[$key])) {
        return '0';
    }
    $value = $_POST[$key];
    return ($value === '1' || $value === 1 || $value === true || $value === 'true' || $value === 't' || $value === 'yes') ? '1' : '0';
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
$imageRows = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: $id;

        foreach ($fields as $field) {
            $values[$field] = post_value($field);
        }

        $values['parking'] = post_bool('parking');
        $values['pets_allowed'] = post_bool('pets_allowed');
        $values['balcony_garden'] = post_bool('balcony_garden');
        $values['osusume'] = post_bool('osusume');

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

        if ($id) {
            $deleteIds = isset($_POST['delete_image']) && is_array($_POST['delete_image']) ? $_POST['delete_image'] : [];
            if ($deleteIds) {
                $in = implode(',', array_fill(0, count($deleteIds), '?'));
                $stmt = $pdo->prepare("SELECT id, file_path FROM property_images WHERE property_type = 'sale' AND property_id = ? AND id IN ($in)");
                $stmt->execute(array_merge([$id], $deleteIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $filePath = __DIR__ . '/../../' . $row['file_path'];
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
                $del = $pdo->prepare("DELETE FROM property_images WHERE property_type = 'sale' AND property_id = ? AND id IN ($in)");
                $del->execute(array_merge([$id], $deleteIds));
            }

            if (isset($_POST['image_sort']) && is_array($_POST['image_sort'])) {
                $sortStmt = $pdo->prepare("UPDATE property_images SET sort = :sort WHERE id = :id AND property_type = 'sale' AND property_id = :property_id");
                foreach ($_POST['image_sort'] as $imageId => $sortValue) {
                    $sortStmt->execute([
                        'sort' => (int)$sortValue,
                        'id' => (int)$imageId,
                        'property_id' => $id,
                    ]);
                }
            }

            if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $uploadDir = __DIR__ . '/../../uploads/sale';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $insertImg = $pdo->prepare('INSERT INTO property_images (property_type, property_id, file_path, sort, status, created_at) VALUES (\'sale\', :property_id, :file_path, :sort, 1, NOW())');
                $fileCount = count($_FILES['images']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $tmpName = $_FILES['images']['tmp_name'][$i];
                    $mime = mime_content_type($tmpName);
                    if (!isset($allowed[$mime])) {
                        continue;
                    }
                    $ext = $allowed[$mime];
                    $fileName = sprintf('sale_%d_%s_%04d.%s', $id, date('YmdHis'), $i, $ext);
                    $destPath = $uploadDir . '/' . $fileName;
                    if (move_uploaded_file($tmpName, $destPath)) {
                        $insertImg->execute([
                            'property_id' => $id,
                            'file_path' => 'uploads/sale/' . $fileName,
                            'sort' => 0,
                        ]);
                    }
                }
            }
        }
    }

    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM `sale_properties` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $values = array_merge($values, $row);
        }

        $imgStmt = $pdo->prepare("SELECT id, file_path, sort FROM property_images WHERE property_type = 'sale' AND property_id = :id ORDER BY sort ASC, id ASC");
        $imgStmt->execute(['id' => $id]);
        $imageRows = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
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

<form method="post" action="" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?php echo h((string)$id); ?>">
  <div>
    <label>公開状態
      <select name="status">
        <option value="1" <?php echo ((int)$values['status'] === 1) ? 'selected' : ''; ?>>公開</option>
        <option value="0" <?php echo ((int)$values['status'] === 0) ? 'selected' : ''; ?>>下書き</option>
      </select>
    </label>
  </div>
  <div><label>物件名 <span style="color:#d00;">※</span> <input type="text" name="name" value="<?php echo h((string)$values['name']); ?>" required></label></div>
  <div>
    <label>種別
      <select name="cate">
        <?php
        $saleCateOptions = ['一戸建て', '土地', 'マンション', 'アパート', '駐車場', '店舗', '事務所', 'その他'];
        foreach ($saleCateOptions as $option):
          $selected = ((string)$values['cate'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div><label>キャッチコピー <input type="text" name="catchCopy" value="<?php echo h((string)$values['catchCopy']); ?>"></label></div>
  <div><label>所在地 <input type="text" name="location" value="<?php echo h((string)$values['location']); ?>"></label></div>
  <div><label>価格 <span style="color:#d00;">※</span> <input type="text" name="price" value="<?php echo h((string)$values['price']); ?>" required></label></div>
  <div>
    <label>取引態様
      <select name="transaction_type">
        <?php
        $transactionOptions = ['一般媒介', '専任媒介', '専属専任媒介', '売主', '代理', '仲介', 'その他'];
        foreach ($transactionOptions as $option):
          $selected = ((string)$values['transaction_type'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>間取り
      <select name="floor_plan">
        <?php
        $floorPlanOptions = ['1K', '1DK', '1LDK', '2K', '2DK', '2LDK', '3K', '3DK', '3LDK', '4K', '4DK', '4LDK', '5K', '5DK', '5LDK', '6K', '6DK', '6LDK', 'その他'];
        foreach ($floorPlanOptions as $option):
          $selected = ((string)$values['floor_plan'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div><label>土地面積 <input type="text" name="land_area" value="<?php echo h((string)$values['land_area']); ?>"></label></div>
  <div><label>建物面積 <input type="text" name="building_area" value="<?php echo h((string)$values['building_area']); ?>"></label></div>
  <div>
    <label>築年数
      <select name="age">
        <?php
        $ageOptions = ['新築', '1年', '2年', '3年', '4年', '5年', '6年', '7年', '8年', '9年', '10年', '15年', '20年', '25年', '30年', '35年', '40年', '45年', '50年', '不明'];
        foreach ($ageOptions as $option):
          $selected = ((string)$values['age'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div><label>建築年月 <input type="text" name="construction_date" value="<?php echo h((string)$values['construction_date']); ?>"></label></div>
  <div>
    <label>新築
      <select name="shinchiku">
        <option value="1" <?php echo ((int)$values['shinchiku'] === 1) ? 'selected' : ''; ?>>新築</option>
        <option value="0" <?php echo ((int)$values['shinchiku'] === 0) ? 'selected' : ''; ?>>中古</option>
      </select>
    </label>
  </div>
  <div>
    <label>構造
      <select name="structure">
        <?php
        $structureOptions = ['木造', '軽量鉄骨', '鉄骨造', '鉄筋コンクリート', '鉄骨鉄筋コンクリート', 'その他'];
        foreach ($structureOptions as $option):
          $selected = ((string)$values['structure'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>階数
      <select name="floors">
        <?php
        $floorOptions = ['1階', '2階', '3階', '4階', '5階', '6階', '7階', '8階', '9階', '10階', '11階以上', '地下1階', '地下2階', '不明'];
        foreach ($floorOptions as $option):
          $selected = ((string)$values['floors'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>方位
      <select name="direction">
        <?php
        $directionOptions = ['北', '北東', '東', '南東', '南', '南西', '西', '北西', '不明', 'その他'];
        foreach ($directionOptions as $option):
          $selected = ((string)$values['direction'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>バルコニー・庭
      <select name="balcony_garden">
        <option value="1" <?php echo ((string)$values['balcony_garden'] === '1') ? 'selected' : ''; ?>>有り</option>
        <option value="0" <?php echo ((string)$values['balcony_garden'] === '0') ? 'selected' : ''; ?>>無し</option>
      </select>
    </label>
  </div>
  <div>
    <label>駐車場
      <select name="parking">
        <option value="1" <?php echo ((string)$values['parking'] === '1') ? 'selected' : ''; ?>>有り</option>
        <option value="0" <?php echo ((string)$values['parking'] === '0') ? 'selected' : ''; ?>>無し</option>
      </select>
    </label>
  </div>
  <div><label>最寄駅距離 <input type="text" name="distance_to_station" value="<?php echo h((string)$values['distance_to_station']); ?>"></label></div>
  <div><label>利用可能路線 <input type="text" name="available_lines" value="<?php echo h((string)$values['available_lines']); ?>"></label></div>
  <div><label>近隣施設 <input type="text" name="shops" value="<?php echo h((string)$values['shops']); ?>"></label></div>
  <div><label>教育施設 <input type="text" name="schools" value="<?php echo h((string)$values['schools']); ?>"></label></div>
  <div><label>医療機関 <input type="text" name="hospitals" value="<?php echo h((string)$values['hospitals']); ?>"></label></div>
  <div><label>公園・公共施設 <input type="text" name="parks_facilities" value="<?php echo h((string)$values['parks_facilities']); ?>"></label></div>
  <div>
    <label>土地権利
      <select name="land_rights">
        <?php
        $landRightsOptions = ['所有権', '借地権', '定期借地権', '地上権', '賃借権', 'その他'];
        foreach ($landRightsOptions as $option):
          $selected = ((string)$values['land_rights'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>都市計画
      <select name="urban_planning">
        <?php
        $urbanPlanningOptions = ['市街化区域', '市街化調整区域', '非線引区域', '準都市計画区域', 'その他'];
        foreach ($urbanPlanningOptions as $option):
          $selected = ((string)$values['urban_planning'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>用途地域
      <select name="zoning">
        <?php
        $zoningOptions = [
          '第一種低層住居専用地域',
          '第二種低層住居専用地域',
          '第一種中高層住居専用地域',
          '第二種中高層住居専用地域',
          '第一種住居地域',
          '第二種住居地域',
          '準住居地域',
          '近隣商業地域',
          '商業地域',
          '準工業地域',
          '工業地域',
          '工業専用地域',
          'その他',
        ];
        foreach ($zoningOptions as $option):
          $selected = ((string)$values['zoning'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>建ぺい率
      <select name="building_coverage_ratio">
        <?php
        $coverageOptions = ['40%', '50%', '60%', '70%', '80%', '90%', '100%'];
        $coverageValue = (string)$values['building_coverage_ratio'];
        if ($coverageValue === '') {
          $coverageValue = '60%';
        }
        foreach ($coverageOptions as $option):
          $selected = ($coverageValue === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div>
    <label>容積率
      <select name="floor_area_ratio">
        <?php
        $floorAreaOptions = ['60%', '80%', '100%', '120%', '150%', '160%', '200%', '250%', '300%', '400%', '500%', '600%'];
        $floorAreaValue = (string)$values['floor_area_ratio'];
        if ($floorAreaValue === '') {
          $floorAreaValue = '60%';
        }
        foreach ($floorAreaOptions as $option):
          $selected = ($floorAreaValue === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
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
  <div>
    <label>現況
      <select name="current_status">
        <?php
        $currentStatusOptions = ['空室', '居住中', '賃貸中', '更地', '建築中', '完成済', 'その他'];
        foreach ($currentStatusOptions as $option):
          $selected = ((string)$values['current_status'] === $option) ? 'selected' : '';
        ?>
          <option value="<?php echo h($option); ?>" <?php echo $selected; ?>><?php echo h($option); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div><label>建築確認番号 <input type="text" name="building_confirmation_number" value="<?php echo h((string)$values['building_confirmation_number']); ?>"></label></div>
  <div><label>引渡し時期 <input type="text" name="handover_time" value="<?php echo h((string)$values['handover_time']); ?>"></label></div>
  <div>
    <label>ペット可否
      <select name="pets_allowed">
        <option value="1" <?php echo ((string)$values['pets_allowed'] === '1') ? 'selected' : ''; ?>>可</option>
        <option value="0" <?php echo ((string)$values['pets_allowed'] === '0') ? 'selected' : ''; ?>>不可</option>
      </select>
    </label>
  </div>
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
        <option value="1" <?php echo ((string)$values['osusume'] === '1') ? 'selected' : ''; ?>>Yes</option>
        <option value="0" <?php echo ((string)$values['osusume'] === '0') ? 'selected' : ''; ?>>No</option>
      </select>
    </label>
  </div>
  <div>
    <button type="submit">保存</button>
  </div>

  <?php if ($id): ?>
  <hr>
  <div>
    <label>画像追加 <input type="file" name="images[]" multiple></label>
  </div>
  <?php if ($imageRows): ?>
    <table id="sale-image-table">
      <thead>
        <tr>
          <th>画像</th>
          <th>並び順</th>
          <th>削除</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($imageRows as $img): ?>
          <tr class="draggable-row" draggable="true" data-image-id="<?php echo h((string)$img['id']); ?>">
            <td><img src="../<?php echo h($img['file_path']); ?>" alt="" style="max-width:120px;"></td>
            <td><input type="number" name="image_sort[<?php echo h((string)$img['id']); ?>]" value="<?php echo h((string)$img['sort']); ?>"></td>
            <td><input type="checkbox" name="delete_image[]" value="<?php echo h((string)$img['id']); ?>"></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <script>
      (function () {
        const table = document.getElementById('sale-image-table');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        let dragRow = null;

        function updateSort() {
          const rows = tbody.querySelectorAll('.draggable-row');
          rows.forEach((row, index) => {
            const input = row.querySelector('input[type="number"]');
            if (input) {
              input.value = index + 1;
            }
          });
        }

        tbody.addEventListener('dragstart', (event) => {
          const row = event.target.closest('.draggable-row');
          if (!row) return;
          dragRow = row;
          event.dataTransfer.effectAllowed = 'move';
          row.classList.add('dragging');
        });

        tbody.addEventListener('dragend', () => {
          if (dragRow) {
            dragRow.classList.remove('dragging');
            dragRow = null;
          }
          updateSort();
        });

        tbody.addEventListener('dragover', (event) => {
          event.preventDefault();
          const row = event.target.closest('.draggable-row');
          if (!row || row === dragRow) return;
          const rect = row.getBoundingClientRect();
          const next = (event.clientY - rect.top) > rect.height / 2;
          tbody.insertBefore(dragRow, next ? row.nextSibling : row);
        });
      })();
    </script>
  <?php else: ?>
    <p>画像は未登録です。</p>
  <?php endif; ?>
  <?php endif; ?>
</form>

