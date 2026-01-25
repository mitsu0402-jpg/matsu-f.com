<?php
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../control/lib/db.php';

if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$showThanks = isset($_GET['thanks']) && $_GET['thanks'] === '1';

$values = [
    'name' => '',
    'contact' => '',
    'request' => 'sell',
    'note' => '',
];

if (!$showThanks && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name'] = trim((string)($_POST['name'] ?? ''));
    $values['contact'] = trim((string)($_POST['contact'] ?? ''));
    $values['request'] = (string)($_POST['request'] ?? 'sell');
    $values['note'] = trim((string)($_POST['note'] ?? ''));

    if ($values['name'] === '') {
        $errors[] = 'お名前を入力してください。';
    }
    if ($values['contact'] === '') {
        $errors[] = '連絡先を入力してください。';
    }
    if (!in_array($values['request'], ['sell', 'rent', 'buy', 'borrow', 'consult'], true)) {
        $errors[] = 'ご要望を選択してください。';
    }

    if (!$errors) {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                'INSERT INTO contact_requests (name, contact, request_type, note)
                 VALUES (:name, :contact, :request_type, :note)'
            );
            $stmt->execute([
                ':name' => $values['name'],
                ':contact' => $values['contact'],
                ':request_type' => $values['request'],
                ':note' => $values['note'] !== '' ? $values['note'] : null,
            ]);
            header('Location: https://matsu-f.com/thanks/', true, 303);
            exit;
        } catch (Throwable $e) {
            $errors[] = '保存に失敗しました。';
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <style>
        :root {
            --border-color: #d6d0c7;
            --focus-color: #7a4b2a;
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

        .contact-wrap {
            max-width: 720px;
            margin: 0 auto;
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .contact-title {
            margin: 0 0 16px;
            font-size: 22px;
            letter-spacing: 0.08em;
            text-align: center;
        }

        .contact-form {
            display: grid;
            gap: 14px;
        }

        .contact-field {
            display: grid;
            gap: 6px;
        }

        .contact-label {
            font-size: 14px;
        }

        .contact-input,
        .contact-select,
        .contact-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
        }

        .contact-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .contact-input:focus,
        .contact-select:focus,
        .contact-textarea:focus {
            outline: none;
            border-color: var(--focus-color);
            box-shadow: 0 0 0 2px rgba(122, 75, 42, 0.15);
        }

        .contact-note {
            font-size: 12px;
            color: #555;
            text-align: center;
        }

        .contact-message {
            font-size: 13px;
            color: #b02a2a;
            text-align: center;
        }

        .contact-submit {
            padding: 10px 16px;
            border: none;
            border-radius: 999px;
            background: #15C0D0;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }

        .contact-submit:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="contact-wrap">
        <?php if ($showThanks): ?>
            <h2 class="contact-title">送信ありがとうございました</h2>
            <p class="contact-note">内容を確認後、担当よりご連絡いたします。</p>
        <?php else: ?>
            <h2 class="contact-title">お問い合わせ</h2>
            <?php if ($errors): ?>
                <p class="contact-message">
                    <?php echo h(implode(' ', $errors)); ?>
                </p>
            <?php endif; ?>
            <form class="contact-form" method="post" action="">
                <div class="contact-field">
                    <label class="contact-label" for="contact-name">お名前</label>
                    <input class="contact-input" id="contact-name" name="name" type="text" value="<?php echo h($values['name']); ?>">
                </div>
                <div class="contact-field">
                    <label class="contact-label" for="contact-contact">連絡先（TEL またはメール）</label>
                    <input class="contact-input" id="contact-contact" name="contact" type="text" value="<?php echo h($values['contact']); ?>">
                </div>
                <div class="contact-field">
                    <label class="contact-label" for="contact-request">ご要望</label>
                    <select class="contact-select" id="contact-request" name="request">
                        <option value="sell" <?php echo $values['request'] === 'sell' ? 'selected' : ''; ?>>売りたい</option>
                        <option value="rent" <?php echo $values['request'] === 'rent' ? 'selected' : ''; ?>>貸したい</option>
                        <option value="buy" <?php echo $values['request'] === 'buy' ? 'selected' : ''; ?>>買いたい</option>
                        <option value="borrow" <?php echo $values['request'] === 'borrow' ? 'selected' : ''; ?>>借りたい</option>
                        <option value="consult" <?php echo $values['request'] === 'consult' ? 'selected' : ''; ?>>相談したい</option>
                    </select>
                </div>
                <div class="contact-field">
                    <label class="contact-label" for="contact-note">備考</label>
                    <textarea class="contact-textarea" id="contact-note" name="note"><?php echo h($values['note']); ?></textarea>
                </div>
                <button class="contact-submit" type="submit">送信</button>
                <p class="contact-note">※送信内容は保存されます。</p>
            </form>
        <?php endif; ?>
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
