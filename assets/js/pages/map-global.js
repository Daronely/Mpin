/* MPIN — global pulse map. */
(() => {
  'use strict';
  const $ = MPIN.$, $$ = MPIN.$$;
  const mapEl = document.getElementById('globalMap');
  if (!mapEl || typeof L === 'undefined') return;

  const map = L.map(mapEl, { center: [26, 10], zoom: 2, worldCopyJump: true, minZoom: 2 });
  const tiles = MPIN.tiles();
  L.tileLayer(tiles.url, { attribution: tiles.attr, maxZoom: 19 }).addTo(map);


  // Guard against layout races: re-measure the container once styles/layout settle.
  const fixSize = () => map.invalidateSize();
  window.addEventListener('load', fixSize);
  window.addEventListener('resize', fixSize);
  setTimeout(fixSize, 300);
  setTimeout(fixSize, 1200);
  const clusterGroup = L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 46 });
  let heatLayer = null;
  let mode = 'cluster';

  function pinIcon(count) {
    const size = count >= 60 ? 'l' : count >= 15 ? 'm' : 's';
    return L.divIcon({
      className: 'pin-marker',
      html: `<span class="pin-dot pin-${size}"></span><span class="pin-count">${MPIN.fmt(count)}</span>`,
      iconSize: [30, 30], iconAnchor: [15, 15],
    });
  }

  async function load() {
    try {
      const res = await MPIN.api((MPIN.base || '') + '/api/map/global');
      const points = res.data.points || [];
      clusterGroup.clearLayers();
      if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
      if (!points.length) {
        $('#mapMeta').textContent = MPIN.t('gmap.no_data');
        return;
      }
      const maxC = Math.max(...points.map((p) => p.count), 1);
      const heatData = [];
      points.forEach((p) => {
        const marker = L.marker([p.lat, p.lng], { icon: pinIcon(p.count) });
        marker.bindPopup(
          `<p class="map-popup-title">${MPIN.esc(p.city ? p.city + ', ' + (p.country || '') : p.country || MPIN.t('map.somewhere'))}</p>` +
          `<p class="map-popup-sub">${MPIN.t('map.responses_from', { n: MPIN.fmt(p.count), p: MPIN.fmt(p.polls) })}</p>`
        );
        clusterGroup.addLayer(marker);
        heatData.push([p.lat, p.lng, 0.25 + 0.75 * (p.count / maxC)]);
      });
      heatLayer = L.heatLayer(heatData, { radius: 26, blur: 20, maxZoom: 8,
        gradient: { 0.2: '#3b82f6', 0.5: '#22d3ee', 0.8: '#fbbf24', 1: '#f87171' } });
      applyMode();
      $('#mapMeta').textContent = MPIN.t('gmap.updated', { n: MPIN.fmt(points.length), time: MPIN.locale === 'fa' ? new Date().toLocaleTimeString('fa-IR') : new Date().toLocaleTimeString() });
    } catch (e) {
      $('#mapMeta').textContent = MPIN.t('poll.map_load_fail');
    }
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

  /* search */
  const searchBox = document.getElementById('mapSearch');
  const searchInput = document.getElementById('mapSearchInput');
  const searchResults = document.getElementById('mapSearchResults');
  let timer = null;
  document.getElementById('mapSearchBtn').addEventListener('click', () => {
    searchBox.hidden = !searchBox.hidden;
    if (!searchBox.hidden) searchInput.focus();
  });
  searchInput.addEventListener('input', () => {
    clearTimeout(timer);
    const q = searchInput.value.trim();
    if (q.length < 3) { searchResults.innerHTML = ''; return; }
    timer = setTimeout(async () => {
      try {
        const r = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&accept-language=en&q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
        const items = await r.json();
        searchResults.innerHTML = items.length
          ? items.map((it) => `<li data-lat="${it.lat}" data-lng="${it.lon}">${MPIN.esc(it.display_name)}</li>`).join('')
          : '<li class="muted">No results</li>';
      } catch (e) { searchResults.innerHTML = '<li class="muted">Search unavailable</li>'; }
    }, 450);
  });
  searchResults.addEventListener('click', (e) => {
    const li = e.target.closest('li[data-lat]');
    if (!li) return;
    map.flyTo([Number(li.dataset.lat), Number(li.dataset.lng)], 8, { duration: 1.2 });
    searchBox.hidden = true;
  });

  load();
  setInterval(() => { if (document.visibilityState === 'visible') load(); }, 60000);
})();
