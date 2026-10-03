<?php
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/control/lib/db.php';
require_once __DIR__ . '/control/lib/contact_guard.php';

session_start(['cookie_httponly' => true, 'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
header('Cache-Control: no-store');
if (!isset($_SESSION['contact_token'])) {
    $_SESSION['contact_token'] = bin2hex(random_bytes(32));
    $_SESSION['contact_started'] = time();
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function send_text_mail(string $to, string $subject, string $body, array $bcc = []): bool
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: no-reply@matsu-f.com',
    ];
    if ($bcc) {
        $headers[] = 'Bcc: ' . implode(', ', $bcc);
    }
    $headerText = implode("\r\n", $headers);

    if (function_exists('mb_send_mail')) {
        return mb_send_mail($to, $subject, $body, $headerText);
    }

    $encodedSubject = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($subject, 'UTF-8')
        : $subject;
    return mail($to, $encodedSubject, $body, $headerText);
}

$errors = [];
$showThanks = isset($_GET['thanks']) && $_GET['thanks'] === '1';

$values = [
    'name' => '',
    'contact' => '',
    'request' => 'sell',
    'note' => '',
];

if (!$showThanks && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $prefillRequest = (string)($_GET['request'] ?? '');
    $prefillNote = trim((string)($_GET['note'] ?? ''));
    if (in_array($prefillRequest, ['sell', 'rent', 'buy', 'borrow', 'consult'], true)) {
        $values['request'] = $prefillRequest;
    }
    if ($prefillNote !== '') {
        $values['note'] = $prefillNote;
    }
}

if (!$showThanks && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['name', 'contact', 'request', 'note'] as $field) {
        $values[$field] = isset($_POST[$field]) && is_string($_POST[$field]) ? trim($_POST[$field]) : '';
    }
    $token = $_POST['form_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['contact_token'], $token)
        || !isset($_POST['website']) || !is_string($_POST['website']) || $_POST['website'] !== '') {
        $errors[] = '送信を確認できませんでした。ページを開き直してください。';
    }
    if (!isset($_SESSION['contact_started']) || time() - $_SESSION['contact_started'] < 3) {
        $errors[] = '送信が早すぎます。数秒待ってからもう一度送信してください。';
    }
    foreach (['name' => 100, 'contact' => 254, 'note' => 5000] as $field => $maximum) {
        if (preg_match('//u', $values[$field]) !== 1
            || (function_exists('mb_strlen') ? mb_strlen($values[$field], 'UTF-8') : strlen($values[$field])) > $maximum
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $values[$field])) {
            $errors[] = '入力内容の文字数・形式を確認してください。';
        }
    }
    $phone = preg_replace('/[\s()\-]/', '', $values['contact']);
    if ($values['contact'] !== '' && !filter_var($values['contact'], FILTER_VALIDATE_EMAIL)
        && !preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors[] = '連絡先には電話番号またはメールアドレスを入力してください。';
    }

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
            $fingerprint = hash('sha256', json_encode($values, JSON_UNESCAPED_UNICODE));
            if (!contact_reserve_submission((string)($_SERVER['REMOTE_ADDR'] ?? ''), $fingerprint)) {
                $errors[] = '連続送信または同じ内容の送信を受け付けられません。10分ほど待ってからお試しください。';
            }
        } catch (Throwable $e) {
            error_log('Contact rate limit storage unavailable');
            $errors[] = '現在送信を受け付けられません。時間をおいてお試しください。';
        }
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

            $mailSubject = '【松永不動産】お問い合わせがありました';
            $mailBody = implode("\n", [
                'お問い合わせがありました。',
                '',
                'お名前: ' . $values['name'],
                '連絡先: ' . $values['contact'],
                'ご要望: ' . $values['request'],
                '備考: ' . ($values['note'] !== '' ? $values['note'] : '-'),
                '',
                'ダッシュボード: https://matsu-f.com/control/public/index.php?page=contact_list',
            ]);
            send_text_mail('info@matsu-f.com', $mailSubject, $mailBody, ['mitsu0402@gmail.com']);

            $_SESSION['contact_token'] = bin2hex(random_bytes(32));
            $_SESSION['contact_started'] = time();
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        <?php require __DIR__ . '/inc/siteHeaderFooterCss.php'; ?>
        :root {
            --border-color: #d6d0c7;
            --focus-color: #7a4b2a;
            --bg: #f6f4f1;
            --card: #ffffff;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Monda, Helvetica, Arial, Sans-Serif, serif;
            background: var(--bg);
            color: #1b1b1b;
        }

        .contact-page {
            padding: 24px;
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

        @media (max-width: 767px) {
            .contact-page {
                padding: 12px;
            }

            .contact-wrap {
                padding: 18px 14px;
            }

            .contact-title {
                font-size: 20px;
            }

            .contact-form {
                gap: 12px;
            }

            .contact-input,
            .contact-select,
            .contact-textarea {
                font-size: 16px;
            }

            .contact-submit {
                width: 100%;
            }
            .contact-page {
                padding: 10px;
            }

            .contact-wrap {
                padding: 16px 12px;
            }

            .contact-title {
                font-size: 18px;
            }
        }
    </style>
    <title>お問い合わせ 松永不動産</title>
</head>
<body>
<?php
$siteHeroTitle = 'お問い合わせ';
$siteNavActive = 'contact';
require __DIR__ . '/inc/siteHeader.php';
?>
<main class="site-main">
    <div class="contact-page">
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
                    <input type="hidden" name="form_token" value="<?php echo h($_SESSION['contact_token']); ?>">
                    <div hidden aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input id="contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                    </div>
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
    </div>
</main>
<?php
$siteFooterMaxWidth = '1200px';
require __DIR__ . '/inc/siteFooter.php';
?>
</body>
</html>
