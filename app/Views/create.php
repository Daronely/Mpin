<?php
/** Create / edit poll — wizard form. @var array $categories $poll $questions */
require_once MPIN_APP . '/Views/partials/icons.php';
$isEdit = !empty($poll);
$vendor = [];
?>
<section class="container create-page">
  <div class="page-head">
    <div>
      <h1><?= mpin_icon('plus', 26) ?> <?= e($isEdit ? __('create.title_edit') : __('create.title_new')) ?></h1>
      <p class="muted"><?= e($isEdit ? __('create.sub_edit') : __('create.sub_new')) ?></p>
    </div>
    <?php if ($isEdit): ?>
      <a class="btn btn-ghost" href="<?= e(url('/poll/' . $poll['slug'])) ?>"><?= mpin_icon('eye', 15) ?> <?= e(__('create.view_poll')) ?></a>
    <?php endif; ?>
  </div>

  <form id="pollForm" class="create-grid" novalidate enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

    <!-- LEFT: steps -->
    <div class="create-main">
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('info', 17) ?> <?= e(__('create.basics')) ?></h2></div>
        <div class="field">
          <label for="fTitle"><?= e(__('create.title_f')) ?> <span class="req">*</span></label>
          <input id="fTitle" class="field" type="text" name="title" maxlength="150" required
                 value="<?= e($poll['title'] ?? '') ?>" placeholder="<?= e(__('create.title_ph')) ?>">
          <p class="field-err" hidden></p>
        </div>
        <div class="field">
          <label for="fDesc"><?= e(__('create.description_f')) ?></label>
          <textarea id="fDesc" name="description" rows="3" maxlength="2000"
            placeholder="<?= e(__('create.desc_ph')) ?>"><?= e($poll['description'] ?? '') ?></textarea>
        </div>
        <div class="field-row">
          <div class="field">
            <label for="fCat"><?= e(__('create.category_f')) ?> <span class="req">*</span></label>
            <select id="fCat" name="category_id" class="select" required>
              <option value=""><?= e(__('create.select_cat')) ?></option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (isset($poll) && (int)$poll['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= e(__('cat.' . $c['slug'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="fSlug"><?= e(__('create.custom_link')) ?> <span class="muted small">(<?= e(__('common.optional')) ?>)</span></label>
            <div class="slug-field"><span><?= e(url('/poll/')) ?></span>
              <input id="fSlug" type="text" name="slug" maxlength="60" pattern="[a-z0-9][a-z0-9-]*"
                     value="<?= e($poll['slug'] ?? '') ?>" placeholder="cs2-players">
            </div>
          </div>
        </div>
        <div class="field">
          <label><?= e(__('create.cover')) ?> <span class="muted small">(<?= e(__('create.cover_hint')) ?>)</span></label>
          <div class="cover-drop" id="coverDrop">
            <input type="file" id="coverInput" name="cover" accept="image/jpeg,image/png,image/webp" hidden>
            <div class="cover-preview" id="coverPreview" <?= empty($poll['cover']) ? 'hidden' : '' ?>>
              <img src="<?= e(upload_url($poll['cover'] ?? null) ?? '') ?>" alt="" id="coverImg">
              <button type="button" class="btn btn-sm btn-ghost" id="coverRemove"><?= mpin_icon('x', 13) ?> <?= e(__('create.remove_cover')) ?></button>
            </div>
            <button type="button" class="cover-pick" id="coverPick" <?= empty($poll['cover']) ? '' : 'hidden' ?>>
              <?= mpin_icon('plus', 18) ?> <span><?= e(__('create.upload_cover')) ?></span>
            </button>
            <img id="coverNewPreview" class="cover-new" alt="" hidden>
          </div>
          <input type="hidden" name="cover_action" id="coverAction" value="">
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2><?= mpin_icon('list', 17) ?> <?= e(__('create.questions')) ?> <span class="muted small" id="qCount"></span></h2>
          <button class="btn btn-ghost btn-sm" type="button" id="addQuestion"><?= mpin_icon('plus', 14) ?> <?= e(__('create.add_question')) ?></button>
        </div>
        <div id="questionBuilder"></div>
        <p class="muted small"><?= e(__('create.tip')) ?></p>
      </section>
    </div>

    <!-- RIGHT: settings -->
    <aside class="create-side">
      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('settings', 16) ?> <?= e(__('create.settings')) ?></h2></div>
        <div class="field">
          <label for="fVis"><?= e(__('create.visibility')) ?></label>
          <select id="fVis" name="visibility" class="select">
            <option value="public" <?= ($poll['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>><?= e(__('create.public_vis')) ?></option>
            <option value="private" <?= ($poll['visibility'] ?? '') === 'private' ? 'selected' : '' ?>><?= e(__('create.private_vis')) ?></option>
          </select>
        </div>
        <label class="switch-row">
          <span>
            <strong><?= e(__('create.anonymous')) ?></strong>
            <span class="muted small"><?= e(__('create.anonymous_sub')) ?></span>
          </span>
          <input type="checkbox" name="allow_anonymous" value="1" <?= (isset($poll) ? (int)$poll['allow_anonymous'] : 1) ? 'checked' : '' ?>>
        </label>
        <label class="switch-row">
          <span>
            <strong><?= e(__('create.multiple')) ?></strong>
            <span class="muted small"><?= e(__('create.multiple_sub')) ?></span>
          </span>
          <input type="checkbox" name="allow_multiple" value="1" <?= !empty($poll['allow_multiple']) ? 'checked' : '' ?>>
        </label>
        <div class="field-row">
          <div class="field">
            <label for="fStart"><?= e(__('create.start')) ?> <span class="muted small">(<?= e(__('create.utc_note')) ?>)</span></label>
            <input id="fStart" type="datetime-local" name="start_at" value="<?= e(substr((string)($poll['start_at'] ?? ''), 0, 16)) ?>">
          </div>
          <div class="field">
            <label for="fEnd"><?= e(__('create.end')) ?> <span class="muted small">(<?= e(__('create.utc_note')) ?>)</span></label>
            <input id="fEnd" type="datetime-local" name="end_at" value="<?= e(substr((string)($poll['end_at'] ?? ''), 0, 16)) ?>">
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head"><h2><?= mpin_icon('shield', 16) ?> <?= e(__('create.privacy_panel')) ?></h2></div>
        <p class="muted small"><?= e(__('create.privacy_note')) ?></p>
      </section>

      <div class="create-actions">
        <p class="form-err" id="formError" hidden></p>
        <button class="btn btn-primary btn-lg btn-block" type="submit" id="pollSubmitBtn">
          <?= mpin_icon('send', 16) ?> <?= e($isEdit ? __('create.save_changes') : __('create.publish')) ?>
        </button>
        <span class="muted small"><?= e(__('create.terms_note')) ?> <a href="<?= e(url('/terms')) ?>"><?= e(__('footer.terms')) ?></a>.</span>
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
