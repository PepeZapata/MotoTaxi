<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mototaxi - Ciudadano</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.4.0/pusher.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
  :root {
    --primary: #1d4ed8;
    --primary-dark: #1e40af;
    --bg: #f8fafc;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
    --success: #16a34a;
    --danger: #dc2626;
  }
  * { box-sizing: border-box; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
    padding: 0;
    min-height: 100vh;
  }
  .app { max-width: 480px; margin: 0 auto; padding: 24px 16px 60px; }
  header { text-align: center; margin-bottom: 24px; }
  header h1 { font-size: 22px; margin: 0; }
  header p { color: var(--muted); font-size: 13px; margin: 4px 0 0; }

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  }
  .card h2 { font-size: 16px; margin: 0 0 14px; }

  label { display: block; font-size: 13px; color: var(--muted); margin: 10px 0 4px; }
  input, select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
  }
  input:focus { outline: none; border-color: var(--primary); }

  button {
    width: 100%;
    padding: 11px;
    border: none;
    border-radius: 8px;
    background: var(--primary);
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 14px;
  }
  button:hover { background: var(--primary-dark); }
  button.secondary { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
  button.secondary:hover { background: #eff6ff; }
  button:disabled { opacity: 0.5; cursor: not-allowed; }

  .error { color: var(--danger); font-size: 13px; margin-top: 8px; }
  .hint { color: var(--muted); font-size: 12px; margin-top: 6px; }

  .hidden { display: none !important; }

  .status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
  }
  .status-open { background: #fef9c3; color: #854d0e; }
  .status-assigned, .status-in_progress { background: #dbeafe; color: #1e40af; }
  .status-completed { background: #dcfce7; color: #166534; }
  .status-cancelled { background: #fee2e2; color: #991b1b; }

  .driver-info { display: flex; align-items: center; gap: 12px; }
  .driver-avatar {
    width: 40px; height: 40px; border-radius: 50%; background: var(--primary);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; flex-shrink: 0;
  }
  .driver-info div p { margin: 0; }
  .driver-info .name { font-weight: 600; font-size: 13px; }
  .driver-info .plate { font-size: 12px; color: var(--muted); }

  .timeline { margin-top: 14px; padding-left: 4px; }
  .timeline .step { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: 13px; color: var(--muted); }
  .timeline .step.done { color: var(--text); }
  .timeline .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border); flex-shrink: 0; }
  .timeline .step.done .dot { background: var(--success); }

  .userbar { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--muted); margin-bottom: 16px; }
  .userbar button { width: auto; margin: 0; padding: 6px 12px; font-size: 12px; }

  #mapWrapper { position: relative; }
  #map { height: 320px; border-radius: 10px; z-index: 0; }

  .floating-card {
    position: absolute;
    left: 10px; right: 10px; bottom: 10px;
    background: white;
    border-radius: 10px;
    padding: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.18);
    z-index: 500;
  }
  .floating-card .status-line { font-weight: 600; font-size: 13px; margin-bottom: 6px; }

  .map-toggle { display: flex; gap: 8px; margin: 10px 0; }
  .map-mode {
    flex: 1; margin: 0; padding: 8px; font-size: 12px; font-weight: 600;
    background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); border-radius: 8px;
  }
  .map-mode.active.origin-mode { background: #16a34a; color: white; border-color: #16a34a; }
  .map-mode.active.dest-mode { background: #dc2626; color: white; border-color: #dc2626; }

  .autocomplete-wrap { position: relative; }
  .suggestions {
    position: absolute; left: 0; right: 0; top: 100%;
    background: white; border: 1px solid var(--border); border-top: none;
    border-radius: 0 0 8px 8px; max-height: 220px; overflow-y: auto; z-index: 1000;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  }
  .suggestions .item { padding: 10px 12px; font-size: 13px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
  .suggestions .item:last-child { border-bottom: none; }
  .suggestions .item:hover { background: #f8fafc; }
  .suggestions .item.loading { color: var(--muted); }
</style>
</head>
<body>
<div class="app">
  <header>
    <h1>Mototaxi</h1>
    <p>App del ciudadano</p>
  </header>

  <div id="loginView" class="card">
    <h2>Iniciar sesion</h2>
    <label>Correo</label>
    <input id="loginEmail" type="email" placeholder="ciudadano1@mototaxi.test" value="ciudadano1@mototaxi.test">
    <label>Contrasena</label>
    <input id="loginPassword" type="password" placeholder="password" value="password">
    <button onclick="login()">Entrar</button>
    <div id="loginError" class="error hidden"></div>
  </div>

  <div id="appView" class="hidden">
    <div class="userbar">
      <span id="userGreeting"></span>
      <button class="secondary" onclick="logout()">Cerrar sesion</button>
    </div>

    <div class="card">
      <div id="mapWrapper">
        <div id="map"></div>
        <div id="floatingCard" class="floating-card hidden">
          <p class="status-line" id="floatingStatusLine"></p>
          <div id="floatingDriverBox" class="driver-info hidden">
            <div class="driver-avatar" id="driverInitial">?</div>
            <div>
              <p class="name" id="driverName"></p>
              <p class="plate" id="driverPlate"></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="createView" class="card">
      <h2>A donde vas?</h2>

      <label>Origen</label>
      <div class="autocomplete-wrap">
        <input id="originAddress" placeholder="Escribe una direccion o toca el mapa" autocomplete="off">
        <div id="originSuggestions" class="suggestions hidden"></div>
      </div>

      <label>Destino</label>
      <div class="autocomplete-wrap">
        <input id="destAddress" placeholder="Escribe una direccion o toca el mapa" autocomplete="off">
        <div id="destSuggestions" class="suggestions hidden"></div>
      </div>

      <div class="map-toggle">
        <button type="button" class="map-mode active" id="modeOriginBtn" onclick="setMapMode('origin')">Tocar mapa: origen</button>
        <button type="button" class="map-mode" id="modeDestBtn" onclick="setMapMode('destination')">Tocar mapa: destino</button>
      </div>
      <button class="secondary" onclick="useMyLocation()">Usar mi ubicacion actual como origen</button>

      <button onclick="createTrip()" id="createBtn">Pedir mototaxi</button>
      <div id="createError" class="error hidden"></div>
    </div>

    <div id="tripView" class="card hidden">
      <h2>Tu viaje <span id="tripBadge" class="status-badge"></span></h2>
      <div class="timeline" id="tripTimeline"></div>
      <p class="hint" id="realtimeStatus">Conectando al canal en vivo...</p>
      <button class="secondary" onclick="newTrip()">Pedir otro viaje</button>
    </div>
  </div>
</div>

<script>
  const API_BASE = window.location.origin + '/api';

  const REVERB_APP_KEY = '{{ config('broadcasting.connections.reverb.key') }}';
  const REVERB_HOST = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const REVERB_PORT = {{ config('broadcasting.connections.reverb.options.port', 443) }};
  const FORCE_TLS = '{{ config('broadcasting.connections.reverb.options.scheme', 'https') }}' === 'https';

  let token = localStorage.getItem('mototaxi_token') || null;
  let user = JSON.parse(localStorage.getItem('mototaxi_user') || 'null');
  let currentTripId = localStorage.getItem('mototaxi_trip_id') || null;
  let pusher = null;
  let tripChannel = null;

  async function api(path, options = {}) {
    const res = await fetch(API_BASE + path, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(token ? { Authorization: 'Bearer ' + token } : {}),
        ...(options.headers || {}),
      },
      body: options.body ? JSON.stringify(options.body) : undefined,
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Error desconocido');
      throw new Error(msg);
    }

    return data;
  }

  function show(id) { document.getElementById(id).classList.remove('hidden'); }
  function hide(id) { document.getElementById(id).classList.add('hidden'); }

  async function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    hide('loginError');

    try {
      const data = await api('/auth/login', { method: 'POST', body: { email, password } });
      token = data.token;
      user = data.user;
      localStorage.setItem('mototaxi_token', token);
      localStorage.setItem('mototaxi_user', JSON.stringify(user));
      boot();
    } catch (e) {
      const el = document.getElementById('loginError');
      el.textContent = e.message;
      show('loginError');
    }
  }

  function logout() {
    localStorage.removeItem('mototaxi_token');
    localStorage.removeItem('mototaxi_user');
    localStorage.removeItem('mototaxi_trip_id');
    token = null; user = null; currentTripId = null;
    if (pusher) pusher.disconnect();
    hide('appView');
    show('loginView');
  }

  let map = null;
  let originMarker = null;
  let destMarker = null;
  let driverMarker = null;
  let routeLine = null;
  let mapMode = 'origin';
  let originCoords = null;
  let destCoords = null;
  let nearbyMarkers = {};
  let nearbyPollInterval = null;

  const DEFAULT_CENTER = [20.9674, -89.5926];

  function pinIcon(color) {
    return L.divIcon({
      html: '<div style="background:' + color + ';width:20px;height:20px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,0.4)"></div>',
      iconSize: [20, 20],
      iconAnchor: [10, 20],
      className: '',
    });
  }

  function motoIcon() {
    return L.divIcon({
      html: '<div style="font-size:20px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4));">M</div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
      className: '',
    });
  }

  function initMap() {
    if (map) return;
    map = L.map('map').setView(DEFAULT_CENTER, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    map.on('click', function (e) { placePin(mapMode, e.latlng.lat, e.latlng.lng); });
    setMapMode('origin');
    startNearbyPolling();
  }

  function setMapMode(mode) {
    mapMode = mode;
    const originBtn = document.getElementById('modeOriginBtn');
    const destBtn = document.getElementById('modeDestBtn');
    originBtn.classList.toggle('active', mode === 'origin');
    originBtn.classList.toggle('origin-mode', mode === 'origin');
    destBtn.classList.toggle('active', mode === 'destination');
    destBtn.classList.toggle('dest-mode', mode === 'destination');
  }

  function placePin(type, lat, lng, skipAddressUpdate) {
    const isOrigin = type === 'origin';

    if (isOrigin) {
      if (originMarker) originMarker.setLatLng([lat, lng]);
      else {
        originMarker = L.marker([lat, lng], { icon: pinIcon('#16a34a'), draggable: true }).addTo(map);
        originMarker.on('dragend', function () {
          const p = originMarker.getLatLng();
          setOriginCoords(p.lat, p.lng, false);
        });
      }
      setOriginCoords(lat, lng, skipAddressUpdate);
      if (!destCoords) setMapMode('destination');
    } else {
      if (destMarker) destMarker.setLatLng([lat, lng]);
      else {
        destMarker = L.marker([lat, lng], { icon: pinIcon('#dc2626'), draggable: true }).addTo(map);
        destMarker.on('dragend', function () {
          const p = destMarker.getLatLng();
          setDestCoords(p.lat, p.lng, false);
        });
      }
      setDestCoords(lat, lng, skipAddressUpdate);
    }

    maybeDrawPreviewRoute();
  }

  function setOriginCoords(lat, lng, skipAddressUpdate) {
    originCoords = { lat: lat, lng: lng };
    if (!skipAddressUpdate) reverseGeocode(lat, lng, 'originAddress');
  }

  function setDestCoords(lat, lng, skipAddressUpdate) {
    destCoords = { lat: lat, lng: lng };
    if (!skipAddressUpdate) reverseGeocode(lat, lng, 'destAddress');
  }

  function maybeDrawPreviewRoute() {
    if (originCoords && destCoords) {
      drawRoute(originCoords, destCoords, '#1d4ed8');
    }
  }

  async function reverseGeocode(lat, lng, inputId) {
    const input = document.getElementById(inputId);
    input.value = 'Buscando direccion...';
    try {
      const res = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng);
      const data = await res.json();
      input.value = data.display_name || (lat.toFixed(5) + ', ' + lng.toFixed(5));
    } catch (e) {
      input.value = lat.toFixed(5) + ', ' + lng.toFixed(5);
    }
  }

  function setupAutocomplete(inputId, suggestionsId, type) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(suggestionsId);
    let debounceTimer = null;

    input.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      const q = input.value.trim();
      if (q.length < 3) { box.classList.add('hidden'); box.innerHTML = ''; return; }

      box.innerHTML = '<div class="item loading">Buscando...</div>';
      box.classList.remove('hidden');

      debounceTimer = setTimeout(async function () {
        try {
          const center = map.getCenter();
          const url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) +
            '&limit=5&addressdetails=0&viewbox=' + (center.lng - 0.3) + ',' + (center.lat + 0.3) + ',' + (center.lng + 0.3) + ',' + (center.lat - 0.3) + '&bounded=0';
          const res = await fetch(url);
          const results = await res.json();

          if (!results.length) {
            box.innerHTML = '<div class="item loading">Sin resultados</div>';
            return;
          }

          box.innerHTML = results.map(function (r, i) {
            return '<div class="item" data-i="' + i + '">' + r.display_name + '</div>';
          }).join('');

          box.querySelectorAll('.item').forEach(function (el, i) {
            el.addEventListener('click', function () {
              const r = results[i];
              input.value = r.display_name;
              box.classList.add('hidden');
              const lat = parseFloat(r.lat), lng = parseFloat(r.lon);
              map.setView([lat, lng], 16);
              placePin(type, lat, lng, true);
            });
          });
        } catch (e) {
          box.innerHTML = '<div class="item loading">Error buscando. Intenta tocar el mapa.</div>';
        }
      }, 400);
    });

    document.addEventListener('click', function (e) {
      if (!box.contains(e.target) && e.target !== input) box.classList.add('hidden');
    });
  }

  function useMyLocation() {
    if (!navigator.geolocation) {
      alert('Tu navegador no soporta geolocalizacion.');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        map.setView([pos.coords.latitude, pos.coords.longitude], 15);
        placePin('origin', pos.coords.latitude, pos.coords.longitude);
      },
      function () { alert('No se pudo obtener tu ubicacion. Marca el origen tocando el mapa.'); }
    );
  }

  function startNearbyPolling() {
    stopNearbyPolling();
    pollNearby();
    nearbyPollInterval = setInterval(pollNearby, 8000);
  }

  function stopNearbyPolling() {
    if (nearbyPollInterval) clearInterval(nearbyPollInterval);
    nearbyPollInterval = null;
    Object.values(nearbyMarkers).forEach(function (m) { map.removeLayer(m); });
    nearbyMarkers = {};
  }

  async function pollNearby() {
    if (currentTripId) return;
    try {
      const center = originCoords || map.getCenter();
      const lat = center.lat, lng = center.lng;
      const drivers = await api('/drivers/nearby?lat=' + lat + '&lng=' + lng + '&radius_km=5');

      const seenIds = new Set();
      drivers.forEach(function (d) {
        seenIds.add(d.id);
        if (nearbyMarkers[d.id]) {
          nearbyMarkers[d.id].setLatLng([d.lat, d.lng]);
        } else {
          nearbyMarkers[d.id] = L.marker([d.lat, d.lng], { icon: motoIcon() }).addTo(map);
        }
      });

      Object.keys(nearbyMarkers).forEach(function (id) {
        if (!seenIds.has(Number(id))) {
          map.removeLayer(nearbyMarkers[id]);
          delete nearbyMarkers[id];
        }
      });
    } catch (e) {
      // silencioso
    }
  }

  async function drawRoute(from, to, color) {
    try {
      const url = 'https://router.project-osrm.org/route/v1/driving/' + from.lng + ',' + from.lat + ';' + to.lng + ',' + to.lat + '?overview=full&geometry=geojson';
      const res = await fetch(url);
      const data = await res.json();
      const coords = data.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; });

      if (routeLine) map.removeLayer(routeLine);
      routeLine = L.polyline(coords, { color: color, weight: 4, opacity: 0.7 }).addTo(map);
    } catch (e) {
      if (routeLine) map.removeLayer(routeLine);
      routeLine = L.polyline([[from.lat, from.lng], [to.lat, to.lng]], { color: color, weight: 3, opacity: 0.5, dashArray: '6 6' }).addTo(map);
    }
  }

  function animateMarkerTo(marker, newLat, newLng) {
    const start = marker.getLatLng();
    const startTime = performance.now();
    const duration = 1000;

    function step(now) {
      const t = Math.min(1, (now - startTime) / duration);
      const lat = start.lat + (newLat - start.lat) * t;
      const lng = start.lng + (newLng - start.lng) * t;
      marker.setLatLng([lat, lng]);
      if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  async function createTrip() {
    hide('createError');

    if (!originCoords || !destCoords) {
      const el = document.getElementById('createError');
      el.textContent = 'Marca el origen y el destino (escribiendo la direccion o tocando el mapa) antes de continuar.';
      show('createError');
      return;
    }

    document.getElementById('createBtn').disabled = true;

    try {
      const body = {
        origin_lat: originCoords.lat,
        origin_lng: originCoords.lng,
        origin_address: document.getElementById('originAddress').value || null,
        destination_lat: destCoords.lat,
        destination_lng: destCoords.lng,
        destination_address: document.getElementById('destAddress').value || null,
      };

      const trip = await api('/service-requests', { method: 'POST', body: body });
      currentTripId = trip.id;
      localStorage.setItem('mototaxi_trip_id', currentTripId);

      hide('createView');
      stopNearbyPolling();
      showFloating('Buscando un conductor cercano...');
      await loadTrip();
      connectRealtime();
    } catch (e) {
      const el = document.getElementById('createError');
      el.textContent = e.message;
      show('createError');
    } finally {
      document.getElementById('createBtn').disabled = false;
    }
  }

  function newTrip() {
    currentTripId = null;
    localStorage.removeItem('mototaxi_trip_id');
    if (tripChannel) pusher.unsubscribe(tripChannel.name);

    hide('tripView');
    show('createView');
    hideFloating();

    if (originMarker) { map.removeLayer(originMarker); originMarker = null; }
    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
    if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }
    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
    originCoords = null; destCoords = null;
    document.getElementById('originAddress').value = '';
    document.getElementById('destAddress').value = '';
    setMapMode('origin');
    startNearbyPolling();
    setTimeout(function () { map.invalidateSize(); }, 100);
  }

  function showFloating(statusLine, driver) {
    show('floatingCard');
    document.getElementById('floatingStatusLine').textContent = statusLine;

    if (driver) {
      show('floatingDriverBox');
      document.getElementById('driverInitial').textContent = (driver.name || '?').charAt(0);
      document.getElementById('driverName').textContent = driver.name || 'Conductor';
      document.getElementById('driverPlate').textContent = driver.plate ? ('Placas: ' + driver.plate) : (driver.phone ? ('Tel: ' + driver.phone) : '');
    } else {
      hide('floatingDriverBox');
    }
  }

  function hideFloating() {
    hide('floatingCard');
  }

  async function loadTrip() {
    if (!currentTripId) return;
    const trip = await api('/service-requests/' + currentTripId);
    renderTrip(trip);
  }

  const STATUS_MESSAGES = {
    open: 'Buscando un conductor cercano...',
    accepted: 'Tu mototaxi esta en camino',
    en_route_to_pickup: 'Tu mototaxi esta en camino',
    arrived: 'Tu conductor llego al punto de recogida',
    started: 'Viaje en curso',
    finished: 'Viaje terminado. Buen viaje!',
  };

  function renderTrip(trip) {
    show('tripView');

    const badge = document.getElementById('tripBadge');
    badge.textContent = trip.status;
    badge.className = 'status-badge status-' + trip.status;

    const steps = [
      { key: 'requested', label: 'Solicitud creada' },
      { key: 'accepted', label: 'Conductor asignado' },
      { key: 'started', label: 'Viaje en curso' },
      { key: 'finished', label: 'Viaje terminado' },
    ];
    const doneStatuses = (trip.statusLogs || []).map(function (l) { return l.status; });
    document.getElementById('tripTimeline').innerHTML = steps.map(function (s) {
      return '<div class="step ' + (doneStatuses.includes(s.key) ? 'done' : '') + '"><span class="dot"></span> ' + s.label + '</div>';
    }).join('');

    const assignment = trip.assignment;
    const assignmentStatus = assignment ? (assignment.acceptanceStatus || assignment.acceptance_status) : null;
    const statusKey = assignmentStatus || 'open';

    if (assignment && assignment.driverProfile) {
      const driverUser = assignment.driverProfile.user;
      const plate = assignment.vehicle ? assignment.vehicle.plate : null;

      showFloating(STATUS_MESSAGES[statusKey] || 'Viaje en curso', {
        name: driverUser ? driverUser.name : null,
        phone: driverUser ? driverUser.phone : null,
        plate: plate,
      });

      const loc = assignment.driverProfile.location;
      if (!driverMarker && loc) {
        driverMarker = L.marker([loc.latitude, loc.longitude], { icon: motoIcon() }).addTo(map);
      }

      if (['started', 'in_progress'].includes(trip.status) && trip.origin_lat) {
        drawRoute(
          { lat: parseFloat(trip.origin_lat), lng: parseFloat(trip.origin_lng) },
          { lat: parseFloat(trip.destination_lat), lng: parseFloat(trip.destination_lng) },
          '#1d4ed8'
        );
      } else if (driverMarker && trip.origin_lat && !['started', 'in_progress'].includes(trip.status)) {
        const dp = driverMarker.getLatLng();
        drawRoute({ lat: dp.lat, lng: dp.lng }, { lat: parseFloat(trip.origin_lat), lng: parseFloat(trip.origin_lng) }, '#16a34a');
      }
    } else {
      showFloating(STATUS_MESSAGES.open);
    }

    if (trip.status === 'completed' || trip.status === 'finished') {
      showFloating('Viaje terminado. Buen viaje!');
      setTimeout(function () { newTrip(); }, 3000);
    }

    if (trip.status === 'cancelled') {
      showFloating('El viaje fue cancelado.');
      setTimeout(function () { newTrip(); }, 3000);
    }
  }

  function connectRealtime() {
    if (!user) return;

    api('/auth/me').then(function (me) {
      const citizenProfileId = me.citizenProfile ? me.citizenProfile.id : null;
      if (!citizenProfileId) return;

      if (!pusher) {
        pusher = new Pusher(REVERB_APP_KEY, {
          cluster: 'mt1',
          wsHost: REVERB_HOST,
          wsPort: REVERB_PORT,
          wssPort: REVERB_PORT,
          forceTLS: FORCE_TLS,
          enabledTransports: FORCE_TLS ? ['ws', 'wss'] : ['ws'],
          disableStats: true,
          authEndpoint: window.location.origin + '/broadcasting/auth',
          auth: { headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' } },
        });

        pusher.connection.bind('connected', function () {
          document.getElementById('realtimeStatus').textContent = 'Conectado - esperando actualizaciones...';
        });
        pusher.connection.bind('error', function () {
          document.getElementById('realtimeStatus').textContent = 'No se pudo conectar al servidor en tiempo real.';
        });
      }

      tripChannel = pusher.subscribe('private-citizen.' + citizenProfileId);

      tripChannel.bind('pusher:subscription_succeeded', function () {
        document.getElementById('realtimeStatus').textContent = 'Escuchando actualizaciones en vivo de tu viaje.';
      });

      tripChannel.bind('trip-assignment.accepted', function (data) {
        if (data.service_request_id != currentTripId) return;
        loadTrip();
      });

      tripChannel.bind('trip-status.updated', function (data) {
        if (data.service_request_id != currentTripId) return;
        loadTrip();
      });

      tripChannel.bind('driver-location.updated', function (data) {
        if (data.service_request_id != currentTripId) return;

        if (!driverMarker) {
          driverMarker = L.marker([data.lat, data.lng], { icon: motoIcon() }).addTo(map);
        } else {
          animateMarkerTo(driverMarker, data.lat, data.lng);
        }
      });
    });
  }

  function boot() {
    hide('loginView');
    show('appView');
    document.getElementById('userGreeting').textContent = 'Hola, ' + (user && user.name ? user.name : '');

    initMap();
    setupAutocomplete('originAddress', 'originSuggestions', 'origin');
    setupAutocomplete('destAddress', 'destSuggestions', 'destination');

    if (currentTripId) {
      hide('createView');
      stopNearbyPolling();
      loadTrip().then(connectRealtime);
    } else {
      show('createView');
      setTimeout(function () { map.invalidateSize(); }, 100);
    }
  }

  if (token && user) {
    boot();
  }
</script>
</body>
</html>
