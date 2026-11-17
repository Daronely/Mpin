<?php /** @var array $categories */ \App\Core\View::partial('admin-head', ['section' => 'categories']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <section class="manage-grid">
    <div class="panel table-wrap">
      <table class="table">
        <thead><tr><th><?= e(__('common.name')) ?></th><th><?= e(__('admin.slug_f')) ?></th><th><?= e(__('common.polls')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
          <tr data-cat="<?= (int)$c['id'] ?>">
            <td><span class="cat-mini"><?= mpin_icon($c['icon'], 16) ?></span> <strong><?= e(__('cat.' . $c['slug'])) ?></strong></td>
            <td class="muted"><?= e($c['slug']) ?></td>
            <td><?= e(local_num((int)$c['polls_count'])) ?></td>
            <td class="t-actions">
              <?php if ((int)$c['polls_count'] === 0): ?>
                <button class="icon-btn danger" data-ca="delete" title="<?= e(__('common.delete')) ?>"><?= mpin_icon('trash', 15) ?></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <aside class="panel">
      <div class="panel-head"><h2><?= mpin_icon('plus', 16) ?> <?= e(__('admin.add_category')) ?></h2></div>
      <form id="categoryForm" novalidate>
        <div class="field"><label for="cName"><?= e(__('common.name')) ?></label>
          <input id="cName" class="field" name="name" maxlength="60" required placeholder="e.g. Sports"></div>
        <div class="field"><label for="cSlug"><?= e(__('admin.slug_f')) ?> <span class="muted small">(<?= e(__('admin.slug_hint')) ?>)</span></label>
          <input id="cSlug" class="field" name="slug" maxlength="40" placeholder="sports"></div>
        <div class="field"><label for="cIcon"><?= e(__('admin.icon_f')) ?></label>
          <select id="cIcon" class="select" name="icon">
            <?php foreach (['tag','gamepad','cpu','code','monitor','drive','music','heart','book','users','globe','star','zap','map'] as $ic): ?>
              <option value="<?= e($ic) ?>"><?= e($ic) ?></option>
            <?php endforeach; ?>
          </select></div>
        <p class="form-err" hidden></p>
        <button class="btn btn-primary btn-block" type="submit"><?= e(__('admin.add_category')) ?></button>
      </form>
    </aside>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
