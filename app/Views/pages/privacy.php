<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head"><div><h1><?= e(__('privacy.title')) ?></h1>
    <p class="muted"><?= e(__('privacy.updated', ['date' => local_date(utcnow())])) ?></p></div></div>
  <div class="doc-body">
    <h2><?= e(__('privacy.s1')) ?></h2>
    <p><?= e(__('privacy.s1a')) ?></p>
    <p><?= e(__('privacy.s1b')) ?></p>
    <p><?= e(__('privacy.s1c')) ?></p>

    <h2><?= e(__('privacy.s2')) ?></h2>
    <ul class="doc-list">
      <li><?= e(__('privacy.s2a')) ?></li>
      <li><?= e(__('privacy.s2b')) ?></li>
      <li><?= e(__('privacy.s2c')) ?></li>
      <li><?= e(__('privacy.s2d')) ?></li>
    </ul>

    <h2><?= e(__('privacy.s3')) ?></h2>
    <ul class="doc-list">
      <li><?= e(__('privacy.s3a')) ?></li>
      <li><?= e(__('privacy.s3b')) ?></li>
      <li><?= e(__('privacy.s3c')) ?></li>
    </ul>

    <h2><?= e(__('privacy.s4')) ?></h2>
    <p><?= __('privacy.s4a') ?></p>

    <h2><?= e(__('privacy.s5')) ?></h2>
    <p><?= e(__('privacy.s5a')) ?></p>

    <h2><?= e(__('privacy.s6')) ?></h2>
    <p><?= e(__('privacy.s6a')) ?></p>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
