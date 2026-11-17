/* MPIN — login & register forms. */
(() => {
  'use strict';
  const $ = MPIN.$;
  const base = MPIN.base || '';

  /* store the ?next= target so the API response can redirect correctly */
  const next = new URLSearchParams(location.search).get('next');
  if (next && next.startsWith('/')) {
    try { sessionStorage.setItem('mpin-next', next); } catch (e) { /* ignore */ }
  }

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = loginForm.querySelector('button[type=submit]');
      const err = document.getElementById('loginError');
      btn.disabled = true; btn.textContent = 'Logging in…';
      try {
        const res = await MPIN.api(base + '/api/auth/login', {
          method: 'POST',
          body: { login: loginForm.login.value.trim(), password: loginForm.password.value, remember: loginForm.remember.checked ? 1 : 0, website: loginForm.website.value },
        });
        location.href = res.redirect || (base + '/dashboard');
      } catch (ex) {
        btn.disabled = false; btn.textContent = 'Log in';
        err.hidden = false; err.textContent = ex.message;
      }
    });
  }

  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = registerForm.querySelector('button[type=submit]');
      const err = document.getElementById('registerError');
      if (registerForm.password.value !== registerForm.confirm_password.value) {
        err.hidden = false; err.textContent = 'Passwords do not match.';
        return;
      }
      btn.disabled = true; btn.textContent = 'Creating account…';
      try {
        const res = await MPIN.api(base + '/api/auth/register', {
          method: 'POST',
          body: {
            username: registerForm.username.value.trim(),
            display_name: registerForm.display_name.value.trim(),
            email: registerForm.email.value.trim(),
            password: registerForm.password.value,
            confirm_password: registerForm.confirm_password.value,
            website: registerForm.website.value,
          },
        });
        location.href = res.redirect || (base + '/dashboard');
      } catch (ex) {
        btn.disabled = false; btn.textContent = 'Create account';
        err.hidden = false; err.textContent = ex.message;
      }
    });
  }
})();
