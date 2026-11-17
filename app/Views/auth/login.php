<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="auth-wrap container">
  <div class="auth-card">
    <div class="auth-hero">
      <?= mpin_logo(44, 'lg') ?>
      <h2>Welcome back</h2>
      <p class="muted">Log in to manage your polls and see your stats.</p>
      <div class="auth-hero-art"><?= mpin_icon('map', 120) ?></div>
    </div>
    <div class="auth-body">
      <h1>Log in to Mpin</h1>
      <form id="loginForm" novalidate>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="field">
          <label for="lLogin">Username or e-mail</label>
          <input id="lLogin" class="field" name="login" type="text" required autocomplete="username" placeholder="you@example.com">
        </div>
        <div class="field">
          <label for="lPass">Password</label>
          <input id="lPass" class="field" name="password" type="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <label class="check-note">
          <input type="checkbox" name="remember" checked> Keep me logged in for 30 days
        </label>
        <p class="form-err" id="loginError" hidden></p>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Log in</button>
      </form>
      <p class="auth-alt">New to Mpin? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
    </div>
  </div>
</section>
<?php $extraJs = ['js/pages/auth.js']; $wide = false; ?>
