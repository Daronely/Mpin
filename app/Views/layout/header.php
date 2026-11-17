<?php
/** @var string $title  @var string $active  @var array $vendor  @var array $extraJs */
$user = current_user();
$vendor = $vendor ?? [];
$extraJs = $extraJs ?? [];
$active = $active ?? '';
$unread = 0;
if ($user) {
    try {
        $unread = (int) \App\Core\Database::value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']]);
    } catch (Throwable $e) {
        $unread = 0;
    }
}
require_once MPIN_APP . '/Views/partials/icons.php';

$vendorCssMap = [
    'leaflet' => ['vendor/leaflet/leaflet.css'],
    'cluster' => ['vendor/markercluster/MarkerCluster.css', 'vendor/markercluster/MarkerCluster.Default.css'],
];
$vendorJsMap = [
    'leaflet' => ['vendor/leaflet/leaflet.js'],
    'cluster' => ['vendor/markercluster/leaflet.markercluster.js'],
    'heat'    => ['vendor/heatmap/leaflet-heat.js'],
    'chart'   => ['vendor/chartjs/chart.umd.min.js'],
];
$navItems = [
    ['explore', 'Explore', '/explore', 'compass'],
    ['map', 'Global Map', '/map', 'globe'],
    ['create', 'Create', '/create', 'plus'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Mpin') ?></title>
<meta name="description" content="Mpin — Interactive community polling on a world map. Ask questions, collect answers, and watch the world appear.">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(url('/assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?php foreach ($vendor as $v): ?>
    <?php foreach ($vendorCssMap[$v] ?? [] as $css): ?>
        <link rel="stylesheet" href="<?= e(asset($css)) ?>">
    <?php endforeach; ?>
<?php endforeach; ?>
<script>
(function(){try{var t=localStorage.getItem('mpin-theme');if(!t){t=matchMedia('(prefers-color-scheme: light)').matches?'light':'dark';}document.documentElement.dataset.theme=t;}catch(e){document.documentElement.dataset.theme='dark';}})();
</script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="container nav-wrap">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="Mpin home">
      <?= mpin_logo(32) ?><span class="brand-name">Mpin</span>
    </a>

    <nav class="main-nav" id="mainNav" aria-label="Main">
      <?php foreach ($navItems as [$key, $label, $href, $icon]): ?>
        <a class="nav-link<?= $active === $key ? ' active' : '' ?>" href="<?= e(url($href)) ?>">
          <?= mpin_icon($icon, 17) ?><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="nav-actions">
      <button class="icon-btn" id="themeToggle" type="button" title="Toggle theme" aria-label="Toggle dark / light theme">
        <span class="ico-sun"><?= mpin_icon('sun', 18) ?></span>
        <span class="ico-moon"><?= mpin_icon('moon', 18) ?></span>
      </button>

      <?php if ($user): ?>
        <a class="icon-btn nav-bell" href="<?= e(url('/dashboard')) ?>" title="Notifications" aria-label="Notifications">
          <?= mpin_icon('bell', 18) ?>
          <?php if ($unread > 0): ?><span class="badge-dot"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?>
        </a>
        <div class="user-menu" data-dropdown>
          <button class="avatar-btn" type="button" data-dropdown-toggle aria-haspopup="menu">
            <span class="avatar" style="--ac: <?= e($user['avatar_color'] ?: '#0891b2') ?>"><?= e(initials($user['display_name'])) ?></span>
            <?= mpin_icon('chevron-down', 14) ?>
          </button>
          <div class="dropdown" role="menu">
            <div class="dropdown-head">
              <strong><?= e($user['display_name']) ?></strong>
              <span>@<?= e($user['username']) ?></span>
            </div>
            <a role="menuitem" href="<?= e(url('/dashboard')) ?>"><?= mpin_icon('grid', 16) ?> Dashboard</a>
            <a role="menuitem" href="<?= e(url('/u/' . $user['username'])) ?>"><?= mpin_icon('user', 16) ?> My profile</a>
            <a role="menuitem" href="<?= e(url('/settings')) ?>"><?= mpin_icon('settings', 16) ?> Settings</a>
            <?php if ($user['role'] === 'admin'): ?>
              <a role="menuitem" class="admin-link" href="<?= e(url('/admin')) ?>"><?= mpin_icon('shield', 16) ?> Admin panel</a>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/logout')) ?>">
              <?= csrf_field() ?>
              <button type="submit" role="menuitem"><?= mpin_icon('logout', 16) ?> Log out</button>
            </form>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/login')) ?>">Log in</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Get started</a>
      <?php endif; ?>

      <button class="icon-btn nav-burger" id="navBurger" type="button" aria-label="Menu" aria-expanded="false">
        <?= mpin_icon('list', 20) ?>
      </button>
    </div>
  </div>
</header>

<main id="main" class="site-main<?= !empty($wide) ? ' wide' : '' ?>">
