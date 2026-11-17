<?php
/**
 * MPIN — global helper functions.
 */
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;

/* ---------------- constants from config ---------------- */
define('APP_URL',  rtrim((string)(MPIN_CONFIG['app']['url'] ?? ''), '/'));
define('APP_KEY',  (string)(MPIN_CONFIG['app']['key'] ?? 'dev-key'));
define('APP_DEV',  (MPIN_CONFIG['app']['env'] ?? 'production') === 'development');
define('PRIVACY_GRID',    (float)(MPIN_CONFIG['privacy']['grid'] ?? 0.05));
define('PRIVACY_MIN_CELL',(int)(MPIN_CONFIG['privacy']['min_cell'] ?? 3));

/* Base path when installed in a sub-folder (e.g. /mpin). */
$_base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('BASE_PATH', $_base === '/' || $_base === '/install' ? '' : rtrim($_base, '/'));
unset($_base);

/* ---------------- output & escaping ---------------- */

/** HTML-escape a value. */
function e(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL for an app path ('/explore' → 'https://host/explore'). */
function url(string $path = ''): string
{
    if ($path !== '' && !str_starts_with($path, '/') && !preg_match('#^https?://#', $path)) {
        $path = '/' . $path;
    }
    return APP_URL . BASE_PATH . $path;
}

/** URL of a public asset file (with mtime cache-busting). */
function asset(string $path): string
{
    $file = MPIN_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '1';
    return url('/assets/' . ltrim($path, '/')) . '?v=' . $v;
}

/** Uploaded/cover asset URL. */
function upload_url(?string $name): ?string
{
    if (!$name) {
        return null;
    }
    return url('/uploads/' . rawurlencode($name));
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)), true, 302);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------------- JSON responses ---------------- */

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_ok(array $data = [], int $status = 200): never
{
    json_out(['ok' => true] + $data, $status);
}

function json_err(string $message, array $errors = [], int $status = 400): never
{
    json_out(['ok' => false, 'error' => $message, 'errors' => $errors], $status);
}

/** Decode a JSON request body (AJAX). */
function json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/* ---------------- flash messages ---------------- */

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $v;
}

/* ---------------- users ---------------- */

function current_user(): ?array
{
    return Auth::current();
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $ini = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini !== '' ? $ini : 'M';
}

/* ---------------- misc utilities ---------------- */

function utcnow(): string
{
    return gmdate('Y-m-d H:i:s');
}

function str_random(int $len = 32): string
{
    return substr(bin2hex(random_bytes((int)ceil($len / 2))), 0, $len);
}

/** Client IP (honours trusted proxy setting). */
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty(MPIN_CONFIG['app']['trust_proxy'])) {
        $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($fwd !== '') {
            $first = trim(explode(',', $fwd)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                $ip = $first;
            }
        }
    }
    return $ip;
}

/** Salted, non-reversible IP hash — used for moderation/dedup only. */
function ip_hash(?string $ip = null): string
{
    return hash_hmac('sha256', $ip ?? client_ip(), APP_KEY);
}

/** Human-friendly URL slug from a title. */
function slugify(string $text, int $maxLen = 80): string
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    $text = $text === '' ? 'poll' : $text;
    if (strlen($text) > $maxLen) {
        $text = substr($text, 0, $maxLen);
        $text = preg_replace('/-[^-]*$/', '', $text) ?? $text;
    }
    return $text;
}

/** Generate a unique poll slug. */
function unique_slug(string $title, ?int $ignorePollId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $i = 1;
    while (true) {
        $row = Database::one('SELECT id FROM polls WHERE slug = ?', [$slug]);
        if ($row === null || ($ignorePollId !== null && (int)$row['id'] === $ignorePollId)) {
            return $slug;
        }
        $slug = $base . '-' . (++$i);
    }
}

/** Compact number formatting (12345 → 12.3K), localized digits. */
function num_fmt(int|float $n): string
{
    if ($n >= 1_000_000) $s = round($n / 1_000_000, 1) . 'M';
    elseif ($n >= 1_000) $s = round($n / 1_000, 1) . 'K';
    else $s = (string)(int)$n;
    return local_num($s);
}

/** Percent with 1 decimal. */
function pct(int|float $part, int|float $total): float
{
    return $total > 0 ? round($part / $total * 100, 1) : 0.0;
}

/** Read a site setting. */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (Database::all('SELECT setting_key, setting_value FROM settings') as $r) {
            $cache[$r['setting_key']] = $r['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function save_setting(string $key, string $value): void
{
    Database::run(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
}

/* ---------------- privacy grid helpers ---------------- */

/**
 * Snap a coordinate pair onto the privacy grid and add a deterministic
 * pseudo-random offset inside the cell, so no exact point is ever stored
 * or served, while a cell's representation stays stable across requests.
 * @return array{lat:float,lng:float}
 */
function snap_location(float $lat, float $lng, int $seed): array
{
    $lat = max(-85.0, min(85.0, $lat));
    $lng = max(-180.0, min(180.0, $lng));
    $g = PRIVACY_GRID;
    $gLat = floor($lat / $g) * $g + $g / 2;   // cell centre
    $gLng = floor($lng / $g) * $g + $g / 2;
    $h = (int) hexdec(substr(hash('sha256', $seed . '|' . $gLat . '|' . $gLng), 0, 12));
    $off = ($g * 0.72);                        // keep inside the cell
    return [
        'lat' => round($gLat + (($h % 1000) / 1000 - 0.5) * $off, 6),
        'lng' => round($gLng + ((intdiv($h, 1000) % 1000) / 1000 - 0.5) * $off, 6),
    ];
}

/** Parse a datetime-local value ('Y-m-d\TH:i') into MySQL format; null when blank/invalid. */
function parse_dt(?string $v): ?string
{
    if ($v === null || trim($v) === '') return null;
    $d = DateTime::createFromFormat('Y-m-d\TH:i', trim($v))
      ?: DateTime::createFromFormat('Y-m-d H:i:s', trim($v))
      ?: DateTime::createFromFormat('Y-m-d H:i', trim($v));
    return $d ? $d->format('Y-m-d H:i:s') : null;
}

/** '2026-11-17 12:00:00' (UTC) → ISO-8601 with Z suffix. */
function iso(?string $dt): ?string
{
    if (!$dt) return null;
    return str_replace(' ', 'T', $dt) . 'Z';
}

/** Days ago from now (UTC), for "new" badges. */
function is_fresh(?string $dt, int $days = 7): bool
{
    if (!$dt) return false;
    return (time() - strtotime($dt . ' UTC')) < $days * 86400;
}
