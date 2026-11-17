<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Poll;
use App\Models\User;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $myPolls = Poll::forUser((int)$user['id']);

        $totalResponses = 0;
        $totalCountries = 0;
        foreach ($myPolls as $p) {
            $totalResponses += (int)$p['response_count'];
        }
        if ($myPolls) {
            $ids = implode(',', array_map(static fn($p) => (int)$p['id'], $myPolls));
            $totalCountries = (int) Database::value(
                "SELECT COUNT(DISTINCT country) FROM locations WHERE poll_id IN ($ids)
                 AND country IS NOT NULL AND country != ''");
        }
        $favorites = Database::all(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM favorites f
             JOIN polls p ON p.id = f.poll_id
             JOIN categories c ON c.id = p.category_id
             WHERE f.user_id = ? ORDER BY f.created_at DESC', [$user['id']]);

        $unread = (int) Database::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']]);

        View::render('dashboard', [
            'title'  => 'Dashboard — Mpin',
            'active' => 'dashboard',
            'user'   => $user,
            'myPolls' => $myPolls,
            'participated' => User::participated((int)$user['id'], 8),
            'stats' => [
                'polls' => count($myPolls),
                'responses' => $totalResponses,
                'countries' => $totalCountries,
                'participated' => (int) Database::value(
                    'SELECT COUNT(DISTINCT poll_id) FROM responses WHERE user_id = ?', [$user['id']]),
            ],
            'favorites' => $favorites,
            'unread' => $unread,
        ]);
    }

    /** Manage a single poll: results, export, settings, danger zone. */
    public function poll(int $id): void
    {
        $user = Auth::requireLogin();
        $poll = Poll::byId($id);
        if (!$poll) {
            json_err('Not found.', [], 404);
        }
        if ((int)$poll['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            View::render('pages/error', [
                'title' => 'Forbidden — Mpin', 'code' => 403,
                'heading' => '403 — Not your poll',
                'message' => 'You can only manage polls you have created.',
            ]);
            exit;
        }
        View::render('dashboard_poll', [
            'title'   => $poll['title'] . ' — Manage — Mpin',
            'active'  => 'dashboard',
            'poll'    => $poll,
            'status'  => Poll::effectiveStatus($poll),
            'results' => Poll::results($id),
        ]);
    }

    public function export(int $id): void
    {
        $user = Auth::requireLogin();
        $poll = Poll::byId($id);
        if (!$poll || ((int)$poll['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin')) {
            json_err('Not found.', [], 404);
        }
        $format = ($_GET['format'] ?? 'csv') === 'json' ? 'json' : 'csv';
        $export = Poll::export($id, $format);
        $safeSlug = preg_replace('/[^a-z0-9-]/', '', $poll['slug']) ?: 'poll';

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $safeSlug . '-data.json"');
            echo json_encode([
                'poll' => [
                    'slug' => $poll['slug'],
                    'title' => $poll['title'],
                    'exported_at' => iso(utcnow()),
                ],
                'columns' => $export['header'],
                'rows' => $export['rows'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeSlug . '-data.csv"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, $export['header']);
        foreach ($export['rows'] as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
