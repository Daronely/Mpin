<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="auth-wrap container">
  <div class="auth-card">
    <div class="auth-hero">
      <?= mpin_logo(44, 'lg') ?>
      <h2><?= e(__('auth.login_welcome')) ?></h2>
      <p class="muted"><?= e(__('auth.login_sub')) ?></p>
      <div class="auth-hero-art"><?= mpin_icon('map', 120) ?></div>
    </div>
    <div class="auth-body">
      <h1><?= e(__('auth.login_title')) ?></h1>
      <form id="loginForm" novalidate>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="field">
          <label for="lLogin"><?= e(__('auth.username_email')) ?></label>
          <input id="lLogin" class="field" name="login" type="text" required autocomplete="username" placeholder="you@example.com">
        </div>
        <div class="field">
          <label for="lPass"><?= e(__('auth.password')) ?></label>
          <input id="lPass" class="field" name="password" type="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <label class="check-note">
          <input type="checkbox" name="remember" checked> <?= e(__('auth.keep_logged')) ?>
        </label>
        <p class="form-err" id="loginError" hidden></p>
        <button class="btn btn-primary btn-lg btn-block" type="submit"><?= e(__('auth.login_btn')) ?></button>
      </form>
      <p class="auth-alt"><?= e(__('auth.new_here')) ?> <a href="<?= e(url('/register')) ?>"><?= e(__('auth.create_one')) ?></a></p>
    </div>
  </div>
</section>
<?php $extraJs = ['js/pages/auth.js']; $wide = false; ?>
