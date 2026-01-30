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
    'news_list' => __DIR__ . '/../views/news/list.php',
    'settings' => __DIR__ . '/../views/settings/index.php',
];

$titleMap = [
    'dashboard' => 'ダッシュボード',
    'sale_create' => '売り物件追加',
    'sale_list' => '売り一覧',
    'sale_edit' => '売り物件編集',
    'rent_create' => '賃貸物件追加',
    'rent_list' => '賃貸一覧',
    'rent_edit' => '賃貸物件編集',
    'area_list' => 'エリア一覧',
    'area_edit' => 'エリア編集',
    'news_list' => 'お知らせ一覧',
    'settings' => '設定',
];

$viewFile = $routes[$currentPage] ?? $routes['dashboard'];
$pageTitle = $titleMap[$currentPage] ?? 'ダッシュボード';
$documentTitle = 'コントロールパネル ' . $pageTitle;
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin">
  <header class="app-header">
    <div class="brand">松永不動産　コントロールパネル</div>
    <div class="header-title">管理画面</div>
  </header>

  <nav class="nav-grid">
    <a class="nav-card nav-card-1<?php echo $currentPage === 'dashboard' ? ' is-current' : ''; ?>" href="index.php?page=dashboard" <?php echo $currentPage === 'dashboard' ? 'aria-current="page"' : ''; ?>>ダッシュボード</a>
    <a class="nav-card nav-card-7<?php echo $currentPage === 'news_list' ? ' is-current' : ''; ?>" href="index.php?page=news_list" <?php echo $currentPage === 'news_list' ? 'aria-current="page"' : ''; ?>>お知らせ一覧</a>
    <a class="nav-card nav-card-2<?php echo $currentPage === 'sale_create' ? ' is-current' : ''; ?>" href="index.php?page=sale_create" <?php echo $currentPage === 'sale_create' ? 'aria-current="page"' : ''; ?>>売り物件追加</a>
    <a class="nav-card nav-card-3<?php echo $currentPage === 'sale_list' ? ' is-current' : ''; ?>" href="index.php?page=sale_list" <?php echo $currentPage === 'sale_list' ? 'aria-current="page"' : ''; ?>>売り物件一覧</a>
    <a class="nav-card nav-card-4<?php echo $currentPage === 'rent_create' ? ' is-current' : ''; ?>" href="index.php?page=rent_create" <?php echo $currentPage === 'rent_create' ? 'aria-current="page"' : ''; ?>>賃貸物件追加</a>
    <a class="nav-card nav-card-5<?php echo $currentPage === 'rent_list' ? ' is-current' : ''; ?>" href="index.php?page=rent_list" <?php echo $currentPage === 'rent_list' ? 'aria-current="page"' : ''; ?>>賃貸物件一覧</a>
    <a class="nav-card nav-card-6<?php echo $currentPage === 'area_list' ? ' is-current' : ''; ?>" href="index.php?page=area_list" <?php echo $currentPage === 'area_list' ? 'aria-current="page"' : ''; ?>>エリア一覧</a>
    <a class="nav-card nav-card-8<?php echo $currentPage === 'settings' ? ' is-current' : ''; ?>" href="index.php?page=settings" <?php echo $currentPage === 'settings' ? 'aria-current="page"' : ''; ?>>設定</a>
  </nav>

  <main class="content">
    <h2><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
    <?php if (is_file($viewFile)): ?>
      <?php include $viewFile; ?>
    <?php else: ?>
      <p>画面が見つかりません。</p>
    <?php endif; ?>
  </main>
</body>
</html>
