/* MPIN — dashboard: manage a single poll (close/reopen/delete, growth chart). */
(() => {
  'use strict';
  const $ = MPIN.$;
  const cfgEl = document.getElementById('manageCfg');
  const growthEl = document.getElementById('growthData');
  if (!cfgEl) return;
  const cfg = JSON.parse(cfgEl.textContent);

  /* growth chart */
  if (growthEl && typeof Chart !== 'undefined') {
    const data = JSON.parse(growthEl.textContent);
    const ctx = document.getElementById('growthChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.map((g) => g.date.slice(5)),
          datasets: [{
            data: data.map((g) => g.count),
            borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.14)',
            fill: true, tension: .38, borderWidth: 2, pointRadius: 2, pointBackgroundColor: '#22d3ee',
          }],
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { color: 'rgba(140,160,190,.1)' }, ticks: { color: '#8fa3bf', maxTicksLimit: 10, font: { size: 10 } } },
            y: { grid: { color: 'rgba(140,160,190,.1)' }, ticks: { color: '#8fa3bf', precision: 0 }, beginAtZero: true },
          },
        },
      });
    }
  }

  /* manage actions */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-manage]');
    if (!btn) return;
    const action = btn.dataset.manage;
    if (btn.dataset.confirm) {
      const ok = await MPIN.confirmTyped(btn.dataset.confirm);
      if (!ok) return;
    }
    btn.disabled = true;
    try {
      const res = await MPIN.api(`${MPIN.base || ''}/poll/${encodeURIComponent(cfg.slug)}/manage`, {
        method: 'POST', body: { action },
      });
      MPIN.toast(res.message || 'Done.');
      if (res.redirect) { setTimeout(() => { location.href = res.redirect; }, 700); }
      else { setTimeout(() => location.reload(), 700); }
    } catch (err) {
      MPIN.toast(err.message, 'err');
      btn.disabled = false;
    }
  });
})();
