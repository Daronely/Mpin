<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Poll;

final class PollController
{
    public function show(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            $this->notFound();
        }

        $results = Poll::results((int)$poll['id']);
        $user = Auth::current();

        // Has the current visitor already responded?
        $alreadyResponded = false;
        if ($user) {
            $alreadyResponded = (bool) Database::value(
                'SELECT id FROM responses WHERE poll_id = ? AND user_id = ?', [$poll['id'], $user['id']]);
        }
        if (!$alreadyResponded && !$poll['allow_multiple']) {
            // guests: cookie hash (silent check, best-effort)
            $alreadyResponded = $results['participants'] > 0 && $this->guestResponded((int)$poll['id']);
        }

        $isFavorite = $user ? (bool) Database::value(
            'SELECT poll_id FROM favorites WHERE user_id = ? AND poll_id = ?', [$user['id'], $poll['id']]) : false;

        $owner = $user && ((int)$user['id'] === (int)$poll['user_id'] || $user['role'] === 'admin');

        View::render('poll', [
            'title'    => $poll['title'] . ' — Mpin',
            'active'   => '',
            'poll'     => $poll,
            'results'  => $results,
            'status'   => Poll::effectiveStatus($poll),
            'canParticipate' => Poll::canParticipate($poll),
            'alreadyResponded' => $alreadyResponded,
            'isFavorite' => $isFavorite,
            'isOwner'  => $owner,
            'related'  => Poll::listPublic(['category' => $poll['category_slug'], 'per' => 4])['items'],
        ]);
    }

    /** Owner actions: close / reopen / delete (AJAX, JSON). */
    public function manage(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        $user = Auth::current();
        if (!$poll || !$user) {
            json_err('Not found.', [], 404);
        }
        if ((int)$poll['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') {
            json_err('You do not own this poll.', [], 403);
        }
        $action = (string)($_POST['action'] ?? json_body()['action'] ?? '');
        switch ($action) {
            case 'close':
                Database::update('polls', ['status' => 'closed'], 'id = ?', [$poll['id']]);
                json_ok(['message' => 'Poll closed.', 'status' => 'closed']);
            case 'open':
                Database::update('polls', ['status' => 'open'], 'id = ?', [$poll['id']]);
                json_ok(['message' => 'Poll reopened.', 'status' => 'open']);
            case 'delete':
                Database::run('DELETE FROM polls WHERE id = ?', [$poll['id']]);
                json_ok(['message' => 'Poll deleted.', 'redirect' => url('/dashboard')]);
            default:
                json_err('Unknown action.');
        }
    }

    private function guestResponded(int $pollId): bool
    {
        $raw = $_COOKIE['mpin_guest'] ?? '';
        if (!is_string($raw) || !preg_match('/^[a-f0-9]{64}$/', $raw)) {
            return false;
        }
        $gh = hash_hmac('sha256', $raw, APP_KEY);
        return (bool) Database::value(
            'SELECT id FROM responses WHERE poll_id = ? AND guest_hash = ?', [$pollId, $gh]);
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('pages/error', [
            'title' => 'Not found — Mpin', 'code' => 404,
            'heading' => '404 — Poll not found',
            'message' => 'This poll does not exist, was deleted, or the link is wrong.',
        ]);
        exit;
    }
}
