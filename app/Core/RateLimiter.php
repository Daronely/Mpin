<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Fixed-window rate limiter backed by the rate_limits table.
 * Identity defaults to a salted hash of the client IP.
 */
final class RateLimiter
{
    /**
     * @param string $action    logical action name, e.g. "login"
     * @param int    $max       max hits per window
     * @param int    $windowSec window size in seconds
     * @param string|null $identity explicit identity (e.g. username)
     * @return bool true = allowed (and counted), false = over the limit
     */
    public static function check(string $action, int $max, int $windowSec, ?string $identity = null): bool
    {
        $id   = $identity ?? ip_hash();
        $win  = (int) (time() - (time() % $windowSec));
        $key  = hash('sha256', $action . '|' . $id);   // fixed length for column

        $st = Database::run(
            'INSERT INTO rate_limits (action, ip_hash, window_start, hits)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE hits = hits + 1',
            [$action, $key, $win]
        );

        $hits = (int) (Database::value(
            'SELECT hits FROM rate_limits WHERE action = ? AND ip_hash = ? AND window_start = ?',
            [$action, $key, $win]
        ) ?? 0);

        if (random_int(1, 100) === 1) {
            Database::run('DELETE FROM rate_limits WHERE window_start < ?', [time() - 86400]);
        }
        return $hits <= $max;
    }

    /** Seconds until the current window resets (0 when unknown). */
    public static function retryAfter(string $action, int $windowSec, ?string $identity = null): int
    {
        $id  = $identity ?? ip_hash();
        $key = hash('sha256', $action . '|' . $id);
        $max = (int) (Database::value(
            'SELECT MAX(window_start) FROM rate_limits WHERE action = ? AND ip_hash = ?',
            [$action, $key]
        ) ?? 0);
        return $max > 0 ? max(0, $max + $windowSec - time()) : 0;
    }
}
