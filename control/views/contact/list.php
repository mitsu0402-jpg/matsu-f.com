<?php
require_once __DIR__ . '/../../lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function request_label(string $type): string
{
    $map = [
        'sell' => '売りたい',
        'rent' => '貸したい',
        'buy' => '買いたい',
        'borrow' => '借りたい',
        'consult' => '相談したい',
    ];
    return $map[$type] ?? $type;
}

function extract_first_url(string $text): string
{
    if (preg_match('~https?://[^\s]+~u', $text, $matches) === 1) {
        return (string)$matches[0];
    }
    return '';
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$request = isset($_GET['request']) ? trim((string)$_GET['request']) : '';
$rows = [];
$error = '';

try {
    $pdo = getPDO();
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = '(name LIKE :q OR contact LIKE :q OR note LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    if ($request !== '') {
        $where[] = 'request_type = :request';
        $params['request'] = $request;
    }

    $sql = 'SELECT id, name, contact, request_type, note, created_at
            FROM contact_requests';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY id DESC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'エラーが発生しました。' . $e->getMessage();
}
?>

<section>
  <form method="get" action="index.php">
    <input type="hidden" name="page" value="contact_list">
    <label>キーワード <input type="text" name="q" value="<?php echo h($q); ?>"></label>
    <label>ご要望
      <select name="request">
        <option value="" <?php echo $request === '' ? 'selected' : ''; ?>>すべて</option>
        <option value="sell" <?php echo $request === 'sell' ? 'selected' : ''; ?>>売りたい</option>
        <option value="rent" <?php echo $request === 'rent' ? 'selected' : ''; ?>>貸したい</option>
        <option value="buy" <?php echo $request === 'buy' ? 'selected' : ''; ?>>買いたい</option>
        <option value="borrow" <?php echo $request === 'borrow' ? 'selected' : ''; ?>>借りたい</option>
        <option value="consult" <?php echo $request === 'consult' ? 'selected' : ''; ?>>相談したい</option>
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
          <th>受付日時</th>
          <th>お名前</th>
          <th>連絡先</th>
          <th>ご要望</th>
          <th>備考</th>
          <th>物件リンク</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7">データがありません。</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <?php $detailUrl = extract_first_url((string)($row['note'] ?? '')); ?>
            <tr>
              <td><?php echo h((string)($row['id'] ?? '')); ?></td>
              <td><?php echo h((string)($row['created_at'] ?? '')); ?></td>
              <td><?php echo h((string)($row['name'] ?? '')); ?></td>
              <td><?php echo h((string)($row['contact'] ?? '')); ?></td>
              <td><?php echo h(request_label((string)($row['request_type'] ?? ''))); ?></td>
              <td><?php echo nl2br(h((string)($row['note'] ?? ''))); ?></td>
              <td>
                <?php if ($detailUrl !== ''): ?>
                  <a href="<?php echo h($detailUrl); ?>" target="_blank" rel="noopener noreferrer">詳細を開く</a>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
