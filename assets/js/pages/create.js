/* MPIN — create / edit poll: question builder + submit. */
(() => {
  'use strict';
  const $ = MPIN.$, $$ = MPIN.$$;
  const cfgEl = document.getElementById('createData');
  if (!cfgEl) return;
  const cfg = JSON.parse(cfgEl.textContent);
  const builder = document.getElementById('questionBuilder');
  const qCount = document.getElementById('qCount');
  const TYPES = {
    yesno: () => MPIN.t('qt.yesno'),
    single: () => MPIN.t('qt.single'),
    multi: () => MPIN.t('qt.multi'),
    text: () => MPIN.t('qt.text'),
    number: () => MPIN.t('qt.number'),
    rating: () => MPIN.t('qt.rating'),
  };

  const uid = () => 'q' + Math.random().toString(36).slice(2, 8);

  function questionTemplate(q) {
    q = q || { type: 'single', text: '', required: true, options: ['', ''] , config: {} };
    const id = uid();
    const wrap = document.createElement('div');
    wrap.className = 'qb-item';
    wrap.dataset.id = id;
    wrap.innerHTML = `
      <div class="qb-head">
        <select class="field select qb-type" aria-label="Question type">
          ${Object.entries(TYPES).map(([v, l]) => `<option value="${v}" ${q.type === v ? 'selected' : ''}>${l()}</option>`).join('')}
        </select>
        <input class="field qb-text" type="text" maxlength="300" placeholder="${MPIN.t('create.ask_something')}" value="${MPIN.esc(q.text)}">
        <button class="icon-btn danger qb-remove" type="button" title="Remove question">${MPIN.$ ? '' : ''}</button>
      </div>
      <div class="qb-extra"></div>
      <div class="qb-foot">
        <label class="check-note"><input type="checkbox" class="qb-required" ${q.required ? 'checked' : ''}> Required</label>
        <div class="mini-controls">
          <button class="icon-btn qb-up" type="button" title="Move up">▲</button>
          <button class="icon-btn qb-down" type="button" title="Move down">▼</button>
        </div>
      </div>`;
    wrap.querySelector('.qb-remove').innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
    wrap.querySelector('.qb-up').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>';
    wrap.querySelector('.qb-down').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    builder.appendChild(wrap);
    renderExtras(wrap, q);
    return wrap;
  }

  function renderExtras(wrap, q) {
    const type = wrap.querySelector('.qb-type').value;
    const extra = wrap.querySelector('.qb-extra');
    if (type === 'single' || type === 'multi') {
      const opts = (q.options && q.options.length ? q.options : ['', '']);
      extra.innerHTML = `<div class="qb-opts">` +
        opts.map((o) => optRow(o)).join('') +
        `</div><button type="button" class="btn btn-ghost btn-sm qb-add-opt">${MPIN.t('create.add_option')}</button>`;
    } else if (type === 'number') {
      const cfgN = q.config || {};
      extra.innerHTML = `<div class="qb-num-row">
          <div class="field"><label>Min (optional)</label><input class="qb-min" type="number" step="any" value="${cfgN.min != null ? MPIN.esc(cfgN.min) : ''}"></div>
          <div class="field"><label>Max (optional)</label><input class="qb-max" type="number" step="any" value="${cfgN.max != null ? MPIN.esc(cfgN.max) : ''}"></div>
        </div>`;
    } else {
      extra.innerHTML = '';
    }
    updateCount();
  }

  function optRow(value) {
    return `<div class="qb-opt"><input class="field qb-opt-input" type="text" maxlength="150" placeholder="${MPIN.t('create.option_ph')}" value="${MPIN.esc(value || '')}">
      <button type="button" class="icon-btn danger qb-opt-remove" title="Remove option">✕</button></div>`;
  }

  function updateCount() {
    const n = $$('.qb-item', builder).length;
    qCount.textContent = `(${n}/10)`;
  }

  builder.addEventListener('change', (e) => {
    if (e.target.classList.contains('qb-type')) {
      renderExtras(e.target.closest('.qb-item'), {});
    }
  });
  builder.addEventListener('click', (e) => {
    const item = e.target.closest('.qb-item');
    if (!item) return;
    if (e.target.closest('.qb-remove')) {
      if ($$('.qb-item', builder).length <= 1) { MPIN.toast(MPIN.t('create.min_questions'), 'err'); return; }
      item.remove(); updateCount();
    }
    if (e.target.closest('.qb-add-opt')) {
      const opts = item.querySelector('.qb-opts');
      if ($$('.qb-opt', opts).length >= 10) { MPIN.toast(MPIN.t('create.max_options'), 'err'); return; }
      opts.insertAdjacentHTML('beforeend', optRow(''));
    }
    if (e.target.closest('.qb-opt-remove')) {
      const opts = e.target.closest('.qb-opts');
      if ($$('.qb-opt', opts).length <= 2) { MPIN.toast(MPIN.t('create.min_options'), 'err'); return; }
      e.target.closest('.qb-opt').remove();
    }
    if (e.target.closest('.qb-up')) {
      const prev = item.previousElementSibling;
      if (prev) builder.insertBefore(item, prev);
    }
    if (e.target.closest('.qb-down')) {
      const next = item.nextElementSibling;
      if (next) builder.insertBefore(next, item);
    }
  });

  document.getElementById('addQuestion').addEventListener('click', () => {
    if ($$('.qb-item', builder).length >= 10) { MPIN.toast(MPIN.t('create.max_questions'), 'err'); return; }
    questionTemplate();
    builder.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  /* seed existing (edit mode) or one default question */
  (cfg.questions && cfg.questions.length ? cfg.questions : [null]).forEach(questionTemplate);

  /* ---------- cover upload ---------- */
  const coverDrop = document.getElementById('coverDrop');
  const coverInput = document.getElementById('coverInput');
  const coverPick = document.getElementById('coverPick');
  const coverPreview = document.getElementById('coverPreview');
  const coverNew = document.getElementById('coverNewPreview');
  const coverAction = document.getElementById('coverAction');

  coverPick.addEventListener('click', () => coverInput.click());
  coverDrop.addEventListener('dragover', (e) => { e.preventDefault(); coverDrop.classList.add('drag'); });
  coverDrop.addEventListener('dragleave', () => coverDrop.classList.remove('drag'));
  coverDrop.addEventListener('drop', (e) => {
    e.preventDefault(); coverDrop.classList.remove('drag');
    if (e.dataTransfer.files.length) { coverInput.files = e.dataTransfer.files; previewCover(); }
  });
  coverInput.addEventListener('change', previewCover);
  function previewCover() {
    const f = coverInput.files && coverInput.files[0];
    if (!f) return;
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { MPIN.toast(MPIN.t('create.cover_bad_type'), 'err'); coverInput.value = ''; return; }
    if (f.size > 4 * 1024 * 1024) { MPIN.toast(MPIN.t('create.cover_too_big'), 'err'); coverInput.value = ''; return; }
    const reader = new FileReader();
    reader.onload = () => {
      coverNew.src = reader.result;
      coverNew.hidden = false;
      coverAction.value = 'new';
    };
    reader.readAsDataURL(f);
  }
  document.getElementById('coverRemove')?.addEventListener('click', () => {
    coverPreview.hidden = true;
    coverPick.hidden = false;
    coverInput.value = '';
    if (coverNew.src) { coverNew.hidden = true; coverNew.removeAttribute('src'); }
    coverAction.value = 'remove';
  });

  /* ---------- submit ---------- */
  const form = document.getElementById('pollForm');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('pollSubmitBtn');
    const errBox = document.getElementById('formError');

    /* collect questions */
    const questions = [];
    let valid = true;
    $$('.qb-item', builder).forEach((item) => {
      const type = item.querySelector('.qb-type').value;
      const text = item.querySelector('.qb-text').value.trim();
      const required = item.querySelector('.qb-required').checked;
      const q = { type, text, required, options: [], config: {} };
      if (text.length < 3) { valid = false; item.querySelector('.qb-text').style.borderColor = 'var(--danger)'; }
      else item.querySelector('.qb-text').style.borderColor = '';
      if (type === 'single' || type === 'multi') {
        q.options = $$('.qb-opt-input', item).map((i) => i.value.trim()).filter(Boolean);
        if (q.options.length < 2) valid = false;
      }
      if (type === 'number') {
        const mn = item.querySelector('.qb-min').value;
        const mx = item.querySelector('.qb-max').value;
        if (mn !== '') q.config.min = Number(mn);
        if (mx !== '') q.config.max = Number(mx);
      }
      questions.push(q);
    });

    if (!form.title.value.trim() || !form.category_id.value || !valid) {
      errBox.hidden = false;
      errBox.textContent = MPIN.t('create.form_error');
      return;
    }
    errBox.hidden = true;

    const fd = new FormData(form);
    fd.set('questions', JSON.stringify(questions));
    /* unchecked checkboxes don't post: normalize */
    if (!fd.get('allow_anonymous')) fd.set('allow_anonymous', '0');
    if (!fd.get('allow_multiple')) fd.set('allow_multiple', '0');
    if (!fd.get('cover')) fd.delete('cover');

    btn.disabled = true;
    btn.textContent = cfg.isEdit ? MPIN.t('create.saving') : MPIN.t('create.publishing');
    try {
      const path = cfg.isEdit ? `${MPIN.base || ''}/poll/${encodeURIComponent(cfg.editSlug)}/edit` : `${MPIN.base || ''}/create`;
      const res = await MPIN.api(path, { method: 'POST', form: fd });
      MPIN.toast(MPIN.t(cfg.isEdit ? 'toast.poll_updated' : 'toast.poll_published'));
      setTimeout(() => { location.href = res.redirect; }, 600);
    } catch (err) {
      btn.disabled = false;
      btn.textContent = MPIN.t(cfg.isEdit ? 'create.save_changes' : 'create.publish');
      errBox.hidden = false;
      errBox.textContent = err.message;
      if (err.data && err.data.errors) {
        const map = { title: 'fTitle', category_id: 'fCat', slug: 'fSlug', end_at: 'fEnd' };
        Object.entries(err.data.errors).forEach(([k, msg]) => {
          const el = document.getElementById(map[k] || '');
          if (el) { el.style.borderColor = 'var(--danger)'; }
        });
      }
    }
  });

  if (cfg.hasResponses) {
    MPIN.toast(MPIN.t('create.locked_note'), 'ok', 6000);
    $$('.qb-item', builder).forEach((item) => {
      item.querySelectorAll('.qb-remove, .qb-up, .qb-down, .qb-add-opt, .qb-opt-remove, input, select').forEach((el) => { el.disabled = true; });
    });
    document.getElementById('addQuestion').disabled = true;
    form.querySelectorAll('.qb-text, .qb-opt-input').forEach((i) => (i.readOnly = true));
  }
})();
