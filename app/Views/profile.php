<?php /** @var array $profile $polls */ require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container profile">
  <div class="profile-head panel">
    <span class="avatar xxl" style="--ac: <?= e($profile['avatar_color'] ?: '#0891b2') ?>"><?= e(initials($profile['display_name'])) ?></span>
    <div class="profile-info">
      <h1><?= e($profile['display_name']) ?></h1>
      <p class="muted">@<?= e($profile['username']) ?> · joined <?= e(date('M Y', strtotime($profile['created_at'] . ' UTC'))) ?>
        <?php if ($profile['role'] === 'admin'): ?><span class="chip chip-verified"><?= mpin_icon('shield', 12) ?> Admin</span><?php endif; ?>
      </p>
      <?php if ($profile['bio']): ?><p class="profile-bio"><?= e($profile['bio']) ?></p><?php endif; ?>
    </div>
    <div class="profile-stats">
      <div class="hero-stat"><strong><?= (int)$profile['polls_count'] ?></strong><span>Polls</span></div>
      <div class="hero-stat"><strong><?= e(num_fmt((int)$profile['responses_count'])) ?></strong><span>Answers</span></div>
      <div class="hero-stat"><strong><?= (int)$profile['countries_count'] ?></strong><span>Countries</span></div>
    </div>
  </div>

  <section class="dash-section">
    <div class="section-head"><h2><?= mpin_icon('grid', 20) ?> Public polls</h2></div>
    <?php if ($polls): ?>
      <div class="poll-grid">
        <?php foreach ($polls as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><?= mpin_icon('map', 32) ?><p>No public polls yet.</p></div>
    <?php endif; ?>
  </section>
</section>
<?php $extraJs = []; $wide = false; ?>
