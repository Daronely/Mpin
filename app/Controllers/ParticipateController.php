<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Poll;

final class ParticipateController
{
    public function show(string $slug): void
    {
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            http_response_code(404);
            View::render('pages/error', [
                'title' => '404 — Mpin', 'code' => 404,
                'heading' => __('error.404_heading'),
                'message' => __('error.404_poll'),
            ]);
            exit;
        }
        if (!Poll::canParticipate($poll)) {
            redirect('/poll/' . $poll['slug']);
        }
        $user = Auth::current();
        if (!$user && !(int)$poll['allow_anonymous']) {
            redirect('/login?next=' . urlencode('/poll/' . $poll['slug'] . '/participate'));
        }

        View::render('participate', [
            'title'  => 'Participate: ' . $poll['title'] . ' — Mpin',
            'active' => '',
            'poll'       => $poll,
            'questions'  => Poll::questions((int)$poll['id']),
            'loggedIn'   => $user !== null,
        ]);
    }
}
