<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ResponseModel
{
    /**
     * Submit a response to a poll (answers + optional location).
     *
     * @param array|null   $user     logged-in user row or null (guest)
     * @param array        $poll     poll row
     * @param array        $answers  {question_id: {option_id | option_ids[] | text | number | rating}}
     * @param array|null   $location {lat,lng,country?,region?,city?} chosen by the participant
     * @return array{ok:bool,error?:string,errors?:array,suppressed?:bool}
     */
    public static function submit(?array $user, array $poll, array $answers, ?array $location): array
    {
        $pollId = (int)$poll['id'];
        $questions = Poll::questions($pollId);

        if (!Poll::canParticipate($poll)) {
            $st = Poll::effectiveStatus($poll);
            return ['ok' => false, 'error' => $st === 'scheduled'
                ? 'This poll has not started yet.'
                : 'This poll is closed and no longer accepts responses.'];
        }
        if ($user === null && !(int)$poll['allow_anonymous']) {
            return ['ok' => false, 'error' => 'You need an account to participate in this poll.'];
        }

        /* ---- de-duplication ---- */
        if (!(int)$poll['allow_multiple']) {
            if ($user) {
                $dup = Database::value(
                    'SELECT id FROM responses WHERE poll_id = ? AND user_id = ?', [$pollId, $user['id']]);
            } else {
                $gh = self::guestHash();
                $dup = $gh
                    ? Database::value(
                        'SELECT id FROM responses WHERE poll_id = ? AND guest_hash = ?', [$pollId, $gh])
                    : Database::value(
                        'SELECT id FROM responses WHERE poll_id = ? AND ip_hash = ?', [$pollId, ip_hash()]);
            }
            if ($dup) {
                return ['ok' => false, 'error' => 'You have already responded to this poll.'];
            }
        }

        /* ---- validate answers ---- */
        $errors = [];
        $rows = [];   // [question_id, option_id?, text?, number?, rating?]
        foreach ($questions as $q) {
            $qid = (int)$q['id'];
            $a = $answers[$qid] ?? $answers[(string)$qid] ?? null;
            $type = $q['type'];

            switch ($type) {
                case 'yesno':
                case 'single':
                    $opt = $a['option_id'] ?? null;
                    $valid = array_column($q['options'], 'id');
                    if ($opt === null || $opt === '') {
                        if ($q['is_required']) $errors["q$qid"] = 'This question is required.';
                        break;
                    }
                    if (!in_array((int)$opt, array_map('intval', $valid), true)) {
                        $errors["q$qid"] = 'Invalid option selected.';
                        break;
                    }
                    $rows[] = ['question_id' => $qid, 'option_id' => (int)$opt];
                    break;

                case 'multi':
                    $ids = $a['option_ids'] ?? ($a['option_id'] ?? null);
                    $ids = is_array($ids) ? $ids : ($ids !== null ? [$ids] : []);
                    $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'is_numeric'))));
                    $valid = array_map('intval', array_column($q['options'], 'id'));
                    if (!$ids) {
                        if ($q['is_required']) $errors["q$qid"] = 'Select at least one option.';
                        break;
                    }
                    if (array_diff($ids, $valid)) {
                        $errors["q$qid"] = 'Invalid option selected.';
                        break;
                    }
                    foreach ($ids as $id) {
                        $rows[] = ['question_id' => $qid, 'option_id' => $id];
                    }
                    break;

                case 'text':
                    $text = trim((string)($a['text'] ?? ''));
                    if ($text === '') {
                        if ($q['is_required']) $errors["q$qid"] = 'This question is required.';
                        break;
                    }
                    if (mb_strlen($text) > 500) {
                        $errors["q$qid"] = 'Answer is too long (max 500 characters).';
                        break;
                    }
                    $rows[] = ['question_id' => $qid, 'answer_text' => $text];
                    break;

                case 'number':
                    $num = $a['number'] ?? null;
                    if ($num === null || $num === '') {
                        if ($q['is_required']) $errors["q$qid"] = 'This question is required.';
                        break;
                    }
                    if (!is_numeric($num)) {
                        $errors["q$qid"] = 'Please enter a valid number.';
                        break;
                    }
                    $num = (float)$num;
                    $cfg = $q['config'] ?? [];
                    if (isset($cfg['min']) && $num < (float)$cfg['min']) {
                        $errors["q$qid"] = 'Number must be at least ' . $cfg['min'] . '.';
                        break;
                    }
                    if (isset($cfg['max']) && $num > (float)$cfg['max']) {
                        $errors["q$qid"] = 'Number must be at most ' . $cfg['max'] . '.';
                        break;
                    }
                    $rows[] = ['question_id' => $qid, 'answer_number' => $num];
                    break;

                case 'rating':
                    $r = $a['rating'] ?? null;
                    if ($r === null || $r === '') {
                        if ($q['is_required']) $errors["q$qid"] = 'Please pick a rating.';
                        break;
                    }
                    if (!ctype_digit((string)$r) || (int)$r < 1 || (int)$r > 5) {
                        $errors["q$qid"] = 'Rating must be between 1 and 5.';
                        break;
                    }
                    $rows[] = ['question_id' => $qid, 'answer_rating' => (int)$r];
                    break;
            }
        }
        if ($errors) {
            return ['ok' => false, 'error' => 'Please complete the highlighted questions.',
                    'errors' => $errors];
        }
        if (!$rows) {
            return ['ok' => false, 'error' => 'Nothing to submit.'];
        }

        /* ---- validate location ---- */
        $loc = null;
        if ($location && isset($location['lat'], $location['lng'])
            && is_numeric($location['lat']) && is_numeric($location['lng'])) {
            $lat = (float)$location['lat'];
            $lng = (float)$location['lng'];
            if ($lat >= -85 && $lat <= 85 && $lng >= -180 && $lng <= 180) {
                // Privacy: snap to grid + deterministic jitter; exact point is discarded.
                $seed = crc32($pollId . '|' . microtime(false) . '|' . str_random(8));
                $pt = snap_location($lat, $lng, $seed);
                $loc = [
                    'lat' => $pt['lat'],
                    'lng' => $pt['lng'],
                    'country' => self::cleanGeo($location['country'] ?? null, 80),
                    'region'  => self::cleanGeo($location['region'] ?? null, 120),
                    'city'    => self::cleanGeo($location['city'] ?? null, 120),
                ];
            } else {
                return ['ok' => false, 'error' => 'The selected location is out of range.'];
            }
        }

        /* ---- persist ---- */
        $guestHash = $user ? null : self::guestHash(true);
        try {
            Database::tx(static function () use ($user, $pollId, $guestHash, $rows, $loc, &$responseId): void {
                $responseId = Database::insert('responses', [
                    'poll_id'    => $pollId,
                    'user_id'    => $user['id'] ?? null,
                    'guest_hash' => $guestHash,
                    'ip_hash'    => ip_hash(),
                    'country'    => $loc['country'] ?? null,
                    'created_at' => utcnow(),
                ]);
                foreach ($rows as $r) {
                    $r['response_id'] = $responseId;
                    Database::insert('response_answers', $r);
                }
                if ($loc) {
                    Database::insert('locations', [
                        'response_id' => $responseId,
                        'poll_id'     => $pollId,
                        'lat'         => $loc['lat'],
                        'lng'         => $loc['lng'],
                        'country'     => $loc['country'],
                        'region'      => $loc['region'],
                        'city'        => $loc['city'],
                        'created_at'  => utcnow(),
                    ]);
                }
                Database::run('UPDATE polls SET response_count = response_count + 1 WHERE id = ?', [$pollId]);

                // Notify the poll owner (skip own responses / demo floods).
                $ownerId = (int) Database::value('SELECT user_id FROM polls WHERE id = ?', [$pollId]);
                if ($ownerId && (!$user || (int)$user['id'] !== $ownerId)) {
                    Database::insert('notifications', [
                        'user_id' => $ownerId,
                        'type'    => 'response',
                        'title'   => 'New response',
                        'body'    => 'Someone responded to "' . Database::value('SELECT title FROM polls WHERE id = ?', [$pollId]) . '".',
                        'link'    => '/dashboard',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            error_log('[mpin] response submit: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not save your response. Please try again.'];
        }

        return ['ok' => true];
    }

    /** Get or create the guest fingerprint cookie. */
    private static function guestHash(bool $setCookie = false): ?string
    {
        $raw = $_COOKIE['mpin_guest'] ?? '';
        if (!is_string($raw) || !preg_match('/^[a-f0-9]{64}$/', $raw)) {
            if (!$setCookie) {
                return null;
            }
            $raw = bin2hex(random_bytes(32));
            $secure = str_starts_with(APP_URL, 'https://');
            setcookie('mpin_guest', $raw, [
                'expires' => time() + 365 * 86400,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return hash_hmac('sha256', $raw, APP_KEY);
    }

    private static function cleanGeo(?string $v, int $max): ?string
    {
        $v = trim((string)$v);
        if ($v === '') {
            return null;
        }
        return mb_substr($v, 0, $max);
    }

    /** Delete a user's own responses (privacy: right to erasure). */
    public static function deleteUserResponses(int $userId): int
    {
        $ids = array_column(Database::all(
            'SELECT id FROM responses WHERE user_id = ?', [$userId]), 'id');
        if (!$ids) {
            return 0;
        }
        $in = implode(',', array_map('intval', $ids));
        $n = 0;
        Database::tx(static function () use ($in, $ids, &$n): void {
            $n = Database::affected("DELETE FROM responses WHERE id IN ($in)");
            Database::run(
                "UPDATE polls p
                 JOIN (SELECT poll_id, COUNT(*) c FROM responses GROUP BY poll_id) t
                   ON t.poll_id = p.id
                 SET p.response_count = t.c");
        });
        return $n;
    }
}
