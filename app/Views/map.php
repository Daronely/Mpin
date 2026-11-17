<?php /** @var array $stats */ require_once MPIN_APP . '/Views/partials/icons.php'; $vendor = ['leaflet', 'cluster', 'heat']; ?>
<section class="page-head container wide-head">
  <div>
    <span class="hero-badge"><?= mpin_icon('zap', 15) ?> <?= e(__('gmap.badge')) ?></span>
    <h1><?= mpin_icon('globe', 28) ?> <?= e(__('gmap.title')) ?></h1>
    <p class="muted"><?= e(__('gmap.sub')) ?></p>
  </div>
  <div class="head-actions map-head-stats">
    <div class="hero-stat"><strong><?= e(local_num(num_fmt($stats['responses']))) ?></strong><span><?= e(__('common.responses')) ?></span></div>
    <div class="hero-stat"><strong><?= e(local_num(num_fmt($stats['countries']))) ?></strong><span><?= e(__('common.countries')) ?></span></div>
    <div class="hero-stat"><strong><?= e(local_num(num_fmt($stats['cities']))) ?></strong><span><?= e(__('common.cities')) ?></span></div>
  </div>
</section>

<section class="container wide-map-section">
  <div class="panel map-panel">
    <div class="panel-head">
      <h2><?= mpin_icon('map', 18) ?> <?= e(__('gmap.activity_map')) ?></h2>
      <div class="map-controls">
        <div class="seg" role="tablist" aria-label="Map mode">
          <button class="seg-btn active" data-mode="cluster" role="tab" aria-selected="true"><?= mpin_icon('layers', 14) ?> <?= e(__('poll.clusters')) ?></button>
          <button class="seg-btn" data-mode="heat" role="tab"><?= mpin_icon('zap', 14) ?> <?= e(__('poll.heatmap')) ?></button>
        </div>
        <button class="icon-btn" id="mapSearchBtn" type="button" title="<?= e(__('poll.search_place')) ?>"><?= mpin_icon('search', 16) ?></button>
      </div>
    </div>
    <div class="map-wrap map-wrap-tall">
      <div id="globalMap" class="map-canvas"></div>
      <div class="map-search" id="mapSearch" hidden>
        <input type="search" id="mapSearchInput" placeholder="<?= e(__('poll.search_ph')) ?>" autocomplete="off">
        <ul id="mapSearchResults" class="map-search-results"></ul>
      </div>
    </div>
    <div class="map-legend">
      <span class="muted" id="mapMeta"><?= e(__('common.loading')) ?></span>
      <span class="legend-item"><?= mpin_icon('shield', 13) ?> <?= e(__('poll.privacy_note')) ?></span>
    </div>
  </div>
</section>
<?php $extraJs = ['js/pages/map-global.js']; $wide = true; ?>
