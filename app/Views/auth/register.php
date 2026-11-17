<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="auth-wrap container">
  <div class="auth-card">
    <div class="auth-hero">
      <?= mpin_logo(44, 'rg') ?>
      <h2>Join Mpin</h2>
      <p class="muted">Create polls, watch the world answer, and keep your favorites in one dashboard.</p>
      <div class="auth-hero-art"><?= mpin_icon('pin', 120) ?></div>
    </div>
    <div class="auth-body">
      <h1>Create your account</h1>
      <form id="registerForm" novalidate>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="field">
          <label for="rUser">Username</label>
          <input id="rUser" class="field" name="username" type="text" required autocomplete="username"
                 pattern="[a-zA-Z0-9_]{3,30}" placeholder="lowercase, digits, underscore">
        </div>
        <div class="field">
          <label for="rName">Display name</label>
          <input id="rName" class="field" name="display_name" type="text" maxlength="60" placeholder="How should we show you?">
        </div>
        <div class="field">
          <label for="rMail">E-mail</label>
          <input id="rMail" class="field" name="email" type="email" required autocomplete="email" placeholder="you@example.com">
        </div>
        <div class="field-row">
          <div class="field">
            <label for="rPass">Password</label>
            <input id="rPass" class="field" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="8+ characters">
          </div>
          <div class="field">
            <label for="rPass2">Repeat password</label>
            <input id="rPass2" class="field" name="confirm_password" type="password" required autocomplete="new-password" placeholder="repeat it">
          </div>
        </div>
        <p class="form-err" id="registerError" hidden></p>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Create account</button>
        <p class="muted small auth-legal">By signing up you accept the <a href="<?= e(url('/terms')) ?>">Terms</a> and <a href="<?= e(url('/privacy-policy')) ?>">Privacy Policy</a>.</p>
      </form>
      <p class="auth-alt">Already have an account? <a href="<?= e(url('/login')) ?>">Log in</a></p>
    </div>
  </div>
</section>
<?php $extraJs = ['js/pages/auth.js']; $wide = false; ?>
