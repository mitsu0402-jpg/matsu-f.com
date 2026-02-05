<?php
$siteHeroTitle = (string)($siteHeroTitle ?? '');
$siteNavActive = (string)($siteNavActive ?? 'home');
$siteBreadcrumbs = is_array($siteBreadcrumbs ?? null) ? $siteBreadcrumbs : [];
?>
<header class="site-header">
    <div class="site-header-inner">
        <a class="site-logo" href="/">
            <img src="/image/logo.png" alt="松永不動産ロゴ">
            <span>松永不動産</span>
        </a>
        <button class="menu-toggle" type="button" aria-label="メニュー" aria-expanded="false" aria-controls="site-nav">
            <span></span><span></span><span></span>
        </button>
        <nav class="site-nav" id="site-nav">
            <a href="/"<?php echo $siteNavActive === 'home' ? ' class="is-active"' : ''; ?>>HOME</a>
            <a href="/saleList.php"<?php echo $siteNavActive === 'sale' ? ' class="is-active"' : ''; ?>>売り物件一覧</a>
            <a href="/rentList.php"<?php echo $siteNavActive === 'rent' ? ' class="is-active"' : ''; ?>>貸し物件一覧</a>
            <a href="/contact.php"<?php echo $siteNavActive === 'contact' ? ' class="is-active"' : ''; ?>>売りたい・貸したい</a>
        </nav>
    </div>
    <p class="site-hero-title"><?php echo h($siteHeroTitle); ?></p>
    <?php if ($siteBreadcrumbs): ?>
    <div class="site-breadcrumb">
        <?php foreach ($siteBreadcrumbs as $index => $item): ?>
            <?php
                $label = (string)($item['label'] ?? '');
                $href = trim((string)($item['href'] ?? ''));
            ?>
            <?php if ($index > 0): ?>
                <span class="site-breadcrumb-sep">&gt;</span>
            <?php endif; ?>
            <?php if ($href !== ''): ?>
                <a href="<?php echo h($href); ?>"><?php echo h($label); ?></a>
            <?php else: ?>
                <span><?php echo h($label); ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</header>
<script>
    (function () {
        var toggle = document.querySelector('.menu-toggle');
        var nav = document.querySelector('.site-nav');
        if (!toggle || !nav) {
            return;
        }
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    })();
</script>
