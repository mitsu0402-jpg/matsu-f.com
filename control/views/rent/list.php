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

    $sql = 'SELECT id, name, cate, price, location, status, lastUpdateDate FROM `rent_properties`';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY lastUpdateDate DESC, id DESC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'エラーが発生しました。' . $e->getMessage();
}
?>

<section>
  <form method="get" action="index.php">
    <input type="hidden" name="page" value="rent_list">
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
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>物件名</th>
        <th>種別</th>
        <th>賃料</th>
        <th>所在地</th>
        <th>公開状態</th>
        <th>更新日</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8">データがありません。</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?php echo h((string)$row['id']); ?></td>
            <td><?php echo h((string)$row['name']); ?></td>
            <td><?php echo h((string)$row['cate']); ?></td>
            <td><?php echo h((string)$row['price']); ?></td>
            <td><?php echo h((string)$row['location']); ?></td>
            <td><?php echo ((int)$row['status'] === 1) ? '公開' : '下書き'; ?></td>
            <td><?php echo h((string)$row['lastUpdateDate']); ?></td>
            <td><a href="index.php?page=rent_edit&id=<?php echo h((string)$row['id']); ?>">編集</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</section>

