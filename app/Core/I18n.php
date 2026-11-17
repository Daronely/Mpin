<?php
/**
 * MPIN — internationalization (EN + FA, LTR/RTL, Jalali dates, Persian digits).
 */
declare(strict_types=1);

namespace App\Core {

    final class I18n
    {
        public const SUPPORTED = ['en', 'fa'];
        private static string $lang = 'en';
        private static array $dict = [];

        /** Called from bootstrap after session start. */
        public static function init(): void
        {
            // 1. persisted user preference (DB)  2. session  3. cookie  4. browser  5. en
            $lang = null;
            if (!empty($_SESSION['uid'])) {
                try {
                    $lang = Database::value('SELECT locale FROM users WHERE id = ?', [$_SESSION['uid']]);
                } catch (\Throwable $e) {
                    $lang = null;
                }
            }
            $lang = $lang ?: ($_SESSION['lang'] ?? null);
            $lang = $lang ?: ($_COOKIE['mpin_lang'] ?? null);
            if (!$lang) {
                $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
                $lang = preg_match('/^fa\b|,\s*fa\b/i', $accept) ? 'fa' : 'en';
            }
            self::$lang = in_array($lang, self::SUPPORTED, true) ? $lang : 'en';
            $_SESSION['lang'] = self::$lang;

            if (!isset($_COOKIE['mpin_lang']) || $_COOKIE['mpin_lang'] !== self::$lang) {
                setcookie('mpin_lang', self::$lang, [
                    'expires' => time() + 365 * 86400, 'path' => '/',
                    'secure' => str_starts_with(APP_URL, 'https://'), 'httponly' => true, 'samesite' => 'Lax',
                ]);
            }

            self::$dict = require MPIN_APP . '/Lang/' . self::$lang . '.php';
        }

        public static function lang(): string
        {
            return self::$lang;
        }

        public static function isRtl(): bool
        {
            return self::$lang === 'fa';
        }

        /** Full dictionary (for the JS bridge). */
        public static function all(): array
        {
            return self::$dict;
        }

        /** Switch language (route /lang/{code}); persists in session/cookie/user row. */
        public static function set(string $lang): void
        {
            if (!in_array($lang, self::SUPPORTED, true)) {
                return;
            }
            $_SESSION['lang'] = $lang;
            self::$lang = $lang;
            self::$dict = require MPIN_APP . '/Lang/' . $lang . '.php';
            if (!empty($_SESSION['uid'])) {
                try {
                    Database::update('users', ['locale' => $lang], 'id = ?', [$_SESSION['uid']]);
                } catch (\Throwable $e) {
                    /* column may not exist pre-migration */
                }
            }
        }
    }
}

namespace {

    /** Translate a key with :param placeholders. */
    function __(string $key, array $params = []): string
    {
        static $dict = null;
        if ($dict === null) {
            $dict = \App\Core\I18n::all();
        }
        $s = $dict[$key] ?? $key;
        foreach ($params as $k => $v) {
            $s = str_replace(':' . $k, (string)$v, $s);
        }
        return $s;
    }

    function lang(): string
    {
        return \App\Core\I18n::lang();
    }

    function lang_dir(): string
    {
        return \App\Core\I18n::isRtl() ? 'rtl' : 'ltr';
    }

    /** Convert Latin digits to Persian digits (fa only). */
    function local_num(string|int|float|null $n): string
    {
        $s = (string)$n;
        if (\App\Core\I18n::isRtl()) {
            $s = str_replace(['0','1','2','3','4','5','6','7','8','9'],
                             ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], $s);
        }
        return $s;
    }

    /**
     * Localized date: EN "Nov 17, 2026" · FA "۲۶ آبان ۱۴۰۵" (Jalali).
     */
    function local_date(?string $dt, bool $withTime = false): string
    {
        if (!$dt) {
            return '—';
        }
        $ts = strtotime($dt . ' UTC');
        if (!\App\Core\I18n::isRtl()) {
            $out = gmdate('M j, Y', $ts);
            if ($withTime) {
                $out .= ', ' . gmdate('H:i', $ts) . ' UTC';
            }
            return $out;
        }
        [$jy, $jm, $jd] = gregorian_to_jalali((int)gmdate('Y', $ts), (int)gmdate('n', $ts), (int)gmdate('j', $ts));
        $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        $out = local_num($jd . ' ' . $months[$jm - 1] . ' ' . $jy);
        if ($withTime) {
            $out .= '، ' . local_num(gmdate('H:i', $ts));
        }
        return $out;
    }

    /** Gregorian → Jalali (standard algorithm). */
    function gregorian_to_jalali(int $gy, int $gm, int $gd): array
    {
        $gDm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
              + intdiv($gy2 + 399, 400) + $gd + $gDm[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }
}
