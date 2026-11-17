<?php
/** @var array $stats $popular $latest $categories $mapData */
require_once MPIN_APP . '/Views/partials/icons.php';
$vendor = ['leaflet', 'cluster'];
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="hero-badge"><?= mpin_icon('sparkles', 15) ?> <?= e(__('home.hero_badge')) ?></span>
      <h1><?= e(__('home.hero_title_1')) ?><br><span class="grad-text"><?= e(__('home.hero_title_2')) ?></span></h1>
      <p class="hero-sub"><?= e(__('home.hero_sub')) ?></p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/create')) ?>"><?= mpin_icon('plus', 18) ?> <?= e(__('home.create_poll')) ?></a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/explore')) ?>"><?= mpin_icon('compass', 18) ?> <?= e(__('home.explore_polls')) ?></a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><strong data-count="<?= (int)$stats['polls'] ?>">0</strong><span><?= e(__('home.stat_polls')) ?></span></div>
        <div class="hero-stat"><strong data-count="<?= (int)$stats['responses'] ?>">0</strong><span><?= e(__('home.stat_responses')) ?></span></div>
        <div class="hero-stat"><strong data-count="<?= (int)$stats['countries'] ?>">0</strong><span><?= e(__('home.stat_countries')) ?></span></div>
        <div class="hero-stat"><strong data-count="<?= (int)$stats['cities'] ?>">0</strong><span><?= e(__('home.stat_cities')) ?></span></div>
      </div>
    </div>
    <div class="hero-map">
      <div class="map-frame">
        <div id="heroMap" class="map-canvas" aria-label="<?= e(__('gmap.activity_map')) ?>"></div>
        <div class="map-frame-badge"><span class="pulse-dot"></span> <?= e(local_num(num_fmt($stats['responses_24h']))) ?> <?= e(__('home.responses_24h')) ?></div>
      </div>
    </div>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('fire', 22) ?> <?= e(__('home.trending_title')) ?></h2>
      <p class="muted"><?= e(__('home.trending_sub')) ?></p>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/explore?sort=trending')) ?>"><?= e(__('common.see_all')) ?></a>
  </div>
  <?php if ($trending): ?>
    <div class="poll-grid">
      <?php foreach ($trending as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    </div>
  <?php else: ?>
    <div class="empty-state"><?= mpin_icon('map', 36) ?><p><?= e(__('home.no_polls')) ?></p><a class="btn btn-primary" href="<?= e(url('/create')) ?>"><?= e(__('home.create_poll')) ?></a></div>
  <?php endif; ?>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('star', 22) ?> <?= e(__('home.popular_title')) ?></h2>
      <p class="muted"><?= e(__('home.popular_sub')) ?></p>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/explore?sort=popular')) ?>"><?= e(__('common.see_all')) ?></a>
  </div>
  <div class="poll-grid">
    <?php foreach (array_slice($popular, 0, 6) as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('clock', 22) ?> <?= e(__('home.fresh_title')) ?></h2>
      <p class="muted"><?= e(__('home.fresh_sub')) ?></p>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/explore')) ?>"><?= e(__('common.see_all')) ?></a>
  </div>
  <div class="poll-grid">
    <?php foreach (array_slice($latest, 0, 6) as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('grid', 22) ?> <?= e(__('home.categories_title')) ?></h2>
      <p class="muted"><?= e(__('home.categories_sub')) ?></p>
    </div>
  </div>
  <div class="cat-grid">
    <?php foreach ($categories as $c): ?>
      <a class="cat-card" href="<?= e(url('/explore?category=' . $c['slug'])) ?>">
        <span class="cat-ico"><?= mpin_icon($c['icon'], 20) ?></span>
        <span class="cat-name"><?= e(__('cat.' . $c['slug'])) ?></span>
        <span class="cat-count"><?= e(local_num(num_fmt((int)$c['poll_count']))) ?> <?= e(__('common.polls')) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section container">
  <div class="cta-band">
    <div class="cta-band-inner">
      <h2><?= mpin_logo(40, 'cta') ?> <?= e(__('home.cta_title')) ?></h2>
      <p><?= e(__('home.cta_sub')) ?></p>
      <div class="hero-actions center">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/register')) ?>"><?= e(__('home.join')) ?></a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/about')) ?>"><?= e(__('home.how_it_works')) ?></a>
      </div>
    </div>
  </div>
</section>

<script id="heroMapData" type="application/json"><?= json_encode($mapData, JSON_UNESCAPED_UNICODE) ?></script>
<?php
$extraJs = ['js/pages/home.js'];
$wide = false;
