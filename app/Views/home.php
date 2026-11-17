<?php
/** @var array $stats $popular $latest $categories $mapData */
require_once MPIN_APP . '/Views/partials/icons.php';
$vendor = ['leaflet', 'cluster'];  // hero mini-map
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="hero-badge"><?= mpin_icon('sparkles', 15) ?> Interactive community mapping</span>
      <h1>Ask the world.<br><span class="grad-text">Watch it appear.</span></h1>
      <p class="hero-sub">
            Mpin turns public polls into living maps. Create a poll, share the link,
            and see answers and participants paint the planet — city by city, country by country.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/create')) ?>"><?= mpin_icon('plus', 18) ?> Create your poll</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/explore')) ?>"><?= mpin_icon('compass', 18) ?> Explore polls</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat"><strong data-count="<?= (int)$stats['polls'] ?>">0</strong><span>Live polls</span></div>
        <div class="hero-stat"><strong data-count="<?= (int)$stats['responses'] ?>">0</strong><span>Responses</span></div>
        <div class="hero-stat"><strong data-count="<?= (int)$stats['countries'] ?>">0</strong><span>Countries</span></div>
        <div class="hero-stat"><strong data-count="<?= (int)$stats['cities'] ?>">0</strong><span>Cities</span></div>
      </div>
    </div>
    <div class="hero-map">
      <div class="map-frame">
        <div id="heroMap" class="map-canvas" aria-label="World map preview"></div>
        <div class="map-frame-badge"><span class="pulse-dot"></span> <?= e(num_fmt($stats['responses_24h'])) ?> responses in the last 24h</div>
      </div>
    </div>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('fire', 22) ?> Trending right now</h2>
      <p class="muted">The polls collecting the most answers this week.</p>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/explore?sort=trending')) ?>">See all <?= mpin_icon('arrow-right', 15) ?></a>
  </div>
  <?php if ($trending): ?>
    <div class="poll-grid">
      <?php foreach ($trending as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    </div>
  <?php else: ?>
    <div class="empty-state"><?= mpin_icon('map', 36) ?><p>No polls yet — be the first to create one!</p><a class="btn btn-primary" href="<?= e(url('/create')) ?>">Create a poll</a></div>
  <?php endif; ?>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('star', 22) ?> Most popular</h2>
      <p class="muted">All-time favorites of the community.</p>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/explore?sort=popular')) ?>">See all <?= mpin_icon('arrow-right', 15) ?></a>
  </div>
  <div class="poll-grid">
    <?php foreach (array_slice($popular, 0, 6) as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('clock', 22) ?> Fresh polls</h2>
      <p class="muted">Just launched — your answer could be the first pin.</p>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/explore')) ?>">See all <?= mpin_icon('arrow-right', 15) ?></a>
  </div>
  <div class="poll-grid">
    <?php foreach (array_slice($latest, 0, 6) as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
  </div>
</section>

<section class="section container">
  <div class="section-head">
    <div>
      <h2><?= mpin_icon('grid', 22) ?> Browse by category</h2>
      <p class="muted">From gaming to operating systems — find your community.</p>
    </div>
  </div>
  <div class="cat-grid">
    <?php foreach ($categories as $c): ?>
      <a class="cat-card" href="<?= e(url('/explore?category=' . $c['slug'])) ?>">
        <span class="cat-ico"><?= mpin_icon($c['icon'], 20) ?></span>
        <span class="cat-name"><?= e($c['name']) ?></span>
        <span class="cat-count"><?= (int)$c['poll_count'] ?> poll<?= (int)$c['poll_count'] === 1 ? '' : 's' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section container">
  <div class="cta-band">
    <div class="cta-band-inner">
      <h2><?= mpin_logo(40, 'cta') ?> Ready to put your question on the map?</h2>
      <p>It takes less than a minute. No account needed to answer — only to create.</p>
      <div class="hero-actions center">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/register')) ?>">Join Mpin</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/about')) ?>">How it works</a>
      </div>
    </div>
  </div>
</section>

<script id="heroMapData" type="application/json"><?= json_encode($mapData, JSON_UNESCAPED_UNICODE) ?></script>
<?php
$extraJs = ['js/pages/home.js'];
$wide = false;
