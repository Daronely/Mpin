<?php
/**
 * MPIN — application bootstrap.
 * Loads config, autoloader, session and core helpers.
 */
declare(strict_types=1);

define('MPIN_ROOT', dirname(__DIR__));
define('MPIN_APP', __DIR__);
define('MPIN_VERSION', '1.0.0');

/* ------------------------------------------------------------------ */
/* Load configuration                                                  */
/* ------------------------------------------------------------------ */
$configFile = MPIN_APP . '/config.php';
if (!is_file($configFile)) {
    // Not installed yet — hand over to the installer.
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = rtrim($base, '/');
    $install = $base . '/install/';
    if (PHP_SAPI !== 'cli' && !str_contains($_SERVER['REQUEST_URI'] ?? '', '/install')) {
        header('Location: ' . $install, true, 302);
        exit;
    }
}
$config = is_file($configFile) ? require $configFile : null;

define('MPIN_CONFIG', $config ?? []);

/* ------------------------------------------------------------------ */
/* PSR-4 style autoloader (App\ → app/)                                */
/* ------------------------------------------------------------------ */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel  = substr($class, strlen($prefix));
    $file = MPIN_APP . '/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/* ------------------------------------------------------------------ */
/* Error handling                                                      */
/* ------------------------------------------------------------------ */
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
$dev = ($config['app']['env'] ?? 'production') === 'development';
ini_set('display_errors', $dev ? '1' : '0');
ini_set('log_errors', '1');
@mkdir(MPIN_ROOT . '/storage/logs', 0775, true);
ini_set('error_log', MPIN_ROOT . '/storage/logs/php-error.log');
error_reporting(E_ALL);

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) return true;
    throw new ErrorException($str, 0, $no, $file, $line);
});

set_exception_handler(static function (Throwable $e) use ($dev): void {
    $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api')
          || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    error_log('[mpin] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Internal server error']);
    } elseif ($dev) {
        http_response_code(500);
        echo '<pre style="padding:24px;font:14px/1.6 monospace">'
           . htmlspecialchars((string)$e) . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Error — Mpin</title>'
           . '<body style="font-family:system-ui;background:#0b1220;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0">'
           . '<div style="text-align:center"><div style="font-size:44px">🗺️</div>'
           . '<h1 style="font-size:22px;margin:12px 0 6px">Something went wrong</h1>'
           . '<p style="color:#94a3b8;margin:0">The error has been logged. Please try again later.</p></div></body>';
    }
    exit;
});

/* ------------------------------------------------------------------ */
/* Helpers + Database                                                  */
/* ------------------------------------------------------------------ */
require MPIN_APP . '/Core/helpers.php';

use App\Core\Database;

if ($config !== null) {
    Database::init($config['db']);
}

/* ------------------------------------------------------------------ */
/* Session                                                             */
/* ------------------------------------------------------------------ */
if (PHP_SAPI !== 'cli' && $config !== null) {
    $secure = str_starts_with($config['app']['url'] ?? '', 'https://');
    session_name('mpinsid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    \App\Core\I18n::init();
}
