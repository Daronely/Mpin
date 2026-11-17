<?php
/** @var array $poll $results $status $canParticipate $alreadyResponded $isFavorite $isOwner $related */
require_once MPIN_APP . '/Views/partials/icons.php';
$cover = upload_url($poll['cover']);
$vendor = ['leaflet', 'cluster', 'heat', 'chart'];
?>
<article class="poll-page">
  <?php if ($cover): ?>
    <div class="poll-hero" style="background-image:url('<?= e($cover) ?>')"></div>
  <?php else: ?>
    <div class="poll-hero poll-hero-fallback"></div>
  <?php endif; ?>

  <div class="container poll-head">
    <div class="poll-head-main">
      <div class="poll-chips">
        <a class="chip chip-cat" href="<?= e(url('/explore?category=' . $poll['category_slug'])) ?>">
          <?= mpin_icon($poll['category_icon'] ?? 'tag', 13) ?> <?= e($poll['category_name']) ?>
        </a>
        <?php if ((int)$poll['is_verified']): ?><span class="chip chip-verified"><?= mpin_icon('check', 12) ?> Verified</span><?php endif; ?>
        <span class="chip chip-status s-<?= e($status) ?>"><?= e(ucfirst($status)) ?></span>
        <?php if ($poll['visibility'] === 'private'): ?><span class="chip"><?= mpin_icon('lock', 12) ?> Private</span><?php endif; ?>
      </div>
      <h1 class="poll-title"><?= e($poll['title']) ?></h1>
      <p class="poll-desc"><?= nl2br(e($poll['description'])) ?></p>
      <div class="poll-meta">
        <a class="meta-creator" href="<?= e(url('/u/' . $poll['username'])) ?>" style="--ac: <?= e($poll['avatar_color'] ?: '#0891b2') ?>">
          <span class="avatar sm"><?= e(initials($poll['display_name'])) ?></span>
          by <strong>@<?= e($poll['username']) ?></strong>
        </a>
        <span class="meta-stat"><?= mpin_icon('users', 15) ?> <strong id="statParticipants"><?= e(num_fmt($results['participants'])) ?></strong> participants</span>
        <span class="meta-stat"><?= mpin_icon('globe', 15) ?> <strong><?= (int)$results['countries'] ?></strong> countries</span>
        <span class="meta-stat"><?= mpin_icon('clock', 15) ?> <?= e(date('M j, Y', strtotime($poll['created_at'] . ' UTC'))) ?></span>
      </div>
    </div>

    <div class="poll-actions">
      <?php if ($canParticipate): ?>
        <a class="btn btn-primary btn-lg poll-cta" href="<?= e(url('/poll/' . $poll['slug'] . '/participate')) ?>">
          <?= mpin_icon('send', 18) ?> <?= $alreadyResponded ? 'Respond again' : 'Participate' ?>
        </a>
      <?php else: ?>
        <button class="btn btn-lg poll-cta" disabled><?= $status === 'scheduled' ? 'Not started yet' : 'Poll closed' ?></button>
      <?php endif; ?>
      <div class="poll-actions-row">
        <button class="btn btn-ghost" id="shareBtn" type="button" data-url="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('share', 16) ?> Share</button>
        <button class="btn btn-ghost fav-btn<?= $isFavorite ? ' active' : '' ?>" id="favBtn" type="button" data-poll="<?= (int)$poll['id'] ?>" <?= current_user() ? '' : 'data-needs-login="1"' ?>>
          <?= mpin_icon('star', 16) ?> <span><?= $isFavorite ? 'Saved' : 'Save' ?></span>
        </button>
        <button class="btn btn-ghost" id="reportBtn" type="button"><?= mpin_icon('flag', 16) ?> Report</button>
        <?php if ($isOwner): ?>
          <a class="btn btn-ghost" href="<?= e(url('/dashboard/poll/' . $poll['id'])) ?>"><?= mpin_icon('settings', 16) ?> Manage</a>
        <?php endif; ?>
      </div>
      <?php if ($alreadyResponded && $canParticipate && !$poll['allow_multiple']): ?>
        <p class="mini-note"><?= mpin_icon('check', 13) ?> You already responded to this poll.</p>
      <?php endif; ?>
      <?php if (!$poll['allow_anonymous'] && !current_user()): ?>
        <p class="mini-note"><?= mpin_icon('info', 13) ?> An account is required to participate.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="container poll-layout">
    <div class="poll-main">
      <!-- ============ MAP ============ -->
      <section class="panel map-panel">
        <div class="panel-head">
          <h2><?= mpin_icon('map', 18) ?> Live results map</h2>
          <div class="map-controls">
            <div class="seg" role="tablist" aria-label="Map mode">
              <button class="seg-btn active" data-mode="cluster" role="tab" aria-selected="true"><?= mpin_icon('layers', 14) ?> Clusters</button>
              <button class="seg-btn" data-mode="heat" role="tab"><?= mpin_icon('zap', 14) ?> Heatmap</button>
            </div>
            <button class="icon-btn" id="mapSearchBtn" type="button" title="Search a place"><?= mpin_icon('search', 16) ?></button>
            <button class="icon-btn" id="mapRefreshBtn" type="button" title="Refresh data"><?= mpin_icon('clock', 16) ?></button>
          </div>
        </div>
        <div class="map-wrap">
          <div id="pollMap" class="map-canvas"></div>
          <div class="map-search" id="mapSearch" hidden>
            <input type="search" id="mapSearchInput" placeholder="Search city, country…" autocomplete="off">
            <ul id="mapSearchResults" class="map-search-results"></ul>
          </div>
        </div>
        <div class="map-legend">
          <span class="muted" id="mapMeta">Loading map data…</span>
          <span class="legend-item"><?= mpin_icon('shield', 13) ?> Locations are approximate &amp; aggregated for privacy</span>
        </div>
      </section>

      <!-- ============ ANALYTICS ============ -->
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('chart', 18) ?> Analytics</h2></div>
        <div class="stat-grid">
          <div class="stat-card"><span class="stat-num" id="stParticipants"><?= e(num_fmt($results['participants'])) ?></span><span class="stat-label">Participants</span></div>
          <div class="stat-card"><span class="stat-num"><?= (int)$results['countries'] ?></span><span class="stat-label">Countries</span></div>
          <div class="stat-card"><span class="stat-num"><?= (int)$results['cities'] ?></span><span class="stat-label">Cities</span></div>
        </div>
        <div class="chart-box"><canvas id="growthChart" height="110" aria-label="Participation growth"></canvas></div>
        <div class="geo-cols">
          <div>
            <h3 class="mini-h"><?= mpin_icon('globe', 14) ?> Top countries</h3>
            <ul class="bar-list" id="topCountries"></ul>
          </div>
          <div>
            <h3 class="mini-h"><?= mpin_icon('pin', 14) ?> Top cities</h3>
            <ul class="bar-list" id="topCities"></ul>
          </div>
        </div>
      </section>

      <!-- ============ ANSWERS ============ -->
      <section class="panel" id="resultsPanel">
        <div class="panel-head"><h2><?= mpin_icon('list', 18) ?> Answers</h2></div>
        <div id="answersArea"></div>
      </section>
    </div>

    <!-- ============ SIDEBAR ============ -->
    <aside class="poll-side">
      <section class="panel" id="filterPanel">
        <div class="panel-head"><h3><?= mpin_icon('filter', 16) ?> Filter by answers</h3></div>
        <div id="mapFilters" class="map-filters"><p class="muted small">Choice questions can filter the map.</p></div>
        <button class="btn btn-ghost btn-sm btn-block" id="clearFilters" type="button" hidden>Clear filters</button>
      </section>

      <?php if ($poll['start_at'] || $poll['end_at']): ?>
      <section class="panel">
        <div class="panel-head"><h3><?= mpin_icon('calendar', 16) ?> Schedule</h3></div>
        <ul class="kv-list">
          <?php if ($poll['start_at']): ?><li><span>Starts</span><strong><?= e(date('M j, Y H:i', strtotime($poll['start_at'] . ' UTC'))) ?> UTC</strong></li><?php endif; ?>
          <?php if ($poll['end_at']): ?><li><span>Ends</span><strong><?= e(date('M j, Y H:i', strtotime($poll['end_at'] . ' UTC'))) ?> UTC</strong></li><?php endif; ?>
          <li><span>Anonymous</span><strong><?= $poll['allow_anonymous'] ? 'Allowed' : 'Requires account' ?></strong></li>
          <li><span>Repeat answers</span><strong><?= $poll['allow_multiple'] ? 'Allowed' : 'Once per person' ?></strong></li>
        </ul>
      </section>
      <?php endif; ?>

      <?php if ($related): ?>
      <section class="panel">
        <div class="panel-head"><h3><?= mpin_icon('compass', 16) ?> More in <?= e($poll['category_name']) ?></h3></div>
        <div class="side-polls">
          <?php foreach (array_slice($related, 0, 3) as $p): if ((int)$p['id'] === (int)$poll['id']) continue; ?>
            <a class="side-poll" href="<?= e(url('/poll/' . $p['slug'])) ?>">
              <strong><?= e($p['title']) ?></strong>
              <span class="muted small"><?= e(num_fmt((int)$p['response_count'])) ?> participants · <?= (int)($p['countries_count'] ?? 0) ?> countries</span>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </aside>
  </div>
