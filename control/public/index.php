<?php
$currentPage = $_GET['page'] ?? 'dashboard';

$routes = [
    'dashboard' => __DIR__ . '/../views/dashboard/index.php',
    'sale_create' => __DIR__ . '/../views/sale/create.php',
    'sale_list' => __DIR__ . '/../views/sale/list.php',
    'sale_edit' => __DIR__ . '/../views/sale/edit.php',
    'rent_create' => __DIR__ . '/../views/rent/create.php',
    'rent_list' => __DIR__ . '/../views/rent/list.php',
    'rent_edit' => __DIR__ . '/../views/rent/edit.php',
    'area_list' => __DIR__ . '/../views/area/list.php',
    'area_edit' => __DIR__ . '/../views/area/edit.php',
    'settings' => __DIR__ . '/../views/settings/index.php',
];

$titleMap = [
    'dashboard' => 'ダッシュボード',
    'sale_create' => '売り物件新規登録',
    'sale_list' => '売り物件一覧',
    'sale_edit' => '売り物件編集',
    'rent_create' => '賃貸物件新規登録',
    'rent_list' => '賃貸物件一覧',
    'rent_edit' => '賃貸物件編集',
    'area_list' => 'エリア一覧',
    'area_edit' => 'エリア編集',
    'settings' => '設定',
];

$viewFile = $routes[$currentPage] ?? $routes['dashboard'];
$pageTitle = $titleMap[$currentPage] ?? 'ダッシュボード';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
  <header>
    <h1>管理画面</h1>
    <nav>
      <a href="index.php?page=dashboard">ダッシュボード</a>
      <a href="index.php?page=sale_create">売り物件新規登録</a>
      <a href="index.php?page=sale_list">売り物件一覧</a>
      <a href="index.php?page=sale_edit">売り物件編集</a>
      <a href="index.php?page=rent_create">賃貸物件新規登録</a>
      <a href="index.php?page=rent_list">賃貸物件一覧</a>
      <a href="index.php?page=rent_edit">賃貸物件編集</a>
      <a href="index.php?page=area_list">エリア一覧</a>
      <a href="index.php?page=area_edit">エリア編集</a>
      <a href="index.php?page=settings">設定</a>
    </nav>
  </header>

  <main>
    <h2><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php if (is_file($viewFile)): ?>
      <?php include $viewFile; ?>
    <?php else: ?>
      <p>画面が見つかりません。</p>
    <?php endif; ?>
  </main>
</body>
</html>
