/* MPIN — admin panel actions. */
(() => {
  'use strict';
  const base = MPIN.base || '';

  document.addEventListener('click', async (e) => {
    /* user actions */
    const ub = e.target.closest('[data-ua]');
    if (ub) {
      const row = ub.closest('tr[data-user]');
      await act(`${base}/admin/users/${row.dataset.user}/${ub.dataset.ua}`, ub);
      return;
    }
    /* poll actions */
    const pb = e.target.closest('[data-pa]');
    if (pb) {
      const row = pb.closest('tr[data-poll]');
      await act(`${base}/admin/polls/${row.dataset.poll}/${pb.dataset.pa}`, pb);
      return;
    }
    /* report actions */
    const rb = e.target.closest('[data-ra]');
    if (rb) {
      const row = rb.closest('tr[data-report]');
      await act(`${base}/admin/reports/${row.dataset.report}/${rb.dataset.ra}`, rb);
      return;
    }
    /* category delete */
    const cb = e.target.closest('[data-ca]');
    if (cb) {
      const row = cb.closest('tr[data-cat]');
      await act(`${base}/admin/categories/${row.dataset.cat}/delete`, cb);
    }
  });

  async function act(path, btn) {
    if (btn.dataset.confirm) {
      const ok = await MPIN.confirmTyped(btn.dataset.confirm);
      if (!ok) return;
    }
    btn.disabled = true;
    try {
      const res = await MPIN.api(path, { method: 'POST', body: {} });
      MPIN.toast(res.message || 'Done.');
      setTimeout(() => location.reload(), 650);
    } catch (ex) {
      MPIN.toast(ex.message, 'err');
      btn.disabled = false;
    }
  }

  /* add category */
  const catForm = document.getElementById('categoryForm');
  if (catForm) {
    catForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const err = catForm.querySelector('.form-err');
      try {
        await MPIN.api(base + '/admin/categories/add', {
          method: 'POST',
          body: { name: catForm.name.value, slug: catForm.slug.value, icon: catForm.icon.value },
        });
        err.hidden = true;
        MPIN.toast('Category added.');
        setTimeout(() => location.reload(), 600);
      } catch (ex) { err.hidden = false; err.textContent = ex.message; }
    });
  }

  /* site settings */
  const setForm = document.getElementById('siteSettingsForm');
  if (setForm) {
    setForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const err = setForm.querySelector('.form-err');
      try {
        await MPIN.api(base + '/admin/settings/save', {
          method: 'POST',
          body: { site_tagline: setForm.site_tagline.value, maintenance: setForm.maintenance.checked ? 1 : 0 },
        });
        err.hidden = true;
        MPIN.toast('Settings saved.');
      } catch (ex) { err.hidden = false; err.textContent = ex.message; }
    });
  }
})();
