<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Models\Poll;
use App\Models\ResponseModel;

final class ApiController
{
    /* ---------------- public data ---------------- */

    public function stats(): void
    {
        $this->cache(60);
        json_ok(['data' => Poll::globalStats()]);
    }

    public function polls(): void
    {
        $list = Poll::listPublic([
            'sort'     => (string)($_GET['sort'] ?? 'latest'),
            'q'        => (string)($_GET['q'] ?? ''),
            'category' => (string)($_GET['category'] ?? ''),
            'country'  => (string)($_GET['country'] ?? ''),
            'page'     => (int)($_GET['page'] ?? 1),
            'per'      => (int)($_GET['per'] ?? 12),
        ]);
        $this->cache(30);
        json_ok(['data' => $this->pollCards($list)]);
    }

    public function poll(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        if (!$poll || $poll['visibility'] !== 'public') {
            json_err('Poll not found.', [], 404);
        }
        $this->cache(30);
        json_ok(['data' => $this->pollCard($poll)]);
    }

    public function pollMap(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            json_err('Poll not found.', [], 404);
        }
        $filters = [];
        foreach ($_GET as $k => $v) {
            if (preg_match('/^f(\d+)$/', $k, $m) && $v !== '') {
                $filters[(int)$m[1]] = array_map('intval', explode(',', (string)$v));
            }
        }
        $this->cache(20);
        json_ok(['data' => Poll::mapData((int)$poll['id'], $filters)]);
    }

    public function pollResults(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            json_err('Poll not found.', [], 404);
        }
        $this->cache(30);
        json_ok(['data' => Poll::results((int)$poll['id'])]);
    }

    public function globalMap(): void
    {
        $this->cache(60);
        json_ok(['data' => Poll::globalMap()]);
    }

    public function countries(): void
    {
        $this->cache(300);
        json_ok(['data' => Poll::countries()]);
    }

    /* ---------------- participation ---------------- */

    public function submitResponse(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            json_err('Poll not found.', [], 404);
        }
        if (!RateLimiter::check('respond', 30, 3600)) {
            json_err('Too many responses from your network. Please slow down.', [], 429);
        }
        $body = json_body();
        $answers = is_array($body['answers'] ?? null) ? $body['answers'] : [];
        $location = is_array($body['location'] ?? null) ? $body['location'] : null;
        if ($location !== null && ($location === [] || !isset($location['lat']))) {
            $location = null;
        }
        if (!empty($body['website'])) {   // honeypot
            json_err('Request rejected.');
        }

        $user = Auth::current();
        $result = ResponseModel::submit($user, $poll, $answers, $location);
        if (!$result['ok']) {
            json_err($result['error'], $result['errors'] ?? []);
        }
        json_ok(['message' => 'Response recorded. Thank you!']);
    }

    /* ---------------- auth ---------------- */

    public function login(): void
    {
        $body = array_merge($_POST, json_body());
        $login = trim((string)($body['login'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $remember = !empty($body['remember']);

        if (!RateLimiter::check('login', 8, 900, $login !== '' ? $login : null)) {
            $wait = max(60, RateLimiter::retryAfter('login', 900, $login !== '' ? $login : null));
            json_err("Too many attempts. Try again in " . ceil($wait / 60) . " minutes.", [], 429);
        }
        $result = Auth::attempt($login, $password, $remember);
        if (!$result['ok']) {
            json_err($result['error']);
        }
        json_ok(['message' => 'Welcome back!', 'redirect' => url($_SESSION['_next'] ?? '/dashboard')]);
    }

    public function register(): void
    {
        if (!RateLimiter::check('register', 5, 3600)) {
            json_err('Too many registrations from your network. Please try later.', [], 429);
        }
        $body = array_merge($_POST, json_body());
        if (!empty($body['website'])) {
            json_err('Request rejected.');
        }
        $username = trim((string)($body['username'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $confirm = (string)($body['confirm_password'] ?? '');
        $displayName = trim((string)($body['display_name'] ?? ''));

        if ($password !== $confirm) {
            json_err('Passwords do not match.', ['confirm_password' => 'Passwords do not match.']);
        }
        $result = Auth::register($username, $email, $password, $displayName);
        if (!$result['ok']) {
            json_err($result['error']);
        }
        $u = Database::one('SELECT * FROM users WHERE id = ?', [$result['id']]);
        Auth::startSession($u);
        json_ok(['message' => 'Account created. Welcome to Mpin!', 'redirect' => url('/dashboard')]);
    }

    public function logout(): void
    {
        Auth::logout();
        json_ok(['redirect' => url('/')]);
    }

    public function me(): void
    {
        $u = Auth::current();
        if (!$u) {
            json_ok(['data' => null]);
        }
        json_ok(['data' => [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'display_name' => $u['display_name'],
            'role' => $u['role'],
            'avatar_color' => $u['avatar_color'],
        ]]);
    }

    /* ---------------- favorites & notifications ---------------- */

    public function toggleFavorite(int $pollId): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in to save favorites.', [], 401);
        }
        $exists = Database::one(
            'SELECT poll_id FROM favorites WHERE user_id = ? AND poll_id = ?', [$user['id'], $pollId]);
        if ($exists) {
            Database::run('DELETE FROM favorites WHERE user_id = ? AND poll_id = ?', [$user['id'], $pollId]);
            json_ok(['favorite' => false]);
        }
        if (!Poll::byId($pollId)) {
            json_err('Poll not found.', [], 404);
        }
        Database::insert('favorites', ['user_id' => $user['id'], 'poll_id' => $pollId, 'created_at' => utcnow()]);
        json_ok(['favorite' => true]);
    }

    public function notifications(): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in.', [], 401);
        }
        $rows = Database::all(
            'SELECT id, type, title, body, link, is_read, created_at
             FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 30', [$user['id']]);
        json_ok(['data' => $rows]);
    }

    public function notificationsRead(): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in.', [], 401);
        }
        Database::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$user['id']]);
        json_ok(['message' => 'Marked as read.']);
    }

    /* ---------------- reports ---------------- */

    public function report(): void
    {
        if (!RateLimiter::check('report', 10, 3600)) {
            json_err('Too many reports. Please try later.', [], 429);
        }
        $body = json_body();
        $pollId = (int)($body['poll_id'] ?? 0);
        $reason = (string)($body['reason'] ?? '');
        $details = mb_substr(trim((string)($body['details'] ?? '')), 0, 500);
        if (!in_array($reason, ['spam', 'abuse', 'fake', 'privacy', 'other'], true)) {
            json_err('Invalid reason.');
        }
        if (!Poll::byId($pollId)) {
            json_err('Poll not found.', [], 404);
        }
        $user = Auth::current();
        Database::insert('reports', [
            'poll_id' => $pollId,
            'reporter_user_id' => $user['id'] ?? null,
            'reason' => $reason,
            'details' => $details,
            'created_at' => utcnow(),
        ]);
        json_ok(['message' => 'Report submitted. Our moderators will review it.']);
    }

    /* ---------------- helpers ---------------- */

    private function cache(int $seconds): void
    {
        header('Cache-Control: public, max-age=' . $seconds);
    }

    /** Shape a poll row for cards. */
    private function pollCard(array $p): array
    {
        return [
            'id' => (int)$p['id'],
            'slug' => $p['slug'],
            'title' => $p['title'],
            'description' => $p['description'],
            'cover' => upload_url($p['cover']),
            'category' => $p['category_name'],
            'category_slug' => $p['category_slug'],
            'creator' => $p['display_name'],
            'creator_username' => $p['username'],
            'participants' => (int)$p['response_count'],
            'countries' => (int)($p['countries_count'] ?? 0),
            'created_at' => iso($p['created_at']),
            'verified' => (bool)$p['is_verified'],
        ];
    }

    private function pollCards(array $list): array
    {
        return [
            'items' => array_map(fn($p) => $this->pollCard($p), $list['items']),
            'total' => $list['total'],
            'page' => $list['page'],
            'per' => $list['per'],
        ];
    }
}
