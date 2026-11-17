<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Authentication: sessions, remember-me cookies, registration.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    /** Current logged-in user row (or null). */
    public static function current(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        if (!empty($_SESSION['uid'])) {
            $u = Database::one(
                'SELECT * FROM users WHERE id = ? AND is_active = 1', [$_SESSION['uid']]
            );
            if ($u) {
                self::$user = $u;
                return self::$user;
            }
            unset($_SESSION['uid']);
        }

        // Remember-me cookie
        $rm = $_COOKIE['mpinrm'] ?? '';
        if (is_string($rm) && preg_match('/^[a-f0-9]{64}$/', $rm)) {
            $hash = hash_hmac('sha256', $rm, APP_KEY);
            $u = Database::one(
                'SELECT * FROM users WHERE remember_hash = ? AND is_active = 1', [$hash]
            );
            if ($u) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$u['id'];
                self::$user = $u;
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::current() !== null;
    }

    public static function isAdmin(): bool
    {
        $u = self::current();
        return $u !== null && $u['role'] === 'admin';
    }

    /** Redirect to login if not authenticated. Terminates request. */
    public static function requireLogin(): array
    {
        $u = self::current();
        if ($u === null) {
            $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            redirect('/login?next=' . $next);
        }
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::current();
        if ($u === null) {
            redirect('/login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        }
        if ($u['role'] !== 'admin') {
            http_response_code(403);
            View::render('pages/error', [
                'title' => 'Forbidden', 'code' => 403,
                'heading' => '403 — Restricted area',
                'message' => 'You do not have permission to view this page.',
            ]);
            exit;
        }
        return $u;
    }

    /**
     * Verify credentials and create a session.
     * @return array{ok:bool,error?:string}
     */
    public static function attempt(string $login, string $password, bool $remember = false): array
    {
        $u = Database::one(
            'SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1',
            [$login, $login]
        );
        if (!$u || !password_verify($password, $u['password_hash'])) {
            return ['ok' => false, 'error' => 'Invalid username/email or password.'];
        }
        if ((int)$u['is_active'] !== 1) {
            return ['ok' => false, 'error' => 'This account has been suspended.'];
        }
        self::startSession($u, $remember);
        return ['ok' => true];
    }

    /** @param array $u user row */
    public static function startSession(array $u, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        Database::update('users', ['last_login' => utcnow()], 'id = ?', [$u['id']]);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hash  = hash_hmac('sha256', $token, APP_KEY);
            Database::update('users', ['remember_hash' => $hash], 'id = ?', [$u['id']]);
            $secure = str_starts_with(APP_URL, 'https://');
            setcookie('mpinrm', $token, [
                'expires'  => time() + 30 * 86400,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        self::$user = Database::one('SELECT * FROM users WHERE id = ?', [$u['id']]);
        self::$resolved = true;
    }

    public static function logout(): void
    {
        $u = self::current();
        if ($u) {
            Database::update('users', ['remember_hash' => null], 'id = ?', [$u['id']]);
        }
        setcookie('mpinrm', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        self::$user = null;
        self::$resolved = true;
    }

    /**
     * Register a new user.
     * @return array{ok:bool,error?:string,id?:int}
     */
    public static function register(string $username, string $email, string $password, string $displayName, string $role = 'user'): array
    {
        $username = strtolower(trim($username));
        $email    = strtolower(trim($email));
        $displayName = trim($displayName) ?: $username;

        if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            return ['ok' => false, 'error' => 'Username must be 3-30 characters (a-z, 0-9, underscore).'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            return ['ok' => false, 'error' => 'Please enter a valid e-mail address.'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
        }
        if (mb_strlen($displayName) > 60) {
            return ['ok' => false, 'error' => 'Display name is too long (max 60).'];
        }
        if (Database::value('SELECT id FROM users WHERE username = ?', [$username])) {
            return ['ok' => false, 'error' => 'This username is already taken.'];
        }
        if (Database::value('SELECT id FROM users WHERE email = ?', [$email])) {
            return ['ok' => false, 'error' => 'This e-mail is already registered.'];
        }

        $id = Database::insert('users', [
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'display_name'  => $displayName,
            'avatar_color'  => sprintf('#%06X', random_int(0, 0xFFFFFF)),
            'role'          => $role,
        ]);
        return ['ok' => true, 'id' => $id];
    }
}
