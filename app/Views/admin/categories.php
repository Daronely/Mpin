<?php /** @var array $categories */ \App\Core\View::partial('admin-head', ['section' => 'categories']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <section class="manage-grid">
    <div class="panel table-wrap">
      <table class="table">
        <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Polls</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
          <tr data-cat="<?= (int)$c['id'] ?>">
            <td><?= mpin_icon($c['icon'], 18) ?></td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td class="muted"><?= e($c['slug']) ?></td>
            <td><?= (int)$c['polls_count'] ?></td>
            <td class="t-actions">
              <?php if ((int)$c['polls_count'] === 0): ?>
                <button class="icon-btn danger" data-ca="delete" title="Delete"><?= mpin_icon('trash', 15) ?></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <aside class="panel">
      <div class="panel-head"><h2><?= mpin_icon('plus', 16) ?> Add category</h2></div>
      <form id="categoryForm" novalidate>
        <div class="field"><label for="cName">Name</label>
          <input id="cName" class="field" name="name" maxlength="60" required placeholder="e.g. Sports"></div>
        <div class="field"><label for="cSlug">Slug <span class="muted small">(optional)</span></label>
          <input id="cSlug" class="field" name="slug" maxlength="40" placeholder="sports"></div>
        <div class="field"><label for="cIcon">Icon key</label>
          <select id="cIcon" class="select" name="icon">
            <?php foreach (['tag','gamepad','cpu','code','monitor','drive','music','heart','book','users','globe','star','zap','map'] as $ic): ?>
              <option value="<?= e($ic) ?>"><?= e($ic) ?></option>
            <?php endforeach; ?>
          </select></div>
        <p class="form-err" hidden></p>
        <button class="btn btn-primary btn-block" type="submit">Add category</button>
      </form>
    </aside>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
