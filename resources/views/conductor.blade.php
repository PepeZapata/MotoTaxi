<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mototaxi - Conductor</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.4.0/pusher.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
  :root {
    --primary: #16a34a;
    --primary-dark: #15803d;
    --bg: #f8fafc;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
    --danger: #dc2626;
    --warn: #d97706;
  }
  * { box-sizing: border-box; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
    min-height: 100vh;
  }
  .app { max-width: 480px; margin: 0 auto; padding: 24px 16px 60px; }
  header { text-align: center; margin-bottom: 20px; }
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
  input {
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
    margin-top: 10px;
  }
  button:hover { background: var(--primary-dark); }
  button.secondary { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
  button.secondary:hover { background: #f0fdf4; }
  button.danger { background: var(--danger); }
  button.danger:hover { background: #b91c1c; }
  button:disabled { opacity: 0.5; cursor: not-allowed; }

  .error { color: var(--danger); font-size: 13px; margin-top: 8px; }
  .hidden { display: none !important; }

  .userbar { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--muted); margin-bottom: 16px; }
  .userbar button { width: auto; margin: 0; padding: 6px 12px; font-size: 12px; }

  .toggle-row { display: flex; justify-content: space-between; align-items: center; }
  .toggle-row .label { font-size: 14px; font-weight: 600; }
  .pill { font-size: 12px; padding: 4px 10px; border-radius: 999px; font-weight: 600; }
  .pill-off { background: #fee2e2; color: #991b1b; }
  .pill-on { background: #dcfce7; color: #166534; }

  .request-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px;
    margin-top: 10px;
  }
  .request-item p { margin: 2px 0; font-size: 13px; }
  .request-item .addr { font-weight: 600; font-size: 14px; }
  .request-item button { margin-top: 8px; }
  .empty { color: var(--muted); font-size: 13px; text-align: center; padding: 20px 0; }

  .trip-step {
    display: block; margin-top: 8px;
  }
  .status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #dbeafe; color: #1e40af; }

  #log { font-family: monospace; font-size: 11px; color: #94a3b8; background: #0f172a; padding: 8px; border-radius: 6px; max-height: 90px; overflow-y: auto; margin-top: 10px; }
  .hint { color: var(--muted); font-size: 12px; margin-top: 6px; }

  #driverMap { height: 280px; border-radius: 10px; z-index: 0; }
  .leaflet-popup-content button { width: auto; margin-top: 6px; padding: 6px 10px; }
</style>
</head>
<body>
<div class="app">
  <header>
    <h1>🏍️ Mototaxi</h1>
    <p>App del conductor</p>
  </header>

  <!-- ===================== LOGIN ===================== -->
  <div id="loginView" class="card">
    <h2>Iniciar sesión</h2>
    <label>Correo</label>
    <input id="loginEmail" type="email" value="conductor1@mototaxi.test">
    <label>Contraseña</label>
    <input id="loginPassword" type="password" value="password">
    <button onclick="login()">Entrar</button>
    <div id="loginError" class="error hidden"></div>
  </div>

  <!-- ===================== APP (logueado) ===================== -->
  <div id="appView" class="hidden">
    <div class="userbar">
      <span id="userGreeting"></span>
      <button class="secondary" onclick="logout()">Cerrar sesión</button>
    </div>

    <!-- Disponibilidad -->
    <div class="card">
      <div class="toggle-row">
        <span class="label">Estado</span>
        <span id="availabilityPill" class="pill pill-off">Desconectado</span>
      </div>
      <button id="toggleBtn" onclick="toggleAvailability()">Conectarme y recibir viajes</button>
      <p class="hint" id="locationHint">Necesitamos tu ubicación para mostrarte viajes cercanos.</p>
    </div>

    <!-- Mapa: solicitudes cercanas como pines, o ruta del viaje en curso -->
    <div id="mapCard" class="card hidden">
      <h2 id="mapCardTitle">Mapa</h2>
      <div id="driverMap"></div>
    </div>

    <!-- Solicitudes cercanas -->
    <div id="requestsView" class="card hidden">
      <h2>Solicitudes cercanas</h2>
      <div id="requestsList"></div>
      <p id="requestsEmpty" class="empty hidden">Ninguna solicitud cercana por ahora. Te avisamos en cuanto llegue una.</p>
    </div>

    <!-- Viaje activo -->
    <div id="tripView" class="card hidden">
      <h2>Viaje en curso <span id="tripBadge" class="status-badge"></span></h2>
      <p id="tripInfo"></p>
      <div id="tripActions"></div>
    </div>

    <div id="log"></div>
  </div>
</div>

<script>
  // ---------- Config ----------
  const API_BASE = window.location.origin + '/api';
  const REVERB_APP_KEY = '{{ config('broadcasting.connections.reverb.key') }}';
  const REVERB_HOST = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const REVERB_PORT = {{ config('broadcasting.connections.reverb.options.port', 443) }};
  const FORCE_TLS = '{{ config('broadcasting.connections.reverb.options.scheme', 'https') }}' === 'https';
  const LOCATION_REFRESH_MS = 15000; // cada cuánto reportamos ubicación mientras está disponible

  // ---------- Estado ----------
  let token = localStorage.getItem('mototaxi_driver_token') || null;
  let user = JSON.parse(localStorage.getItem('mototaxi_driver_user') || 'null');
  let pusher = null;
  let subscribedChannels = []; // nombres completos, ej: "drivers.zone.d58r3"
  let openRequests = {}; // id -> request, para no duplicar en la lista
  let currentTrip = null; // { id (service_request_id), assignmentId, status, origin, destination, originAddress, destinationAddress }
  let locationInterval = null;
  let lastLat = null, lastLng = null;

  // ---------- Mapa ----------
  let map = null;
  let driverOwnMarker = null;
  let nearbyMarkers = {}; // id de solicitud -> marker
  let originMarker = null;
  let destMarker = null;
  let routeLine = null;

  // ---------- Helpers ----------
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

  function log(msg) {
    const el = document.getElementById('log');
    const time = new Date().toLocaleTimeString();
    el.textContent += `[${time}] ${msg}\n`;
    el.scrollTop = el.scrollHeight;
  }

  function getLocation() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) return reject(new Error('Sin geolocalización'));
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        (err) => reject(err)
      );
    });
  }

  // ---------- Mapa: helpers ----------
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
      html: '<div style="font-size:20px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4));">🏍️</div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
      className: '',
    });
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

  function initDriverMap(lat, lng) {
    if (map) return;
    map = L.map('driverMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);
  }

  function showMapCard(title) {
    document.getElementById('mapCardTitle').textContent = title;
    show('mapCard');
    setTimeout(() => { if (map) map.invalidateSize(); }, 150);
  }

  function updateOwnMarker(lat, lng) {
    if (!map) return;
    if (!driverOwnMarker) {
      driverOwnMarker = L.marker([lat, lng], { icon: motoIcon() }).addTo(map);
    } else {
      animateMarkerTo(driverOwnMarker, lat, lng);
    }
  }

  function renderRequestMarkers() {
    if (!map) return;
    const ids = Object.keys(openRequests);
    const seen = new Set();

    ids.forEach((id) => {
      const r = openRequests[id];
      if (!r.origin_lat || !r.origin_lng) return;
      seen.add(Number(id));
      const lat = parseFloat(r.origin_lat), lng = parseFloat(r.origin_lng);

      if (nearbyMarkers[id]) {
        nearbyMarkers[id].setLatLng([lat, lng]);
      } else {
        const marker = L.marker([lat, lng], { icon: pinIcon('#f59e0b') }).addTo(map);
        marker.bindPopup(
          '<div style="font-size:13px;"><b>' + (r.origin_address || 'Origen (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')') + '</b><br>' +
          '&rarr; ' + (r.destination_address || 'Destino') + '<br>' +
          (r.estimated_cost ? ('Estimado: $' + r.estimated_cost + '<br>') : '') +
          '<button onclick="acceptRequest(' + r.id + ')">Aceptar viaje</button></div>'
        );
        nearbyMarkers[id] = marker;
      }
    });

    Object.keys(nearbyMarkers).forEach((id) => {
      if (!seen.has(Number(id))) {
        map.removeLayer(nearbyMarkers[id]);
        delete nearbyMarkers[id];
      }
    });
  }

  function clearRequestMarkers() {
    Object.values(nearbyMarkers).forEach((m) => map.removeLayer(m));
    nearbyMarkers = {};
  }

  async function drawTripRoute(from, to, color) {
    try {
      const url = 'https://router.project-osrm.org/route/v1/driving/' + from.lng + ',' + from.lat + ';' + to.lng + ',' + to.lat + '?overview=full&geometry=geojson';
      const res = await fetch(url);
      const data = await res.json();
      const coords = data.routes[0].geometry.coordinates.map((c) => [c[1], c[0]]);

      if (routeLine) map.removeLayer(routeLine);
      routeLine = L.polyline(coords, { color: color, weight: 4, opacity: 0.7 }).addTo(map);
    } catch (e) {
      if (routeLine) map.removeLayer(routeLine);
      routeLine = L.polyline([[from.lat, from.lng], [to.lat, to.lng]], { color: color, weight: 3, opacity: 0.5, dashArray: '6 6' }).addTo(map);
    }
  }

  function setupTripMap() {
    if (!map || !currentTrip || !currentTrip.origin) return;

    if (!originMarker) {
      originMarker = L.marker([currentTrip.origin.lat, currentTrip.origin.lng], { icon: pinIcon('#16a34a') })
        .addTo(map).bindPopup('Recoger aquí: ' + (currentTrip.originAddress || ''));
    }
    if (!destMarker) {
      destMarker = L.marker([currentTrip.destination.lat, currentTrip.destination.lng], { icon: pinIcon('#dc2626') })
        .addTo(map).bindPopup('Destino: ' + (currentTrip.destinationAddress || ''));
    }

    const bounds = L.latLngBounds([
      [currentTrip.origin.lat, currentTrip.origin.lng],
      [currentTrip.destination.lat, currentTrip.destination.lng],
    ]);
    if (lastLat) bounds.extend([lastLat, lastLng]);
    map.fitBounds(bounds, { padding: [30, 30] });

    updateTripRoute();
  }

  function updateTripRoute() {
    if (!map || !currentTrip || !currentTrip.origin) return;
    const driverPos = driverOwnMarker ? driverOwnMarker.getLatLng() : (lastLat ? { lat: lastLat, lng: lastLng } : null);

    if (['accepted', 'en_route_to_pickup'].includes(currentTrip.status) && driverPos) {
      drawTripRoute(driverPos, currentTrip.origin, '#16a34a');
    } else if (currentTrip.status === 'arrived') {
      if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
    } else if (currentTrip.status === 'started') {
      drawTripRoute(currentTrip.origin, currentTrip.destination, '#1d4ed8');
    }
  }

  function clearTripMapLayers() {
    if (originMarker) { map.removeLayer(originMarker); originMarker = null; }
    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
  }

  // ---------- Login ----------
  async function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    hide('loginError');
    try {
      const data = await api('/auth/login', { method: 'POST', body: { email, password } });
      token = data.token;
      user = data.user;
      localStorage.setItem('mototaxi_driver_token', token);
      localStorage.setItem('mototaxi_driver_user', JSON.stringify(user));
      boot();
    } catch (e) {
      const el = document.getElementById('loginError');
      el.textContent = e.message;
      show('loginError');
    }
  }

  function logout() {
    stopEverything();
    localStorage.removeItem('mototaxi_driver_token');
    localStorage.removeItem('mototaxi_driver_user');
    token = null; user = null;
    hide('appView');
    show('loginView');
  }

  function stopEverything() {
    if (locationInterval) clearInterval(locationInterval);
    if (pusher) { pusher.disconnect(); pusher = null; }
    subscribedChannels = [];
  }

  // ---------- Disponibilidad ----------
  async function toggleAvailability() {
    const pill = document.getElementById('availabilityPill');
    const isOnline = pill.textContent === 'Disponible';

    if (isOnline) {
      // Pasar a offline
      await api('/driver/availability', { method: 'POST', body: { availability_status: 'offline' } });
      pill.textContent = 'Desconectado';
      pill.className = 'pill pill-off';
      document.getElementById('toggleBtn').textContent = 'Conectarme y recibir viajes';
      stopEverything();
      hide('requestsView');
      hide('mapCard');
      if (map) clearRequestMarkers();
      log('Te desconectaste. Ya no recibirás solicitudes nuevas.');
      return;
    }

    // Pasar a online: primero necesitamos ubicación
    document.getElementById('toggleBtn').disabled = true;
    try {
      const { lat, lng } = await getLocation();
      lastLat = lat; lastLng = lng;

      await api('/driver/location', { method: 'POST', body: { latitude: lat, longitude: lng } });
      await api('/driver/availability', { method: 'POST', body: { availability_status: 'available' } });

      pill.textContent = 'Disponible';
      pill.className = 'pill pill-on';
      document.getElementById('toggleBtn').textContent = 'Desconectarme';
      document.getElementById('locationHint').textContent = `Ubicación reportada: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;

      show('requestsView');
      initDriverMap(lat, lng);
      updateOwnMarker(lat, lng);
      showMapCard('Solicitudes cercanas');
      await refreshZoneAndSubscribe(lat, lng);
      await loadNearbyRequests(lat, lng);

      locationInterval = setInterval(async () => {
        try {
          const pos = await getLocation();
          lastLat = pos.lat; lastLng = pos.lng;
          await api('/driver/location', { method: 'POST', body: { latitude: pos.lat, longitude: pos.lng } });
          await refreshZoneAndSubscribe(pos.lat, pos.lng);
          updateOwnMarker(pos.lat, pos.lng);
          if (currentTrip) updateTripRoute();
        } catch (e) {
          log('No se pudo actualizar ubicación: ' + e.message);
        }
      }, LOCATION_REFRESH_MS);
    } catch (e) {
      alert('No se pudo obtener tu ubicación: ' + e.message);
    } finally {
      document.getElementById('toggleBtn').disabled = false;
    }
  }

  // ---------- Zona geohash + suscripción en tiempo real ----------
  async function refreshZoneAndSubscribe(lat, lng) {
    const zone = await api(`/geo/zone?lat=${lat}&lng=${lng}`);
    const newChannels = zone.channels; // ej: ["drivers.zone.d58r3", ...]

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
      pusher.connection.bind('connected', () => log('Conectado al servidor en tiempo real.'));
    }

    // Desuscribir canales que ya no aplican (nos movimos de zona)
    subscribedChannels
      .filter((c) => !newChannels.includes(c))
      .forEach((c) => {
        pusher.unsubscribe('private-' + c);
      });

    // Suscribir canales nuevos que todavía no tenemos
    newChannels
      .filter((c) => !subscribedChannels.includes(c))
      .forEach((c) => {
        const channel = pusher.subscribe('private-' + c);
        channel.bind('service-request.created', (data) => {
          log(`Nueva solicitud cercana recibida (#${data.id})`);
          addRequestToList(data);
        });
      });

    if (subscribedChannels.length && JSON.stringify(subscribedChannels.sort()) !== JSON.stringify([...newChannels].sort())) {
      log(`Cambiaste de zona. Ahora escuchando ${newChannels.length} celdas cercanas.`);
    }

    subscribedChannels = newChannels;
  }

  // ---------- Solicitudes cercanas ----------
  async function loadNearbyRequests(lat, lng) {
    const list = await api(`/service-requests/open?lat=${lat}&lng=${lng}`);
    openRequests = {};
    list.forEach((r) => { openRequests[r.id] = r; });
    renderRequests();
    renderRequestMarkers();
  }

  function addRequestToList(data) {
    if (currentTrip) return; // si ya tiene un viaje activo, no lo saturamos con más solicitudes
    openRequests[data.id] = data;
    renderRequests();
    renderRequestMarkers();
  }

  function renderRequests() {
    const container = document.getElementById('requestsList');
    const ids = Object.keys(openRequests);

    if (!ids.length) {
      container.innerHTML = '';
      show('requestsEmpty');
      return;
    }
    hide('requestsEmpty');

    container.innerHTML = ids.map((id) => {
      const r = openRequests[id];
      return `
        <div class="request-item">
          <p class="addr">${r.origin_address || 'Origen sin dirección (' + r.origin_lat + ', ' + r.origin_lng + ')'}</p>
          <p>→ ${r.destination_address || 'Destino (' + r.destination_lat + ', ' + r.destination_lng + ')'}</p>
          ${r.estimated_cost ? `<p>Estimado: $${r.estimated_cost}</p>` : ''}
          <button onclick="acceptRequest(${r.id})">Aceptar viaje</button>
        </div>
      `;
    }).join('');
  }

  async function acceptRequest(id) {
    const reqData = openRequests[id]; // coords/direcciones, las necesitamos para el mapa del viaje
    try {
      const assignment = await api(`/service-requests/${id}/accept`, { method: 'POST' });
      currentTrip = {
        id: assignment.service_request_id,
        assignmentId: assignment.id,
        status: assignment.acceptance_status,
        origin: reqData ? { lat: parseFloat(reqData.origin_lat), lng: parseFloat(reqData.origin_lng) } : null,
        destination: reqData ? { lat: parseFloat(reqData.destination_lat), lng: parseFloat(reqData.destination_lng) } : null,
        originAddress: reqData ? reqData.origin_address : null,
        destinationAddress: reqData ? reqData.destination_address : null,
      };
      openRequests = {};
      hide('requestsView');
      clearRequestMarkers();
      showMapCard('Tu viaje');
      setupTripMap();
      renderTrip();
    } catch (e) {
      alert('No se pudo aceptar: ' + e.message + '\n(probablemente otro conductor la tomó primero)');
      // refrescamos la lista por si esa solicitud ya no está disponible
      if (lastLat) loadNearbyRequests(lastLat, lastLng);
    }
  }

  // ---------- Viaje activo: avanzar estados ----------
  const NEXT_STATUS = {
    accepted: { next: 'en_route_to_pickup', label: 'Voy en camino' },
    en_route_to_pickup: { next: 'arrived', label: 'Llegué al punto de recogida' },
    arrived: { next: 'started', label: 'Iniciar viaje' },
    started: { next: 'finished', label: 'Finalizar viaje' },
  };

  function renderTrip() {
    show('tripView');
    document.getElementById('tripBadge').textContent = currentTrip.status;
    document.getElementById('tripInfo').textContent = `Solicitud #${currentTrip.id}`;

    const step = NEXT_STATUS[currentTrip.status];
    const actions = document.getElementById('tripActions');

    if (!step) {
      actions.innerHTML = '<p>Viaje finalizado. ¡Buen trabajo!</p>';
      setTimeout(() => {
        currentTrip = null;
        hide('tripView');
        show('requestsView');
        clearTripMapLayers();
        showMapCard('Solicitudes cercanas');
        if (lastLat) loadNearbyRequests(lastLat, lastLng);
      }, 2000);
      return;
    }

    actions.innerHTML = `<button onclick="advanceTrip()">${step.label}</button>`;
  }

  async function advanceTrip() {
    const step = NEXT_STATUS[currentTrip.status];
    if (!step) return;

    try {
      await api(`/trip-assignments/${currentTrip.assignmentId}/status`, {
        method: 'PATCH',
        body: { acceptance_status: step.next },
      });
      currentTrip.status = step.next;
      renderTrip();
      updateTripRoute();
    } catch (e) {
      alert('Error al actualizar el viaje: ' + e.message);
    }
  }

  // ---------- Arranque ----------
  function boot() {
    hide('loginView');
    show('appView');
    document.getElementById('userGreeting').textContent = `Hola, ${user?.name || ''}`;
  }

  if (token && user) {
    boot();
  }
</script>
</body>
</html>
