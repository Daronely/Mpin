<?php /** @var array $reports */ \App\Core\View::partial('admin-head', ['section' => 'reports']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th>When</th><th>Poll</th><th>Reason</th><th>Details</th><th>Reporter</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (!$reports): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:32px">No reports. 🎉</td></tr>
      <?php endif; ?>
      <?php foreach ($reports as $r): ?>
        <tr data-report="<?= (int)$r['id'] ?>">
          <td class="muted"><?= e(date('M j H:i', strtotime($r['created_at'] . ' UTC'))) ?></td>
          <td><a class="t-link" href="<?= e(url('/poll/' . $r['poll_slug'])) ?>"><?= e($r['poll_title']) ?></a></td>
          <td><span class="chip"><?= e($r['reason']) ?></span></td>
          <td class="muted"><?= e($r['details'] ?: '—') ?></td>
          <td><?= $r['reporter'] ? '@' . e($r['reporter']) : 'guest' ?></td>
          <td><span class="chip chip-status <?= $r['status'] === 'open' ? 's-open' : ($r['status'] === 'resolved' ? 's-closed' : '') ?>"><?= e($r['status']) ?></span></td>
          <td class="t-actions">
            <?php if ($r['status'] === 'open'): ?>
              <button class="icon-btn" data-ra="resolve" title="Mark resolved"><?= mpin_icon('check', 15) ?></button>
              <button class="icon-btn" data-ra="dismiss" title="Dismiss"><?= mpin_icon('x', 15) ?></button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
