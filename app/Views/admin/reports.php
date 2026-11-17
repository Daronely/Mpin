<?php /** @var array $reports */ \App\Core\View::partial('admin-head', ['section' => 'reports']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th><?= e(__('common.when')) ?></th><th><?= e(__('common.polls')) ?></th><th><?= e(__('poll.report_reason')) ?></th><th><?= e(__('poll.report_details')) ?></th><th><?= e(__('admin.reporter')) ?></th><th><?= e(__('common.status')) ?></th><th><?= e(__('common.actions')) ?></th></tr></thead>
      <tbody>
      <?php if (!$reports): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:32px"><?= e(__('admin.no_reports')) ?></td></tr>
      <?php endif; ?>
      <?php foreach ($reports as $r): ?>
        <tr data-report="<?= (int)$r['id'] ?>">
          <td class="muted"><?= e(local_date($r['created_at'], true)) ?></td>
          <td><a class="t-link" href="<?= e(url('/poll/' . $r['poll_slug'])) ?>"><?= e($r['poll_title']) ?></a></td>
          <td><span class="chip"><?= e($r['reason']) ?></span></td>
          <td class="muted"><?= e($r['details'] ?: '—') ?></td>
          <td><?= $r['reporter'] ? '@' . e($r['reporter']) : e(__('common.anonymous')) ?></td>
          <td><span class="chip chip-status <?= $r['status'] === 'open' ? 's-open' : ($r['status'] === 'resolved' ? 's-closed' : '') ?>"><?= e($r['status']) ?></span></td>
          <td class="t-actions">
            <?php if ($r['status'] === 'open'): ?>
              <button class="icon-btn" data-ra="resolve" title="<?= e(__('admin.resolve')) ?>"><?= mpin_icon('check', 15) ?></button>
              <button class="icon-btn" data-ra="dismiss" title="<?= e(__('admin.dismiss')) ?>"><?= mpin_icon('x', 15) ?></button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
