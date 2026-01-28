<?php
require_once __DIR__ . '/../../lib/db.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';
$editId = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $pdo = getPDO();

        if ($action === 'delete') {
            $deleteId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if ($deleteId) {
                $stmt = $pdo->prepare('DELETE FROM notices WHERE id = :id');
                $stmt->execute(['id' => $deleteId]);
                $message = '削除しました。';
            }
        } else {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $title = trim((string)($_POST['title'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            $linkUrl = trim((string)($_POST['link_url'] ?? ''));
            $publishedAt = '';
            $sort = 0;
            $status = (int)($_POST['status'] ?? 1);

            if ($title === '' || $body === '') {
                $error = 'タイトルと本文を入力してください。';
            } else {
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE notices SET title = :title, body = :body, link_url = :link_url, status = :status, updated_at = NOW() WHERE id = :id');
                    $stmt->execute([
                        'title' => $title,
                        'body' => $body,
                        'link_url' => $linkUrl,
                        'status' => $status,
                        'id' => $id,
                    ]);
                    $message = '更新しました。';
                    $editId = $id;
                } else {
                    $stmt = $pdo->prepare('INSERT INTO notices (title, body, link_url, status, created_at, updated_at) VALUES (:title, :body, :link_url, :status, NOW(), NOW())');
                    $stmt->execute([
                        'title' => $title,
                        'body' => $body,
                        'link_url' => $linkUrl,
                        'status' => $status,
                    ]);
                    $message = '追加しました。';
                }
            }
        }
    } catch (Throwable $e) {
        $error = 'エラーが発生しました。' . $e->getMessage();
    }
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$rows = [];

try {
    $pdo = $pdo ?? getPDO();
    $where = '';
    $params = [];
    if ($q !== '') {
        $where = 'WHERE title LIKE :q OR body LIKE :q';
        $params['q'] = '%' . $q . '%';
    }
    $stmt = $pdo->prepare("SELECT id, title, status, created_at FROM notices $where ORDER BY created_at DESC, id DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($editId) {
        $stmt = $pdo->prepare('SELECT id, title, body, link_url, status FROM notices WHERE id = :id');
        $stmt->execute(['id' => $editId]);
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    $error = 'エラーが発生しました。' . $e->getMessage();
}

$editRow = $editRow ?? ['id' => '', 'title' => '', 'body' => '', 'link_url' => '', 'status' => 1];
$isEditing = $editRow['id'] !== '';
?>

<?php if ($message): ?>
  <p><?php echo h($message); ?></p>
<?php endif; ?>
<?php if ($error): ?>
  <p><?php echo h($error); ?></p>
<?php endif; ?>

<section>
  <form method="post" action="index.php?page=news_list">
    <input type="hidden" name="id" value="<?php echo h((string)$editRow['id']); ?>">
    <div>
      <label>お知らせ<?php if ($isEditing): ?>編集<?php else: ?>追加<?php endif; ?></label>
    </div>
    <div>
      <label>タイトル <span class="req">※</span>
        <input type="text" name="title" value="<?php echo h((string)$editRow['title']); ?>" required>
      </label>
    </div>
    <div>
      <label>本文 <span class="req">※</span>
        <textarea name="body" rows="4" required><?php echo h((string)$editRow['body']); ?></textarea>
      </label>
    </div>
    <div>
      <label>リンク先
        <input type="text" name="link_url" value="<?php echo h((string)($editRow['link_url'] ?? '')); ?>">
      </label>
    </div>
    <div>
      <label>公開状態
        <select name="status">
          <option value="1" <?php echo ((int)$editRow['status'] === 1) ? 'selected' : ''; ?>>公開</option>
          <option value="0" <?php echo ((int)$editRow['status'] === 0) ? 'selected' : ''; ?>>下書き</option>
        </select>
      </label>
    </div>
    <div>
      <button type="submit" name="action" value="save"><?php echo $isEditing ? '更新' : '追加'; ?></button>
      <?php if ($isEditing): ?>
        <a href="index.php?page=news_list">クリア</a>
      <?php endif; ?>
    </div>
  </form>
</section>

<section>
  <form method="get" action="index.php">
    <input type="hidden" name="page" value="news_list">
    <label>
      検索
      <input type="text" name="q" value="<?php echo h($q); ?>">
    </label>
    <button type="submit">検索</button>
  </form>
</section>

<section>
  <div class="table-scroll">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>タイトル</th>
        <th>登録日時</th>
        <th>公開状態</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5">データがありません。</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?php echo h((string)$row['id']); ?></td>
            <td><?php echo h((string)$row['title']); ?></td>
            <td><?php echo h((string)$row['created_at']); ?></td>
            <td><?php echo ((int)$row['status'] === 1) ? '公開' : '下書き'; ?></td>
            <td>
              <a href="index.php?page=news_list&edit_id=<?php echo h((string)$row['id']); ?>">編集</a>
              <form method="post" action="index.php?page=news_list" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo h((string)$row['id']); ?>">
                <button type="submit" name="action" value="delete" onclick="return confirm('削除しますか？');">削除</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</section>
