<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head"><div><h1><?= e(__('terms.title')) ?></h1>
    <p class="muted"><?= e(__('terms.updated', ['date' => local_date(utcnow())])) ?></p></div></div>
  <div class="doc-body">
    <h2><?= e(__('terms.s1')) ?></h2>
    <p><?= e(__('terms.s1a')) ?></p>
    <h2><?= e(__('terms.s2')) ?></h2>
    <p><?= e(__('terms.s2a')) ?></p>
    <h2><?= e(__('terms.s3')) ?></h2>
    <ul class="doc-list">
      <li><?= e(__('terms.s3a')) ?></li>
      <li><?= e(__('terms.s3b')) ?></li>
      <li><?= e(__('terms.s3c')) ?></li>
      <li><?= e(__('terms.s3d')) ?></li>
      <li><?= e(__('terms.s3e')) ?></li>
    </ul>
    <h2><?= e(__('terms.s4')) ?></h2>
    <p><?= e(__('terms.s4a')) ?></p>
    <h2><?= e(__('terms.s5')) ?></h2>
    <p><?= e(__('terms.s5a')) ?></p>
    <h2><?= e(__('terms.s6')) ?></h2>
    <p><?= e(__('terms.s6a')) ?></p>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
