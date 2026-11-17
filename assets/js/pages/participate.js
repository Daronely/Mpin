/* MPIN — participate wizard: answers → location → review → submit. */
(() => {
  'use strict';
  const $ = MPIN.$, $$ = MPIN.$$;
  const cfgEl = document.getElementById('participateData');
  if (!cfgEl || typeof L === 'undefined') return;
  const cfg = JSON.parse(cfgEl.textContent);
  const questions = cfg.questions;

  /* ---------- step machinery ---------- */
  const steps = $$('#wizardSteps .step');
  const panels = $$('.wizard-panel');
  let current = 1;

  function goTo(n) {
    current = n;
    panels.forEach((p) => p.classList.toggle('active', Number(p.dataset.panel) === n));
    steps.forEach((s) => {
      const sn = Number(s.dataset.step);
      s.classList.toggle('active', sn === n);
      s.classList.toggle('done', sn < n);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (n === 2) setTimeout(() => pickMap.invalidateSize(), 60);
  }
  $$('[data-back]').forEach((b) => b.addEventListener('click', () => goTo(Number(b.dataset.back))));

  /* ---------- star rating UI ---------- */
  $$('.rating').forEach((group) => {
    const stars = $$('.star-btn', group);
    stars.forEach((star, i) => {
      star.addEventListener('mouseenter', () => stars.forEach((s, j) => s.classList.toggle('on', j <= i)));
      star.addEventListener('mouseleave', () => paint(group));
      star.addEventListener('click', () => {
        group.dataset.value = String(i + 1);
        $$('input', group).forEach((inp) => { inp.checked = Number(inp.value) === i + 1; });
        paint(group);
      });
    });
    function paint(g) {
      const v = Number(g.dataset.value || 0);
      stars.forEach((s, j) => s.classList.toggle('on', j < v));
    }
  });

  /* ---------- collect + validate answers ---------- */
  function collectAnswers() {
    const answers = {};
    let ok = true;
    questions.forEach((q) => {
      const field = document.querySelector(`.q-card[data-qid="${q.id}"]`);
      const errEl = field.querySelector('.q-error');
      errEl.hidden = true;
      let value = null;

      if (q.type === 'yesno' || q.type === 'single' || q.type === 'rating') {
        const picked = field.querySelector('input:checked');
        value = picked ? { [q.type === 'rating' ? 'rating' : 'option_id']: Number(picked.value) } : null;
      } else if (q.type === 'multi') {
        const picked = $$('input:checked', field);
        value = picked.length ? { option_ids: picked.map((i) => Number(i.value)) } : null;
      } else if (q.type === 'text') {
        const t = field.querySelector('textarea').value.trim();
        value = t ? { text: t } : null;
      } else if (q.type === 'number') {
        const n = field.querySelector('input').value;
        value = n !== '' ? { number: Number(n) } : null;
      }

      if (!value && q.required) {
        ok = false;
        errEl.textContent = 'This question is required.';
        errEl.hidden = false;
      }
      if (value) answers[q.id] = value;
    });
    return ok ? answers : null;
  }

  $('#answersForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const answers = collectAnswers();
    if (!answers) { MPIN.toast('Please complete the required questions.', 'err'); return; }
    window.__mpinAnswers = answers;
    buildReview(answers);
    goTo(2);
  });

  /* ---------- location picker ---------- */
  const pickMap = L.map('pickMap', { center: [26, 10], zoom: 2, worldCopyJump: true, minZoom: 2 });
  const pickTiles = MPIN.tiles();
  L.tileLayer(pickTiles.url, { attribution: pickTiles.attr, maxZoom: 19 }).addTo(pickMap);


  const fixPickSize = () => pickMap.invalidateSize();
  window.addEventListener('load', fixPickSize);
  window.addEventListener('resize', fixPickSize);
  setTimeout(fixPickSize, 300);
  setTimeout(fixPickSize, 1200);

  let picked = null;
  let marker = null;
  const confirmBox = $('#locConfirm');
  const locLabel = $('#locLabel');
  const toStep3 = $('#toStep3');
  const shareChk = $('#shareLocation');

  function setPoint(lat, lng, label) {
    picked = { lat: Number(lat.toFixed(5)), lng: Number(lng.toFixed(5)) };
    if (label) { picked.label = label; }
    if (marker) pickMap.removeLayer(marker);
    marker = L.marker([lat, lng], {
      icon: L.divIcon({ className: 'pin-marker', iconSize: [30, 30], iconAnchor: [15, 30],
        html: '<span class="pin-dot pin-m" style="position:absolute;left:50%;top:50%;translate:-50% -60%;"></span>' }),
      draggable: true,
    }).addTo(pickMap);
    marker.on('dragend', () => {
      const p = marker.getLatLng();
      setPoint(p.lat, p.lng, picked ? picked.label : null);
    });
    confirmBox.hidden = false;
    locLabel.textContent = label || `${picked.lat.toFixed(2)}°, ${picked.lng.toFixed(2)}°`;
    checkReady();
  }

  function checkReady() {
    toStep3.disabled = !(shareChk.checked && picked);
  }
  shareChk.addEventListener('change', checkReady);
  $('#locClear').addEventListener('click', () => {
    picked = null;
    if (marker) { pickMap.removeLayer(marker); marker = null; }
    confirmBox.hidden = true;
    checkReady();
  });

  pickMap.on('click', async (e) => {
    if (!shareChk.checked) {
      MPIN.toast('Check “Pin my approximate location” first, or continue without a pin.', 'err');
      shareChk.focus();
      return;
    }
    setPoint(e.latlng.lat, e.latlng.wrap().lng);
    locLabel.textContent = 'Looking up place…';
    reverseGeocode(e.latlng.lat, e.latlng.wrap().lng);
  });

  async function reverseGeocode(lat, lng) {
    try {
      const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&zoom=10&accept-language=en&lat=${lat}&lon=${lng}`, { headers: { Accept: 'application/json' } });
      const d = await r.json();
      const a = d.address || {};
      const city = a.city || a.town || a.village || a.county || a.state || '';
      const region = a.state || '';
      const country = a.country || '';
      if (picked) {
        picked.city = city; picked.region = region; picked.country = country;
        locLabel.textContent = [city, country].filter(Boolean).join(', ') || `${picked.lat.toFixed(2)}°, ${picked.lng.toFixed(2)}°`;
      }
    } catch (e) { /* keep coords only */ }
  }

  $('#gpsBtn').addEventListener('click', () => {
    if (!navigator.geolocation) { MPIN.toast('Geolocation is not supported by your browser.', 'err'); return; }
    MPIN.toast('Requesting your location…');
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        shareChk.checked = true;
        checkReady();
        setPoint(pos.coords.latitude, pos.coords.longitude);
        pickMap.flyTo([pos.coords.latitude, pos.coords.longitude], 8, { duration: 1 });
        reverseGeocode(pos.coords.latitude, pos.coords.longitude);
      },
      () => MPIN.toast('Location permission denied — pick a point manually instead.', 'err'),
      { timeout: 10000, maximumAge: 60000 }
    );
  });

  /* ---------- review ---------- */
  function buildReview(answers) {
    const box = $('#reviewList');
    box.innerHTML = '';
    questions.forEach((q) => {
      const a = answers[q.id];
      let text = '— skipped —';
      if (a) {
        if (a.option_id !== undefined) {
          const opt = q.options.find((o) => o.id === a.option_id);
          text = opt ? opt.text : '?';
        } else if (a.option_ids) {
          text = a.option_ids.map((id) => (q.options.find((o) => o.id === id) || {}).text || '?').join(', ');
        } else if (a.text) text = '“' + a.text + '”';
        else if (a.number !== undefined) text = String(a.number);
        else if (a.rating) text = a.rating + ' / 5 ★';
      }
      const item = document.createElement('div');
      item.className = 'review-item';
      item.innerHTML = `<span class="rq">${MPIN.esc(q.text)}</span><span class="ra">${MPIN.esc(text)}</span>`;
      box.appendChild(item);
    });
    const locItem = document.createElement('div');
    locItem.className = 'review-item';
    locItem.innerHTML = `<span class="rq">Location</span>` +
      (picked && shareChk.checked
        ? `<span class="ra">${MPIN.esc(picked.label || `${picked.lat}°, ${picked.lng}°`)}</span><span class="muted">Approximate position — snapped to a ~5 km privacy grid before saving.</span>`
        : '<span class="muted">No location shared (that’s okay!).</span>');
    box.appendChild(locItem);
  }

  toStep3.addEventListener('click', () => goTo(3));

  /* ---------- submit ---------- */
  $('#submitResponse').addEventListener('click', async () => {
    const btn = $('#submitResponse');
    btn.disabled = true;
    btn.textContent = 'Submitting…';
    const payload = {
      answers: window.__mpinAnswers || {},
      location: (picked && shareChk.checked)
        ? { lat: picked.lat, lng: picked.lng, city: picked.city || null, region: picked.region || null, country: picked.country || null }
        : null,
    };
    try {
      await MPIN.api(`${MPIN.base || ''}/api/polls/${encodeURIComponent(cfg.slug)}/responses`, { method: 'POST', body: payload });
      goTo(4);
    } catch (e) {
      MPIN.toast(e.message, 'err');
      if (e.data && e.data.errors) {
        goTo(1);
        Object.entries(e.data.errors).forEach(([k, msg]) => {
          const qid = k.replace('q', '');
          const field = document.querySelector(`.q-card[data-qid="${qid}"] .q-error`);
          if (field) { field.textContent = msg; field.hidden = false; }
        });
      }
      btn.disabled = false;
      btn.innerHTML = MPIN.esc('Submit response');
    }
  });
})();
