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
          <?= mpin_icon($poll['category_icon'] ?? 'tag', 13) ?> <?= e(__('cat.' . $poll['category_slug'])) ?>
        </a>
        <?php if ((int)$poll['is_verified']): ?><span class="chip chip-verified"><?= mpin_icon('check', 12) ?> <?= e(__('common.verified')) ?></span><?php endif; ?>
        <span class="chip chip-status s-<?= e($status) ?>"><?= e(__('status.' . $status)) ?></span>
        <?php if ($poll['visibility'] === 'private'): ?><span class="chip"><?= mpin_icon('lock', 12) ?> <?= e(__('common.private')) ?></span><?php endif; ?>
      </div>
      <h1 class="poll-title"><?= e($poll['title']) ?></h1>
      <p class="poll-desc"><?= nl2br(e($poll['description'])) ?></p>
      <div class="poll-meta">
        <a class="meta-creator" href="<?= e(url('/u/' . $poll['username'])) ?>" style="--ac: <?= e($poll['avatar_color'] ?: '#0891b2') ?>">
          <span class="avatar sm"><?= e(initials($poll['display_name'])) ?></span>
          <?= e(__('common.by')) ?> <strong>@<?= e($poll['username']) ?></strong>
        </a>
        <span class="meta-stat"><?= mpin_icon('users', 15) ?> <strong id="statParticipants"><?= e(local_num(num_fmt($results['participants']))) ?></strong> <?= e(__('common.participants')) ?></span>
        <span class="meta-stat"><?= mpin_icon('globe', 15) ?> <strong><?= e(local_num((int)$results['countries'])) ?></strong> <?= e(__('common.countries')) ?></span>
        <span class="meta-stat"><?= mpin_icon('clock', 15) ?> <?= e(local_date($poll['created_at'])) ?></span>
      </div>
    </div>

    <div class="poll-actions">
      <?php if ($canParticipate): ?>
        <a class="btn btn-primary btn-lg poll-cta" href="<?= e(url('/poll/' . $poll['slug'] . '/participate')) ?>">
          <?= mpin_icon('send', 18) ?> <?= e($alreadyResponded ? __('poll.respond_again') : __('poll.participate')) ?>
        </a>
      <?php else: ?>
        <button class="btn btn-lg poll-cta" disabled><?= e($status === 'scheduled' ? __('poll.not_started') : __('poll.poll_closed')) ?></button>
      <?php endif; ?>
      <div class="poll-actions-row">
        <button class="btn btn-ghost" id="shareBtn" type="button" data-url="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('share', 16) ?> <?= e(__('common.share')) ?></button>
        <button class="btn btn-ghost fav-btn<?= $isFavorite ? ' active' : '' ?>" id="favBtn" type="button" data-poll="<?= (int)$poll['id'] ?>" <?= current_user() ? '' : 'data-needs-login="1"' ?>>
          <?= mpin_icon('star', 16) ?> <span><?= e($isFavorite ? __('common.saved') : __('common.save')) ?></span>
        </button>
        <button class="btn btn-ghost" id="reportBtn" type="button"><?= mpin_icon('flag', 16) ?> <?= e(__('common.report')) ?></button>
        <?php if ($isOwner): ?>
          <a class="btn btn-ghost" href="<?= e(url('/dashboard/poll/' . $poll['id'])) ?>"><?= mpin_icon('settings', 16) ?> <?= e(__('common.manage')) ?></a>
        <?php endif; ?>
      </div>
      <?php if ($alreadyResponded && $canParticipate && !$poll['allow_multiple']): ?>
        <p class="mini-note"><?= mpin_icon('check', 13) ?> <?= e(__('poll.already_responded')) ?></p>
      <?php endif; ?>
      <?php if (!$poll['allow_anonymous'] && !current_user()): ?>
        <p class="mini-note"><?= mpin_icon('info', 13) ?> <?= e(__('poll.account_required')) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="container poll-layout">
    <div class="poll-main">
      <!-- ============ MAP ============ -->
      <section class="panel map-panel">
        <div class="panel-head">
          <h2><?= mpin_icon('map', 18) ?> <?= e(__('poll.live_map')) ?></h2>
          <div class="map-controls">
            <div class="seg" role="tablist" aria-label="Map mode">
              <button class="seg-btn active" data-mode="cluster" role="tab" aria-selected="true"><?= mpin_icon('layers', 14) ?> <?= e(__('poll.clusters')) ?></button>
              <button class="seg-btn" data-mode="heat" role="tab"><?= mpin_icon('zap', 14) ?> <?= e(__('poll.heatmap')) ?></button>
            </div>
            <button class="icon-btn" id="mapSearchBtn" type="button" title="<?= e(__('poll.search_place')) ?>"><?= mpin_icon('search', 16) ?></button>
            <button class="icon-btn" id="mapRefreshBtn" type="button" title="<?= e(__('poll.refresh')) ?>"><?= mpin_icon('clock', 16) ?></button>
          </div>
        </div>
        <div class="map-wrap">
          <div id="pollMap" class="map-canvas"></div>
          <div class="map-search" id="mapSearch" hidden>
            <input type="search" id="mapSearchInput" placeholder="<?= e(__('poll.search_ph')) ?>" autocomplete="off">
            <ul id="mapSearchResults" class="map-search-results"></ul>
          </div>
        </div>
        <div class="map-legend">
          <span class="muted" id="mapMeta"><?= e(__('common.loading')) ?></span>
          <span class="legend-item"><?= mpin_icon('shield', 13) ?> <?= e(__('poll.privacy_note')) ?></span>
        </div>
      </section>

      <!-- ============ ANALYTICS ============ -->
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('chart', 18) ?> <?= e(__('poll.analytics')) ?></h2></div>
        <div class="stat-grid">
          <div class="stat-card"><span class="stat-num" id="stParticipants"><?= e(local_num(num_fmt($results['participants']))) ?></span><span class="stat-label"><?= e(__('common.participants')) ?></span></div>
          <div class="stat-card"><span class="stat-num"><?= e(local_num((int)$results['countries'])) ?></span><span class="stat-label"><?= e(__('common.countries')) ?></span></div>
          <div class="stat-card"><span class="stat-num"><?= e(local_num((int)$results['cities'])) ?></span><span class="stat-label"><?= e(__('common.cities')) ?></span></div>
        </div>
        <div class="chart-box"><canvas id="growthChart" height="110" aria-label="<?= e(__('poll.growth')) ?>"></canvas></div>
        <div class="geo-cols">
          <div>
            <h3 class="mini-h"><?= mpin_icon('globe', 14) ?> <?= e(__('poll.top_countries')) ?></h3>
            <ul class="bar-list" id="topCountries"></ul>
          </div>
          <div>
            <h3 class="mini-h"><?= mpin_icon('pin', 14) ?> <?= e(__('poll.top_cities')) ?></h3>
            <ul class="bar-list" id="topCities"></ul>
          </div>
        </div>
      </section>

      <!-- ============ ANSWERS ============ -->
      <section class="panel" id="resultsPanel">
        <div class="panel-head"><h2><?= mpin_icon('list', 18) ?> <?= e(__('poll.answers')) ?></h2></div>
        <div id="answersArea"></div>
      </section>
    </div>

    <!-- ============ SIDEBAR ============ -->
    <aside class="poll-side">
      <section class="panel" id="filterPanel">
        <div class="panel-head"><h3><?= mpin_icon('filter', 16) ?> <?= e(__('poll.filter_title')) ?></h3></div>
        <div id="mapFilters" class="map-filters"><p class="muted small"><?= e(__('poll.filter_hint')) ?></p></div>
        <button class="btn btn-ghost btn-sm btn-block" id="clearFilters" type="button" hidden><?= e(__('poll.clear_filters')) ?></button>
      </section>

      <section class="panel">
        <div class="panel-head"><h3><?= mpin_icon('calendar', 16) ?> <?= e(__('poll.schedule')) ?></h3></div>
        <ul class="kv-list">
          <?php if ($poll['start_at']): ?><li><span><?= e(__('poll.starts')) ?></span><strong><?= e(local_date($poll['start_at'], true)) ?></strong></li><?php endif; ?>
          <?php if ($poll['end_at']): ?><li><span><?= e(__('poll.ends')) ?></span><strong><?= e(local_date($poll['end_at'], true)) ?></strong></li><?php endif; ?>
          <li><span><?= e(__('poll.participate')) ?></span><strong><?= e($poll['allow_anonymous'] ? __('poll.anon_allowed') : __('poll.anon_required')) ?></strong></li>
          <li><span><?= e(__('create.multiple')) ?></span><strong><?= e($poll['allow_multiple'] ? __('poll.repeat_allowed') : __('poll.repeat_once')) ?></strong></li>
        </ul>
      </section>

      <?php if ($related): ?>
      <section class="panel">
        <div class="panel-head"><h3><?= mpin_icon('compass', 16) ?> <?= e(__('poll.more_in', ['cat' => __('cat.' . $poll['category_slug'])])) ?></h3></div>
        <div class="side-polls">
          <?php foreach (array_slice($related, 0, 3) as $p): if ((int)$p['id'] === (int)$poll['id']) continue; ?>
            <a class="side-poll" href="<?= e(url('/poll/' . $p['slug'])) ?>">
              <strong><?= e($p['title']) ?></strong>
              <span class="muted small"><?= e(local_num(num_fmt((int)$p['response_count']))) ?> <?= e(__('common.participants')) ?> · <?= e(local_num((int)($p['countries_count'] ?? 0))) ?> <?= e(__('common.countries')) ?></span>
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
    <h3><?= e(__('poll.report_title')) ?></h3>
    <form id="reportForm" data-poll="<?= (int)$poll['id'] ?>">
      <div class="field"><label><?= e(__('poll.report_reason')) ?></label>
        <select name="reason" class="select" required>
          <option value="spam">Spam</option>
          <option value="abuse">Abusive</option>
          <option value="fake">Fake / misleading</option>
          <option value="privacy">Privacy</option>
          <option value="other"><?= e(__('cat.other')) ?></option>
        </select>
      </div>
      <div class="field"><label><?= e(__('poll.report_details')) ?></label><textarea name="details" rows="3" maxlength="500" placeholder="<?= e(__('poll.report_details_ph')) ?>"></textarea></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" data-close><?= e(__('common.cancel')) ?></button>
        <button type="submit" class="btn btn-primary"><?= e(__('poll.report_send')) ?></button>
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
