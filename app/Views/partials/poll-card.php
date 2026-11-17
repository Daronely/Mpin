<?php
/**
 * Poll card partial.
 * Expects: $p (poll row with category_*, username, display_name, response_count, countries_count…)
 */
require_once MPIN_APP . '/Views/partials/icons.php';
$cover = upload_url($p['cover'] ?? null);
?>
<article class="poll-card">
  <a class="poll-card-link" href="<?= e(url('/poll/' . $p['slug'])) ?>">
    <div class="poll-card-media<?= $cover ? '' : ' no-cover' ?>">
      <?php if ($cover): ?>
        <img src="<?= e($cover) ?>" alt="" loading="lazy">
      <?php else: ?>
        <div class="poll-card-pattern"><?= mpin_icon('pin', 44) ?></div>
      <?php endif; ?>
      <span class="chip chip-cat"><?= mpin_icon($p['category_icon'] ?? 'tag', 13) ?> <?= e($p['category_name'] ?? ($p['category_slug'] ?? '')) ?></span>
      <?php if (!empty($p['is_verified'])): ?><span class="chip chip-verified" title="Verified poll"><?= mpin_icon('check', 12) ?> Verified</span><?php endif; ?>
    </div>
    <div class="poll-card-body">
      <h3 class="poll-card-title"><?= e($p['title']) ?></h3>
      <p class="poll-card-desc"><?= e(mb_strimwidth((string)($p['description'] ?? ''), 0, 110, '…')) ?></p>
      <div class="poll-card-meta">
        <span class="meta-creator" style="--ac: <?= e($p['avatar_color'] ?? '#0891b2') ?>">
          <?= e(initials($p['display_name'] ?? '?')) ?>
          <span>@<?= e($p['username'] ?? '') ?></span>
        </span>
        <span class="meta-stat" title="Participants"><?= mpin_icon('users', 14) ?> <?= e(num_fmt((int)($p['response_count'] ?? 0))) ?></span>
        <span class="meta-stat" title="Countries"><?= mpin_icon('globe', 14) ?> <?= e(num_fmt((int)($p['countries_count'] ?? 0))) ?></span>
      </div>
      <?php if (($p['responses_7d'] ?? 0) > 2): ?>
        <span class="trend-badge"><?= mpin_icon('fire', 13) ?> <?= (int)$p['responses_7d'] ?> this week</span>
      <?php endif; ?>
    </div>
  </a>
</article>
