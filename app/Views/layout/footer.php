</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand" href="<?= e(url('/')) ?>"><?= mpin_logo(28, 'ft') ?><span class="brand-name">Mpin</span></a>
      <p class="tagline">People Answer. The World Appears.</p>
      <p class="footer-note">An interactive community mapping &amp; polling platform. Built with PHP, MySQL, Leaflet &amp; Chart.js.</p>
    </div>
    <nav class="footer-col" aria-label="Product">
      <h4>Product</h4>
      <a href="<?= e(url('/explore')) ?>">Explore polls</a>
      <a href="<?= e(url('/map')) ?>">Global map</a>
      <a href="<?= e(url('/create')) ?>">Create a poll</a>
      <a href="<?= e(url('/about')) ?>">About Mpin</a>
    </nav>
    <nav class="footer-col" aria-label="Legal">
      <h4>Legal</h4>
      <a href="<?= e(url('/privacy-policy')) ?>">Privacy policy</a>
      <a href="<?= e(url('/terms')) ?>">Terms of service</a>
    </nav>
    <div class="footer-col">
      <h4>Powered by</h4>
      <span class="footer-tech">OpenStreetMap · Leaflet · Chart.js</span>
      <p class="footer-note">&copy; <?= date('Y') ?> Mpin — Map data © OpenStreetMap contributors.</p>
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
