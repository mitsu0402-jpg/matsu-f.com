<?php
// All checks run before storing a request or sending notification mail.
function contact_limit_path(): string
{
    // Outside the document root; never store message bodies or raw IP addresses.
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . 'matsu-contact-' . hash('sha256', __DIR__) . '.json';
}

function contact_reserve_submission(string $ip, string $fingerprint): bool
{
    $handle = @fopen(contact_limit_path(), 'c+');
    if (!$handle) {
        throw new RuntimeException('Cannot open contact rate limit storage');
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Cannot lock contact rate limit storage');
        }
        $raw = stream_get_contents($handle);
        $entries = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($entries)) {
            throw new RuntimeException('Invalid contact rate limit storage');
        }
        $now = time();
        $entries = array_values(array_filter($entries, static function ($row) use ($now) {
            return is_array($row) && ($row['time'] ?? 0) > $now - 600;
        }));
        $ipHash = hash('sha256', $ip);
        $count = 0;
        foreach ($entries as $row) {
            if (($row['fingerprint'] ?? '') === $fingerprint) {
                return false;
            }
            if (($row['ip'] ?? '') === $ipHash) {
                $count++;
            }
        }
        if ($count >= 3) {
            return false;
        }
        $entries[] = ['time' => $now, 'ip' => $ipHash, 'fingerprint' => $fingerprint];
        $json = json_encode($entries);
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
            throw new RuntimeException('Cannot write contact rate limit storage');
        }
        return true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
