<?php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

$currentPage = $_GET['page'] ?? 'dashboard';
$loginError = '';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['control_password'])) {
    $password = trim((string)($_POST['control_password'] ?? ''));
    if ($password === '0721') {
        $_SESSION['control_authed'] = true;
        $redirectPage = isset($_GET['page']) ? (string)$_GET['page'] : 'dashboard';
        header('Location: index.php?page=' . urlencode($redirectPage), true, 303);
        exit;
    }
    $loginError = 'パスワードが違います。';
}

if (empty($_SESSION['control_authed'])) {
    $pageTitle = 'コントロールパネル ログイン';
    ?>
    <!doctype html>
    <html lang="ja">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
      <style>
        body {
          margin: 0;
          min-height: 100vh;
          display: grid;
          place-items: center;
          font-family: system-ui, -apple-system, Segoe UI, sans-serif;
          background: #f4f6f8;
        }
        .login-card {
          width: min(420px, 92vw);
          background: #fff;
          padding: 24px;
          border-radius: 12px;
          box-shadow: 0 16px 36px rgba(0, 0, 0, 0.12);
        }
        .login-title {
          margin: 0 0 12px;
          font-size: 18px;
          font-weight: 700;
          text-align: center;
        }
        .login-field {
          display: grid;
          gap: 8px;
          margin-bottom: 12px;
        }
        .login-input {
          padding: 10px 12px;
          border: 1px solid #cfd6dd;
          border-radius: 8px;
          font-size: 16px;
        }
        .login-btn {
          width: 100%;
          padding: 10px 12px;
          border: none;
          border-radius: 999px;
          background: #124b32;
          color: #fff;
          font-weight: 700;
          cursor: pointer;
        }
        .login-error {
          margin: 0 0 10px;
          color: #b02a2a;
          text-align: center;
          font-size: 13px;
        }
      </style>
    </head>
    <body>
      <form class="login-card" method="post" action="">
        <div class="login-title">コントロールパネル ログイン</div>
        <?php if ($loginError): ?>
          <p class="login-error"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="login-field">
          <label for="control-password">パスワード</label>
          <input class="login-input" id="control-password" name="control_password" type="password" required>
        </div>
        <button class="login-btn" type="submit">ログイン</button>
      </form>
    </body>
    </html>
<?php
    exit;
}

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
    'contact_list' => __DIR__ . '/../views/contact/list.php',
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
    'contact_list' => 'お問い合わせ一覧',
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
    <div style="margin-left:auto;">
      <a href="index.php?logout=1" style="color:#ffffff; text-decoration:none; font-size:12px;">ログアウト</a>
    </div>
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
    <a class="nav-card nav-card-9<?php echo $currentPage === 'contact_list' ? ' is-current' : ''; ?>" href="index.php?page=contact_list" <?php echo $currentPage === 'contact_list' ? 'aria-current="page"' : ''; ?>>お問い合わせ一覧</a>
    <a class="nav-card nav-card-blog" href="https://matsu-f.com/wp-login.php" target="_blank" rel="noopener noreferrer" style="background: url('assets/img/btn11.png') center / cover no-repeat;">ブログ追加</a>
  </nav>

  <main class="content">
    <div class="content-head">
      <h2><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
      <?php if ($currentPage === 'contact_list'): ?>
        <a class="header-link-btn" href="https://tools.heteml.jp/" target="_blank" rel="noopener noreferrer">メーラーへログインする</a>
      <?php endif; ?>
    </div>
    <?php if (is_file($viewFile)): ?>
      <?php include $viewFile; ?>
    <?php else: ?>
      <p>画面が見つかりません。</p>
    <?php endif; ?>
  </main>
</body>
</html>

