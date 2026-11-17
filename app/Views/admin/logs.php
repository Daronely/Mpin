<?php /** @var array $logs */ \App\Core\View::partial('admin-head', ['section' => 'logs']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <section class="panel">
    <div class="panel-head">
      <h2><?= mpin_icon('list', 17) ?> <?= e(__('admin.audit_title')) ?></h2>
      <p class="muted small"><?= e(__('admin.audit_sub')) ?></p>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th><?= e(__('common.when')) ?></th><th><?= e(__('admin.role')) ?></th><th><?= e(__('admin.audit_action')) ?></th><th><?= e(__('admin.audit_target')) ?></th><th><?= e(__('admin.audit_details')) ?></th></tr></thead>
        <tbody>
        <?php if (!$logs): ?>
          <tr><td colspan="5" class="muted" style="text-align:center;padding:32px"><?= e(__('admin.audit_empty')) ?></td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td class="muted"><?= e(local_date($l['created_at'], true)) ?></td>
            <td>@<?= e($l['username'] ?? $l['actor']) ?></td>
            <td><span class="chip chip-cat"><?= e($l['action']) ?></span></td>
            <td><span class="muted small"><?= e($l['target_type']) ?> #<?= e(local_num($l['target_id'])) ?></span></td>
            <td class="muted"><?= e($l['details'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
