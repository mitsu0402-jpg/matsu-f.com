<?php
require_once __DIR__ . '/../../lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$cate = isset($_GET['cate']) ? trim((string)$_GET['cate']) : '';
$status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

$rows = [];
$error = '';

try {
    $pdo = getPDO();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reorder') {
        $order = $_POST['order'] ?? [];
        if (!is_array($order)) {
            $order = [];
        }
        $ids = array_values(array_filter(array_map('intval', $order), function ($value) {
            return $value > 0;
        }));

        $response = ['ok' => false];
        if ($ids) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE sale_properties SET sort = :sort WHERE id = :id');
            foreach ($ids as $index => $id) {
                $stmt->execute([
                    ':sort' => $index + 1,
                    ':id' => $id,
                ]);
            }
            $pdo->commit();
            $response['ok'] = true;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(name LIKE :q OR location LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if ($cate !== '') {
        $where[] = 'cate LIKE :cate';
        $params['cate'] = '%' . $cate . '%';
    }
    if ($status !== '') {
        $where[] = 'status = :status';
        $params['status'] = (int)$status;
    }

    $sql = 'SELECT id, name, cate, price, location, status, lastUpdateDate, sort FROM `sale_properties`';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY sort ASC, id ASC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'エラーが発生しました。' . $e->getMessage();
}
?>

<style>
  .draggable-row { cursor: move; }
  .drag-handle { cursor: grab; color: #5f6b62; font-weight: 700; }
  .drag-handle:active { cursor: grabbing; }
</style>

<section>
  <form method="get" action="index.php">
    <input type="hidden" name="page" value="sale_list">
    <label>キーワード <input type="text" name="q" value="<?php echo h($q); ?>"></label>
    <label>種別 <input type="text" name="cate" value="<?php echo h($cate); ?>"></label>
    <label>公開状態
      <select name="status">
        <option value="" <?php echo ($status === '') ? 'selected' : ''; ?>>すべて</option>
        <option value="1" <?php echo ($status === '1') ? 'selected' : ''; ?>>公開</option>
        <option value="0" <?php echo ($status === '0') ? 'selected' : ''; ?>>下書き</option>
      </select>
    </label>
    <button type="submit">検索</button>
  </form>
</section>

<?php if ($error): ?>
  <p><?php echo h($error); ?></p>
<?php endif; ?>

<section>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>並び替え</th>
        <th>物件名</th>
        <th>種別</th>
        <th>価格</th>
        <th>所在地</th>
        <th>公開状態</th>
        <th>更新日</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="9">データがありません。</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr class="draggable-row" draggable="true" data-id="<?php echo h((string)$row['id']); ?>">
            <td><?php echo h((string)$row['id']); ?></td>
            <td class="drag-handle" aria-label="ドラッグして並び替え">≡</td>
            <td><?php echo h((string)$row['name']); ?></td>
            <td><?php echo h((string)$row['cate']); ?></td>
            <td><?php echo h((string)$row['price']); ?></td>
            <td><?php echo h((string)$row['location']); ?></td>
            <td><?php echo ((int)$row['status'] === 1) ? '公開' : '下書き'; ?></td>
            <td><?php echo h((string)$row['lastUpdateDate']); ?></td>
            <td><a href="index.php?page=sale_edit&id=<?php echo h((string)$row['id']); ?>">編集</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</section>

<script>
  (function () {
    var tbody = document.querySelector('table tbody');
    if (!tbody) return;
    var draggingRow = null;

    function getDragAfterElement(container, y) {
      var rows = Array.prototype.slice.call(container.querySelectorAll('tr.draggable-row:not(.dragging)'));
      var closest = { offset: Number.NEGATIVE_INFINITY, element: null };
      rows.forEach(function (row) {
        var box = row.getBoundingClientRect();
        var offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          closest = { offset: offset, element: row };
        }
      });
      return closest.element;
    }

    function sendOrder() {
      var order = Array.prototype.slice.call(tbody.querySelectorAll('tr.draggable-row'))
        .map(function (row) { return row.getAttribute('data-id'); })
        .filter(Boolean);
      if (!order.length) return;
      var formData = new FormData();
      formData.append('action', 'reorder');
      order.forEach(function (id) { formData.append('order[]', id); });

      fetch('index.php?page=sale_list', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      }).catch(function () {});
    }

    tbody.addEventListener('dragstart', function (event) {
      var row = event.target.closest('tr.draggable-row');
      if (!row) return;
      draggingRow = row;
      row.classList.add('dragging');
      event.dataTransfer.effectAllowed = 'move';
    });

    tbody.addEventListener('dragend', function () {
      if (!draggingRow) return;
      draggingRow.classList.remove('dragging');
      draggingRow = null;
      sendOrder();
    });

    tbody.addEventListener('dragover', function (event) {
      event.preventDefault();
      var afterElement = getDragAfterElement(tbody, event.clientY);
      if (!draggingRow) return;
      if (afterElement == null) {
        tbody.appendChild(draggingRow);
      } else {
        tbody.insertBefore(draggingRow, afterElement);
      }
    });
  })();
</script>

