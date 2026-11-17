<?php
/** @var array $poll $questions $loggedIn */
require_once MPIN_APP . '/Views/partials/icons.php';
$vendor = ['leaflet'];
?>
<section class="container participate">
  <div class="participate-head">
    <a class="crumb" href="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('arrow-right', 14) ?> <?= e(__('part.back_to_poll')) ?></a>
    <h1><?= e($poll['title']) ?></h1>
    <p class="muted">
      <?= e(__('part.questions_count', ['n' => local_num(count($questions))])) ?> ·
      <?= e($poll['allow_anonymous'] || $loggedIn ? __('part.then_pin') : __('part.login_required')) ?>
    </p>
  </div>

  <div class="wizard">
    <ol class="wizard-steps" id="wizardSteps">
      <li class="step active" data-step="1"><span class="step-dot"><?= e(local_num(1)) ?></span> <?= e(__('part.step_answer')) ?></li>
      <li class="step" data-step="2"><span class="step-dot"><?= e(local_num(2)) ?></span> <?= e(__('part.step_location')) ?></li>
      <li class="step" data-step="3"><span class="step-dot"><?= e(local_num(3)) ?></span> <?= e(__('part.step_review')) ?></li>
    </ol>

    <div class="wizard-panels">
      <!-- STEP 1: questions -->
      <div class="wizard-panel active" data-panel="1">
        <form id="answersForm" novalidate>
          <?php foreach ($questions as $i => $q): ?>
            <fieldset class="q-card" data-qid="<?= (int)$q['id'] ?>" data-type="<?= e($q['type']) ?>" data-required="<?= $q['is_required'] ? '1' : '0' ?>">
              <legend>
                <span class="q-num"><?= e(local_num($i + 1)) ?></span>
                <?= e($q['question_text']) ?>
                <?php if ($q['is_required']): ?><span class="req">*</span><?php endif; ?>
              </legend>
              <p class="q-error" hidden></p>

              <?php if ($q['type'] === 'yesno' || $q['type'] === 'single'): ?>
                <div class="opt-list" role="radiogroup">
                  <?php foreach ($q['options'] as $o): ?>
                    <label class="opt">
                      <input type="radio" name="q<?= (int)$q['id'] ?>" value="<?= (int)$o['id'] ?>" <?= $q['is_required'] ? 'required' : '' ?>>
                      <span class="opt-mark"><?= mpin_icon('check', 14) ?></span>
                      <span><?= e($o['option_text']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>

              <?php elseif ($q['type'] === 'multi'): ?>
                <div class="opt-list">
                  <?php foreach ($q['options'] as $o): ?>
                    <label class="opt">
                      <input type="checkbox" name="q<?= (int)$q['id'] ?>[]" value="<?= (int)$o['id'] ?>">
                      <span class="opt-mark"><?= mpin_icon('check', 14) ?></span>
                      <span><?= e($o['option_text']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>

              <?php elseif ($q['type'] === 'text'): ?>
                <textarea class="field" name="q<?= (int)$q['id'] ?>" rows="3" maxlength="500"
                  placeholder="<?= e(__('part.type_answer')) ?> (<?= e($q['is_required'] ? __('common.required') : __('common.optional')) ?>)"></textarea>

              <?php elseif ($q['type'] === 'number'): $cfg = $q['config'] ?: []; ?>
                <input class="field" type="number" step="any" name="q<?= (int)$q['id'] ?>"
                  <?= isset($cfg['min']) ? 'min="' . e($cfg['min']) . '"' : '' ?>
                  <?= isset($cfg['max']) ? 'max="' . e($cfg['max']) . '"' : '' ?>
                  placeholder="<?= e($q['is_required'] ? __('part.enter_number') : __('part.optional_number')) ?>">

              <?php elseif ($q['type'] === 'rating'): ?>
                <div class="rating" role="radiogroup" aria-label="<?= e(__('qt.rating')) ?>">
                  <?php for ($r = 1; $r <= 5; $r++): ?>
                    <label class="star-btn" title="<?= e(__('part.star', ['n' => local_num($r)])) ?>">
                      <input type="radio" name="q<?= (int)$q['id'] ?>" value="<?= $r ?>" <?= $q['is_required'] ? 'required' : '' ?>>
                      <?= mpin_icon('star', 26) ?>
                    </label>
                  <?php endfor; ?>
                </div>
              <?php endif; ?>
            </fieldset>
          <?php endforeach; ?>

          <!-- honeypot -->
          <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

          <div class="wizard-nav">
            <span></span>
            <button class="btn btn-primary btn-lg" type="submit" id="toStep2"><?= e(__('common.continue')) ?> <?= mpin_icon('arrow-right', 16) ?></button>
          </div>
        </form>
      </div>

      <!-- STEP 2: location -->
      <div class="wizard-panel" data-panel="2">
        <div class="loc-intro">
          <h2><?= mpin_icon('pin', 20) ?> <?= e(__('part.where_from')) ?></h2>
          <p class="muted"><?= e(__('part.privacy_explain')) ?></p>
          <label class="check-note">
            <input type="checkbox" id="shareLocation"> <?= e(__('part.share_location_check')) ?>
          </label>
        </div>
        <div class="map-wrap loc-map">
          <div id="pickMap" class="map-canvas"></div>
          <button class="btn btn-ghost loc-gps" id="gpsBtn" type="button"><?= mpin_icon('globe', 16) ?> <?= e(__('part.use_my_location')) ?></button>
          <div class="loc-confirm" id="locConfirm" hidden>
            <?= mpin_icon('pin', 16) ?> <span id="locLabel"><?= e(__('part.location_selected')) ?></span>
            <button class="btn btn-sm btn-ghost" id="locClear" type="button"><?= mpin_icon('x', 13) ?> <?= e(__('common.clear')) ?></button>
          </div>
        </div>
        <div class="wizard-nav">
          <button class="btn btn-ghost" data-back="1"><?= e(__('common.back')) ?></button>
          <button class="btn btn-primary btn-lg" id="toStep3" type="button" disabled><?= e(__('common.continue')) ?> <?= mpin_icon('arrow-right', 16) ?></button>
        </div>
      </div>

      <!-- STEP 3: review -->
      <div class="wizard-panel" data-panel="3">
        <h2><?= mpin_icon('eye', 20) ?> <?= e(__('part.review_title')) ?></h2>
        <div id="reviewList" class="review-list"></div>
        <div class="wizard-nav">
          <button class="btn btn-ghost" data-back="2"><?= e(__('common.back')) ?></button>
          <button class="btn btn-primary btn-lg btn-lg-accent" id="submitResponse" type="button"><?= mpin_icon('send', 16) ?> <?= e(__('part.submit')) ?></button>
        </div>
      </div>

      <!-- STEP 4: done -->
      <div class="wizard-panel" data-panel="4">
        <div class="done-state">
          <div class="done-ico"><?= mpin_logo(72, 'done') ?></div>
          <h2><?= e(__('part.thank_you')) ?></h2>
          <p class="muted"><?= e(__('part.thank_you_sub')) ?></p>
          <div class="hero-actions center">
            <a class="btn btn-primary btn-lg" href="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('map', 16) ?> <?= e(__('part.see_map')) ?></a>
            <a class="btn btn-ghost btn-lg" href="<?= e(url('/explore')) ?>"><?= e(__('part.explore_more')) ?></a>
          </div>
          <p class="mini-note"><a href="<?= e(url('/my-data')) ?>"><?= mpin_icon('shield', 13) ?> <?= e(__('part.manage_data')) ?></a></p>
        </div>
      </div>
    </div>
  </div>
</section>

<script id="participateData" type="application/json"><?= json_encode([
    'slug' => $poll['slug'],
    'questions' => array_map(static fn($q) => [
        'id' => (int)$q['id'], 'type' => $q['type'], 'text' => $q['question_text'],
        'required' => (bool)$q['is_required'],
        'options' => array_map(static fn($o) => ['id' => (int)$o['id'], 'text' => $o['option_text']], $q['options']),
    ], $questions),
], JSON_UNESCAPED_UNICODE) ?></script>
<?php $extraJs = ['js/pages/participate.js']; $wide = false; ?>