</article>

<div class="modal" id="reportModal" hidden>
  <div class="modal-box">
    <h3>Report this poll</h3>
    <form id="reportForm" data-poll="<?= (int)$poll['id'] ?>">
      <div class="field"><label>Reason</label>
        <select name="reason" class="select" required>
          <option value="spam">Spam</option>
          <option value="abuse">Abusive content</option>
          <option value="fake">Misleading / fake</option>
          <option value="privacy">Privacy concern</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="field"><label>Details (optional)</label><textarea name="details" rows="3" maxlength="500" placeholder="Tell us more…"></textarea></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Send report</button>
      </div>
    </form>
  </div>
</div>

<script id="pollData" type="application/json"><?= json_encode([
    'slug' => $poll['slug'],
    'title' => $poll['title'],
    'status' => $status,
    'questions' => array_map(static fn($q) => [
        'id' => (int)$q['id'], 'type' => $q['type'], 'text' => $q['question_text'],
        'options' => array_map(static fn($o) => ['id' => (int)$o['id'], 'text' => $o['option_text']], $q['options']),
    ], \App\Models\Poll::questions((int)$poll['id'])),
], JSON_UNESCAPED_UNICODE) ?></script>
<?php $extraJs = ['js/pages/poll.js']; $wide = true; ?>
