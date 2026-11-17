<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal pattern router.  Patterns support {name} placeholders,
 * matched as [^/]+  (or [^/]+ regex override: {id:\d+}).
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, keys:array, fn:callable}> */
    private array $routes = [];

    /**
     * @param string $pattern URL pattern with {name} placeholders
     * @param callable|array{0:class-string,1:string} $fn handler
     */
    public function get(string $pattern, callable|array $fn): void  { $this->add('GET', $pattern, $fn); }
    public function post(string $pattern, callable|array $fn): void { $this->add('POST', $pattern, $fn); }

    private function add(string $method, string $pattern, callable|array $fn): void
    {
        $keys = [];          // placeholder name => bool (int param?)
        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            static function (array $m) use (&$keys): string {
                $keys[$m[1]] = (bool) preg_match('#^\\\\d\+[\{\?\*\+]*$|^\\\\d\{\d+(,\d+)?\}$#', $m[2] ?? '');
                return '(' . ($m[2] ?? '[^/]+') . ')';
            },
            $pattern
        );
        $this->routes[] = [
            'method' => $method,
            'regex'  => '#^' . $regex . '$#',
            'keys'   => $keys,
            'fn'     => $fn,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        // Normalise: strip base path (sub-folder installs) and trailing slash.
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        $foundPath = false;
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $path, $m)) {
                continue;
            }
            $foundPath = true;
            if ($r['method'] !== $method) {
                continue;
            }
            array_shift($m);
            $args = [];
            $i = 0;
            foreach ($r['keys'] as $k => $isInt) {
                $args[$k] = $isInt ? (int)$m[$i] : $m[$i];
                $i++;
            }
            if ($r['method'] === 'POST') {
                Csrf::check();          // every POST must carry a valid CSRF token
            }
            $fn = $r['fn'];
            if (is_array($fn) && is_string($fn[0])) {
                $fn = [new $fn[0](), $fn[1]];   // [class, method] → instance callable
            }
            $fn(...$args);
            return;
        }

        if ($foundPath) {
            http_response_code(405);
            header('Allow: GET, POST');
        } else {
            http_response_code(404);
        }
        if (str_starts_with($path, '/api')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => $foundPath ? 'Method not allowed' : 'Not found']);
        } else {
            http_response_code($foundPath ? 405 : 404);
            View::render('pages/error', [
                'title' => '404 — Mpin',
                'code'  => $foundPath ? 405 : 404,
                'heading' => $foundPath ? '405 — Method not allowed' : __('error.404_heading'),
                'message' => __('error.404_message'),
            ]);
        }
    }
}
