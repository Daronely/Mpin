<?php /** @var array $user $myPolls $participated $stats $favorites $unread */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container dashboard">
  <div class="page-head">
    <div class="dash-user">
      <span class="avatar xl" style="--ac: <?= e($user['avatar_color'] ?: '#0891b2') ?>"><?= e(initials($user['display_name'])) ?></span>
      <div>
        <h1><?= e(__('dash.hello', ['name' => e($user['display_name'])])) ?></h1>
        <p class="muted"><?= e(__('dash.member_since', ['user' => e($user['username']), 'date' => local_date($user['created_at'])])) ?></p>
      </div>
    </div>
    <div class="head-actions">
      <a class="btn btn-primary" href="<?= e(url('/create')) ?>"><?= mpin_icon('plus', 16) ?> <?= e(__('dash.new_poll')) ?></a>
    </div>
  </div>

  <div class="stat-grid dash-stats">
    <a class="stat-card link-card" href="#my-polls"><span class="stat-num"><?= e(local_num((int)$stats['polls'])) ?></span><span class="stat-label"><?= e(__('dash.polls_created')) ?></span></a>
    <div class="stat-card"><span class="stat-num"><?= e(local_num(num_fmt($stats['responses']))) ?></span><span class="stat-label"><?= e(__('dash.responses_received')) ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= e(local_num((int)$stats['countries'])) ?></span><span class="stat-label"><?= e(__('dash.countries_reached')) ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= e(local_num((int)$stats['participated'])) ?></span><span class="stat-label"><?= e(__('dash.polls_answered')) ?></span></div>
  </div>

  <section class="dash-section" id="my-polls">
    <div class="section-head">
      <h2><?= mpin_icon('grid', 20) ?> <?= e(__('dash.my_polls')) ?></h2>
    </div>
    <?php if ($myPolls): ?>
      <div class="table-wrap panel">
        <table class="table">
          <thead><tr><th><?= e(__('explore.title')) ?></th><th><?= e(__('common.status')) ?></th><th><?= e(__('common.participants')) ?></th><th><?= e(__('dash.created')) ?></th><th></th></tr></thead>
          <tbody>
          <?php foreach ($myPolls as $p): ?>
            <tr>
              <td>
                <a class="t-link" href="<?= e(url('/poll/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
                <span class="chip chip-cat sm"><?= e(__('cat.' . $p['category_slug'])) ?></span>
              </td>
              <td><span class="chip chip-status s-<?= e(\App\Models\Poll::effectiveStatus($p)) ?>"><?= e(__('status.' . \App\Models\Poll::effectiveStatus($p))) ?></span></td>
              <td><strong><?= e(local_num((int)$p['response_count'])) ?></strong></td>
              <td class="muted"><?= e(local_date($p['created_at'])) ?></td>
              <td class="t-actions">
                <a class="icon-btn" href="<?= e(url('/dashboard/poll/' . $p['id'])) ?>" title="<?= e(__('common.manage')) ?>"><?= mpin_icon('settings', 16) ?></a>
                <a class="icon-btn" href="<?= e(url('/poll/' . $p['slug'] . '/edit')) ?>" title="<?= e(__('common.edit')) ?>"><?= mpin_icon('edit', 16) ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state"><?= mpin_icon('map', 32) ?><p><?= e(__('dash.no_polls')) ?></p><a class="btn btn-primary" href="<?= e(url('/create')) ?>"><?= e(__('dash.create_first')) ?></a></div>
    <?php endif; ?>
  </section>

  <?php if ($participated): ?>
  <section class="dash-section">
    <div class="section-head"><h2><?= mpin_icon('send', 20) ?> <?= e(__('dash.answered_polls')) ?></h2></div>
    <div class="poll-grid">
      <?php foreach ($participated as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($favorites): ?>
  <section class="dash-section">
    <div class="section-head"><h2><?= mpin_icon('star', 20) ?> <?= e(__('dash.saved_polls')) ?></h2></div>
    <div class="poll-grid">
      <?php foreach ($favorites as $p) { $p['countries_count'] = 0; \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    </div>
  </section>
  <?php endif; ?>
</section>
<?php $extraJs = []; $wide = false; ?>
