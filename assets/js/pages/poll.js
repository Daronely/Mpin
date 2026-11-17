/* MPIN — poll page: interactive map, filters, charts, live refresh. */
(() => {
  'use strict';
  const $ = MPIN.$, $$ = MPIN.$$;

  const cfgEl = document.getElementById('pollData');
  if (!cfgEl || typeof L === 'undefined') return;
  const cfg = JSON.parse(cfgEl.textContent);
  const slug = cfg.slug;

  /* ============ MAP ============ */
  const mapEl = document.getElementById('pollMap');
  const map = L.map(mapEl, { center: [26, 10], zoom: 2, worldCopyJump: true, minZoom: 2 });
  window.__pollMap = map;   // debug/embed hook
  const tiles = MPIN.tiles();
  L.tileLayer(tiles.url, { attribution: tiles.attr, maxZoom: 19 }).addTo(map);


  // Guard against layout races: re-measure the container once styles/layout settle.
  const fixSize = () => map.invalidateSize();
  window.addEventListener('load', fixSize);
  window.addEventListener('resize', fixSize);
  setTimeout(fixSize, 300);
  setTimeout(fixSize, 1200);
  const clusterGroup = L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 42 });
  let heatLayer = null;
  let mode = 'cluster';
  let bounds = null;

  function pinIcon(count) {
    const size = count >= 40 ? 'l' : count >= 10 ? 'm' : 's';
    return L.divIcon({
      className: 'pin-marker',
      html: `<span class="pin-dot pin-${size}"></span><span class="pin-count">${MPIN.fmt(count)}</span>`,
      iconSize: [30, 30], iconAnchor: [15, 15],
    });
  }

  function renderPoints(points, total, shown, suppressed) {
    clusterGroup.clearLayers();
    if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }

    if (!points.length) {
      $('#mapMeta').textContent = 'No map points yet — be among the first to answer!';
      return;
    }
    const heatData = [];
    const maxC = Math.max(...points.map((p) => p.count), 1);
    points.forEach((p) => {
      const marker = L.marker([p.lat, p.lng], { icon: pinIcon(p.count) });
      marker.bindPopup(
        `<p class="map-popup-title">${MPIN.esc(p.city ? p.city + ', ' + (p.country || '') : p.country || 'Somewhere on Earth')}</p>` +
        `<p class="map-popup-sub">${MPIN.fmt(p.count)} participant${p.count === 1 ? '' : 's'} pin${p.count === 1 ? '' : 's'} here (approx.)</p>`
      );
      clusterGroup.addLayer(marker);
      heatData.push([p.lat, p.lng, 0.25 + 0.75 * (p.count / maxC)]);
    });
    heatLayer = L.heatLayer(heatData, { radius: 26, blur: 20, maxZoom: 8,
      gradient: { 0.2: '#3b82f6', 0.5: '#22d3ee', 0.8: '#fbbf24', 1: '#f87171' } });

    applyMode();
    if (!bounds) {
      bounds = L.latLngBounds(points.map((p) => [p.lat, p.lng]));
      map.invalidateSize();
      map.fitBounds(bounds.pad(0.25), { maxZoom: 6 });
    }
    const bits = [`${MPIN.fmt(total)} participants · ${points.length} areas`];
    if (suppressed > 0) bits.push(`${MPIN.fmt(suppressed)} hidden for privacy`);
    $('#mapMeta').textContent = bits.join(' · ');
  }

  function applyMode() {
    if (mode === 'cluster') {
      if (heatLayer) map.removeLayer(heatLayer);
      if (!map.hasLayer(clusterGroup)) map.addLayer(clusterGroup);
    } else {
      map.removeLayer(clusterGroup);
      if (heatLayer && !map.hasLayer(heatLayer)) map.addLayer(heatLayer);
    }
  }

  $$('.seg-btn[data-mode]').forEach((b) => {
    b.addEventListener('click', () => {
      $$('.seg-btn[data-mode]').forEach((x) => { x.classList.remove('active'); x.setAttribute('aria-selected', 'false'); });
      b.classList.add('active'); b.setAttribute('aria-selected', 'true');
      mode = b.dataset.mode;
      applyMode();
    });
  });

  /* ============ FILTERS ============ */
  const filterBox = $('#mapFilters');
  const clearBtn = $('#clearFilters');
  const activeFilters = new Map(); // qid -> Set(optionIds)

  const choiceQuestions = (cfg.questions || []).filter(
    (q) => ['yesno', 'single', 'multi'].includes(q.type) && q.options.length
  );

  if (choiceQuestions.length) {
    filterBox.innerHTML = '';
    choiceQuestions.forEach((q) => {
      const wrap = document.createElement('div');
      wrap.className = 'field';
      wrap.innerHTML = `<label class="small muted">${MPIN.esc(q.text)}</label>` +
        q.options.map((o) =>
          `<label class="check-note"><input type="checkbox" value="${o.id}"> ${MPIN.esc(o.text)}</label>`
        ).join('');
      wrap.addEventListener('change', () => {
        const picked = $$('input:checked', wrap).map((i) => Number(i.value));
        if (picked.length) activeFilters.set(q.id, picked);
        else activeFilters.delete(q.id);
        clearBtn.hidden = activeFilters.size === 0;
        loadMap();
      });
      filterBox.appendChild(wrap);
    });
    clearBtn.addEventListener('click', () => {
      activeFilters.clear();
      $$('input', filterBox).forEach((i) => { i.checked = false; });
      clearBtn.hidden = true;
      loadMap();
    });
  }

  function mapUrl() {
    const params = new URLSearchParams();
    activeFilters.forEach((opts, qid) => params.set('f' + qid, opts.join(',')));
    const qs = params.toString();
    return `${MPIN.base || ''}/api/polls/${encodeURIComponent(slug)}/map${qs ? '?' + qs : ''}`;
  }

  async function loadMap() {
    try {
      const res = await MPIN.api(mapUrl());
      renderPoints(res.data.points, res.data.total, res.data.shown, res.data.suppressed);
    } catch (e) {
      $('#mapMeta').textContent = 'Could not load map data.';
    }
  }
  loadMap();
  $('#mapRefreshBtn')?.addEventListener('click', loadMap);

  /* live refresh every 30s when tab is visible */
  setInterval(() => { if (document.visibilityState === 'visible') loadMap(); }, 30000);

  /* ============ MAP SEARCH (Nominatim) ============ */
  const searchBox = $('#mapSearch');
  const searchInput = $('#mapSearchInput');
  const searchResults = $('#mapSearchResults');
  let searchTimer = null;
  $('#mapSearchBtn')?.addEventListener('click', () => {
    searchBox.hidden = !searchBox.hidden;
    if (!searchBox.hidden) searchInput.focus();
  });
  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q.length < 3) { searchResults.innerHTML = ''; return; }
    searchTimer = setTimeout(async () => {
      try {
        const r = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&accept-language=en&q=' + encodeURIComponent(q),
          { headers: { 'Accept': 'application/json' } });
        const items = await r.json();
        searchResults.innerHTML = items.length
          ? items.map((it) => `<li data-lat="${it.lat}" data-lng="${it.lon}">${MPIN.esc(it.display_name)}</li>`).join('')
          : '<li class="muted">No results</li>';
      } catch (e) {
        searchResults.innerHTML = '<li class="muted">Search unavailable</li>';
      }
    }, 450);
  });
  searchResults?.addEventListener('click', (e) => {
    const li = e.target.closest('li[data-lat]');
    if (!li) return;
    map.flyTo([Number(li.dataset.lat), Number(li.dataset.lng)], 8, { duration: 1.2 });
    searchBox.hidden = true;
  });

  /* ============ RESULTS ============ */
  const colorize = (i) => ['#22d3ee', '#3b82f6', '#34d399', '#fbbf24', '#f87171', '#a78bfa', '#f472b6', '#60a5fa', '#4ade80', '#fb923c'][i % 10];

  async function loadResults() {
    let d;
    try {
      d = (await MPIN.api(`${MPIN.base || ''}/api/polls/${encodeURIComponent(slug)}/results`)).data;
    } catch (e) { return; }

    /* headline numbers */
    $('#statParticipants').textContent = MPIN.fmt(d.participants);
    $('#stParticipants').textContent = MPIN.fmt(d.participants);

    /* growth chart */
    if (typeof Chart !== 'undefined') {
      const ctx = document.getElementById('growthChart');
      if (ctx) {
        const labels = d.growth.map((g) => g.date.slice(5));
        new Chart(ctx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'New responses', data: d.growth.map((g) => g.count),
              borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.14)',
              fill: true, tension: .38, borderWidth: 2,
              pointRadius: 2, pointBackgroundColor: '#22d3ee',
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

    /* geo bar lists */
    const barHtml = (rows, key) => rows.map((r) => {
      const label = key === 'city' ? `${r.city}` + (r.country ? ` <span class="muted small">(${MPIN.esc(r.country)})</span>` : '') : MPIN.esc(r.country);
      const max = Math.max(...rows.map((x) => x.c), 1);
      return `<li><span class="bl-label">${label}</span><span class="bl-bar"><i style="width:${Math.max(3, (r.c / max) * 100)}%"></i></span><span class="bl-val">${MPIN.fmt(r.c)}</span></li>`;
    }).join('');
    $('#topCountries').innerHTML = d.top_countries.length
      ? barHtml(d.top_countries, 'country') : '<li class="muted small">No data yet</li>';
    $('#topCities').innerHTML = d.top_cities.length
      ? barHtml(d.top_cities, 'city') : '<li class="muted small">No data yet</li>';

    /* answers */
    const area = $('#answersArea');
    area.innerHTML = '';
    d.questions.forEach((q, qi) => {
      const block = document.createElement('div');
      block.className = 'ans-block';
      let inner = '';
      if (['yesno', 'single', 'multi'].includes(q.type)) {
        const total = q.options.reduce((s, o) => s + o.count, 0) || 1;
        inner = q.options.map((o) => `
          <li>
            <span class="bl-label">${MPIN.esc(o.text)}</span>
            <span class="bl-bar"><i style="width:${(o.count / total) * 100}%; background:linear-gradient(90deg, ${colorize(qi)}, ${colorize(qi + 2)})"></i></span>
            <span class="bl-val">${MPIN.fmt(o.count)} · ${Math.round((o.count / total) * 100)}%</span>
          </li>`).join('');
        inner = `<ul class="bar-list static">${inner}</ul>
                 <p class="muted small">${MPIN.fmt(q.answered)} answer${q.answered === 1 ? '' : 's'}</p>`;
      } else if (q.type === 'rating') {
        const rating = Array.isArray(q.rating) ? q.rating : Object.values(q.rating);
        const total = rating.reduce((a, b) => a + b, 0) || 1;
        inner = `<ul class="bar-list static">` + rating.map((c, i) => `
          <li><span class="bl-label">${i + 1} ★</span>
          <span class="bl-bar"><i style="width:${(c / total) * 100}%; background:linear-gradient(90deg,#fbbf24,#f59e0b)"></i></span>
          <span class="bl-val">${MPIN.fmt(c)} · ${Math.round((c / total) * 100)}%</span></li>`).join('') + `</ul>
          <p class="muted small">Average: <strong style="color:#fbbf24">${q.answered ? (rating.reduce((s, c, i) => s + c * (i + 1), 0) / q.answered).toFixed(1) : '—'}</strong> / 5</p>`;
      } else if (q.type === 'number' && q.number.count) {
        inner = `<p class="kv-line">min <strong>${q.number.min}</strong> · average <strong>${q.number.avg}</strong> · max <strong>${q.number.max}</strong> · <strong>${q.number.count}</strong> answers</p>
                 <div class="chart-box" style="height:130px"><canvas id="histChart-${q.id}" height="130"></canvas></div>`;
      } else if (q.type === 'text') {
        inner = q.texts.length
          ? `<ul class="text-answers">${q.texts.slice(0, 8).map((t) => `<li>“${MPIN.esc(t)}”</li>`).join('')}</ul>`
          : '<p class="muted small">No written answers yet.</p>';
      } else {
        inner = '<p class="muted small">No answers yet.</p>';
      }
      block.innerHTML = `<h3>${MPIN.esc(q.text)} <span class="muted small">· ${q.type}</span></h3>${inner}`;
      area.appendChild(block);

      if (q.type === 'number' && q.number.count && q.number.histogram.length && typeof Chart !== 'undefined') {
        const c = document.getElementById('histChart-' + q.id);
        if (c) {
          new Chart(c, {
            type: 'bar',
            data: {
              labels: q.number.histogram.map((h) => String(h.from)),
              datasets: [{ data: q.number.histogram.map((h) => h.count), backgroundColor: 'rgba(34,211,238,.55)', borderRadius: 6, borderSkipped: false }],
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              plugins: { legend: { display: false }, tooltip: { callbacks: { title: (it) => `≈ ${q.number.histogram[it[0].dataIndex].from} → ${q.number.histogram[it[0].dataIndex].to}` } } },
              scales: {
                x: { grid: { display: false }, ticks: { color: '#8fa3bf', font: { size: 9 }, maxRotation: 0, autoSkip: true } },
                y: { grid: { color: 'rgba(140,160,190,.1)' }, ticks: { color: '#8fa3bf', precision: 0 }, beginAtZero: true },
              },
            },
          });
        }
      }
    });
  }
  loadResults();

  /* ============ FAVORITE / REPORT ============ */
  $('#favBtn')?.addEventListener('click', async () => {
    const btn = $('#favBtn');
    if (btn.dataset.needsLogin) {
      MPIN.toast('Please log in to save polls.', 'err');
      setTimeout(() => { location.href = `${MPIN.base || ''}/login?next=${encodeURIComponent(location.pathname)}`; }, 900);
      return;
    }
    try {
      const res = await MPIN.api(`${MPIN.base || ''}/api/favorites/${btn.dataset.poll}/toggle`, { method: 'POST' });
      btn.classList.toggle('active', res.favorite);
      btn.querySelector('span').textContent = res.favorite ? 'Saved' : 'Save';
      MPIN.toast(res.favorite ? 'Added to your saved polls.' : 'Removed from saved polls.');
    } catch (e) { MPIN.toast(e.message, 'err'); }
  });

  const reportModal = document.getElementById('reportModal');
  $('#reportBtn')?.addEventListener('click', () => { if (reportModal) reportModal.hidden = false; });
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-close]') || e.target.classList.contains('modal')) {
      if (reportModal) reportModal.hidden = true;
    }
  });
  $('#reportForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = e.target;
    try {
      await MPIN.api(`${MPIN.base || ''}/api/reports`, {
        method: 'POST',
        body: { poll_id: Number(f.dataset.poll), reason: f.reason.value, details: f.details.value },
      });
      reportModal.hidden = true;
      f.reset();
      MPIN.toast('Report submitted. Thank you for keeping Mpin safe.');
    } catch (err) { MPIN.toast(err.message, 'err'); }
  });
})();
