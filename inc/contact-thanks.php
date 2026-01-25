<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <style>
        :root {
            --bg: #f6f4f1;
            --card: #ffffff;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Yu Mincho", "Hiragino Mincho ProN", "Hiragino Mincho Pro", "Noto Serif JP", serif;
            background: var(--bg);
            color: #1b1b1b;
        }

        .thanks-wrap {
            max-width: 720px;
            margin: 0 auto;
            background: var(--card);
            border-radius: 12px;
            padding: 32px 24px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
            text-align: center;
        }

        .thanks-title {
            margin: 0 0 12px;
            font-size: 22px;
            letter-spacing: 0.08em;
        }

        .thanks-text {
            margin: 0;
            font-size: 14px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="thanks-wrap">
        <h2 class="thanks-title">送信ありがとうございました</h2>
        <p class="thanks-text">内容を確認後、担当よりご連絡いたします。</p>
    </div>
    <script>
        (function () {
            function sendHeight() {
                var height = document.documentElement.scrollHeight || document.body.scrollHeight;
                parent.postMessage({ type: 'matsu-sale-height', height: height }, 'https://matsu-f.com');
            }
            window.addEventListener('load', sendHeight);
            window.addEventListener('resize', sendHeight);
        })();
    </script>
</body>
</html>
