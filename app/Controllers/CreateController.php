<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\View;
use App\Models\Category;
use App\Models\Poll;

final class CreateController
{
    public function form(): void
    {
        $user = Auth::requireLogin();
        View::render('create', [
            'title'      => 'Create a poll — Mpin',
            'active'     => 'create',
            'categories' => Category::all(),
            'poll'       => null,
            'questions'  => null,
        ]);
    }

    /** Create via AJAX (multipart form). */
    public function store(): void
    {
        $user = Auth::current();
        if (!$user) {
            json_err('Please log in first.', [], 401);
        }
        if (!RateLimiter::check('create_poll', 10, 3600)) {
            json_err('You are creating polls too fast. Please try again later.', [], 429);
        }

        $data = $this->collectRequest();
        $result = Poll::create((int)$user['id'], $data['data'], $data['questions']);
        if (!$result['ok']) {
            json_err($result['error'] ?? 'Validation failed.', $result['errors'] ?? []);
        }
        json_ok(['slug' => $result['slug'], 'redirect' => url('/poll/' . $result['slug'])]);
    }

    public function editForm(string $slug): void
    {
        $user = Auth::requireLogin();
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            json_err('Not found.', [], 404);
        }
        if ((int)$poll['user_id'] !== (int)$user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            View::render('pages/error', [
                'title' => 'Forbidden — Mpin', 'code' => 403,
                'heading' => '403 — Not your poll',
                'message' => 'Only the creator of this poll can edit it.',
            ]);
            exit;
        }
        View::render('create', [
            'title'      => 'Edit poll — Mpin',
            'active'     => 'create',
            'categories' => Category::all(),
            'poll'       => $poll,
            'questions'  => Poll::questions((int)$poll['id']),
        ]);
    }

    public function update(string $slug): void
    {
        $user = Auth::current();
        $poll = Poll::bySlug($slug);
        if (!$poll) {
            json_err('Not found.', [], 404);
        }
        if ((int)$poll['user_id'] !== (int)$user['id'] && ($user['role'] ?? '') !== 'admin') {
            json_err('You do not own this poll.', [], 403);
        }
        $in = $this->collectRequest((string)$poll['cover']);
        $hasResponses = (int) Database::value(
            'SELECT COUNT(*) FROM responses WHERE poll_id = ?', [$poll['id']]) > 0;

        $result = Poll::update((int)$poll['id'], $in['data'], $hasResponses ? null : $in['questions']);
        if (!$result['ok']) {
            json_err($result['error'] ?? 'Validation failed.', $result['errors'] ?? []);
        }
        json_ok(['slug' => $poll['slug'], 'redirect' => url('/poll/' . $poll['slug'])]);
    }

    /* ---------------------------------------------------------------- */

    /** Merge form fields + JSON question payload + cover upload. */
    private function collectRequest(?string $existingCover = null): array
    {
        $body = json_body();
        $post = $_POST;
        $data = array_merge($post, $body ?: []);

        $data['title'] = trim((string)($data['title'] ?? ''));
        $data['description'] = trim((string)($data['description'] ?? ''));
        $data['slug'] = trim((string)($data['slug'] ?? ''));
        $data['category_id'] = (int)($data['category_id'] ?? 0);
        $data['start_at'] = $data['start_at'] ?? null;
        $data['end_at'] = $data['end_at'] ?? null;

        // cover: keep existing | remove | upload new
        $cover = $existingCover;
        if (($data['cover_action'] ?? '') === 'remove') {
            $this->deleteCover($existingCover);
            $cover = null;
        }
        $newCover = $this->handleUpload();
        if ($newCover !== null) {
            $this->deleteCover($existingCover);
            $cover = $newCover;
        }
        $data['cover'] = $cover;

        // questions arrive as a JSON string (built by the JS wizard)
        $questions = [];
        if (!empty($data['questions']) && is_string($data['questions'])) {
            $decoded = json_decode($data['questions'], true);
            if (is_array($decoded)) {
                $questions = $decoded;
            }
        } elseif (!empty($data['questions']) && is_array($data['questions'])) {
            $questions = $data['questions'];
        }

        // honeypot — bots fill everything
        if (!empty($data['website'])) {
            $questions = [];  // silently fails validation later
        }
        return ['data' => $data, 'questions' => $questions];
    }

    /** Validate + store an uploaded cover image (re-encoded, EXIF stripped). */
    private function handleUpload(): ?string
    {
        $f = $_FILES['cover'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($f['size'] > 4 * 1024 * 1024) {
            return null;
        }
        $info = @getimagesize($f['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($f['tmp_name']),
            default        => @imagecreatefromwebp($f['tmp_name']),
        };
        if (!$src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $maxW = 1600;
        if ($w > $maxW) {
            $nw = $maxW;
            $nh = (int) round($h * $maxW / $w);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }
        @mkdir(MPIN_ROOT . '/uploads', 0775, true);
        $name = 'cover-' . str_random(12) . '.jpg';
        $ok = imagejpeg($src, MPIN_ROOT . '/uploads/' . $name, 84);
        imagedestroy($src);
        return $ok ? $name : null;
    }

    private function deleteCover(?string $name): void
    {
        if ($name && preg_match('/^cover-[a-f0-9]{12}\.jpg$/', $name)) {
            @unlink(MPIN_ROOT . '/uploads/' . $name);
        }
    }
}
