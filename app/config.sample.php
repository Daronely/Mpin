<?php
/**
 * MPIN — configuration template.
 * Copy this file to app/config.php and fill in your values.
 * Never commit app/config.php to version control.
 */
return [

    'app' => [
        // Public base URL of the site, without trailing slash.
        'url'  => 'https://mpin.example.com',

        // 64+ random characters. Generate one, e.g.:
        //   php -r "echo bin2hex(random_bytes(32));"
        'key'  => 'CHANGE_ME_GENERATE_64_RANDOM_HEX_CHARS',

        'env'  => 'production',          // production | development
        'timezone' => 'UTC',             // all timestamps are stored in UTC

        // Set true only when behind a reverse proxy you control
        // (e.g. Cloudflare) so client IPs are read correctly.
        'trust_proxy' => false,
    ],

    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'mpin',
        'user'    => 'mpin',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Location privacy (k-anonymity grid).
    'privacy' => [
        'grid'     => 0.05,   // degrees — snap grid (~5.5 km) for stored points
        'min_cell' => 3,      // suppress map cells with fewer than this many responses
    ],
];
