<?php /** @var string $heading $message $code */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container error-page">
  <div class="error-box">
    <div class="error-ico"><?= mpin_logo(84, 'err') ?></div>
    <h1><?= e($heading ?? 'Something went wrong') ?></h1>
    <p class="muted"><?= e($message ?? 'An unexpected error occurred.') ?></p>
    <div class="hero-actions center">
      <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= mpin_icon('home', 16) ?> Back home</a>
      <a class="btn btn-ghost" href="<?= e(url('/explore')) ?>"><?= mpin_icon('compass', 16) ?> Explore polls</a>
    </div>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
