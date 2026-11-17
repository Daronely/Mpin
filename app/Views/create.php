<?php
/** Create / edit poll — wizard form. @var array $categories $poll $questions */
require_once MPIN_APP . '/Views/partials/icons.php';
$isEdit = !empty($poll);
$vendor = [];
?>
<section class="container create-page">
  <div class="page-head">
    <div>
      <h1><?= mpin_icon('plus', 26) ?> <?= $isEdit ? 'Edit poll' : 'Create a poll' ?></h1>
      <p class="muted"><?= $isEdit
          ? 'Update your poll. Questions lock once responses arrive.'
          : 'Build your questions, publish, and share the link. People answer and the map fills in.' ?></p>
    </div>
    <?php if ($isEdit): ?>
      <a class="btn btn-ghost" href="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('eye', 15) ?> View poll</a>
    <?php endif; ?>
  </div>

  <form id="pollForm" class="create-grid" novalidate enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

    <!-- LEFT: steps -->
    <div class="create-main">
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('info', 17) ?> Basics</h2></div>
        <div class="field">
          <label for="fTitle">Title <span class="req">*</span></label>
          <input id="fTitle" class="field" type="text" name="title" maxlength="150" required
                 value="<?= e($poll['title'] ?? '') ?>" placeholder="e.g. Where do people play Counter-Strike 2?">
          <p class="field-err" hidden></p>
        </div>
        <div class="field">
          <label for="fDesc">Description</label>
          <textarea id="fDesc" name="description" rows="3" maxlength="2000"
            placeholder="Give participants some context…"><?= e($poll['description'] ?? '') ?></textarea>
        </div>
        <div class="field-row">
          <div class="field">
            <label for="fCat">Category <span class="req">*</span></label>
            <select id="fCat" name="category_id" class="select" required>
              <option value="">Select…</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (isset($poll) && (int)$poll['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="fSlug">Custom link <span class="muted small">(optional)</span></label>
            <div class="slug-field"><span><?= e(url('/poll/')) ?></span>
              <input id="fSlug" type="text" name="slug" maxlength="60" pattern="[a-z0-9][a-z0-9-]*"
                     value="<?= e($poll['slug'] ?? '') ?>" placeholder="cs2-players">
            </div>
          </div>
        </div>
        <div class="field">
          <label>Cover image <span class="muted small">(optional, JPG/PNG/WebP ≤ 4 MB)</span></label>
          <div class="cover-drop" id="coverDrop">
            <input type="file" id="coverInput" name="cover" accept="image/jpeg,image/png,image/webp" hidden>
            <div class="cover-preview" id="coverPreview" <?= empty($poll['cover']) ? 'hidden' : '' ?>>
              <img src="<?= e(upload_url($poll['cover'] ?? null) ?? '') ?>" alt="" id="coverImg">
              <button type="button" class="btn btn-sm btn-ghost" id="coverRemove"><?= mpin_icon('x', 13) ?> Remove</button>
            </div>
            <button type="button" class="cover-pick" id="coverPick" <?= empty($poll['cover']) ? '' : 'hidden' ?>>
              <?= mpin_icon('plus', 18) ?> <span>Upload a cover</span>
            </button>
            <img id="coverNewPreview" class="cover-new" alt="" hidden>
          </div>
          <input type="hidden" name="cover_action" id="coverAction" value="">
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2><?= mpin_icon('list', 17) ?> Questions <span class="muted small" id="qCount"></span></h2>
          <button class="btn btn-ghost btn-sm" type="button" id="addQuestion"><?= mpin_icon('plus', 14) ?> Add question</button>
        </div>
        <div id="questionBuilder"></div>
        <p class="muted small">Tip: mix choice questions (great for map filters) with ratings and numbers (great for analytics).</p>
      </section>
    </div>

    <!-- RIGHT: settings -->
    <aside class="create-side">
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('settings', 16) ?> Settings</h2></div>
        <div class="field">
          <label for="fVis">Visibility</label>
          <select id="fVis" name="visibility" class="select">
            <option value="public" <?= ($poll['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>>Public — listed in Explore</option>
            <option value="private" <?= ($poll['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>Private — link only</option>
          </select>
        </div>
        <label class="switch-row">
          <span>
            <strong>Anonymous participation</strong>
            <span class="muted small">Let people answer without an account</span>
          </span>
          <input type="checkbox" name="allow_anonymous" value="1" <?= (isset($poll) ? (int)$poll['allow_anonymous'] : 1) ? 'checked' : '' ?>>
        </label>
        <label class="switch-row">
          <span>
            <strong>Multiple responses</strong>
            <span class="muted small">Allow the same person to answer more than once</span>
          </span>
          <input type="checkbox" name="allow_multiple" value="1" <?= !empty($poll['allow_multiple']) ? 'checked' : '' ?>>
        </label>
        <div class="field-row">
          <div class="field">
            <label for="fStart">Start time <span class="muted small">(UTC, optional)</span></label>
            <input id="fStart" type="datetime-local" name="start_at" value="<?= e(substr((string)($poll['start_at'] ?? ''), 0, 16)) ?>">
          </div>
          <div class="field">
            <label for="fEnd">End time <span class="muted small">(UTC, optional)</span></label>
            <input id="fEnd" type="datetime-local" name="end_at" value="<?= e(substr((string)($poll['end_at'] ?? ''), 0, 16)) ?>">
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('shield', 16) ?> Privacy</h2></div>
        <p class="muted small">Participant locations are always snapped to a ~5&nbsp;km grid and areas with too few answers stay hidden. This is on by default and cannot be disabled.</p>
      </section>

      <div class="create-actions">
        <p class="form-err" id="formError" hidden></p>
        <button class="btn btn-primary btn-lg btn-block" type="submit" id="pollSubmitBtn">
          <?= mpin_icon('send', 16) ?> <?= $isEdit ? 'Save changes' : 'Publish poll' ?>
        </button>
        <span class="muted small">By publishing you agree to the <a href="<?= e(url('/terms')) ?>">Terms</a>.</span>
      </div>
    </aside>
  </form>
</section>

<script id="createData" type="application/json"><?= json_encode([
    'isEdit' => $isEdit,
    'editId' => $isEdit ? (int)$poll['id'] : null,
    'editSlug' => $isEdit ? $poll['slug'] : null,
    'hasResponses' => $isEdit && (int)\App\Core\Database::value('SELECT COUNT(*) FROM responses WHERE poll_id = ?', [$poll['id']]) > 0,
    'questions' => isset($questions) && $questions ? array_map(static fn($q) => [
        'type' => $q['type'], 'text' => $q['question_text'], 'required' => (bool)$q['is_required'],
        'options' => array_column($q['options'], 'option_text'),
        'config' => $q['config'],
    ], $questions) : [],
], JSON_UNESCAPED_UNICODE) ?></script>
<?php $extraJs = ['js/pages/create.js']; $wide = false; ?>
