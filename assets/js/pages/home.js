/* MPIN — home page: hero mini-map. */
(() => {
  'use strict';
  const dataEl = document.getElementById('heroMapData');
  const mapEl = document.getElementById('heroMap');
  if (!mapEl || typeof L === 'undefined') return;

  const data = JSON.parse(dataEl ? dataEl.textContent : '{}');
  const map = L.map(mapEl, {
    center: [28, 12], zoom: 2, zoomControl: false,
    attributionControl: true, scrollWheelZoom: false, worldCopyJump: true,
  });
  const tiles = MPIN.tiles();
  const layer = L.tileLayer(tiles.url, { attribution: tiles.attr, maxZoom: 19 }).addTo(map);


  // Guard against layout races: re-measure the container once styles/layout settle.
  const fixSize = () => map.invalidateSize();
  window.addEventListener('load', fixSize);
  window.addEventListener('resize', fixSize);
  setTimeout(fixSize, 300);
  setTimeout(fixSize, 1200);
  const cluster = L.markerClusterGroup({
    maxClusterRadius: 34,
    spiderfyOnMaxZoom: false,
    showCoverageOnHover: false,
  });
  (data.points || []).forEach((p) => {
    const size = p.count >= 40 ? 'l' : p.count >= 10 ? 'm' : 's';
    const marker = L.marker([p.lat, p.lng], {
      icon: L.divIcon({
        className: 'pin-marker',
        html: `<span class="pin-dot pin-${size}"></span><span class="pin-count">${MPIN.fmt(p.count)}</span>`,
        iconSize: [30, 30], iconAnchor: [15, 15],
      }),
    });
    marker.bindPopup(
      `<p class="map-popup-title">${MPIN.esc(p.city ? p.city + ', ' + (p.country || '') : p.country || 'Somewhere on Earth')}</p>` +
      `<p class="map-popup-sub">${MPIN.fmt(p.count)} responses</p>`
    );
    cluster.addLayer(marker);
  });
  map.addLayer(cluster);
})();
