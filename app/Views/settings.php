<?php /** @var array $user */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container settings">
  <div class="page-head"><div><h1><?= mpin_icon('settings', 26) ?> Settings</h1>
    <p class="muted">Manage your profile, account and privacy.</p></div></div>

  <div class="settings-grid">
    <div>
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('user', 16) ?> Profile</h2></div>
        <form id="profileForm" novalidate>
          <div class="field">
            <label for="sName">Display name</label>
            <input id="sName" class="field" name="display_name" maxlength="60" required value="<?= e($user['display_name']) ?>">
          </div>
          <div class="field">
            <label for="sBio">Bio <span class="muted small">(max 300)</span></label>
            <textarea id="sBio" class="field" name="bio" rows="3" maxlength="300" placeholder="A few words about you…"><?= e($user['bio']) ?></textarea>
          </div>
          <div class="field"><label>Username</label><input class="field" value="@<?= e($user['username']) ?>" disabled></div>
          <p class="form-err" hidden></p>
          <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
      </section>

      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('key', 16) ?> Password</h2></div>
        <form id="passwordForm" novalidate>
          <div class="field"><label for="pCur">Current password</label>
            <input id="pCur" class="field" name="current_password" type="password" required autocomplete="current-password"></div>
          <div class="field-row">
            <div class="field"><label for="pNew">New password</label>
              <input id="pNew" class="field" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
            <div class="field"><label for="pNew2">Repeat new password</label>
              <input id="pNew2" class="field" name="confirm_password" type="password" required autocomplete="new-password"></div>
          </div>
          <p class="form-err" hidden></p>
          <button class="btn btn-primary" type="submit">Change password</button>
        </form>
      </section>
    </div>

    <aside>
      <section class="panel danger-panel">
        <div class="panel-head"><h2><?= mpin_icon('shield', 16) ?> Privacy</h2></div>
        <p class="muted small">Your answers are always stored with approximate, grid-snapped locations. You can erase all of them at any time.</p>
        <ul class="privacy-points">
          <li><?= mpin_icon('check', 14) ?> Locations snap to a ~5 km grid</li>
          <li><?= mpin_icon('check', 14) ?> Sparse areas stay hidden (k-anonymity)</li>
          <li><?= mpin_icon('check', 14) ?> No exact coordinates ever stored</li>
          <li><?= mpin_icon('check', 14) ?> Rate limits protect against spam</li>
        </ul>
        <form id="wipeForm" data-confirm="Type DELETE to confirm">
          <label class="check-note"><input type="checkbox" id="wipeCheck"> I understand this deletes every response I've given (answers + map pins)</label>
          <div class="field"><input class="field" id="wipeConfirm" placeholder="Type DELETE" maxlength="6" disabled></div>
          <button class="btn btn-danger" type="submit" id="wipeBtn" disabled><?= mpin_icon('trash', 15) ?> Delete my responses</button>
        </form>
      </section>
    </aside>
  </div>
</section>
<?php $extraJs = ['js/pages/settings.js']; $wide = false; ?>
