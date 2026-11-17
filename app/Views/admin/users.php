<?php /** @var array $users */ \App\Core\View::partial('admin-head', ['section' => 'users']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <form class="toolbar admin-toolbar" method="get" action="<?= e(url('/admin/users')) ?>">
    <div class="field search-field"><?= mpin_icon('search', 16) ?>
      <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('admin.search_user')) ?>"></div>
    <button class="btn btn-primary" type="submit"><?= e(__('common.search')) ?></button>
  </form>

  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th><?= e(__('common.users')) ?></th><th><?= e(__('admin.role')) ?></th><th><?= e(__('common.status')) ?></th><th><?= e(__('common.polls')) ?></th><th><?= e(__('common.responses')) ?></th><th><?= e(__('admin.joined')) ?></th><th><?= e(__('common.actions')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr data-user="<?= (int)$u['id'] ?>">
          <td>
            <span class="avatar sm" style="--ac: <?= e($u['avatar_color'] ?: '#0891b2') ?>"><?= e(initials($u['display_name'])) ?></span>
            <div class="t-cell"><a class="t-link" href="<?= e(url('/u/' . $u['username'])) ?>"><?= e($u['display_name']) ?></a>
              <span class="muted small">@<?= e($u['username']) ?> · <?= e($u['email']) ?></span></div>
          </td>
          <td><span class="chip<?= $u['role'] === 'admin' ? ' chip-verified' : '' ?>"><?= e($u['role'] === 'admin' ? __('nav.admin') : __('common.users')) ?></span></td>
          <td><?= (int)$u['is_active'] === 1
              ? '<span class="chip chip-status s-open">' . e(__('status.active')) . '</span>'
              : '<span class="chip chip-status s-closed">' . e(__('status.suspended')) . '</span>' ?></td>
          <td><?= e(local_num((int)$u['polls_count'])) ?></td>
          <td><?= e(local_num((int)$u['responses_count'])) ?></td>
          <td class="muted"><?= e(local_date($u['created_at'])) ?></td>
          <td class="t-actions">
            <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
              <?php if ((int)$u['is_active'] === 1): ?>
                <button class="icon-btn" data-ua="suspend" title="<?= e(__('admin.suspend')) ?>"><?= mpin_icon('lock', 15) ?></button>
              <?php else: ?>
                <button class="icon-btn" data-ua="activate" title="<?= e(__('admin.activate')) ?>"><?= mpin_icon('check', 15) ?></button>
              <?php endif; ?>
              <?php if ($u['role'] === 'admin'): ?>
                <button class="icon-btn" data-ua="make-user" title="<?= e(__('admin.make_user')) ?>"><?= mpin_icon('user', 15) ?></button>
              <?php else: ?>
                <button class="icon-btn" data-ua="make-admin" title="<?= e(__('admin.make_admin')) ?>"><?= mpin_icon('shield', 15) ?></button>
              <?php endif; ?>
              <button class="icon-btn danger" data-ua="delete" data-confirm="<?= e(__('admin.delete_user_confirm')) ?>" title="<?= e(__('admin.delete_user')) ?>"><?= mpin_icon('trash', 15) ?></button>
            <?php else: ?>
              <span class="muted small"><?= e(__('common.you')) ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
