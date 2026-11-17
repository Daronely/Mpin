<?php /** @var array $user $myPolls $participated $stats $favorites $unread */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container dashboard">
  <div class="page-head">
    <div class="dash-user">
      <span class="avatar xl" style="--ac: <?= e($user['avatar_color'] ?: '#0891b2') ?>"><?= e(initials($user['display_name'])) ?></span>
      <div>
        <h1>Hi, <?= e($user['display_name']) ?> 👋</h1>
        <p class="muted">@<?= e($user['username']) ?> · member since <?= e(date('M Y', strtotime($user['created_at'] . ' UTC'))) ?></p>
      </div>
    </div>
    <div class="head-actions">
      <a class="btn btn-primary" href="<?= e(url('/create')) ?>"><?= mpin_icon('plus', 16) ?> New poll</a>
    </div>
  </div>

  <div class="stat-grid dash-stats">
    <a class="stat-card link-card" href="#my-polls"><span class="stat-num"><?= (int)$stats['polls'] ?></span><span class="stat-label">Polls created</span></a>
    <div class="stat-card"><span class="stat-num"><?= e(num_fmt($stats['responses'])) ?></span><span class="stat-label">Responses received</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['countries'] ?></span><span class="stat-label">Countries reached</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int)$stats['participated'] ?></span><span class="stat-label">Polls answered</span></div>
  </div>

  <section class="dash-section" id="my-polls">
    <div class="section-head">
      <h2><?= mpin_icon('grid', 20) ?> My polls</h2>
    </div>
    <?php if ($myPolls): ?>
      <div class="table-wrap panel">
        <table class="table">
          <thead><tr><th>Poll</th><th>Status</th><th>Participants</th><th>Countries</th><th>Created</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($myPolls as $p): ?>
            <tr>
              <td>
                <a class="t-link" href="<?= e(url('/poll/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
                <span class="chip chip-cat sm"><?= e($p['category_name']) ?></span>
              </td>
              <td><span class="chip chip-status s-<?= e(\App\Models\Poll::effectiveStatus($p)) ?>"><?= e(\App\Models\Poll::effectiveStatus($p)) ?></span></td>
              <td><strong><?= (int)$p['response_count'] ?></strong></td>
              <td>—</td>
              <td class="muted"><?= e(date('M j, Y', strtotime($p['created_at'] . ' UTC'))) ?></td>
              <td class="t-actions">
                <a class="icon-btn" href="<?= e(url('/dashboard/poll/' . $p['id'])) ?>" title="Manage"><?= mpin_icon('settings', 16) ?></a>
                <a class="icon-btn" href="<?= e(url('/poll/' . $p['slug'] . '/edit')) ?>" title="Edit"><?= mpin_icon('edit', 16) ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state"><?= mpin_icon('map', 32) ?><p>You haven't created any polls yet.</p><a class="btn btn-primary" href="<?= e(url('/create')) ?>">Create your first poll</a></div>
    <?php endif; ?>
  </section>

  <?php if ($participated): ?>
  <section class="dash-section">
    <div class="section-head"><h2><?= mpin_icon('send', 20) ?> Polls you answered</h2></div>
    <div class="poll-grid">
      <?php foreach ($participated as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($favorites): ?>
  <section class="dash-section">
    <div class="section-head"><h2><?= mpin_icon('star', 20) ?> Saved polls</h2></div>
    <div class="poll-grid">
      <?php foreach ($favorites as $p) { $p['countries_count'] = 0; \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    </div>
  </section>
  <?php endif; ?>
</section>
<?php $extraJs = []; $wide = false; ?>
