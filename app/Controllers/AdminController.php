<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Category;
use App\Models\Poll;

final class AdminController
{
    /** Record a moderation action in the audit log. */
    private static function audit(string $action, string $targetType, $targetId, string $details = ''): void
    {
        try {
            $u = Auth::current();
            Database::insert('audit_logs', [
                'user_id'    => $u['id'] ?? null,
                'actor'      => $u['username'] ?? 'system',
                'action'     => $action,
                'target_type' => $targetType,
                'target_id'  => (string)$targetId,
                'details'    => mb_substr($details, 0, 300),
                'created_at' => utcnow(),
            ]);
        } catch (\Throwable $e) {
            error_log('[mpin] audit: ' . $e->getMessage());
        }
    }

    public function index(): void
    {
        Auth::requireAdmin();
        $stats = Poll::globalStats();
        $stats['open_reports'] = (int) Database::value(
            "SELECT COUNT(*) FROM reports WHERE status = 'open'");
        $stats['banned'] = (int) Database::value(
            'SELECT COUNT(*) FROM users WHERE is_active = 0');
        $recentResponses = Database::all(
            'SELECT r.id, r.created_at, p.title, p.slug, u.username
             FROM responses r
             JOIN polls p ON p.id = r.poll_id
             LEFT JOIN users u ON u.id = r.user_id
             ORDER BY r.id DESC LIMIT 10');
        $topPolls = Poll::listPublic(['sort' => 'popular', 'per' => 5])['items'];

        View::render('admin/index', [
            'title' => 'Admin — Mpin', 'active' => 'admin',
            'stats' => $stats, 'recentResponses' => $recentResponses, 'topPolls' => $topPolls,
        ]);
    }

    public function section(string $section): void
    {
        Auth::requireAdmin();
        $data = ['title' => 'Admin: ' . ucfirst($section) . ' — Mpin', 'active' => 'admin', 'section' => $section];

        if ($section === 'users') {
            $q = trim((string)($_GET['q'] ?? ''));
            $where = $q ? 'WHERE username LIKE ? OR email LIKE ? OR display_name LIKE ?' : '';
            $params = $q ? ["%$q%", "%$q%", "%$q%"] : [];
            $data['users'] = Database::all(
                "SELECT u.*, (SELECT COUNT(*) FROM polls p WHERE p.user_id = u.id) polls_count,
                        (SELECT COUNT(*) FROM responses r WHERE r.user_id = u.id) responses_count
                 FROM users u $where ORDER BY u.id DESC LIMIT 200", $params);
        } elseif ($section === 'polls') {
            $data['polls'] = Database::all(
                'SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.username
                 FROM polls p JOIN categories c ON c.id = p.category_id
                 JOIN users u ON u.id = p.user_id
                 ORDER BY p.created_at DESC LIMIT 200');
        } elseif ($section === 'categories') {
            $data['categories'] = Database::all(
                'SELECT c.*, (SELECT COUNT(*) FROM polls p WHERE p.category_id = c.id) polls_count
                 FROM categories c ORDER BY c.sort_order, c.name');
        } elseif ($section === 'reports') {
            $data['reports'] = Database::all(
                'SELECT rp.*, p.title AS poll_title, p.slug AS poll_slug, u.username AS reporter
                 FROM reports rp
                 JOIN polls p ON p.id = rp.poll_id
                 LEFT JOIN users u ON u.id = rp.reporter_user_id
                 ORDER BY FIELD(rp.status, "open", "resolved", "dismissed"), rp.created_at DESC
                 LIMIT 200');
        } elseif ($section === 'logs') {
            $data['logs'] = Database::all(
                'SELECT a.*, u.username
                 FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                 ORDER BY a.id DESC LIMIT 300');
        } elseif ($section === 'settings') {
            $data['settings'] = [
                'site_tagline' => setting('site_tagline', 'People Answer. The World Appears.'),
                'maintenance'  => setting('maintenance', '0'),
            ];
        }
        View::render('admin/' . $section, $data);
    }

    /* ---------------- actions ---------------- */

    public function userAction(int $id, string $action): void
    {
        $admin = Auth::requireAdmin();
        $user = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$user) {
            json_err('User not found.', [], 404);
        }
        if ((int)$user['id'] === (int)$admin['id']) {
            json_err('You cannot moderate your own account.');
        }
        switch ($action) {
            case 'suspend':
                Database::update('users', ['is_active' => 0], 'id = ?', [$id]);
                self::audit('user.suspend', 'user', $id, '@' . $user['username']);
                json_ok(['message' => 'User suspended.']);
            case 'activate':
                Database::update('users', ['is_active' => 1], 'id = ?', [$id]);
                self::audit('user.activate', 'user', $id, '@' . $user['username']);
                json_ok(['message' => 'User activated.']);
            case 'make-admin':
                Database::update('users', ['role' => 'admin'], 'id = ?', [$id]);
                self::audit('user.promote', 'user', $id, '@' . $user['username']);
                json_ok(['message' => 'Promoted to admin.']);
            case 'make-user':
                Database::update('users', ['role' => 'user'], 'id = ?', [$id]);
                self::audit('user.demote', 'user', $id, '@' . $user['username']);
                json_ok(['message' => 'Demoted to regular user.']);
            case 'delete':
                self::audit('user.delete', 'user', $id, '@' . $user['username'] . ' (with all polls/responses)');
                Database::run('DELETE FROM users WHERE id = ?', [$id]);
                json_ok(['message' => 'User and all their data deleted.']);
        }
        json_err('Unknown action.');
    }

    public function pollAction(int $id, string $action): void
    {
        Auth::requireAdmin();
        $poll = Poll::byId($id);
        if (!$poll) {
            json_err('Poll not found.', [], 404);
        }
        switch ($action) {
            case 'feature':   Database::update('polls', ['is_featured' => 1], 'id = ?', [$id]); self::audit('poll.feature', 'poll', $id, $poll['slug']); json_ok(['message' => 'Poll featured.']);
            case 'unfeature': Database::update('polls', ['is_featured' => 0], 'id = ?', [$id]); self::audit('poll.unfeature', 'poll', $id, $poll['slug']); json_ok(['message' => 'Poll unfeatured.']);
            case 'verify':    Database::update('polls', ['is_verified' => 1], 'id = ?', [$id]); self::audit('poll.verify', 'poll', $id, $poll['slug']); json_ok(['message' => 'Poll verified.']);
            case 'unverify':  Database::update('polls', ['is_verified' => 0], 'id = ?', [$id]); self::audit('poll.unverify', 'poll', $id, $poll['slug']); json_ok(['message' => 'Verification removed.']);
            case 'close':     Database::update('polls', ['status' => 'closed'], 'id = ?', [$id]); self::audit('poll.close', 'poll', $id, $poll['slug']); json_ok(['message' => 'Poll closed.']);
            case 'open':      Database::update('polls', ['status' => 'open'], 'id = ?', [$id]); self::audit('poll.open', 'poll', $id, $poll['slug']); json_ok(['message' => 'Poll opened.']);
            case 'delete':    self::audit('poll.delete', 'poll', $id, $poll['slug']); Database::run('DELETE FROM polls WHERE id = ?', [$id]); json_ok(['message' => 'Poll deleted.']);
        }
        json_err('Unknown action.');
    }

    public function categoryAdd(): void
    {
        Auth::requireAdmin();
        $in = array_merge($_POST, json_body());
        $name = trim((string)($in['name'] ?? ''));
        $slug = slugify(trim((string)($in['slug'] ?? '')) ?: $name, 40);
        $icon = preg_replace('/[^a-z-]/', '', (string)($in['icon'] ?? 'tag')) ?: 'tag';
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            json_err('Name must be 2-60 characters.');
        }
        if (Category::bySlug($slug)) {
            json_err('A category with this slug already exists.');
        }
        $max = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) FROM categories');
        Database::insert('categories', [
            'slug' => $slug, 'name' => $name, 'icon' => $icon, 'sort_order' => $max + 1,
        ]);
        self::audit('category.add', 'category', $slug, $name);
        json_ok(['message' => 'Category added.']);
    }

    public function categoryDelete(int $id): void
    {
        Auth::requireAdmin();
        $count = (int) Database::value('SELECT COUNT(*) FROM polls WHERE category_id = ?', [$id]);
        if ($count > 0) {
            json_err("Cannot delete: $count poll(s) use this category. Move them first.");
        }
        self::audit('category.delete', 'category', $id);
        Database::run('DELETE FROM categories WHERE id = ?', [$id]);
        json_ok(['message' => 'Category deleted.']);
    }

    public function reportAction(int $id, string $action): void
    {
        Auth::requireAdmin();
        $status = $action === 'resolve' ? 'resolved' : 'dismissed';
        Database::update('reports', ['status' => $status], 'id = ?', [$id]);
        self::audit('report.' . $status, 'report', $id);
        json_ok(['message' => 'Report ' . $status . '.']);
    }

    public function settingsSave(): void
    {
        Auth::requireAdmin();
        $in = array_merge($_POST, json_body());
        save_setting('site_tagline', mb_substr(trim((string)($in['site_tagline'] ?? '')), 0, 120));
        save_setting('maintenance', !empty($in['maintenance']) ? '1' : '0');
        self::audit('settings.save', 'site', 0, 'tagline/maintenance');
        json_ok(['message' => 'Settings saved.']);
    }
}
