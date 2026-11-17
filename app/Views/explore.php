<?php
/** @var array $list $categories $countries $filters */
require_once MPIN_APP . '/Views/partials/icons.php';
?>
<section class="page-head container">
  <div>
    <h1><?= mpin_icon('compass', 26) ?> <?= e(__('explore.title')) ?></h1>
    <p class="muted"><?= e(__('explore.sub', ['n' => local_num(num_fmt($list['total']))])) ?></p>
  </div>
  <div class="head-actions">
    <a class="btn btn-primary" href="<?= e(url('/create')) ?>"><?= mpin_icon('plus', 16) ?> <?= e(__('explore.new_poll')) ?></a>
  </div>
</section>

<section class="container explore-toolbar">
  <form id="exploreForm" method="get" action="<?= e(url('/explore')) ?>" class="toolbar">
    <div class="field search-field">
      <?= mpin_icon('search', 16) ?>
      <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(__('explore.search_ph')) ?>" autocomplete="off">
    </div>
    <select name="category" class="field select" aria-label="<?= e(__('create.category_f')) ?>">
      <option value=""><?= e(__('explore.all_categories')) ?></option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c['slug']) ?>" <?= $filters['category'] === $c['slug'] ? 'selected' : '' ?>><?= e(__('cat.' . $c['slug'])) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="country" class="field select" aria-label="<?= e(__('common.countries')) ?>">
      <option value=""><?= e(__('explore.all_countries')) ?></option>
      <?php foreach ($countries as $c): ?>
        <option value="<?= e($c['country']) ?>" <?= $filters['country'] === $c['country'] ? 'selected' : '' ?>><?= e($c['country']) ?> (<?= e(local_num((int)$c['c'])) ?>)</option>
      <?php endforeach; ?>
    </select>
    <select name="sort" class="field select" aria-label="Sort">
      <option value="latest" <?= $filters['sort'] === 'latest' ? 'selected' : '' ?>><?= e(__('explore.sort_latest')) ?></option>
      <option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>><?= e(__('explore.sort_popular')) ?></option>
      <option value="trending" <?= $filters['sort'] === 'trending' ? 'selected' : '' ?>><?= e(__('explore.sort_trending')) ?></option>
    </select>
    <button class="btn btn-primary" type="submit"><?= e(__('common.filter')) ?></button>
    <?php if ($filters['q'] || $filters['category'] || $filters['country'] || $filters['sort'] !== 'latest'): ?>
      <a class="btn btn-ghost" href="<?= e(url('/explore')) ?>"><?= e(__('common.reset')) ?></a>
    <?php endif; ?>
  </form>
</section>

<section class="container">
  <div id="pollResults" class="poll-grid" aria-live="polite">
    <?php if (!empty($list['items'])): ?>
      <?php foreach ($list['items'] as $p) { \App\Core\View::partial('poll-card', ['p' => $p]); } ?>
    <?php else: ?>
      <div class="empty-state wide-es"><?= mpin_icon('search', 36) ?><p><?= e(__('explore.no_match')) ?></p><a class="btn btn-primary" href="<?= e(url('/explore')) ?>"><?= e(__('explore.clear_filters')) ?></a></div>
    <?php endif; ?>
  </div>

  <?php if ($list['total'] > $list['per']): ?>
    <div class="pagination">
      <?php
        $pages = (int) ceil($list['total'] / $list['per']);
        $cur = $list['page'];
        $qs = $_GET;
        for ($i = 1; $i <= min($pages, 12); $i++):
            $qs['page'] = $i;
            $href = url('/explore') . '?' . http_build_query($qs);
      ?>
        <a class="page-link<?= $i === $cur ? ' active' : '' ?>" href="<?= e($href) ?>"><?= e(local_num($i)) ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>
<?php $extraJs = []; ?>
