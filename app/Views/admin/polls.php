<?php /** @var array $polls */ \App\Core\View::partial('admin-head', ['section' => 'polls']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th><?= e(__('common.polls')) ?></th><th><?= e(__('admin.creator')) ?></th><th><?= e(__('admin.categories')) ?></th><th><?= e(__('common.status')) ?></th><th><?= e(__('common.actions')) ?></th></tr></thead>
      <tbody>
      <?php foreach ($polls as $p): ?>
        <tr data-poll="<?= (int)$p['id'] ?>">
          <td><a class="t-link" href="<?= e(url('/poll/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
            <span class="muted small"><?= e($p['slug']) ?></span></td>
          <td>@<?= e($p['username']) ?></td>
          <td><?= e(__('cat.' . $p['category_slug'] ?? 'other')) ?></td>
          <td><span class="chip chip-status s-<?= e(\App\Models\Poll::effectiveStatus($p)) ?>"><?= e(__('status.' . \App\Models\Poll::effectiveStatus($p))) ?></span></td>
          <td class="t-actions">
            <?php if ((int)$p['is_featured']): ?>
              <button class="icon-btn" data-pa="unfeature" title="<?= e(__('admin.unfeature')) ?>"><?= mpin_icon('star', 15) ?></button>
            <?php else: ?>
              <button class="icon-btn" data-pa="feature" title="<?= e(__('admin.feature')) ?>"><?= mpin_icon('star', 15) ?></button>
            <?php endif; ?>
            <?php if ((int)$p['is_verified']): ?>
              <button class="icon-btn" data-pa="unverify" title="<?= e(__('admin.unverify')) ?>"><?= mpin_icon('check', 15) ?></button>
            <?php else: ?>
              <button class="icon-btn" data-pa="verify" title="<?= e(__('admin.verify')) ?>"><?= mpin_icon('check', 15) ?></button>
            <?php endif; ?>
            <?php if ($p['status'] === 'open'): ?>
              <button class="icon-btn" data-pa="close" title="<?= e(__('admin.close_poll')) ?>"><?= mpin_icon('lock', 15) ?></button>
            <?php else: ?>
              <button class="icon-btn" data-pa="open" title="<?= e(__('admin.open_poll')) ?>"><?= mpin_icon('check', 15) ?></button>
            <?php endif; ?>
            <button class="icon-btn danger" data-pa="delete" data-confirm="Type DELETE to confirm" title="<?= e(__('admin.delete_poll')) ?>"><?= mpin_icon('trash', 15) ?></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
