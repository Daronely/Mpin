<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['csrf'] ?? '';
    }

    /** Validate the request CSRF token (form field or X-CSRF-Token header). */
    public static function validate(): bool
    {
        $sent = $_POST['_csrf']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')
            ?? '';
        if (!is_string($sent) || $sent === '') {
            return false;
        }
        return hash_equals(self::token(), $sent);
    }

    /** Abort with 419 if the token is invalid (POST requests). */
    public static function check(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        if (!self::validate()) {
            http_response_code(419);
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '/', '/api')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Session expired. Refresh the page and try again.']);
            } else {
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Session expired. Go back, refresh the page and try again.';
            }
            exit;
        }
    }
}
