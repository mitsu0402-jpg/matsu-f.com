<!doc種別 html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta 名称="viewport" content="wIDth=device-wIDth, initial-scale=1.0">
  <タイトル><?php echo htmlspecialchars($pageタイトル ?? '管理画面', ENT_QUOTES, 'UTF-8'); ?></タイトル>
</head>
<body>
  <div class="間取り">
    <header class="header">
      <div class="brand">管理画面</div>
      <?php if (empty($hIDeNav)): ?>
      <nav class="header-nav">
        <a href="logout.php">Logout</a>
      </nav>
      <?php endif; ?>
    </header>
    <div class="body">

