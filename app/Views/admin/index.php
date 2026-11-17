<?php /** @var array $stats $recentResponses $topPolls */
\App\Core\View::partial('admin-head', ['section' => 'index']); ?>
  <div class="stat-grid dash-stats">
    <div class="stat-card"><span class="stat-num"><?= e(local_num(num_fmt($stats['polls']))) ?></span><span class="stat-label"><?= e(__('admin.public_polls')) ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= e(local_num(num_fmt($stats['responses']))) ?></span><span class="stat-label"><?= e(__('common.responses')) ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= e(local_num(num_fmt($stats['users']))) ?></span><span class="stat-label"><?= e(__('common.users')) ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= e(local_num(num_fmt($stats['responses_24h']))) ?></span><span class="stat-label"><?= e(__('admin.responses_24h')) ?></span></div>
    <div class="stat-card<?= $stats['open_reports'] > 0 ? ' warn' : '' ?>"><span class="stat-num"><?= e(local_num((int)$stats['open_reports'])) ?></span><span class="stat-label"><?= e(__('status.open_reports')) ?></span></div>
    <div class="stat-card<?= $stats['banned'] > 0 ? ' warn' : '' ?>"><span class="stat-num"><?= e(local_num((int)$stats['banned'])) ?></span><span class="stat-label"><?= e(__('admin.banned')) ?></span></div>
  </div>

  <div class="manage-grid admin-grid">
    <section class="panel">
      <div class="panel-head"><h2><?= mpin_icon('clock', 16) ?> <?= e(__('admin.latest_responses')) ?></h2></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th><?= e(__('common.when')) ?></th><th><?= e(__('common.polls')) ?></th><th><?= e(__('common.users')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($recentResponses as $r): ?>
          <tr>
            <td class="muted"><?= e(local_date($r['created_at'], true)) ?></td>
            <td><a class="t-link" href="<?= e(url('/poll/' . $r['slug'])) ?>"><?= e($r['title']) ?></a></td>
            <td><?= $r['username'] ? '@' . e($r['username']) : '<span class="muted">' . e(__('common.anonymous')) . '</span>' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>

    <section class="panel">
      <div class="panel-head"><h2><?= mpin_icon('fire', 16) ?> <?= e(__('admin.top_polls')) ?></h2></div>
      <ol class="top-poll-list">
        <?php foreach ($topPolls as $p): ?>
          <li><a class="t-link" href="<?= e(url('/poll/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
              <span class="muted small"><?= e(local_num(num_fmt((int)$p['response_count']))) ?> <?= e(__('common.participants')) ?></span></li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
