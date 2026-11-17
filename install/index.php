<?php
/**
 * MPIN — web installer.
 * Runs standalone (no app config needed). Steps:
 *   1. requirements check
 *   2. database + admin account form
 *   3. write app/config.php, import schema, create admin, optional demo content
 * Self-locks once the app reports itself installed.
 */
declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

$ROOT = dirname(__DIR__);
$cfgFile = $ROOT . '/app/config.php';
$configExists = is_file($cfgFile);

/* ---------- self-lock check ---------- */
$installed = false;
if ($configExists) {
    try {
        $cfg = require $cfgFile;
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['db']['host'], (int)$cfg['db']['port'], $cfg['db']['name'], $cfg['db']['charset'] ?? 'utf8mb4'),
            $cfg['db']['user'], $cfg['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $installed = (bool) $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'installed'")->fetchColumn();
    } catch (Throwable $e) {
        $installed = false;
    }
}
if ($installed && empty($_GET['force'])) {
    page('Already installed', '
      <div class="card ok">
        <h2>✓ Mpin is already installed</h2>
        <p>This installer is locked for your safety. <strong>Delete the <code>install/</code> directory from your web space now.</strong></p>
        <p><a class="btn" href="../">Go to your site →</a></p>
      </div>');
    exit;
}

/* ---------- requirements ---------- */
$checks = [
    'PHP 8.0 or newer'        => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO MySQL driver'        => extension_loaded('pdo_mysql'),
    'mbstring extension'      => extension_loaded('mbstring'),
    'JSON support'            => function_exists('json_encode'),
    'GD extension (covers)'   => extension_loaded('gd'),
    'Fileinfo (uploads)'      => extension_loaded('fileinfo'),
    'uploads/ writable'       => is_writable($ROOT . '/uploads'),
    'storage/ writable'       => is_writable($ROOT . '/storage'),
    'app/ writable (config)'  => is_writable($ROOT . '/app'),
];
$checksOk = !in_array(false, $checks, true);

/* ---------- handle POST ---------- */
$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $checksOk) {
    $dbHost = trim((string)($_POST['db_host'] ?? 'localhost'));
    $dbPort = (int)($_POST['db_port'] ?? 3306);
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $appUrl = rtrim(trim((string)($_POST['app_url'] ?? '')), '/');
    $adminUser = strtolower(trim((string)($_POST['admin_user'] ?? '')));
    $adminMail = strtolower(trim((string)($_POST['admin_mail'] ?? '')));
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $demo = !empty($_POST['demo']);

    if (!$dbName || !$dbUser || !preg_match('/^[a-z0-9_]{3,30}$/', $adminUser)) {
        $errors[] = 'Please fill the database fields and a valid admin username (3-30 chars: a-z, 0-9, _).';
    }
    if (!filter_var($adminMail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin e-mail is invalid.';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }
    if (!preg_match('#^https?://#', $appUrl)) {
        $appUrl = 'https://' . preg_replace('#^https?://#', '', $appUrl);
    }
    if (!preg_match('#^https?://[a-z0-9.\-]+#i', $appUrl)) {
        $errors[] = 'Site URL looks invalid.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName),
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
            );

            /* 1. schema */
            $sql = file_get_contents($ROOT . '/schema.sql');
            $sql = preg_replace('/^--.*$/m', '', $sql);            // strip comments
            foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
                $stmt = trim($stmt, "; \r\n\t");
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }

            /* 2. categories */
            $cats = [
                ['gaming', 'Gaming', 'gamepad'], ['technology', 'Technology', 'cpu'],
                ['programming', 'Programming', 'code'], ['operating-systems', 'Operating Systems', 'monitor'],
                ['hardware', 'Hardware', 'drive'], ['music', 'Music', 'music'],
                ['lifestyle', 'Lifestyle', 'heart'], ['education', 'Education', 'book'],
                ['communities', 'Communities', 'users'], ['other', 'Other', 'tag'],
            ];
            $st = $pdo->prepare('INSERT IGNORE INTO categories (slug, name, icon, sort_order) VALUES (?,?,?,?)');
            foreach ($cats as $i => $c) {
                $st->execute([$c[0], $c[1], $c[2], $i]);
            }

            /* 3. admin account (only if the username is free) */
            $st = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $st->execute([$adminUser, $adminMail]);
            if ($st->fetch()) {
                throw new RuntimeException('A user with this username or e-mail already exists in this database.');
            }
            $pdo->prepare('INSERT INTO users (username, email, password_hash, display_name, avatar_color, role, is_active, created_at)
                           VALUES (?,?,?,?,?,?,1,NOW())')
                ->execute([$adminUser, $adminMail, password_hash($adminPass, PASSWORD_DEFAULT),
                           ucfirst($adminUser), sprintf('#%06X', random_int(0, 0xFFFFFF)), 'admin']);
            $adminId = (int) $pdo->lastInsertId();

            /* 4. demo content */
            if ($demo) {
                require __DIR__ . '/seed.php';
                mpin_seed_demo($pdo, $adminId);
            }

            /* 5. write config */
            $key = bin2hex(random_bytes(32));
            $config = <<<PHP
<?php
/**
 * MPIN configuration — generated by the installer on {DATE}.
 * KEEP THIS FILE SECRET. It contains your database credentials.
 */
return [
    'app' => [
        'url' => '{URL}',
        'key' => '{KEY}',
        'env' => 'production',
        'timezone' => 'UTC',
        'trust_proxy' => false,
    ],
    'db' => [
        'host' => '{DBH}',
        'port' => {DBP},
        'name' => '{DBN}',
        'user' => '{DBU}',
        'pass' => '{DBW}',
        'charset' => 'utf8mb4',
    ],
    'privacy' => [
        'grid' => 0.05,
        'min_cell' => 3,
    ],
];
PHP;
            $config = str_replace(
                ['{DATE}', '{URL}', '{KEY}', '{DBH}', '{DBP}', '{DBN}', '{DBU}', '{DBW}'],
                [date('Y-m-d H:i'), addslashes($appUrl), $key, addslashes($dbHost), $dbPort, addslashes($dbName), addslashes($dbUser), addslashes($dbPass)],
                $config
            );
            if (file_put_contents($cfgFile, $config) === false) {
                throw new RuntimeException('Could not write app/config.php — check permissions on the app/ directory.');
            }
            @chmod($cfgFile, 0640);

            /* 6. mark installed */
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES ("installed", ?)
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
                ->execute([gmdate('Y-m-d H:i:s')]);

            $done = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

/* ---------- view ---------- */
$errHtml = $errors ? '<div class="card err"><h2>Installation problem</h2><ul><li>'
    . implode('</li><li>', array_map('htmlspecialchars', $errors)) . '</li></ul></div>' : '';

if ($done) {
    page('Install complete', '
      <div class="card ok">
        <h2>🎉 Mpin is installed!</h2>
        <p>Your database is ready and your admin account has been created.</p>
        <ol>
          <li><strong>Security:</strong> delete the <code>install/</code> directory from your web space now.</li>
          <li>Log in at <code>' . htmlspecialchars($appUrl ?? '') . '/login</code> with your admin account.</li>
          <li>Explore, then create your first real poll from the dashboard.</li>
        </ol>
        <p><a class="btn" href="../">Open my site →</a></p>
      </div>');
    exit;
}

$checkRows = '';
foreach ($checks as $name => $ok) {
    $checkRows .= '<li class="' . ($ok ? 'ok' : 'bad') . '"><span>' . htmlspecialchars($name) . '</span><b>' . ($ok ? 'OK' : 'MISSING') . '</b></li>';
}

$guessUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://'
    . preg_replace('#/install/?$#', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));

$disabled = $checksOk ? '' : 'disabled';

page('Mpin installer', $errHtml . '
  <div class="card">
    <h2>1 · Server requirements</h2>
    <ul class="checks">' . $checkRows . '</ul>
  </div>
  <form method="post" class="card">
    <h2>2 · Database &amp; site</h2>
    <div class="grid2">
      <label>MySQL host<input name="db_host" value="localhost" required></label>
      <label>MySQL port<input name="db_port" value="3306" required></label>
      <label>Database name<input name="db_name" placeholder="cpaneluser_mpin" required></label>
      <label>Database user<input name="db_user" placeholder="cpaneluser_mpin" required></label>
      <label>Database password<input name="db_pass" type="password" placeholder="••••••••"></label>
      <label>Site URL<input name="app_url" value="' . htmlspecialchars($guessUrl) . '" required></label>
    </div>
    <h2>3 · Admin account</h2>
    <div class="grid2">
      <label>Admin username<input name="admin_user" placeholder="admin" pattern="[a-zA-Z0-9_]{3,30}" required></label>
      <label>Admin e-mail<input name="admin_mail" type="email" placeholder="you@example.com" required></label>
      <label>Admin password (8+ chars)<input name="admin_pass" type="password" minlength="8" required></label>
    </div>
    <label class="chk"><input type="checkbox" name="demo" checked> Load demo content (5 sample polls with worldwide answers — great for trying the map)</label>
    <button class="btn" type="submit" ' . $disabled . '>Install Mpin</button>
    <p class="note">Existing tables are left untouched (CREATE TABLE IF NOT EXISTS). Installing into a used database is safe for the schema, but the admin user must be new.</p>
  </form>');
exit;

/* ================================================================== */

function page(string $title, string $body): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>' . htmlspecialchars($title) . ' — Mpin</title>
<style>
:root{--bg:#0a0f1c;--sf:#101a2e;--tx:#e6edf7;--mu:#8fa3bf;--bd:rgba(143,163,191,.16);--ac:#22d3ee;--ac2:#3b82f6;--ok:#34d399;--bad:#f87171}
*{box-sizing:border-box}
body{margin:0;font:15px/1.6 -apple-system,"Segoe UI",Roboto,sans-serif;background:radial-gradient(1100px 500px at 85% -180px,rgba(34,211,238,.09),transparent 60%),var(--bg);color:var(--tx);min-height:100vh;display:grid;place-items:center;padding:30px 16px}
.wrap{width:min(680px,100%)}
header{display:flex;align-items:center;gap:12px;margin-bottom:20px}
header img{width:44px;height:44px}
header h1{margin:0;font-size:1.35rem}
header p{margin:0;color:var(--mu);font-size:.85rem}
.card{background:var(--sf);border:1px solid var(--bd);border-radius:16px;padding:22px;margin-bottom:16px}
.card h2{margin:0 0 14px;font-size:1.05rem}
.card.ok{border-color:rgba(52,211,153,.4)} .card.err{border-color:rgba(248,113,113,.4)} .card.err ul{margin:0;padding-left:18px;color:#fecaca}
.checks{list-style:none;padding:0;margin:0}
.checks li{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed var(--bd)}
.checks .ok b{color:var(--ok)} .checks .bad b{color:var(--bad)}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:flex;flex-direction:column;gap:5px;font-size:.8rem;font-weight:700;color:var(--mu);margin-bottom:10px}
input{font:inherit;background:#0d1526;color:var(--tx);border:1px solid var(--bd);border-radius:9px;padding:9px 12px}
input:focus{outline:none;border-color:var(--ac)}
.chk{flex-direction:row;align-items:center;gap:9px;color:var(--tx);font-size:.9rem;font-weight:500}
.btn{display:inline-block;background:linear-gradient(135deg,var(--ac),var(--ac2));color:#04222c;font-weight:800;border:0;border-radius:11px;padding:12px 22px;cursor:pointer;font-size:.95rem;margin-top:8px}
.btn:hover{filter:brightness(1.08)}
.note{color:var(--mu);font-size:.78rem}
code{background:#0d1526;border:1px solid var(--bd);padding:.1em .4em;border-radius:5px}
ol{padding-left:20px}
@media(max-width:560px){.grid2{grid-template-columns:1fr}}
</style></head><body>
<div class="wrap">
  <header>
    <img src="../assets/img/favicon.svg" alt="Mpin logo">
    <div><h1>Mpin installer</h1><p>People Answer. The World Appears.</p></div>
  </header>
  ' . $body . '
</div>
</body></html>';
}
