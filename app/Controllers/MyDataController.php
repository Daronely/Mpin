<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

/**
 * "My data" — lets ANYONE (registered or guest) review and delete
 * the responses linked to their account or browser token.
 */
final class MyDataController
{
    public function show(): void
    {
        $user = Auth::current();
        $responses = $this->myResponses($user);
        View::render('my-data', [
            'title'  => __('mydata.title') . ' — Mpin',
            'active' => '',
            'user'    => $user,
            'responses' => $responses,
            'isGuest' => $user === null,
            'flash'   => flash('mydata'),
        ]);
    }

    public function delete(): void
    {
        $user = Auth::current();
        $in = array_merge($_POST, json_body());
        $ids = [];
        if (!empty($in['all'])) {
            $ids = array_column($this->myResponses($user), 'id');
        } elseif (!empty($in['response_id'])) {
            // only delete if it really belongs to this visitor
            $mine = array_column($this->myResponses($user), 'id');
            if (in_array((int)$in['response_id'], array_map('intval', $mine), true)) {
                $ids = [(int)$in['response_id']];
            }
        }
        if (!$ids) {
            flash('mydata', __('mydata.empty'));
            redirect('/my-data');
        }
        $in2 = implode(',', array_map('intval', $ids));
        Database::tx(static function () use ($in2, $ids, $user): void {
            Database::run("DELETE FROM responses WHERE id IN ($in2)");
            Database::run(
                "UPDATE polls p
                 JOIN (SELECT poll_id, COUNT(*) c FROM responses GROUP BY poll_id) t
                   ON t.poll_id = p.id
                 SET p.response_count = t.c");
            try {
                Database::insert('audit_logs', [
                    'user_id' => $user['id'] ?? null,
                    'actor' => $user['username'] ?? 'guest',
                    'action' => 'data.erasure',
                    'target_type' => 'response',
                    'target_id' => count($ids),
                    'details' => 'self-service deletion via /my-data',
                    'created_at' => utcnow(),
                ]);
            } catch (\Throwable $e) { /* audit best-effort */ }
        });
        flash('mydata', count($ids) > 1 ? __('mydata.deleted_all') : __('mydata.deleted'));
        redirect('/my-data');
    }

    /** @return list<array<string,mixed>> */
    private function myResponses(?array $user): array
    {
        if ($user) {
            $sql = 'SELECT r.id, r.created_at, p.title, p.slug,
                           (SELECT COUNT(*) FROM locations l WHERE l.response_id = r.id) AS has_location
                    FROM responses r JOIN polls p ON p.id = r.poll_id
                    WHERE r.user_id = ? ORDER BY r.id DESC LIMIT 500';
            return Database::all($sql, [$user['id']]);
        }
        $raw = $_COOKIE['mpin_guest'] ?? '';
        if (!is_string($raw) || !preg_match('/^[a-f0-9]{64}$/', $raw)) {
            return [];
        }
        $gh = hash_hmac('sha256', $raw, APP_KEY);
        return Database::all(
            'SELECT r.id, r.created_at, p.title, p.slug,
                    (SELECT COUNT(*) FROM locations l WHERE l.response_id = r.id) AS has_location
             FROM responses r JOIN polls p ON p.id = r.poll_id
             WHERE r.guest_hash = ? ORDER BY r.id DESC LIMIT 500', [$gh]);
    }
}
