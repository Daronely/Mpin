<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="auth-wrap container">
  <div class="auth-card">
    <div class="auth-hero">
      <?= mpin_logo(44, 'rg') ?>
      <h2><?= e(__('auth.register_welcome')) ?></h2>
      <p class="muted"><?= e(__('auth.register_sub')) ?></p>
      <div class="auth-hero-art"><?= mpin_icon('pin', 120) ?></div>
    </div>
    <div class="auth-body">
      <h1><?= e(__('auth.register_title')) ?></h1>
      <form id="registerForm" novalidate>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="field">
          <label for="rUser"><?= e(__('auth.username_f')) ?></label>
          <input id="rUser" class="field" name="username" type="text" required autocomplete="username"
                 pattern="[a-zA-Z0-9_]{3,30}" placeholder="<?= e(__('auth.username_hint')) ?>">
        </div>
        <div class="field">
          <label for="rName"><?= e(__('auth.display_name')) ?></label>
          <input id="rName" class="field" name="display_name" type="text" maxlength="60" placeholder="<?= e(__('auth.display_ph')) ?>">
        </div>
        <div class="field">
          <label for="rMail"><?= e(__('auth.email_f')) ?></label>
          <input id="rMail" class="field" name="email" type="email" required autocomplete="email" placeholder="you@example.com">
        </div>
        <div class="field-row">
          <div class="field">
            <label for="rPass"><?= e(__('auth.pass_f')) ?></label>
            <input id="rPass" class="field" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="<?= e(__('auth.pass_hint')) ?>">
          </div>
          <div class="field">
            <label for="rPass2"><?= e(__('auth.pass2_f')) ?></label>
            <input id="rPass2" class="field" name="confirm_password" type="password" required autocomplete="new-password" placeholder="<?= e(__('auth.repeat_ph')) ?>">
          </div>
        </div>
        <p class="form-err" id="registerError" hidden></p>
        <button class="btn btn-primary btn-lg btn-block" type="submit"><?= e(__('auth.register_btn')) ?></button>
        <p class="muted small auth-legal"><?= e(__('auth.terms_note')) ?> <a href="<?= e(url('/terms')) ?>"><?= e(__('footer.terms')) ?></a> <?= e(__('auth.and')) ?> <a href="<?= e(url('/privacy-policy')) ?>"><?= e(__('footer.privacy')) ?></a>.</p>
      </form>
      <p class="auth-alt"><?= e(__('auth.have_account')) ?> <a href="<?= e(url('/login')) ?>"><?= e(__('nav.login')) ?></a></p>
    </div>
  </div>
</section>
<?php $extraJs = ['js/pages/auth.js']; $wide = false; ?>
