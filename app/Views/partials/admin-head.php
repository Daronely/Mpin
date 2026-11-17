<?php
/** Admin layout top: expects $section (or 'index'), renders nav. */
require_once MPIN_APP . '/Views/partials/icons.php';
$section = $section ?? 'index';
$adminNav = [
    'index'      => ['admin.overview', 'grid'],
    'users'      => ['admin.users', 'users'],
    'polls'      => ['admin.polls', 'map'],
    'categories' => ['admin.categories', 'tag'],
    'reports'    => ['admin.reports', 'flag'],
    'logs'       => ['admin.logs', 'list'],
    'settings'   => ['admin.settings', 'settings'],
];
?>
<section class="container admin">
  <div class="page-head">
    <div><h1><?= mpin_icon('shield', 26) ?> <?= e(__('admin.title')) ?></h1>
      <p class="muted"><?= e(__('admin.sub')) ?></p></div>
  </div>
  <nav class="admin-nav" aria-label="Admin sections">
    <?php foreach ($adminNav as $key => [$labelKey, $icon]): ?>
      <a class="<?= $section === $key ? 'active' : '' ?>" href="<?= e(url('/admin' . ($key === 'index' ? '' : '/' . $key))) ?>"><?= mpin_icon($icon, 15) ?> <?= e(__($labelKey)) ?></a>
    <?php endforeach; ?>
  </nav>
