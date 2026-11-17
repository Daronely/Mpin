<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head"><div>
    <span class="hero-badge"><?= mpin_icon('sparkles', 15) ?> <?= e(__('home.hero_badge')) ?></span>
    <h1><?= mpin_logo(40, 'ab') ?> <?= e(__('about.title')) ?></h1>
  </div></div>

  <div class="doc-body">
    <p class="lead"><?= e(__('about.lead')) ?></p>

    <h2><?= mpin_icon('map', 18) ?> <?= e(__('about.how')) ?></h2>
    <ol class="how-steps">
      <li><?= __('about.s1') ?></li>
      <li><?= __('about.s2') ?></li>
      <li><?= __('about.s3') ?></li>
      <li><?= __('about.s4') ?></li>
    </ol>

    <h2><?= mpin_icon('shield', 18) ?> <?= e(__('about.privacy_title')) ?></h2>
    <p><?= e(__('about.privacy_body')) ?></p>

    <h2><?= mpin_icon('cpu', 18) ?> <?= e(__('about.built_title')) ?></h2>
    <p><?= e(__('about.built_body')) ?></p>

    <div class="cta-band slim">
      <div class="cta-band-inner">
        <h3><?= e(__('about.cta')) ?></h3>
        <div class="hero-actions center">
          <a class="btn btn-primary btn-lg" href="<?= e(url('/create')) ?>"><?= e(__('about.first_poll')) ?></a>
          <a class="btn btn-ghost btn-lg" href="<?= e(url('/explore')) ?>"><?= e(__('nav.explore')) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
