<?php /** @var array $user */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container settings">
  <div class="page-head"><div><h1><?= mpin_icon('settings', 26) ?> <?= e(__('set.title')) ?></h1>
    <p class="muted"><?= e(__('set.sub')) ?></p></div></div>

  <div class="settings-grid">
    <div>
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('user', 16) ?> <?= e(__('set.profile')) ?></h2></div>
        <form id="profileForm" novalidate>
          <div class="field">
            <label for="sName"><?= e(__('set.display_name')) ?></label>
            <input id="sName" class="field" name="display_name" maxlength="60" required value="<?= e($user['display_name']) ?>">
          </div>
          <div class="field">
            <label for="sBio"><?= e(__('set.bio')) ?> <span class="muted small">(<?= e(__('common.optional')) ?>)</span></label>
            <textarea id="sBio" class="field" name="bio" rows="3" maxlength="300" placeholder="<?= e(__('set.bio_ph')) ?>"><?= e($user['bio']) ?></textarea>
          </div>
          <div class="field"><label><?= e(__('set.username')) ?></label><input class="field" value="@<?= e($user['username']) ?>" disabled></div>
          <p class="form-err" hidden></p>
          <button class="btn btn-primary" type="submit"><?= e(__('set.save_profile')) ?></button>
        </form>
      </section>

      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('key', 16) ?> <?= e(__('set.password')) ?></h2></div>
        <form id="passwordForm" novalidate>
          <div class="field"><label for="pCur"><?= e(__('set.current_pass')) ?></label>
            <input id="pCur" class="field" name="current_password" type="password" required autocomplete="current-password"></div>
          <div class="field-row">
            <div class="field"><label for="pNew"><?= e(__('set.new_pass')) ?></label>
              <input id="pNew" class="field" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
            <div class="field"><label for="pNew2"><?= e(__('set.repeat_new')) ?></label>
              <input id="pNew2" class="field" name="confirm_password" type="password" required autocomplete="new-password"></div>
          </div>
          <p class="form-err" hidden></p>
          <button class="btn btn-primary" type="submit"><?= e(__('set.change_password')) ?></button>
        </form>
      </section>
    </div>

    <aside>
      <section class="panel danger-panel">
        <div class="panel-head"><h2><?= mpin_icon('shield', 16) ?> <?= e(__('set.privacy')) ?></h2></div>
        <p class="muted small"><?= e(__('set.privacy_note')) ?></p>
        <ul class="privacy-points">
          <li><?= mpin_icon('check', 14) ?> <?= e(__('set.pp1')) ?></li>
          <li><?= mpin_icon('check', 14) ?> <?= e(__('set.pp2')) ?></li>
          <li><?= mpin_icon('check', 14) ?> <?= e(__('set.pp3')) ?></li>
          <li><?= mpin_icon('check', 14) ?> <?= e(__('set.pp4')) ?></li>
        </ul>
        <p class="muted small"><a href="<?= e(url('/my-data')) ?>"><?= mpin_icon('shield', 13) ?> <?= e(__('footer.my_data')) ?> →</a></p>
        <form id="wipeForm" data-confirm="Type DELETE to confirm">
          <label class="check-note"><input type="checkbox" id="wipeCheck"> <?= e(__('set.wipe_check')) ?></label>
          <div class="field"><input class="field" id="wipeConfirm" placeholder="<?= e(__('set.wipe_confirm_ph')) ?>" maxlength="6" disabled></div>
          <button class="btn btn-danger" type="submit" id="wipeBtn" disabled><?= mpin_icon('trash', 15) ?> <?= e(__('set.delete_responses')) ?></button>
        </form>
      </section>
    </aside>
  </div>
</section>
<?php $extraJs = ['js/pages/settings.js']; $wide = false; ?>
