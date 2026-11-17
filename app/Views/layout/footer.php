</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand" href="<?= e(url('/')) ?>"><?= mpin_logo(28, 'ft') ?><span class="brand-name">Mpin</span></a>
      <p class="tagline"><?= e(__('tagline')) ?></p>
      <p class="footer-note"><?= e(__('home.powered_by')) ?></p>
    </div>
    <nav class="footer-col" aria-label="<?= e(__('footer.product')) ?>">
      <h4><?= e(__('footer.product')) ?></h4>
      <a href="<?= e(url('/explore')) ?>"><?= e(__('footer.explore')) ?></a>
      <a href="<?= e(url('/map')) ?>"><?= e(__('footer.global_map')) ?></a>
      <a href="<?= e(url('/create')) ?>"><?= e(__('footer.create')) ?></a>
      <a href="<?= e(url('/about')) ?>"><?= e(__('footer.about')) ?></a>
    </nav>
    <nav class="footer-col" aria-label="<?= e(__('footer.legal')) ?>">
      <h4><?= e(__('footer.legal')) ?></h4>
      <a href="<?= e(url('/privacy-policy')) ?>"><?= e(__('footer.privacy')) ?></a>
      <a href="<?= e(url('/terms')) ?>"><?= e(__('footer.terms')) ?></a>
      <a href="<?= e(url('/my-data')) ?>"><?= e(__('footer.my_data')) ?></a>
    </nav>
    <div class="footer-col">
      <h4><?= e(__('footer.powered_by')) ?></h4>
      <span class="footer-tech">OpenStreetMap · Leaflet · Chart.js</span>
      <p class="footer-note">&copy; <?= local_num(date('Y')) ?> Mpin — <?= e(__('footer.rights')) ?></p>
    </div>
  </div>
</footer>

<div id="toasts" class="toasts" aria-live="polite"></div>

<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach ($vendor ?? [] as $v): ?>
  <?php foreach ($vendorJsMap[$v] ?? [] as $js): ?>
    <script src="<?= e(asset($js)) ?>"></script>
  <?php endforeach; ?>
<?php endforeach; ?>
<?php foreach ($extraJs ?? [] as $js): ?>
  <script src="<?= e(asset($js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
