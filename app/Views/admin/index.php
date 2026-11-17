<?php /** @var array $stats $recentResponses $topPolls */
\App\Core\View::partial('admin-head', ['section' => 'index']); ?>
  <div class="stat-grid dash-stats">
    <div class="stat-card"><span class="stat-num"><?= e(num_fmt($stats['polls'])) ?></span><span class="stat-label">Public polls</span></div>
    <div class="stat-card"><span class="stat-num"><?= e(num_fmt($stats['responses'])) ?></span><span class="stat-label">Responses</span></div>
    <div class="stat-card"><span class="stat-num"><?= e(num_fmt($stats['users'])) ?></span><span class="stat-label">Users</span></div>
    <div class="stat-card"><span class="stat-num"><?= e(num_fmt($stats['responses_24h'])) ?></span><span class="stat-label">Responses 24h</span></div>
    <div class="stat-card<?= $stats['open_reports'] > 0 ? ' warn' : '' ?>"><span class="stat-num"><?= (int)$stats['open_reports'] ?></span><span class="stat-label">Open reports</span></div>
    <div class="stat-card<?= $stats['banned'] > 0 ? ' warn' : '' ?>"><span class="stat-num"><?= (int)$stats['banned'] ?></span><span class="stat-label">Suspended users</span></div>
  </div>

  <div class="manage-grid admin-grid">
    <section class="panel">
      <div class="panel-head"><h2><?= mpin_icon('clock', 16) ?> Latest responses</h2></div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>When</th><th>Poll</th><th>User</th></tr></thead>
        <tbody>
        <?php foreach ($recentResponses as $r): ?>
          <tr>
            <td class="muted"><?= e(date('M j H:i', strtotime($r['created_at'] . ' UTC'))) ?></td>
            <td><a class="t-link" href="<?= e(url('/poll/' . $r['slug'])) ?>"><?= e($r['title']) ?></a></td>
            <td><?= $r['username'] ? '@' . e($r['username']) : '<span class="muted">guest</span>' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>

    <section class="panel">
      <div class="panel-head"><h2><?= mpin_icon('fire', 16) ?> Top polls</h2></div>
      <ol class="top-poll-list">
        <?php foreach ($topPolls as $p): ?>
          <li><a class="t-link" href="<?= e(url('/poll/' . $p['slug'])) ?>"><?= e($p['title']) ?></a>
              <span class="muted small"><?= e(num_fmt((int)$p['response_count'])) ?> participants</span></li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
