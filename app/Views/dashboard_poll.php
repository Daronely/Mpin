<?php /** @var array $poll $results $status */ require_once MPIN_APP . '/Views/partials/icons.php'; $vendor = ['chart']; ?>
<section class="container dashboard">
  <div class="page-head">
    <div>
      <a class="crumb" href="<?= e(url('/dashboard')) ?>"><?= mpin_icon('arrow-right', 14) ?> <?= e(__('nav.dashboard')) ?></a>
      <h1><?= e($poll['title']) ?></h1>
      <p class="muted">
        <span class="chip chip-status s-<?= e($status) ?>"><?= e(__('status.' . $status)) ?></span>
        <?= e(local_num(num_fmt($results['participants']))) ?> <?= e(__('common.participants')) ?> ·
        <?= e(local_num((int)$results['countries'])) ?> <?= e(__('common.countries')) ?> ·
        <?= e(local_num((int)$results['cities'])) ?> <?= e(__('common.cities')) ?>
      </p>
    </div>
    <div class="head-actions">
      <a class="btn btn-ghost" href="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('eye', 15) ?> <?= e(__('common.view')) ?></a>
      <a class="btn btn-ghost" href="<?= e(url('/poll/' . $poll['slug'] . '/edit')) ?>"><?= mpin_icon('edit', 15) ?> <?= e(__('common.edit')) ?></a>
    </div>
  </div>

  <div class="manage-grid">
    <div>
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('chart', 17) ?> <?= e(__('dash.growth')) ?></h2></div>
        <div class="chart-box"><canvas id="growthChart" height="120" aria-label="<?= e(__('dash.growth')) ?>"></canvas></div>
        <div class="stat-grid">
          <div class="stat-card"><span class="stat-num"><?= e(local_num(num_fmt($results['participants']))) ?></span><span class="stat-label"><?= e(__('common.participants')) ?></span></div>
          <div class="stat-card"><span class="stat-num"><?= e(local_num((int)$results['countries'])) ?></span><span class="stat-label"><?= e(__('common.countries')) ?></span></div>
          <div class="stat-card"><span class="stat-num"><?= e(local_num((int)$results['cities'])) ?></span><span class="stat-label"><?= e(__('common.cities')) ?></span></div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('list', 17) ?> <?= e(__('dash.answer_summary')) ?></h2></div>
        <?php foreach ($results['questions'] as $q): ?>
          <div class="ans-block">
            <h3><?= e($q['text']) ?> <span class="muted small">(<?= e(__('qt.' . $q['type'])) ?>)</span></h3>
            <?php if (in_array($q['type'], ['yesno', 'single', 'multi'], true)):
                $total = max(1, array_sum(array_column($q['options'], 'count'))); ?>
              <ul class="bar-list static">
                <?php foreach ($q['options'] as $o): ?>
                  <li>
                    <span class="bl-label"><?= e($o['text']) ?></span>
                    <span class="bl-bar"><i style="width: <?= e(pct($o['count'], $total)) ?>%"></i></span>
                    <span class="bl-val"><?= e(local_num((int)$o['count'])) ?> · <?= e(local_num(pct($o['count'], $total))) ?>%</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php elseif ($q['type'] === 'rating'): ?>
              <ul class="bar-list static">
                <?php $rt = max(1, array_sum($q['rating'])); foreach ($q['rating'] as $si => $c): ?>
                  <li>
                    <span class="bl-label"><?= e(local_num($si + 1)) ?>★</span>
                    <span class="bl-bar"><i style="width: <?= e(pct($c, $rt)) ?>%"></i></span>
                    <span class="bl-val"><?= e(local_num((int)$c)) ?> · <?= e(local_num(pct($c, $rt))) ?>%</span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php elseif ($q['type'] === 'number' && $q['number']['count']): ?>
              <p class="kv-line"><?= e(__('create.min_f')) ?> <strong><?= e(local_num($q['number']['min'])) ?></strong> ·
                 <?= e(__('poll.avg')) ?> <strong><?= e(local_num($q['number']['avg'])) ?></strong> ·
                 <?= e(__('create.max_f')) ?> <strong><?= e(local_num($q['number']['max'])) ?></strong> ·
                 <strong><?= e(local_num((int)$q['number']['count'])) ?></strong> <?= e(__('common.responses')) ?></p>
            <?php elseif ($q['type'] === 'text'): ?>
              <ul class="text-answers">
                <?php foreach (array_slice($q['texts'], 0, 5) as $t): ?><li>“<?= e($t) ?>”</li><?php endforeach; ?>
                <?php if (!$q['texts']): ?><li class="muted"><?= e(__('common.no_data')) ?></li><?php endif; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </section>
    </div>

    <aside>
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('download', 16) ?> <?= e(__('dash.export_data')) ?></h2></div>
        <p class="muted small"><?= e(__('dash.export_sub')) ?></p>
        <div class="btn-col">
          <a class="btn btn-ghost" href="<?= e(url('/dashboard/poll/' . $poll['id'] . '/export?format=csv')) ?>"><?= mpin_icon('download', 15) ?> CSV</a>
          <a class="btn btn-ghost" href="<?= e(url('/dashboard/poll/' . $poll['id'] . '/export?format=json')) ?>"><?= mpin_icon('download', 15) ?> JSON</a>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('settings', 16) ?> <?= e(__('common.status')) ?></h2></div>
        <div class="btn-col">
          <?php if ($status === 'open'): ?>
            <button class="btn btn-ghost" data-manage="close"><?= mpin_icon('lock', 15) ?> <?= e(__('dash.close_poll')) ?></button>
          <?php else: ?>
            <button class="btn btn-ghost" data-manage="open"><?= mpin_icon('check', 15) ?> <?= e(__('dash.reopen_poll')) ?></button>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel danger-panel">
        <div class="panel-head"><h2><?= mpin_icon('trash', 16) ?> <?= e(__('dash.danger_zone')) ?></h2></div>
        <p class="muted small"><?= e(__('dash.delete_poll_note')) ?></p>
        <button class="btn btn-danger btn-block" id="deletePollBtn" data-manage="delete" data-confirm="Type DELETE to confirm">
          <?= mpin_icon('trash', 15) ?> <?= e(__('dash.delete_poll')) ?>
        </button>
      </section>
    </aside>
  </div>
</section>

<script id="growthData" type="application/json"><?= json_encode($results['growth'], JSON_UNESCAPED_UNICODE) ?></script>
<script id="manageCfg" type="application/json"><?= json_encode(['slug' => $poll['slug']], JSON_UNESCAPED_UNICODE) ?></script>
<?php $extraJs = ['js/pages/dashboard-poll.js']; $wide = false; ?>
