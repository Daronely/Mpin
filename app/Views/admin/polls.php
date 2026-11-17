<?php /** @var array $polls */ \App\Core\View::partial('admin-head', ['section' => 'polls']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <div class="panel table-wrap">
    <table class="table">
      <thead><tr><th>Poll</th><th>Creator</th><th>Category</th><th>Status</th><th>Flags</th><th>Answers</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($polls as $p): ?>
        <tr data-poll="<?= (int)$p['id'] ?>">
          <td><a class="t-link" href="<?= e(url('/poll/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
            <span class="muted small"><?= e($p['slug']) ?></span></td>
          <td>@<?= e($p['username']) ?></td>
          <td><?= e($p['category_name']) ?></td>
          <td><span class="chip chip-status s-<?= e(\App\Models\Poll::effectiveStatus($p)) ?>"><?= e(\App\Models\Poll::effectiveStatus($p)) ?></span></td>
          <td class="muted small">
            <?= $p['visibility'] === 'private' ? '🔒 ' : '' ?><?= (int)$p['is_featured'] ? '★ ' : '' ?><?= (int)$p['is_verified'] ? '✔ ' : '' ?>
          </td>
          <td><strong><?= (int)$p['response_count'] ?></strong></td>
          <td class="t-actions">
            <?php if ((int)$p['is_featured']): ?>
              <button class="icon-btn" data-pa="unfeature" title="Unfeature"><?= mpin_icon('star', 15) ?></button>
            <?php else: ?>
              <button class="icon-btn" data-pa="feature" title="Feature on home"><?= mpin_icon('star', 15) ?></button>
            <?php endif; ?>
            <?php if ((int)$p['is_verified']): ?>
              <button class="icon-btn" data-pa="unverify" title="Remove verification"><?= mpin_icon('check', 15) ?></button>
            <?php else: ?>
              <button class="icon-btn" data-pa="verify" title="Verify poll"><?= mpin_icon('check', 15) ?></button>
            <?php endif; ?>
            <?php if ($p['status'] === 'open'): ?>
              <button class="icon-btn" data-pa="close" title="Close"><?= mpin_icon('lock', 15) ?></button>
            <?php else: ?>
              <button class="icon-btn" data-pa="open" title="Open"><?= mpin_icon('check', 15) ?></button>
            <?php endif; ?>
            <button class="icon-btn danger" data-pa="delete" data-confirm="Type DELETE to confirm" title="Delete"><?= mpin_icon('trash', 15) ?></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
