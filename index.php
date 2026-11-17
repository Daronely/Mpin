<?php
/**
 * MPIN — front controller. All HTTP requests enter here
 * (rewritten by .htaccess, or served directly by PHP's built-in server).
 */
require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;

$router = new Router();
(require MPIN_APP . '/routes.php')($router);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/* PHP built-in dev server: let it serve real files (assets, uploads, installer)
   but never the private directories. Apache uses .htaccess for the same job. */
if (PHP_SAPI === 'cli-server') {
    if (preg_match('#^/(app|storage)(/|$)|/\.git(/|$)#', $uri)) {
        http_response_code(403);
        exit('Forbidden');
    }
    $real = MPIN_ROOT . $uri;
    if ($uri !== '/' && (is_file($real) || is_dir($real))) {
        return false;
    }
}

if (BASE_PATH !== '' && str_starts_with($uri, BASE_PATH)) {
    $uri = substr($uri, strlen(BASE_PATH));
    if ($uri === false || $uri === '') {
        $uri = '/';
    }
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
