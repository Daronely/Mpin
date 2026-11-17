<?php /** @var array $settings */ \App\Core\View::partial('admin-head', ['section' => 'settings']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <section class="manage-grid">
    <div class="panel">
      <div class="panel-head"><h2><?= mpin_icon('settings', 16) ?> <?= e(__('admin.site_settings')) ?></h2></div>
      <form id="siteSettingsForm" novalidate>
        <div class="field">
          <label for="stTagline"><?= e(__('admin.tagline_f')) ?></label>
          <input id="stTagline" class="field" name="site_tagline" maxlength="120" value="<?= e($settings['site_tagline']) ?>">
        </div>
        <label class="switch-row">
          <span><strong><?= e(__('admin.maintenance')) ?></strong>
            <span class="muted small"><?= e(__('admin.maintenance_sub')) ?></span></span>
          <input type="checkbox" name="maintenance" <?= $settings['maintenance'] === '1' ? 'checked' : '' ?>>
        </label>
        <p class="form-err" hidden></p>
        <button class="btn btn-primary" type="submit"><?= e(__('common.save')) ?></button>
      </form>
    </div>
    <aside class="panel">
      <div class="panel-head"><h2><?= mpin_icon('info', 16) ?> <?= e(__('admin.system')) ?></h2></div>
      <ul class="kv-list">
        <li><span><?= e(__('admin.version')) ?></span><strong><?= e(MPIN_VERSION) ?></strong></li>
        <li><span><?= e(__('admin.php')) ?></span><strong><?= e(PHP_VERSION) ?></strong></li>
        <li><span><?= e(__('admin.database')) ?></span><strong><?= e(\App\Core\Database::value('SELECT VERSION()') ?? '—') ?></strong></li>
        <li><span><?= e(__('admin.grid')) ?></span><strong><?= e(local_num((string)PRIVACY_GRID)) ?>° (~<?= e(local_num((string)round(PRIVACY_GRID * 111, 1))) ?> km)</strong></li>
        <li><span><?= e(__('admin.min_cell')) ?></span><strong><?= e(__('admin.min_cell_v', ['n' => local_num((int)PRIVACY_MIN_CELL)])) ?></strong></li>
      </ul>
    </aside>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
