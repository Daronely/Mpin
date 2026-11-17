/* MPIN — core front-end helpers (theme, toasts, API, i18n, nav). */
(() => {
  'use strict';

  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

  const meta = $('meta[name="csrf-token"]');
  const DICT = (typeof MPIN_I18N === 'object' && MPIN_I18N) || {};
  const LOCALE = (typeof MPIN_LOCALE === 'string' && MPIN_LOCALE) || 'en';
  const FA_DIGITS = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

  const toFa = (s) => String(s).replace(/[0-9]/g, (d) => FA_DIGITS[+d]);

  const MPIN = (window.MPIN = {
    csrf: meta ? meta.content : '',
    theme: document.documentElement.dataset.theme || 'dark',
    locale: LOCALE,
    rtl: (typeof MPIN_DIR === 'string' ? MPIN_DIR : 'ltr') === 'rtl',
    base: (typeof MPIN_BASE === 'string' ? MPIN_BASE : '') || '',

    /** Translate a UI key (falls back to the key itself). */
    t(key, params) {
      let s = DICT[key] || key;
      if (params) {
        for (const [k, v] of Object.entries(params)) {
          s = s.split(':' + k).join(v);
        }
      }
      return s;
    },

    esc(v) {
      return String(v == null ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    },

    fmt(n) {
      n = Number(n) || 0;
      let s;
      if (n >= 1e6) s = (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
      else if (n >= 1e3) s = (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
      else s = String(n);
      return MPIN.locale === 'fa' ? toFa(s) : s;
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

    /** Coarse browser fingerprint (salted server-side) — layer 4 of the
        multi-layer duplicate-response protection. */
    fingerprint() {
      try {
        const parts = [
          screen.width, screen.height, screen.colorDepth,
          (Intl.DateTimeFormat().resolvedOptions().timeZone || ''),
          (navigator.language || ''), (navigator.languages || []).join(','),
          navigator.platform || '', navigator.hardwareConcurrency || 0,
        ].join('|');
        if (window.crypto && crypto.subtle) {
          return crypto.subtle.digest('SHA-256', new TextEncoder().encode(parts)).then((buf) => {
            return Array.from(new Uint8Array(buf)).map((b) => b.toString(16).padStart(2, '0')).join('');
          });
        }
        return Promise.resolve(null);
      } catch (e) {
        return Promise.resolve(null);
      }
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
      MPIN.toast(MPIN.t('poll.share_copied'));
    } catch (err) {
      window.prompt(MPIN.t('common.copy'), url);
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

  MPIN.$ = $;
  MPIN.$$ = $$;
})();
