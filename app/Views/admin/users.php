<?php /** @var array $users */ \App\Core\View::partial('admin-head', ['section' => 'users']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <form class="toolbar admin-toolbar" method="get" action="<?= e(url('/admin/users')) ?>">
    <div class="field search-field"><?= mpin_icon('search', 16) ?>
      <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search username / e-mail…"></div>
    <button class="btn btn-primary" type="submit">Search</button>
  </form>

  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Polls</th><th>Answers</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr data-user="<?= (int)$u['id'] ?>">
          <td>
            <span class="avatar sm" style="--ac: <?= e($u['avatar_color'] ?: '#0891b2') ?>"><?= e(initials($u['display_name'])) ?></span>
            <div class="t-cell"><a class="t-link" href="<?= e(url('/u/' . $u['username'])) ?>"><?= e($u['display_name']) ?></a>
              <span class="muted small">@<?= e($u['username']) ?> · <?= e($u['email']) ?></span></div>
          </td>
          <td><span class="chip<?= $u['role'] === 'admin' ? ' chip-verified' : '' ?>"><?= e($u['role']) ?></span></td>
          <td><?= (int)$u['is_active'] === 1
              ? '<span class="chip chip-status s-open">active</span>'
              : '<span class="chip chip-status s-closed">suspended</span>' ?></td>
          <td><?= (int)$u['polls_count'] ?></td>
          <td><?= (int)$u['responses_count'] ?></td>
          <td class="muted"><?= e(date('Y-m-d', strtotime($u['created_at'] . ' UTC'))) ?></td>
          <td class="t-actions">
            <?php if ((int)$u['id'] !== (int)current_user()['id']): ?>
              <?php if ((int)$u['is_active'] === 1): ?>
                <button class="icon-btn" data-ua="suspend" title="Suspend"><?= mpin_icon('lock', 15) ?></button>
              <?php else: ?>
                <button class="icon-btn" data-ua="activate" title="Activate"><?= mpin_icon('check', 15) ?></button>
              <?php endif; ?>
              <?php if ($u['role'] === 'admin'): ?>
                <button class="icon-btn" data-ua="make-user" title="Demote to user"><?= mpin_icon('user', 15) ?></button>
              <?php else: ?>
                <button class="icon-btn" data-ua="make-admin" title="Promote to admin"><?= mpin_icon('shield', 15) ?></button>
              <?php endif; ?>
              <button class="icon-btn danger" data-ua="delete" data-confirm="Type DELETE to confirm" title="Delete user"><?= mpin_icon('trash', 15) ?></button>
            <?php else: ?>
              <span class="muted small">you</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
