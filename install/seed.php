<?php
/**
 * MPIN — demo content seeder (used by the installer; can also be run from CLI).
 * Creates a handful of realistic polls plus responses spread across world cities
 * so a fresh install doesn't look empty. All locations go through the same
 * privacy-grid snapping as real responses.
 */
declare(strict_types=1);

/**
 * @param PDO    $pdo     connected database handle
 * @param int    $adminId owner user id for demo polls
 */
function mpin_seed_demo(PDO $pdo, int $adminId): void
{
    $now = time();
    $rand = static function (int $a, int $b) { return random_int($a, $b); };

    /* ---------------- world cities (approximate coords, rounded) ---------------- */
    $cities = [
        // [city, country, lat, lng, weight]
        ['Tehran', 'Iran', 35.7, 51.4, 9], ['Isfahan', 'Iran', 32.7, 51.7, 2],
        ['Istanbul', 'Turkey', 41.0, 29.0, 6], ['Ankara', 'Turkey', 39.9, 32.9, 2],
        ['London', 'United Kingdom', 51.5, -0.1, 7], ['Manchester', 'United Kingdom', 53.5, -2.2, 2],
        ['Berlin', 'Germany', 52.5, 13.4, 5], ['Munich', 'Germany', 48.1, 11.6, 2],
        ['Paris', 'France', 48.9, 2.4, 5], ['Lyon', 'France', 45.8, 4.8, 1],
        ['Amsterdam', 'Netherlands', 52.4, 4.9, 2], ['Stockholm', 'Sweden', 59.3, 18.1, 2],
        ['Warsaw', 'Poland', 52.2, 21.0, 2], ['Vienna', 'Austria', 48.2, 16.4, 1],
        ['Zurich', 'Switzerland', 47.4, 8.5, 1], ['Lisbon', 'Portugal', 38.7, -9.1, 1],
        ['Madrid', 'Spain', 40.4, -3.7, 3], ['Barcelona', 'Spain', 41.4, 2.2, 2],
        ['Rome', 'Italy', 41.9, 12.5, 2], ['Athens', 'Greece', 38.0, 23.7, 1],
        ['Kyiv', 'Ukraine', 50.5, 30.5, 1], ['Moscow', 'Russia', 55.8, 37.6, 3],
        ['Cairo', 'Egypt', 30.0, 31.2, 3], ['Lagos', 'Nigeria', 6.5, 3.4, 2],
        ['Nairobi', 'Kenya', -1.3, 36.8, 1], ['Johannesburg', 'South Africa', -26.2, 28.0, 2],
        ['Dubai', 'United Arab Emirates', 25.2, 55.3, 3], ['Riyadh', 'Saudi Arabia', 24.7, 46.7, 2],
        ['Karachi', 'Pakistan', 24.9, 67.0, 2], ['Lahore', 'Pakistan', 31.5, 74.3, 1],
        ['Delhi', 'India', 28.6, 77.2, 4], ['Mumbai', 'India', 19.1, 72.9, 4], ['Bangalore', 'India', 13.0, 77.6, 2],
        ['Dhaka', 'Bangladesh', 23.8, 90.4, 2],
        ['Jakarta', 'Indonesia', -6.2, 106.8, 3], ['Manila', 'Philippines', 14.6, 121.0, 2],
        ['Singapore', 'Singapore', 1.35, 103.8, 2], ['Bangkok', 'Thailand', 13.8, 100.5, 2],
        ['Ho Chi Minh City', 'Vietnam', 10.8, 106.7, 1],
        ['Hong Kong', 'Hong Kong', 22.3, 114.2, 2], ['Shanghai', 'China', 31.2, 121.5, 3],
        ['Beijing', 'China', 39.9, 116.4, 2], ['Seoul', 'South Korea', 37.6, 127.0, 3],
        ['Tokyo', 'Japan', 35.7, 139.7, 4], ['Osaka', 'Japan', 34.7, 135.5, 1],
        ['Sydney', 'Australia', -33.9, 151.2, 3], ['Melbourne', 'Australia', -37.8, 145.0, 2], ['Auckland', 'New Zealand', -36.8, 174.8, 1],
        ['New York', 'United States', 40.7, -74.0, 6], ['Los Angeles', 'United States', 34.1, -118.2, 5],
        ['Chicago', 'United States', 41.9, -87.6, 3], ['Seattle', 'United States', 47.6, -122.3, 2],
        ['Austin', 'United States', 30.3, -97.7, 2], ['Miami', 'United States', 25.8, -80.2, 1],
        ['Toronto', 'Canada', 43.7, -79.4, 3], ['Vancouver', 'Canada', 49.3, -123.1, 2], ['Montreal', 'Canada', 45.5, -73.6, 2],
        ['Mexico City', 'Mexico', 19.4, -99.1, 3], ['Bogota', 'Colombia', 4.7, -74.1, 1],
        ['Lima', 'Peru', -12.0, -77.0, 1], ['Santiago', 'Chile', -33.4, -70.7, 1],
        ['Sao Paulo', 'Brazil', -23.6, -46.6, 4], ['Rio de Janeiro', 'Brazil', -22.9, -43.2, 2],
        ['Buenos Aires', 'Argentina', -34.6, -58.4, 2],
    ];
    $cityPool = [];
    foreach ($cities as $c) {
        $cityPool = array_merge($cityPool, array_fill(0, $c[4], $c));
    }

    /* ---------------- demo polls ---------------- */
    $cat = static function (PDO $pdo, string $slug): int {
        return (int) $pdo->query("SELECT id FROM categories WHERE slug = " . $pdo->quote($slug))->fetchColumn();
    };

    $polls = [
        [
            'slug' => 'cs2-players', 'title' => 'Where do people play Counter-Strike 2?',
            'desc' => 'CS2 took over from CS:GO in 2023. Where is the player base today — and what do they play on? Add your pin and find your region on the map.',
            'category' => 'gaming', 'responses' => 320, 'featured' => 1,
            'questions' => [
                ['yesno', 'Do you currently play Counter-Strike 2?', 1, []],
                ['single', 'Which platform do you play on most?', 1, ['PC (Steam)', 'Console', 'Cloud gaming', 'Mostly watch, not play']],
                ['number', 'How many hours per week do you play?', 1, []],
                ['rating', 'How would you rate CS2 overall?', 1, []],
            ],
            'weights' => [ // per question option / value weights
                [60, 40],                       // yesno
                [78, 4, 6, 12],                 // platform
                null,                           // number: generated
                null,                           // rating: generated
            ],
        ],
        [
            'slug' => 'daily-operating-system', 'title' => 'Which operating system is your daily driver?',
            'desc' => 'Windows, macOS, Linux, or something else entirely? Pin your location and see how OS loyalty varies by country.',
            'category' => 'operating-systems', 'responses' => 280, 'featured' => 1,
            'questions' => [
                ['single', 'What OS runs on your main machine?', 1, ['Windows 11', 'Windows 10', 'macOS', 'Linux', 'BSD / other']],
                ['yesno', 'Have you switched OS in the last two years?', 1, []],
                ['multi', 'Which devices do you own?', 0, ['Desktop PC', 'Laptop', 'Tablet', 'Raspberry Pi / SBC']],
            ],
            'weights' => [
                [34, 22, 18, 22, 4],
                [25, 75],
                null,
            ],
        ],
        [
            'slug' => 'main-programming-language', 'title' => 'What programming language do you use the most?',
            'desc' => 'JavaScript, Python, PHP, Rust… the eternal flame war, settled on a world map. Vote for your primary language.',
            'category' => 'programming', 'responses' => 240, 'featured' => 0,
            'questions' => [
                ['single', 'Your most-used language right now:', 1, ['JavaScript / TypeScript', 'Python', 'PHP', 'Java', 'C#', 'Go', 'Rust', 'C / C++', 'Other']],
                ['number', 'How many years have you been coding?', 0, []],
                ['yesno', 'Do you code for a living?', 1, []],
            ],
            'weights' => [
                [26, 24, 12, 9, 8, 7, 6, 5, 3],
                null,
                [62, 38],
            ],
        ],
        [
            'slug' => 'music-while-working', 'title' => 'What music do you listen to while working?',
            'desc' => 'Lo-fi, metal, silence? Tell us what fuels your focus — and where the metalheads cluster.',
            'category' => 'music', 'responses' => 180, 'featured' => 0,
            'questions' => [
                ['single', 'Your go-to work soundtrack:', 1, ['Lo-fi / chill', 'Rock / metal', 'Electronic', 'Classical', 'Pop', 'Silence is golden', 'Podcasts / talk']],
                ['rating', 'How much does music help you focus?', 1, []],
            ],
            'weights' => [
                [24, 15, 20, 11, 14, 10, 6],
                null,
            ],
        ],
        [
            'slug' => 'favorite-keyboard-layout', 'title' => 'Which keyboard layout do you type on?',
            'desc' => 'QWERTY rules the world… or does it? A quick poll about layouts — Colemak and Dvorak folks, this is your moment.',
            'category' => 'hardware', 'responses' => 140, 'featured' => 0,
            'questions' => [
                ['single', 'Your daily keyboard layout:', 1, ['QWERTY', 'QWERTZ', 'AZERTY', 'Dvorak', 'Colemak', 'Other']],
                ['yesno', 'Have you ever tried an alternative layout?', 1, []],
            ],
            'weights' => [
                [64, 14, 10, 4, 5, 3],
                [22, 78],
            ],
        ],
    ];

    $grid = 0.05;
    $snap = static function (float $lat, float $lng) use ($grid) {
        $gLat = floor($lat / $grid) * $grid + $grid / 2;
        $gLng = floor($lng / $grid) * $grid + $grid / 2;
        $h = (int) hexdec(substr(hash('sha256', $gLat . '|' . $gLng), 0, 12));
        $off = $grid * 0.72;
        return [
            round($gLat + (($h % 1000) / 1000 - 0.5) * $off, 6),
            round($gLng + ((intdiv($h, 1000) % 1000) / 1000 - 0.5) * $off, 6),
        ];
    };

    foreach ($polls as $P) {
        /* poll row */
        $st = $pdo->prepare('INSERT INTO polls
            (user_id, category_id, slug, title, description, visibility, status, allow_anonymous, allow_multiple,
             is_featured, is_verified, is_demo, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $created = gmdate('Y-m-d H:i:s', $now - random_int(50, 70) * 86400);
        $st->execute([$adminId, $cat($pdo, $P['category']), $P['slug'], $P['title'], $P['desc'],
                      'public', 'open', 1, 0, $P['featured'], 1, 1, $created, $created]);
        $pollId = (int) $pdo->lastInsertId();

        /* questions + options */
        $qIds = [];
        $optIds = [];
        foreach ($P['questions'] as $qi => $q) {
            $st = $pdo->prepare('INSERT INTO poll_questions (poll_id, position, type, question_text, is_required, config) VALUES (?,?,?,?,?,?)');
            $st->execute([$pollId, $qi + 1, $q[0], $q[1], $q[2], null]);
            $qid = (int) $pdo->lastInsertId();
            $opts = [];
            if ($q[0] === 'yesno') {
                $opts = ['Yes', 'No'];
            } elseif (in_array($q[0], ['single', 'multi'], true)) {
                $opts = $q[3];
            }
            foreach ($opts as $oi => $ot) {
                $st = $pdo->prepare('INSERT INTO poll_options (question_id, position, option_text) VALUES (?,?,?)');
                $st->execute([$qid, $oi + 1, $ot]);
                $optIds[$qid][] = (int) $pdo->lastInsertId();
            }
            $qIds[] = ['id' => $qid, 'type' => $q[0], 'opts' => $optIds[$qid] ?? []];
        }

        /* responses */
        $n = $P['responses'];
        $insResp = $pdo->prepare('INSERT INTO responses (poll_id, user_id, guest_hash, ip_hash, country, created_at) VALUES (?,?,?,?,?,?)');
        $insAns  = $pdo->prepare('INSERT INTO response_answers (response_id, question_id, option_id, answer_text, answer_number, answer_rating) VALUES (?,?,?,?,?,?)');
        $insLoc  = $pdo->prepare('INSERT INTO locations (response_id, poll_id, lat, lng, country, region, city, created_at) VALUES (?,?,?,?,?,?,?,?)');

        $made = 0;
        while ($made < $n) {
            $c = $cityPool[array_rand($cityPool)];
            /* time distribution: mostly recent */
            $daysAgo = (int) round(pow(random_int(0, 100) / 100, 1.8) * 55);
            $when = gmdate('Y-m-d H:i:s', $now - $daysAgo * 86400 - random_int(0, 86000));
            $gh = hash('sha256', 'seed-' . $pollId . '-' . $made);

            $insResp->execute([$pollId, null, $gh, hash('sha256', 'seed-ip-' . $made), $c[1], $when]);
            $rid = (int) $pdo->lastInsertId();

            foreach ($qIds as $qi => $q) {
                $w = $P['weights'][$qi] ?? null;
                if ($q['type'] === 'yesno' || $q['type'] === 'single') {
                    $pick = $w ? weighted_pick($w) : 0;
                    $insAns->execute([$rid, $q['id'], $q['opts'][$pick] ?? $q['opts'][0], null, null, null]);
                } elseif ($q['type'] === 'multi') {
                    foreach ($q['opts'] as $oi => $oid) {
                        if (random_int(1, 100) <= [70, 55, 35, 12][$oi % 4]) {
                            $insAns->execute([$rid, $q['id'], $oid, null, null, null]);
                        }
                    }
                } elseif ($q['type'] === 'number') {
                    $v = match ($P['slug']) {
                        'cs2-players' => random_int(0, 40),
                        'main-programming-language' => random_int(0, 30),
                        default => random_int(0, 100),
                    };
                    $insAns->execute([$rid, $q['id'], null, null, $v, null]);
                } elseif ($q['type'] === 'rating') {
                    $insAns->execute([$rid, $q['id'], null, null, null, weighted_pick([4, 12, 24, 34, 26]) + 1]);
                } elseif ($q['type'] === 'text') {
                    /* not used in demo polls */
                }
            }

            [$lat, $lng] = $snap($c[2], $c[3]);
            $insLoc->execute([$rid, $pollId, $lat, $lng, $c[1], null, $c[0], $when]);
            $made++;
        }
        $pdo->prepare('UPDATE polls SET response_count = ? WHERE id = ?')->execute([$n, $pollId]);
    }
}

function weighted_pick(array $weights): int
{
    $total = array_sum($weights);
    $r = random_int(1, max(1, $total));
    $acc = 0;
    foreach ($weights as $i => $w) {
        $acc += $w;
        if ($r <= $acc) {
            return $i;
        }
    }
    return 0;
}
