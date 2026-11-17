<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Poll;
use App\Models\User;

final class ProfileController
{
    public function show(string $username): void
    {
        $profile = User::profile($username);
        if (!$profile) {
            http_response_code(404);
            View::render('pages/error', [
                'title' => 'Not found — Mpin', 'code' => 404,
                'heading' => '404 — User not found',
                'message' => 'No account exists with this username.',
            ]);
            exit;
        }
        $polls = Poll::listPublic(['mine' => true, 'user_id' => (int)$profile['id'], 'per' => 12])['items'];

        View::render('profile', [
            'title'   => '@' . $profile['username'] . ' — Mpin',
            'active'  => '',
            'profile' => $profile,
            'polls'   => $polls,
        ]);
    }
}
