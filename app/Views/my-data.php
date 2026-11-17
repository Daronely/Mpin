<?php /** @var array $user $responses $isGuest $flash */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head">
    <div>
      <h1><?= mpin_icon('shield', 26) ?> <?= e(__('mydata.title')) ?></h1>
      <p class="muted"><?= e($isGuest ? __('mydata.sub_guest') : __('mydata.sub_user')) ?></p>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="toast ok" style="position:static;margin-bottom:16px;"><?= e($flash) ?></div>
  <?php endif; ?>

  <?php if ($responses): ?>
    <div class="panel table-wrap">
      <table class="table">
        <thead>
          <tr><th><?= e(__('explore.title')) ?></th><th><?= e(__('common.when')) ?></th><th><?= e(__('poll.live_map')) ?></th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($responses as $r): ?>
            <tr>
              <td><a class="t-link" href="<?= e(url('/poll/' . $r['slug'])) ?>"><?= e($r['title']) ?></a>
                <?php if ($isGuest): ?><span class="muted small">· <?= e(__('mydata.note')) ?></span><?php endif; ?></td>
              <td class="muted"><?= e(local_date($r['created_at'], true)) ?></td>
              <td><?= (int)$r['has_location'] > 0
                  ? '<span class="chip chip-verified">' . mpin_icon('pin', 12) . ' ' . e(__('mydata.has_location')) . '</span>'
                  : '<span class="chip">' . e(__('mydata.no_location')) . '</span>' ?></td>
              <td class="t-actions">
                <form method="post" action="<?= e(url('/my-data/delete')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="response_id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit"><?= mpin_icon('trash', 14) ?> <?= e(__('mydata.delete_one')) ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <form method="post" action="<?= e(url('/my-data/delete')) ?>" class="panel danger-panel">
      <?= csrf_field() ?>
      <input type="hidden" name="all" value="1">
      <div class="panel-head"><h2><?= mpin_icon('trash', 16) ?> <?= e(__('mydata.delete_all')) ?></h2></div>
      <p class="muted small"><?= e($isGuest ? __('mydata.sub_guest') : __('mydata.sub_user')) ?></p>
      <button class="btn btn-danger" type="submit"><?= mpin_icon('trash', 15) ?> <?= e(__('mydata.delete_all')) ?></button>
    </form>
  <?php else: ?>
    <div class="empty-state"><?= mpin_icon('list', 36) ?><p><?= e(__('mydata.empty')) ?></p></div>
  <?php endif; ?>

  <?php if ($isGuest): ?>
    <p class="muted small"><?= e(__('mydata.login_hint')) ?></p>
  <?php endif; ?>
</section>
<?php $extraJs = []; $wide = false; ?>
