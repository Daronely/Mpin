<?php /** @var array $stats */ require_once MPIN_APP . '/Views/partials/icons.php'; $vendor = ['leaflet', 'cluster', 'heat']; ?>
<section class="page-head container wide-head">
  <div>
    <span class="hero-badge"><?= mpin_icon('zap', 15) ?> Global Pulse</span>
    <h1><?= mpin_icon('globe', 28) ?> The world, answering</h1>
    <p class="muted">Every response ever given on Mpin, aggregated on one live map. Refreshes automatically.</p>
  </div>
  <div class="head-actions map-head-stats">
    <div class="hero-stat"><strong><?= e(num_fmt($stats['responses'])) ?></strong><span>Responses</span></div>
    <div class="hero-stat"><strong><?= e(num_fmt($stats['countries'])) ?></strong><span>Countries</span></div>
    <div class="hero-stat"><strong><?= e(num_fmt($stats['cities'])) ?></strong><span>Cities</span></div>
  </div>
</section>

<section class="container wide-map-section">
  <div class="panel map-panel">
    <div class="panel-head">
      <h2><?= mpin_icon('map', 18) ?> Global activity map</h2>
      <div class="map-controls">
        <div class="seg" role="tablist" aria-label="Map mode">
          <button class="seg-btn active" data-mode="cluster" role="tab" aria-selected="true"><?= mpin_icon('layers', 14) ?> Clusters</button>
          <button class="seg-btn" data-mode="heat" role="tab"><?= mpin_icon('zap', 14) ?> Heatmap</button>
        </div>
        <button class="icon-btn" id="mapSearchBtn" type="button" title="Search a place"><?= mpin_icon('search', 16) ?></button>
      </div>
    </div>
    <div class="map-wrap map-wrap-tall">
      <div id="globalMap" class="map-canvas"></div>
      <div class="map-search" id="mapSearch" hidden>
        <input type="search" id="mapSearchInput" placeholder="Search city, country…" autocomplete="off">
        <ul id="mapSearchResults" class="map-search-results"></ul>
      </div>
    </div>
    <div class="map-legend">
      <span class="muted" id="mapMeta">Loading…</span>
      <span class="legend-item"><?= mpin_icon('shield', 13) ?> Approximate, aggregated locations only</span>
    </div>
  </div>
</section>
<?php $extraJs = ['js/pages/map-global.js']; $wide = true; ?>
