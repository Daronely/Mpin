/* MPIN — settings page forms. */
(() => {
  'use strict';
  const $ = MPIN.$;
  const base = MPIN.base || '';

  const profileForm = document.getElementById('profileForm');
  if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const err = profileForm.querySelector('.form-err');
      try {
        await MPIN.api(base + '/settings/profile', {
          method: 'POST',
          body: { display_name: profileForm.display_name.value, bio: profileForm.bio.value },
        });
        err.hidden = true;
        MPIN.toast('Profile saved.');
        setTimeout(() => location.reload(), 700);
      } catch (ex) { err.hidden = false; err.textContent = ex.message; }
    });
  }

  const passwordForm = document.getElementById('passwordForm');
  if (passwordForm) {
    passwordForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const err = passwordForm.querySelector('.form-err');
      try {
        await MPIN.api(base + '/settings/password', {
          method: 'POST',
          body: {
            current_password: passwordForm.current_password.value,
            new_password: passwordForm.new_password.value,
            confirm_password: passwordForm.confirm_password.value,
          },
        });
        err.hidden = true;
        passwordForm.reset();
        MPIN.toast('Password changed. Please log in again on other devices.');
      } catch (ex) { err.hidden = false; err.textContent = ex.message; }
    });
  }

  /* wipe responses — double confirmation */
  const wipeForm = document.getElementById('wipeForm');
  if (wipeForm) {
    const chk = document.getElementById('wipeCheck');
    const confirmInput = document.getElementById('wipeConfirm');
    const btn = document.getElementById('wipeBtn');
    chk.addEventListener('change', () => { confirmInput.disabled = !chk.checked; });
    confirmInput.addEventListener('input', () => { btn.disabled = confirmInput.value.trim() !== 'DELETE'; });
    wipeForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!chk.checked || confirmInput.value.trim() !== 'DELETE') return;
      btn.disabled = true;
      try {
        const res = await MPIN.api(base + '/settings/privacy', { method: 'POST', body: { confirm: 'DELETE' } });
        MPIN.toast(res.message || 'Your responses were deleted.');
        chk.checked = false; confirmInput.value = ''; confirmInput.disabled = true; btn.disabled = true;
      } catch (ex) {
        MPIN.toast(ex.message, 'err');
        btn.disabled = false;
      }
    });
  }
})();
