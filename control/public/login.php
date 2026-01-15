<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';

$pageTitle = 'Login';
$hideNav = true;

include __DIR__ . '/../views/partials/header.php';
?>
<main class="main full">
  <?php include __DIR__ . '/../views/auth/login.php'; ?>
</main>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
