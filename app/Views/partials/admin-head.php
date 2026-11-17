<?php
/** Admin layout top: expects $section (or 'index'), renders nav. */
require_once MPIN_APP . '/Views/partials/icons.php';
$adminSections = [
    ''           => ['gauge' => 'Overview', 'grid'],
    'users'      => ['Users', 'users'],
    'polls'      => ['Polls', 'map'],
    'categories' => ['Categories', 'tag'],
    'reports'    => ['Reports', 'flag'],
    'settings'   => ['Settings', 'settings'],
];
$section = $section ?? 'index';
?>
<section class="container admin">
  <div class="page-head">
    <div><h1><?= mpin_icon('shield', 26) ?> Admin panel</h1>
      <p class="muted">Moderate users, polls, categories and reports.</p></div>
  </div>
  <nav class="admin-nav" aria-label="Admin sections">
    <a class="<?= $section === 'index' ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>"><?= mpin_icon('grid', 15) ?> Overview</a>
    <a class="<?= $section === 'users' ? 'active' : '' ?>" href="<?= e(url('/admin/users')) ?>"><?= mpin_icon('users', 15) ?> Users</a>
    <a class="<?= $section === 'polls' ? 'active' : '' ?>" href="<?= e(url('/admin/polls')) ?>"><?= mpin_icon('map', 15) ?> Polls</a>
    <a class="<?= $section === 'categories' ? 'active' : '' ?>" href="<?= e(url('/admin/categories')) ?>"><?= mpin_icon('tag', 15) ?> Categories</a>
    <a class="<?= $section === 'reports' ? 'active' : '' ?>" href="<?= e(url('/admin/reports')) ?>"><?= mpin_icon('flag', 15) ?> Reports</a>
    <a class="<?= $section === 'settings' ? 'active' : '' ?>" href="<?= e(url('/admin/settings')) ?>"><?= mpin_icon('settings', 15) ?> Settings</a>
  </nav>
