<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function byUsername(string $username): ?array
    {
        return Database::one('SELECT * FROM users WHERE username = ?', [strtolower($username)]);
    }

    public static function byId(int $id): ?array
    {
        return Database::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** Public profile payload: user + counts. */
    public static function profile(string $username): ?array
    {
        $u = self::byUsername($username);
        if (!$u) {
            return null;
        }
        $u['polls_count']      = (int) Database::value(
            "SELECT COUNT(*) FROM polls WHERE user_id = ? AND status != 'draft'", [$u['id']]);
        $u['responses_count']  = (int) Database::value(
            'SELECT COUNT(*) FROM responses WHERE user_id = ?', [$u['id']]);
        $u['countries_count']  = (int) Database::value(
            'SELECT COUNT(DISTINCT l.country) FROM locations l
             JOIN responses r ON r.id = l.response_id
             JOIN polls p ON p.id = r.poll_id
             WHERE r.user_id = ? AND p.visibility = "public" AND l.country IS NOT NULL AND l.country != ""',
            [$u['id']]);
        return $u;
    }

    /** Polls this user participated in (paginated). */
    public static function participated(int $userId, int $limit = 12, int $offset = 0): array
    {
        return Database::all(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name, u.username, u.display_name,
                    r.created_at AS responded_at
             FROM responses r
             JOIN polls p      ON p.id = r.poll_id
             JOIN categories c ON c.id = p.category_id
             JOIN users u      ON u.id = p.user_id
             WHERE r.user_id = ?
             GROUP BY p.id
             ORDER BY MAX(r.created_at) DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            [$userId]
        );
    }
}
