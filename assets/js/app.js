/* MPIN — core front-end helpers (theme, toasts, API, nav). */
(() => {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

  const meta = $('meta[name="csrf-token"]');
  const MPIN = (window.MPIN = {
    csrf: meta ? meta.content : '',
    theme: document.documentElement.dataset.theme || 'dark',

    esc(v) {
      return String(v == null ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    },

    fmt(n) {
      n = Number(n) || 0;
      if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
      if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
      return String(n);
    },

    tiles() {
      // Single keyless provider; dark mode is applied via a CSS filter on the tile pane.
      return { url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', attr: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' };
    },

    setTheme(t) {
      MPIN.theme = t;
      document.documentElement.dataset.theme = t;
      try { localStorage.setItem('mpin-theme', t); } catch (e) { /* ignore */ }
      document.dispatchEvent(new CustomEvent('mpin:theme', { detail: t }));
    },

    toast(msg, kind = 'ok', ms = 4200) {
      const box = $('#toasts');
      if (!box) return;
      const el = document.createElement('div');
      el.className = 'toast ' + kind;
      el.textContent = msg;
      box.appendChild(el);
      setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 320); }, ms);
    },

    async api(path, opts = {}) {
      const init = { method: opts.method || 'GET', headers: { 'X-CSRF-Token': MPIN.csrf, 'Accept': 'application/json' } };
      if (opts.form instanceof FormData) {
        init.body = opts.form;
      } else if (opts.body !== undefined) {
        init.headers['Content-Type'] = 'application/json';
        init.body = JSON.stringify(opts.body);
      }
      const res = await fetch(path, init);
      let data = {};
      try { data = await res.json(); } catch (e) { /* non-json */ }
      if (!res.ok || data.ok === false) {
        const err = new Error(data.error || 'Request failed (' + res.status + ')');
        err.data = data || {};
        err.status = res.status;
        throw err;
      }
      return data;
    },

    confirmTyped(label) {
      return new Promise((resolve) => {
        const v = window.prompt(label || 'Type DELETE to confirm', '');
        resolve(v === 'DELETE');
      });
    },
  });

  /* ---------- theme toggle ---------- */
  const themeBtn = $('#themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      MPIN.setTheme(MPIN.theme === 'dark' ? 'light' : 'dark');
    });
  }

  /* ---------- user dropdown ---------- */
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-dropdown-toggle]');
    $$('[data-dropdown].open').forEach((d) => {
      if (!toggle || !d.contains(toggle)) d.classList.remove('open');
    });
    if (toggle) {
      const box = toggle.closest('[data-dropdown]');
      if (box) box.classList.toggle('open');
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') $$('[data-dropdown].open').forEach((d) => d.classList.remove('open'));
  });

  /* ---------- mobile nav ---------- */
  const burger = $('#navBurger');
  const mainNav = $('#mainNav');
  if (burger && mainNav) {
    burger.addEventListener('click', () => {
      const open = mainNav.classList.toggle('open');
      burger.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---------- count-up animation ---------- */
  const counters = $$('[data-count]');
  if (counters.length) {
    const animate = (el) => {
      const target = Number(el.dataset.count) || 0;
      const dur = 900;
      const t0 = performance.now();
      const step = (t) => {
        const p = Math.min(1, (t - t0) / dur);
        el.textContent = MPIN.fmt(Math.round(target * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) { animate(en.target); io.unobserve(en.target); } });
      }, { threshold: 0.4 });
      counters.forEach((c) => io.observe(c));
    } else counters.forEach(animate);
  }

  /* ---------- share button ---------- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#shareBtn');
    if (!btn) return;
    const url = btn.dataset.url || location.href;
    if (navigator.share) {
      try { await navigator.share({ url, title: document.title }); } catch (err) { /* cancelled */ }
      return;
    }
    try {
      await navigator.clipboard.writeText(url);
      MPIN.toast('Link copied to clipboard!');
    } catch (err) {
      window.prompt('Copy this link:', url);
    }
  });

  /* ---------- modals ---------- */
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      const m = document.getElementById(opener.dataset.modalOpen);
      if (m) { m.hidden = false; document.body.style.overflow = 'hidden'; }
    }
    if (e.target.closest('[data-close]') || e.target.classList.contains('modal')) {
      const m = e.target.closest('.modal');
      if (m) { m.hidden = true; document.body.style.overflow = ''; }
    }
  });

  /* ---------- log-out via link (no-JS fallback handled server-side) ---------- */
  MPIN.$ = $;
  MPIN.$$ = $$;
})();
