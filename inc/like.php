<?php
header('Content-Type: text/html; charset=UTF-8');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalize_page_path(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    $parts = parse_url($path);
    $cleanPath = (string)($parts['path'] ?? $path);
    $query = (string)($parts['query'] ?? '');
    if ($query !== '') {
        $cleanPath .= '?' . $query;
    }
    return $cleanPath;
}

function blog_content_id_from_path(string $pagePath): int
{
    $raw = sprintf('%u', crc32($pagePath));
    $id = (int)(((int)$raw) % 2147483647);
    return $id > 0 ? $id : 1;
}

$pagePath = trim((string)($_GET['page_path'] ?? ''));
if ($pagePath === '') {
    $ref = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
    if ($ref !== '') {
        $parts = parse_url($ref);
        $refPath = (string)($parts['path'] ?? '');
        $refQuery = (string)($parts['query'] ?? '');
        if ($refPath !== '') {
            $pagePath = $refPath . ($refQuery !== '' ? '?' . $refQuery : '');
        }
    }
}
if ($pagePath === '') {
    $pagePath = '/';
}
$pagePath = normalize_page_path($pagePath);
$contentId = blog_content_id_from_path($pagePath);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background: transparent;
      font-family: Monda, Helvetica, Arial, sans-serif;
      text-align: left;
      overflow: hidden;
    }

    .like-wrap {
      display: flex;
      width: 100%;
      align-items: center;
      justify-content: flex-start;
      padding: 4px;
    }

    .like-button {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: none;
      border-radius: 999px;
      padding: 6px 10px;
      background: rgba(255, 255, 255, 0.92);
      color: #7a4b2a;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
    }

    .like-icon {
      width: 32px;
      height: 32px;
      object-fit: contain;
      flex: 0 0 auto;
    }
  </style>
</head>
<body>
  <div class="like-wrap">
    <button class="like-button" id="blog-like-btn" type="button" aria-label="いいね">
      <img class="like-icon" src="/image/btn_iine.png" alt="">
      いいね！ <span id="blog-like-count">...</span>
    </button>
  </div>

  <script>
    (function () {
      var btn = document.getElementById('blog-like-btn');
      var countEl = document.getElementById('blog-like-count');
      if (!btn || !countEl) {
        return;
      }
      var pagePath = <?php echo json_encode($pagePath, JSON_UNESCAPED_UNICODE); ?>;
      var payload = new URLSearchParams({
        content_type: 'blog',
        content_id: <?php echo json_encode((string)$contentId, JSON_UNESCAPED_UNICODE); ?>,
        page_path: pagePath
      });

      function setCount(value) {
        countEl.textContent = String(value);
      }

      function updateCount() {
        fetch('/api/like.php?' + payload.toString(), { credentials: 'same-origin' })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data && data.ok && typeof data.count === 'number') {
              setCount(data.count);
            } else {
              setCount(0);
            }
          })
          .catch(function () {
            setCount(0);
          });
      }

      btn.addEventListener('click', function () {
        fetch('/api/like.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: payload.toString(),
          credentials: 'same-origin'
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data && data.ok && typeof data.count === 'number') {
              setCount(data.count);
            }
          })
          .catch(function () {});
      });

      updateCount();
    })();
  </script>
</body>
</html>
