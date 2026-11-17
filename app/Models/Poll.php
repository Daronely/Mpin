<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Poll
{
    public const TYPES = ['yesno', 'single', 'multi', 'text', 'number', 'rating'];

    /* ================================================================ */
    /* Fetching                                                          */
    /* ================================================================ */

    /** Poll + creator + category by slug. */
    public static function bySlug(string $slug): ?array
    {
        return Database::one(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name, c.icon AS category_icon,
                    u.username, u.display_name, u.avatar_color, u.role AS creator_role
             FROM polls p
             JOIN categories c ON c.id = p.category_id
             JOIN users u      ON u.id = p.user_id
             WHERE p.slug = ?',
            [$slug]
        );
    }

    /** Poll + creator + category by id. */
    public static function byId(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name,
                    u.username, u.display_name
             FROM polls p
             JOIN categories c ON c.id = p.category_id
             JOIN users u      ON u.id = p.user_id
             WHERE p.id = ?',
            [$id]
        );
    }

    /** Questions with their options (ordered). */
    public static function questions(int $pollId): array
    {
        $qs = Database::all(
            'SELECT * FROM poll_questions WHERE poll_id = ? ORDER BY position, id', [$pollId]
        );
        foreach ($qs as &$q) {
            $q['config'] = $q['config'] ? json_decode($q['config'], true) : null;
            $q['options'] = Database::all(
                'SELECT id, option_text, position FROM poll_options WHERE question_id = ? ORDER BY position, id',
                [$q['id']]
            );
        }
        return $qs;
    }

    /** Effective status considering schedule (open/closed/scheduled/ended). */
    public static function effectiveStatus(array $poll): string
    {
        $now = time();
        if ($poll['start_at'] && strtotime($poll['start_at'] . ' UTC') > $now) {
            return 'scheduled';
        }
        if ($poll['end_at'] && strtotime($poll['end_at'] . ' UTC') < $now) {
            return 'ended';
        }
        return $poll['status'] === 'closed' ? 'closed' : 'open';
    }

    public static function canParticipate(array $poll): bool
    {
        return self::effectiveStatus($poll) === 'open';
    }

    /* ================================================================ */
    /* Listings                                                          */
    /* ================================================================ */

    /**
     * Public poll listing with filters.
     * @param array{q?:string,category?:string,country?:string,sort?:string,page?:int,per?:int} $f
     * @return array{items:array,total:int}
     */
    public static function listPublic(array $f): array
    {
        $where  = ['p.visibility = "public"', "p.status != 'draft'"];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['q']) . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($f['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $f['category'];
        }
        if (!empty($f['country'])) {
            $where[] = 'EXISTS (SELECT 1 FROM locations l WHERE l.poll_id = p.id AND l.country = ?)';
            $params[] = $f['country'];
        }
        if (!empty($f['mine']) && !empty($f['user_id'])) {
            $where[] = 'p.user_id = ?';
            $params[] = $f['user_id'];
        }

        $sortMap = [
            'popular'  => 'p.response_count DESC, p.created_at DESC',
            'trending' => 't7.c DESC, p.response_count DESC',
            'latest'   => 'p.created_at DESC',
        ];
        $sort = $sortMap[$f['sort'] ?? 'latest'] ?? $sortMap['latest'];

        $whereSql = implode(' AND ', $where);
        $total = (int) Database::value(
            "SELECT COUNT(*) FROM polls p JOIN categories c ON c.id = p.category_id
             JOIN users u ON u.id = p.user_id WHERE $whereSql", $params
        );

        $per = min(24, max(4, (int)($f['per'] ?? 12)));
        $page = max(1, (int)($f['page'] ?? 1));
        $off = ($page - 1) * $per;

        $items = Database::all(
            "SELECT p.id, p.slug, p.title, p.description, p.cover, p.status, p.visibility,
                    p.start_at, p.end_at, p.is_featured, p.is_verified, p.response_count,
                    p.created_at, p.allow_anonymous,
                    c.slug AS category_slug, c.name AS category_name, c.icon AS category_icon,
                    u.username, u.display_name, u.avatar_color,
                    (SELECT COUNT(DISTINCT l.country) FROM locations l
                      WHERE l.poll_id = p.id AND l.country IS NOT NULL AND l.country != '') AS countries_count,
                    (SELECT COUNT(*) FROM responses r2 WHERE r2.poll_id = p.id
                       AND r2.created_at > UTC_TIMESTAMP() - INTERVAL 7 DAY) AS responses_7d
             FROM polls p
             JOIN categories c ON c.id = p.category_id
             JOIN users u      ON u.id = p.user_id
             LEFT JOIN (SELECT poll_id, COUNT(*) c FROM responses
                        WHERE created_at > UTC_TIMESTAMP() - INTERVAL 7 DAY GROUP BY poll_id) t7
                    ON t7.poll_id = p.id
             WHERE $whereSql
             ORDER BY $sort
             LIMIT $per OFFSET $off",
            $params
        );
        return ['items' => $items, 'total' => $total, 'page' => $page, 'per' => $per];
    }

    /* ================================================================ */
    /* Creation / editing                                                */
    /* ================================================================ */

    /**
     * Validate + insert a full poll (with questions/options) in one transaction.
     * @param array $data  title, description, category_id, cover, visibility,
     *                     allow_anonymous, allow_multiple, start_at, end_at, slug?
     * @param array $questions list of question arrays
     * @return array{ok:bool,error?:string,errors?:array,slug?:string}
     */
    public static function create(int $userId, array $data, array $questions): array
    {
        $v = self::validate($data, $questions);
        if (!$v['ok']) {
            return $v;
        }
        [$data, $questions] = [$v['data'], $v['questions']];

        $slug = unique_slug($data['slug'] ?: $data['title']);
        try {
            Database::tx(function () use ($userId, $data, $questions, $slug, &$pollId): void {
                $pollId = Database::insert('polls', [
                    'user_id'         => $userId,
                    'category_id'     => $data['category_id'],
                    'slug'            => $slug,
                    'title'           => $data['title'],
                    'description'     => $data['description'],
                    'cover'           => $data['cover'],
                    'visibility'      => $data['visibility'],
                    'status'          => 'open',
                    'allow_anonymous' => $data['allow_anonymous'],
                    'allow_multiple'  => $data['allow_multiple'],
                    'start_at'        => $data['start_at'],
                    'end_at'          => $data['end_at'],
                ]);
                self::insertQuestions($pollId, $questions);
            });
        } catch (\Throwable $e) {
            error_log('[mpin] poll create: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not create the poll. Please try again.'];
        }
        return ['ok' => true, 'slug' => $slug];
    }

    /** Update poll meta; questions only when no responses exist yet. */
    public static function update(int $pollId, array $data, ?array $questions): array
    {
        $hasResponses = (int) Database::value(
            'SELECT COUNT(*) FROM responses WHERE poll_id = ?', [$pollId]
        ) > 0;

        if ($questions !== null && $hasResponses) {
            return ['ok' => false, 'error' => 'Questions cannot be changed because the poll already has responses. Edit title, description or settings instead.'];
        }
        $v = self::validate($data, $questions ?? []);
        if (!$v['ok']) {
            return $v;
        }
        [$data, $questions] = [$v['data'], $v['questions']];

        try {
            Database::tx(function () use ($pollId, $data, $questions, $hasResponses): void {
                Database::update('polls', [
                    'category_id'     => $data['category_id'],
                    'title'           => $data['title'],
                    'description'     => $data['description'],
                    'cover'           => $data['cover'],
                    'visibility'      => $data['visibility'],
                    'allow_anonymous' => $data['allow_anonymous'],
                    'allow_multiple'  => $data['allow_multiple'],
                    'start_at'        => $data['start_at'],
                    'end_at'          => $data['end_at'],
                ], 'id = ?', [$pollId]);

                if ($questions !== null && !$hasResponses) {
                    Database::run('DELETE FROM poll_questions WHERE poll_id = ?', [$pollId]);
                    self::insertQuestions($pollId, $questions);
                }
            });
        } catch (\Throwable $e) {
            error_log('[mpin] poll update: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not save changes. Please try again.'];
        }
        return ['ok' => true];
    }

    /** @return array{ok:bool,error?:string,errors?:array,data?:array,questions?:array} */
    private static function validate(array $data, array $questions): array
    {
        $errors = [];
        $title = trim((string)($data['title'] ?? ''));
        $desc  = trim((string)($data['description'] ?? ''));
        if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
            $errors['title'] = 'Title must be 3-150 characters.';
        }
        if (mb_strlen($desc) > 2000) {
            $errors['description'] = 'Description must be at most 2000 characters.';
        }
        $catId = (int)($data['category_id'] ?? 0);
        if (!Database::value('SELECT id FROM categories WHERE id = ?', [$catId])) {
            $errors['category_id'] = 'Please pick a category.';
        }
        if (!empty($data['slug']) && !preg_match('/^[a-z0-9][a-z0-9-]{1,60}$/', (string)$data['slug'])) {
            $errors['slug'] = 'Custom link may contain only lowercase letters, digits and dashes.';
        }
        $visibility = ($data['visibility'] ?? 'public') === 'private' ? 'private' : 'public';
        $start = parse_dt($data['start_at'] ?? null);
        $end   = parse_dt($data['end_at'] ?? null);
        if ($start && $end && $start > $end) {
            $errors['end_at'] = 'End time must be after the start time.';
        }

        /* --- questions --- */
        $clean = [];
        if (count($questions) < 1 || count($questions) > 10) {
            $errors['questions'] = 'A poll needs between 1 and 10 questions.';
        }
        foreach ($questions as $i => $q) {
            $type = in_array($q['type'] ?? '', self::TYPES, true) ? $q['type'] : '';
            $text = trim((string)($q['text'] ?? ''));
            $required = !empty($q['required']);
            if ($type === '') {
                $errors["q$i"] = "Question " . ($i + 1) . ": unknown type.";
                continue;
            }
            if (mb_strlen($text) < 3 || mb_strlen($text) > 300) {
                $errors["q$i"] = 'Question ' . ($i + 1) . ': text must be 3-300 characters.';
                continue;
            }
            $options = [];
            if ($type === 'yesno') {
                $options = ['Yes', 'No'];
            } elseif ($type === 'single' || $type === 'multi') {
                $options = array_values(array_filter(array_map(
                    static fn($o) => trim((string)$o),
                    is_array($q['options'] ?? null) ? $q['options'] : []
                ), static fn($o) => $o !== ''));
                if (count($options) < 2 || count($options) > 10) {
                    $errors["q$i"] = 'Question ' . ($i + 1) . ': needs 2-10 non-empty options.';
                    continue;
                }
                foreach ($options as $o) {
                    if (mb_strlen($o) > 150) {
                        $errors["q$i"] = 'Question ' . ($i + 1) . ': options must be at most 150 characters.';
                        continue 2;
                    }
                }
            }
            $config = null;
            if ($type === 'number') {
                $cfg = is_array($q['config'] ?? null) ? $q['config'] : [];
                $min = isset($cfg['min']) && is_numeric($cfg['min']) ? (float)$cfg['min'] : null;
                $max = isset($cfg['max']) && is_numeric($cfg['max']) ? (float)$cfg['max'] : null;
                if ($min !== null && $max !== null && $min > $max) {
                    $errors["q$i"] = 'Question ' . ($i + 1) . ': min cannot exceed max.';
                    continue;
                }
                $config = json_encode(array_filter(['min' => $min, 'max' => $max], fn($v) => $v !== null));
            }
            $clean[] = [
                'type' => $type, 'text' => $text, 'required' => $required,
                'options' => $options, 'config' => $config,
            ];
        }
        if ($errors) {
            return ['ok' => false, 'error' => 'Please fix the highlighted fields.', 'errors' => $errors];
        }
        $out = [
            'title' => $title, 'description' => $desc, 'category_id' => $catId,
            'cover' => $data['cover'] ?? null, 'visibility' => $visibility,
            'allow_anonymous' => !empty($data['allow_anonymous']) ? 1 : 0,
            'allow_multiple'  => !empty($data['allow_multiple']) ? 1 : 0,
            'start_at' => $start, 'end_at' => $end,
            'slug' => trim((string)($data['slug'] ?? '')),
        ];
        return ['ok' => true, 'data' => $out, 'questions' => $clean];
    }

    private static function insertQuestions(int $pollId, array $questions): void
    {
        foreach ($questions as $i => $q) {
            $qid = Database::insert('poll_questions', [
                'poll_id'       => $pollId,
                'position'      => $i + 1,
                'type'          => $q['type'],
                'question_text' => $q['text'],
                'is_required'   => $q['required'] ? 1 : 0,
                'config'        => $q['config'],
            ]);
            foreach ($q['options'] as $j => $opt) {
                Database::insert('poll_options', [
                    'question_id' => $qid,
                    'position'    => $j + 1,
                    'option_text' => $opt,
                ]);
            }
        }
    }

    /* ================================================================ */
    /* Analytics                                                         */
    /* ================================================================ */

    /** Global site statistics. */
    public static function globalStats(): array
    {
        return [
            'polls'         => (int) Database::value("SELECT COUNT(*) FROM polls WHERE visibility='public' AND status != 'draft'"),
            'responses'     => (int) Database::value('SELECT COUNT(*) FROM responses'),
            'countries'     => (int) Database::value("SELECT COUNT(DISTINCT country) FROM locations WHERE country IS NOT NULL AND country != ''"),
            'cities'        => (int) Database::value("SELECT COUNT(DISTINCT city) FROM locations WHERE city IS NOT NULL AND city != ''"),
            'users'         => (int) Database::value('SELECT COUNT(*) FROM users'),
            'responses_24h' => (int) Database::value('SELECT COUNT(*) FROM responses WHERE created_at > UTC_TIMESTAMP() - INTERVAL 1 DAY'),
        ];
    }

    /** Distinct countries that have responses (for the country filter). */
    public static function countries(int $limit = 250): array
    {
        return Database::all(
            "SELECT l.country, COUNT(*) c FROM locations l
             JOIN polls p ON p.id = l.poll_id AND p.visibility = 'public'
             WHERE l.country IS NOT NULL AND l.country != ''
             GROUP BY l.country ORDER BY c DESC LIMIT " . (int)$limit
        );
    }

    /**
     * Full analytics payload for a poll.
     * @param array{days?:int} $opts
     */
    public static function results(int $pollId, int $days = 30): array
    {
        $participants = (int) Database::value(
            'SELECT COUNT(*) FROM responses WHERE poll_id = ?', [$pollId]);
        $questions = self::questions($pollId);

        /* growth series */
        $rows = Database::all(
            'SELECT DATE(created_at) d, COUNT(*) c FROM responses
             WHERE poll_id = ? AND created_at > UTC_TIMESTAMP() - INTERVAL ? DAY
             GROUP BY DATE(created_at)', [$pollId, $days]
        );
        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['d']] = (int)$r['c'];
        }
        $growth = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', time() - $i * 86400);
            $growth[] = ['date' => $d, 'count' => $byDay[$d] ?? 0];
        }

        /* geo */
        $topCountries = Database::all(
            "SELECT country, COUNT(*) c FROM locations WHERE poll_id = ?
             AND country IS NOT NULL AND country != '' GROUP BY country ORDER BY c DESC LIMIT 10", [$pollId]);
        $topCities = Database::all(
            "SELECT city, country, COUNT(*) c FROM locations WHERE poll_id = ?
             AND city IS NOT NULL AND city != '' GROUP BY city, country ORDER BY c DESC LIMIT 10", [$pollId]);

        /* per-question statistics */
        $qStats = [];
        foreach ($questions as $q) {
            $stat = ['id' => (int)$q['id'], 'type' => $q['type'], 'text' => $q['question_text']];
            switch ($q['type']) {
                case 'yesno':
                case 'single':
                case 'multi':
                    $counts = [];
                    foreach (Database::all(
                        'SELECT a.option_id, COUNT(*) c
                         FROM response_answers a JOIN poll_options o ON o.id = a.option_id
                         WHERE o.question_id = ? AND a.option_id IS NOT NULL
                         GROUP BY a.option_id', [$q['id']]) as $r) {
                        $counts[(int)$r['option_id']] = (int)$r['c'];
                    }
                    $opts = [];
                    foreach ($q['options'] as $o) {
                        $c = $counts[(int)$o['id']] ?? 0;
                        $opts[] = ['id' => (int)$o['id'], 'text' => $o['option_text'], 'count' => $c];
                    }
                    $answered = (int) Database::value(
                        'SELECT COUNT(DISTINCT response_id) FROM response_answers
                         WHERE question_id = ?', [$q['id']]);
                    $stat['options'] = $opts;
                    $stat['answered'] = $answered;
                    break;

                case 'number':
                    $nrow = Database::one(
                        'SELECT COUNT(*) n, MIN(answer_number) mn, MAX(answer_number) mx, AVG(answer_number) av
                         FROM response_answers WHERE question_id = ? AND answer_number IS NOT NULL', [$q['id']]);
                    $hist = [];
                    if ((int)$nrow['n'] > 0) {
                        $mn = (float)$nrow['mn'];
                        $mx = (float)$nrow['mx'];
                        if ($mx == $mn) {
                            $mx = $mn + 1;
                        }
                        $bucketCount = min(12, max(4, (int) round(sqrt((int)$nrow['n']))));
                        $step = ($mx - $mn) / $bucketCount;
                        $vals = array_map('floatval', array_column(Database::all(
                            'SELECT answer_number v FROM response_answers
                             WHERE question_id = ? AND answer_number IS NOT NULL', [$q['id']]), 'v'));
                        $buckets = array_fill(0, $bucketCount, 0);
                        foreach ($vals as $v) {
                            $idx = min($bucketCount - 1, (int) floor(($v - $mn) / $step));
                            $buckets[$idx]++;
                        }
                        $hist = [];
                        for ($b = 0; $b < $bucketCount; $b++) {
                            $hist[] = [
                                'from' => round($mn + $b * $step, 2),
                                'to'   => round($mn + ($b + 1) * $step, 2),
                                'count' => $buckets[$b],
                            ];
                        }
                    }
                    $stat['answered'] = (int)$nrow['n'];
                    $stat['number'] = [
                        'count' => (int)$nrow['n'],
                        'min'   => $nrow['mn'] !== null ? (float)$nrow['mn'] : null,
                        'max'   => $nrow['mx'] !== null ? (float)$nrow['mx'] : null,
                        'avg'   => $nrow['av'] !== null ? round((float)$nrow['av'], 2) : null,
                        'histogram' => $hist,
                    ];
                    break;

                case 'rating':
                    $dist = array_fill(1, 5, 0);
                    foreach (Database::all(
                        'SELECT answer_rating r, COUNT(*) c FROM response_answers
                         WHERE question_id = ? AND answer_rating IS NOT NULL GROUP BY answer_rating', [$q['id']]) as $r) {
                        $dist[(int)$r['r']] = (int)$r['c'];
                    }
                    ksort($dist);
                    $stat['answered'] = array_sum($dist);
                    // array_values → JSON array (keys 0..4 = stars 1..5)
                    $stat['rating'] = array_values($dist);
                    break;

                case 'text':
                    $texts = Database::all(
                        'SELECT answer_text FROM response_answers
                         WHERE question_id = ? AND answer_text IS NOT NULL
                         ORDER BY id DESC LIMIT 20', [$q['id']]);
                    $stat['answered'] = (int) Database::value(
                        'SELECT COUNT(*) FROM response_answers WHERE question_id = ? AND answer_text IS NOT NULL', [$q['id']]);
                    $stat['texts'] = array_column($texts, 'answer_text');
                    break;
            }
            $qStats[] = $stat;
        }

        return [
            'participants' => $participants,
            'countries' => (int) Database::value(
                "SELECT COUNT(DISTINCT country) FROM locations WHERE poll_id = ? AND country IS NOT NULL AND country != ''", [$pollId]),
            'cities' => (int) Database::value(
                "SELECT COUNT(DISTINCT city) FROM locations WHERE poll_id = ? AND city IS NOT NULL AND city != ''", [$pollId]),
            'growth' => $growth,
            'top_countries' => $topCountries,
            'top_cities' => $topCities,
            'questions' => $qStats,
        ];
    }

    /* ================================================================ */
    /* Map data (privacy-preserving grid aggregation)                    */
    /* ================================================================ */

    /**
     * Aggregated map points for a poll.
     * @param array<int,int[]> $filters question_id => list of option_ids
     */
    public static function mapData(int $pollId, array $filters = []): array
    {
        // All filter values are validated ints, so they are safely inlined;
        // only the poll id is bound as a parameter.
        $filterSql = '';
        foreach ($filters as $qid => $optionIds) {
            $qid = (int)$qid;
            $optionIds = array_map('intval', array_filter((array)$optionIds, 'is_numeric'));
            if ($qid < 1 || !$optionIds) {
                continue;
            }
            $valid = array_map('intval', array_column(Database::all(
                'SELECT o.id FROM poll_options o
                 JOIN poll_questions q ON q.id = o.question_id
                 WHERE q.poll_id = ? AND o.question_id = ?', [$pollId, $qid]), 'id'));
            $optionIds = array_values(array_intersect($optionIds, $valid));
            if (!$optionIds) {
                continue;
            }
            $filterSql .= ' AND l.response_id IN (SELECT response_id FROM response_answers
                            WHERE question_id = ' . $qid . '
                            AND option_id IN (' . implode(',', $optionIds) . '))';
        }

        $rows = Database::all(
            "SELECT ROUND(AVG(l.lat),5) lat, ROUND(AVG(l.lng),5) lng, COUNT(*) c,
                    MAX(l.country) country, MAX(l.region) region, MAX(l.city) city
             FROM locations l
             WHERE l.poll_id = ? $filterSql
             GROUP BY FLOOR(l.lat / ?), FLOOR(l.lng / ?)
             HAVING c >= " . (int)PRIVACY_MIN_CELL . "
             ORDER BY c DESC
             LIMIT 2000",
            [$pollId, PRIVACY_GRID, PRIVACY_GRID]
        );

        $total = (int) Database::value(
            "SELECT COUNT(*) FROM locations l WHERE l.poll_id = ? $filterSql", [$pollId]
        );

        $points = [];
        $shown = 0;
        foreach ($rows as $r) {
            $points[] = [
                'lat' => (float)$r['lat'], 'lng' => (float)$r['lng'], 'count' => (int)$r['c'],
                'country' => $r['country'], 'city' => $r['city'],
            ];
            $shown += (int)$r['c'];
        }
        return [
            'points' => $points,
            'total' => $total,
            'shown' => $shown,
            'suppressed' => max(0, $total - $shown),
            'updated_at' => iso(utcnow()),
        ];
    }

    /** Aggregated points across ALL public polls (global pulse map). */
    public static function globalMap(): array
    {
        $rows = Database::all(
            'SELECT ROUND(AVG(l.lat),5) lat, ROUND(AVG(l.lng),5) lng, COUNT(*) c,
                    MAX(l.country) country, MAX(l.city) city,
                    COUNT(DISTINCT l.poll_id) polls
             FROM locations l
             JOIN polls p ON p.id = l.poll_id AND p.visibility = "public"
             GROUP BY FLOOR(l.lat / ?), FLOOR(l.lng / ?)
             HAVING c >= ' . (int)PRIVACY_MIN_CELL . '
             ORDER BY c DESC
             LIMIT 3000',
            [PRIVACY_GRID, PRIVACY_GRID]
        );
        $points = [];
        foreach ($rows as $r) {
            $points[] = [
                'lat' => (float)$r['lat'], 'lng' => (float)$r['lng'],
                'count' => (int)$r['c'], 'country' => $r['country'], 'city' => $r['city'],
                'polls' => (int)$r['polls'],
            ];
        }
        return ['points' => $points, 'updated_at' => iso(utcnow())];
    }

    /* ================================================================ */
    /* Owner dashboard                                                   */
    /* ================================================================ */

    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM polls p JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC', [$userId]
        );
    }

    /** CSV/JSON export of a poll's responses. */
    public static function export(int $pollId, string $format): array
    {
        $questions = self::questions($pollId);
        $optById = [];
        foreach ($questions as $q) {
            foreach ($q['options'] as $o) {
                $optById[(int)$o['id']] = $o['option_text'];
            }
        }
        $rows = Database::all(
            'SELECT r.id, r.created_at, r.country AS resp_country,
                    l.lat, l.lng, l.country, l.region, l.city,
                    u.username
             FROM responses r
             LEFT JOIN locations l ON l.response_id = r.id
             LEFT JOIN users u     ON u.id  = r.user_id
             WHERE r.poll_id = ?
             ORDER BY r.id', [$pollId]
        );
        $answers = [];
        foreach (Database::all(
            'SELECT response_id, question_id, option_id, answer_text, answer_number, answer_rating
             FROM response_answers WHERE question_id IN
               (SELECT id FROM poll_questions WHERE poll_id = ?)', [$pollId]) as $a) {
            $answers[(int)$a['response_id']][] = $a;
        }

        $header = ['response_id', 'created_at_utc', 'username', 'lat', 'lng', 'country', 'region', 'city'];
        foreach ($questions as $q) {
            $header[] = 'q' . $q['position'] . ': ' . $q['question_text'];
        }

        $lines = [];
        foreach ($rows as $r) {
            $line = [
                $r['id'], $r['created_at'], $r['username'] ?? '(anonymous)',
                $r['lat'], $r['lng'], $r['country'], $r['region'], $r['city'],
            ];
            $byQ = [];
            foreach ($answers[(int)$r['id']] ?? [] as $a) {
                $v = $a['option_id'] !== null
                    ? ($optById[(int)$a['option_id']] ?? $a['option_id'])
                    : ($a['answer_text'] ?? $a['answer_number'] ?? $a['answer_rating'] ?? '');
                $byQ[(int)$a['question_id']][] = (string)$v;
            }
            $qmap = [];
            foreach ($questions as $q) {
                $qmap[(int)$q['id']] = $q;
            }
            foreach ($qmap as $qid => $q) {
                $line[] = isset($byQ[$qid]) ? implode(' | ', $byQ[$qid]) : '';
            }
            $lines[] = $line;
        }
        return ['header' => $header, 'rows' => $lines];
    }
}
