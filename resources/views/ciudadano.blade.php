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

  .driver-info { display: flex; align-items: center; gap: 12px; margin-top: 12px; padding: 12px; background: #f1f5f9; border-radius: 10px; }
  .driver-avatar {
    width: 44px; height: 44px; border-radius: 50%; background: var(--primary);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 16px; flex-shrink: 0;
  }
  .driver-info div p { margin: 0; }
  .driver-info .name { font-weight: 600; font-size: 14px; }
  .driver-info .plate { font-size: 12px; color: var(--muted); }

  .timeline { margin-top: 14px; padding-left: 4px; }
  .timeline .step { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: 13px; color: var(--muted); }
  .timeline .step.done { color: var(--text); }
  .timeline .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--border); flex-shrink: 0; }
  .timeline .step.done .dot { background: var(--success); }

  .userbar { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--muted); margin-bottom: 16px; }
  .userbar button { width: auto; margin: 0; padding: 6px 12px; font-size: 12px; }

  #connLog { font-family: monospace; font-size: 11px; color: #94a3b8; background: #0f172a; padding: 8px; border-radius: 6px; max-height: 100px; overflow-y: auto; margin-top: 10px; }

  .map-toggle { display: flex; gap: 8px; margin-bottom: 10px; }
  .map-mode {
    flex: 1; margin: 0; padding: 8px; font-size: 12px; font-weight: 600;
    background: #f1f5f9; color: var(--muted); border: 1px solid var(--border); border-radius: 8px;
  }
  .map-mode.active.origin-mode { background: #16a34a; color: white; border-color: #16a34a; }
  .map-mode.active.dest-mode { background: #dc2626; color: white; border-color: #dc2626; }
  #map { height: 240px; border-radius: 10px; border: 1px solid var(--border); z-index: 0; }
</style>
</head>
<body>
<div class="app">
  <header>
    <h1>🛵 Mototaxi</h1>
    <p>App del ciudadano</p>
  </header>

  <!-- ===================== LOGIN ===================== -->
  <div id="loginView" class="card">
    <h2>Iniciar sesión</h2>
    <label>Correo</label>
    <input id="loginEmail" type="email" placeholder="ciudadano1@mototaxi.test" value="ciudadano1@mototaxi.test">
    <label>Contraseña</label>
    <input id="loginPassword" type="password" placeholder="password" value="password">
    <button onclick="login()">Entrar</button>
    <div id="loginError" class="error hidden"></div>
  </div>

  <!-- ===================== APP (logueado) ===================== -->
  <div id="appView" class="hidden">
    <div class="userbar">
      <span id="userGreeting"></span>
      <button class="secondary" onclick="logout()">Cerrar sesión</button>
    </div>

    <!-- Crear viaje -->
    <div id="createView" class="card">
      <h2>¿A dónde vas?</h2>

      <div class="map-toggle">
        <button type="button" class="map-mode active" id="modeOriginBtn" onclick="setMapMode('origin')">📍 Marcar origen</button>
        <button type="button" class="map-mode" id="modeDestBtn" onclick="setMapMode('destination')">🏁 Marcar destino</button>
      </div>
      <div id="map"></div>
      <p class="hint">Toca el mapa para poner el pin, o arrástralo para ajustar. Empieza marcando el origen.</p>
      <button class="secondary" onclick="useMyLocation()">📍 Usar mi ubicación actual como origen</button>

      <label>Origen</label>
      <input id="originAddress" placeholder="Se autocompleta al marcar en el mapa">

      <label>Destino</label>
      <input id="destAddress" placeholder="Se autocompleta al marcar en el mapa">

      <button onclick="createTrip()" id="createBtn">Pedir mototaxi</button>
      <div id="createError" class="error hidden"></div>
    </div>

    <!-- Estado del viaje activo -->
    <div id="tripView" class="card hidden">
      <h2>Tu viaje <span id="tripBadge" class="status-badge"></span></h2>

      <div class="timeline" id="tripTimeline"></div>

      <div id="driverBox" class="driver-info hidden">
        <div class="driver-avatar" id="driverInitial">?</div>
        <div>
          <p class="name" id="driverName"></p>
          <p class="plate" id="driverPlate"></p>
        </div>
      </div>

      <p class="hint" id="realtimeStatus">Conectando al canal en vivo...</p>
      <button class="secondary" onclick="newTrip()">Pedir otro viaje</button>
    </div>
  </div>
</div>

<script>
  // ---------- Config ----------
  const API_BASE = window.location.origin + '/api';

  // Estos valores vienen del .env del servidor (la misma configuración
  // 'reverb' que ya usa el backend para disparar los eventos), así que
  // ya no hay que editar este archivo a mano por entorno (local/producción).
  const REVERB_APP_KEY = '{{ config('broadcasting.connections.reverb.key') }}';
  const REVERB_HOST = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const REVERB_PORT = {{ config('broadcasting.connections.reverb.options.port', 443) }};
  const FORCE_TLS = '{{ config('broadcasting.connections.reverb.options.scheme', 'https') }}' === 'https';

  // ---------- Estado en memoria ----------
  let token = localStorage.getItem('mototaxi_token') || null;
  let user = JSON.parse(localStorage.getItem('mototaxi_user') || 'null');
  let currentTripId = localStorage.getItem('mototaxi_trip_id') || null;
  let pusher = null;
  let tripChannel = null;

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

  // ---------- Login ----------
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

  // ---------- Mapa (Leaflet + OpenStreetMap) ----------
  let map = null;
  let originMarker = null;
  let destMarker = null;
  let mapMode = 'origin'; // 'origin' o 'destination' — qué pin coloca el próximo tap
  let originCoords = null; // { lat, lng }
  let destCoords = null;

  // Centro por defecto: Mérida, Yucatán. Se recentra solo si el usuario
  // usa "mi ubicación actual".
  const DEFAULT_CENTER = [20.9674, -89.5926];

  function initMap() {
    if (map) return; // ya inicializado, no crear dos veces
    map = L.map('map').setView(DEFAULT_CENTER, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    map.on('click', (e) => placePin(mapMode, e.latlng.lat, e.latlng.lng));

    // Arranca centrado en Mérida sin pin todavía — el usuario marca origen primero.
    setMapMode('origin');
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

  function placePin(type, lat, lng) {
    const isOrigin = type === 'origin';
    const color = isOrigin ? '#16a34a' : '#dc2626';
    const icon = L.divIcon({
      html: `<div style="background:${color};width:20px;height:20px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,0.4)"></div>`,
      iconSize: [20, 20],
      iconAnchor: [10, 20],
      className: '',
    });

    if (isOrigin) {
      if (originMarker) originMarker.setLatLng([lat, lng]);
      else {
        originMarker = L.marker([lat, lng], { icon, draggable: true }).addTo(map);
        originMarker.on('dragend', () => {
          const p = originMarker.getLatLng();
          setOriginCoords(p.lat, p.lng);
        });
      }
      setOriginCoords(lat, lng);
      // Después de marcar origen, pasamos automáticamente a modo destino.
      if (!destCoords) setMapMode('destination');
    } else {
      if (destMarker) destMarker.setLatLng([lat, lng]);
      else {
        destMarker = L.marker([lat, lng], { icon, draggable: true }).addTo(map);
        destMarker.on('dragend', () => {
          const p = destMarker.getLatLng();
          setDestCoords(p.lat, p.lng);
        });
      }
      setDestCoords(lat, lng);
    }
  }

  function setOriginCoords(lat, lng) {
    originCoords = { lat, lng };
    reverseGeocode(lat, lng, 'originAddress');
  }

  function setDestCoords(lat, lng) {
    destCoords = { lat, lng };
    reverseGeocode(lat, lng, 'destAddress');
  }

  // Geocodificación inversa gratuita (Nominatim/OpenStreetMap) solo para
  // autocompletar el campo de dirección — si falla o tarda, no bloquea nada,
  // el pin en el mapa ya guardó las coordenadas reales que se usan para crear el viaje.
  async function reverseGeocode(lat, lng, inputId) {
    const input = document.getElementById(inputId);
    input.value = 'Buscando dirección...';
    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
      const data = await res.json();
      input.value = data.display_name || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    } catch (e) {
      input.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    }
  }

  function useMyLocation() {
    if (!navigator.geolocation) {
      alert('Tu navegador no soporta geolocalización.');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        map.setView([pos.coords.latitude, pos.coords.longitude], 15);
        placePin('origin', pos.coords.latitude, pos.coords.longitude);
      },
      () => alert('No se pudo obtener tu ubicación. Marca el origen tocando el mapa.')
    );
  }

  // ---------- Crear viaje ----------
  async function createTrip() {
    hide('createError');

    if (!originCoords || !destCoords) {
      const el = document.getElementById('createError');
      el.textContent = 'Marca el origen y el destino en el mapa antes de continuar.';
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

      const trip = await api('/service-requests', { method: 'POST', body });
      currentTripId = trip.id;
      localStorage.setItem('mototaxi_trip_id', currentTripId);

      hide('createView');
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

    // Reinicia el mapa: quita los pines anteriores para el viaje nuevo.
    if (originMarker) { map.removeLayer(originMarker); originMarker = null; }
    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
    originCoords = null; destCoords = null;
    document.getElementById('originAddress').value = '';
    document.getElementById('destAddress').value = '';
    setMapMode('origin');
    setTimeout(() => map.invalidateSize(), 100); // por si el contenedor estaba oculto
  }

  // ---------- Cargar y pintar el estado del viaje ----------
  async function loadTrip() {
    if (!currentTripId) return;

    const trip = await api(`/service-requests/${currentTripId}`);
    renderTrip(trip);
  }

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
    const doneStatuses = (trip.statusLogs || []).map(l => l.status);
    const timeline = document.getElementById('tripTimeline');
    timeline.innerHTML = steps.map(s => `
      <div class="step ${doneStatuses.includes(s.key) ? 'done' : ''}">
        <span class="dot"></span> ${s.label}
      </div>
    `).join('');

    if (trip.assignment && trip.assignment.driverProfile) {
      const driver = trip.assignment.driverProfile.user;
      document.getElementById('driverBox').classList.remove('hidden');
      document.getElementById('driverInitial').textContent = (driver?.name || '?').charAt(0);
      document.getElementById('driverName').textContent = driver?.name || 'Conductor';
      document.getElementById('driverPlate').textContent = driver?.phone ? `Tel: ${driver.phone}` : '';
    }
  }

  // ---------- Tiempo real ----------
  function connectRealtime() {
    if (!user) return;

    // El citizen_profile_id no viene en /auth/login directamente,
    // así que lo resolvemos con /auth/me la primera vez.
    api('/auth/me').then((me) => {
      const citizenProfileId = me.citizenProfile?.id;
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

        pusher.connection.bind('connected', () => {
          setRealtimeStatus('Conectado — esperando actualizaciones...', true);
        });
        pusher.connection.bind('error', () => {
          setRealtimeStatus('No se pudo conectar al servidor en tiempo real.', false);
        });
      }

      tripChannel = pusher.subscribe(`private-citizen.${citizenProfileId}`);

      tripChannel.bind('pusher:subscription_succeeded', () => {
        setRealtimeStatus('Escuchando actualizaciones en vivo de tu viaje.', true);
      });

      tripChannel.bind('trip-assignment.accepted', (data) => {
        if (data.service_request_id != currentTripId) return;
        loadTrip();
      });

      tripChannel.bind('trip-status.updated', (data) => {
        if (data.service_request_id != currentTripId) return;
        loadTrip();
      });
    });
  }

  function setRealtimeStatus(text, ok) {
    const el = document.getElementById('realtimeStatus');
    el.textContent = text;
    el.style.color = ok ? '#16a34a' : '#dc2626';
  }

  // ---------- Arranque ----------
  function boot() {
    hide('loginView');
    show('appView');
    document.getElementById('userGreeting').textContent = `Hola, ${user?.name || ''}`;

    initMap();

    if (currentTripId) {
      hide('createView');
      loadTrip().then(connectRealtime);
    } else {
      show('createView');
      setTimeout(() => map.invalidateSize(), 100);
    }
  }

  if (token && user) {
    boot();
  }
</script>
</body>
</html>
