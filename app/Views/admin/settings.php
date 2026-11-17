<?php /** @var array $settings */ \App\Core\View::partial('admin-head', ['section' => 'settings']);
require_once MPIN_APP . '/Views/partials/icons.php'; ?>
  <section class="manage-grid">
    <div class="panel">
      <div class="panel-head"><h2><?= mpin_icon('settings', 16) ?> Site settings</h2></div>
      <form id="siteSettingsForm" novalidate>
        <div class="field">
          <label for="stTagline">Home tagline</label>
          <input id="stTagline" class="field" name="site_tagline" maxlength="120" value="<?= e($settings['site_tagline']) ?>">
        </div>
        <label class="switch-row">
          <span><strong>Maintenance notice</strong>
            <span class="muted small">Show a banner on the home page</span></span>
          <input type="checkbox" name="maintenance" <?= $settings['maintenance'] === '1' ? 'checked' : '' ?>>
        </label>
        <p class="form-err" hidden></p>
        <button class="btn btn-primary" type="submit">Save settings</button>
      </form>
    </div>
    <aside class="panel">
      <div class="panel-head"><h2><?= mpin_icon('info', 16) ?> System</h2></div>
      <ul class="kv-list">
        <li><span>Mpin version</span><strong><?= e(MPIN_VERSION) ?></strong></li>
        <li><span>PHP</span><strong><?= e(PHP_VERSION) ?></strong></li>
        <li><span>Database</span><strong><?= e(\App\Core\Database::value('SELECT VERSION()') ?? '—') ?></strong></li>
        <li><span>Privacy grid</span><strong><?= e((string)PRIVACY_GRID) ?>° (~<?= e((string)round(PRIVACY_GRID * 111, 1)) ?> km)</strong></li>
        <li><span>Min cell</span><strong><?= (int)PRIVACY_MIN_CELL ?> responses</strong></li>
      </ul>
    </aside>
  </div>
</section>
<?php $extraJs = ['js/pages/admin.js']; $wide = false; ?>
