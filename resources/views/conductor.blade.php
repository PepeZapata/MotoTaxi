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
  html, body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
    height: 100%;
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

  .status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #dbeafe; color: #1e40af; }

  #log { font-family: monospace; font-size: 11px; color: #94a3b8; background: #0f172a; padding: 8px; border-radius: 6px; max-height: 90px; overflow-y: auto; margin-top: 10px; }
  .hint { color: var(--muted); font-size: 12px; margin-top: 6px; }

  .leaflet-popup-content button { width: auto; margin-top: 6px; padding: 6px 10px; }

  /* ---------- Documentos ---------- */
  .tag-pending { background: #fef9c3; color: #854d0e; }
  .tag-approved { background: #dcfce7; color: #166534; }
  .tag-rejected { background: #fee2e2; color: #991b1b; }
  .tag-expired { background: #ffedd5; color: #9a3412; }

  .doc-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 10px;
  }
  .doc-item .doc-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
  }
  .doc-item .doc-head strong { font-size: 13px; }
  .doc-item .doc-reason { color: var(--danger); font-size: 12px; margin: 6px 0 0; }
  .doc-item .doc-expiry { color: var(--muted); font-size: 12px; margin: 4px 0 0; }
  .doc-upload { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }
  .doc-upload input[type="file"] { font-size: 12px; }
  .doc-upload label { font-size: 11px; color: var(--muted); margin: 0; }
  .doc-upload button { margin-top: 2px; }
</style>
@include('partials.bottom-sheet')
@include('partials.route-helpers')
</head>
<body>

<!-- ===================== LOGIN ===================== -->
<div id="authScreen" class="app">
  <header>
    <h1>🏍️ Mototaxi</h1>
    <p>App del conductor</p>
  </header>

  <div id="loginView" class="card">
    <h2>Iniciar sesión</h2>
    <label>Correo</label>
    <input id="loginEmail" type="email" value="conductor1@mototaxi.test">
    <label>Contraseña</label>
    <input id="loginPassword" type="password" value="password">
    <button onclick="login()">Entrar</button>
    <div id="loginError" class="error hidden"></div>
  </div>
</div>

<!-- ===================== APP (logueado): mapa full-screen + sheet ===================== -->
<div id="appView" class="app-shell hidden">
  <div id="driverMap" class="map-layer"></div>

  <div class="topbar">
    <div class="topbar-group">
      <span id="availabilityPill" class="pill pill-off">Desconectado</span>
      <button class="topbar-btn hidden" id="recenterBtn" onclick="recenterMap()">🎯 Centrar</button>
    </div>
    <div class="topbar-group">
      <span id="userGreeting" class="topbar-chip"></span>
      <button class="topbar-btn" onclick="openDocuments()">📄 Documentos</button>
      <button class="topbar-btn" onclick="logout()">Salir</button>
    </div>
  </div>

  <div class="sheet" id="sheet">
    <div class="sheet-drag" id="sheetDrag"><div class="sheet-handle"></div></div>
    <div class="sheet-content">
      <!-- Disponibilidad: oculta durante un viaje activo (no te puedes desconectar a medio viaje) -->
      <div id="availabilityCard" class="card">
        <button id="toggleBtn" onclick="toggleAvailability()">Conectarme y recibir viajes</button>
        <p class="hint" id="locationHint">Necesitamos tu ubicación para mostrarte viajes cercanos.</p>
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
        <p class="hint" id="tripRouteInfo"></p>
        <div id="tripActions"></div>
      </div>

      <!-- Documentos: pantalla aparte, se abre con el botón "📄 Documentos" del topbar -->
      <div id="documentsView" class="card hidden">
        <h2>Mis documentos</h2>
        <p class="hint">Sube una foto clara o un PDF de cada documento. El administrador revisa y aprueba cada uno; si rechaza alguno, aquí verás el motivo para volver a subirlo.</p>
        <div id="documentsList"></div>
        <button class="secondary" onclick="closeDocuments()">Volver</button>
      </div>

      <details class="log-details">
        <summary>Registro de actividad</summary>
        <div id="log"></div>
      </details>
    </div>
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
  const DEFAULT_CENTER = [20.9674, -89.5926]; // Mérida, Yucatán: centro de respaldo antes de tener GPS
  const NAV_ZOOM = 17; // nivel de zoom "navegación" para seguir al conductor durante un viaje

  // Snap points del bottom sheet (fracción de alto de pantalla, o píxeles si es > 1).
  const SHEET_SNAPS = { collapsed: 200, half: 0.5, full: 0.88 };

  // ---------- Estado ----------
  let token = localStorage.getItem('mototaxi_driver_token') || null;
  let user = JSON.parse(localStorage.getItem('mototaxi_driver_user') || 'null');
  let pusher = null;
  let subscribedChannels = []; // nombres completos, ej: "drivers.zone.d58r3"
  let openRequests = {}; // id -> request, para no duplicar en la lista
  let currentTrip = null; // { id (service_request_id), assignmentId, status, origin, destination, originAddress, destinationAddress }
  let locationInterval = null;
  let lastLat = null, lastLng = null;
  let lastHeading = null; // último rumbo conocido (0-359°), para rotar el ícono de la moto
  let wakeLock = null; // Screen Wake Lock: evita que la pantalla se apague mientras está disponible/en viaje

  // ---------- Mapa ----------
  let map = null;
  let driverOwnMarker = null;
  let nearbyMarkers = {}; // id de solicitud -> marker
  let originMarker = null;
  let destMarker = null;
  let routeLine = null;
  let followMode = true; // true = el mapa se recentra solo sobre la moto; se apaga si el conductor arrastra el mapa

  // ---------- Bottom sheet ----------
  let sheet = null;

  // ---------- Helpers ----------
  async function api(path, options = {}) {
    // Si el body ya es FormData (subida de archivos), no lo convertimos a
    // JSON ni forzamos Content-Type: el navegador arma el multipart/form-data
    // con el boundary correcto solo si no lo tocamos.
    const isFormData = options.body instanceof FormData;

    const res = await fetch(API_BASE + path, {
      ...options,
      headers: {
        ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
        Accept: 'application/json',
        ...(token ? { Authorization: 'Bearer ' + token } : {}),
        ...(options.headers || {}),
      },
      body: isFormData ? options.body : (options.body ? JSON.stringify(options.body) : undefined),
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
        (pos) => resolve({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          // El navegador solo entrega "heading" cuando el dispositivo lo soporta
          // y se está moviendo; si no, llega null y lo calculamos nosotros (ver resolveHeading).
          heading: (typeof pos.coords.heading === 'number' && !isNaN(pos.coords.heading))
            ? pos.coords.heading
            : null,
        }),
        (err) => reject(err)
      );
    });
  }

  // distanceMeters() viene de partials/route-helpers.blade.php

  // Rumbo (0-359°, 0 = norte) entre dos coordenadas.
  function bearingDegrees(lat1, lng1, lat2, lng2) {
    const toRad = (d) => (d * Math.PI) / 180;
    const toDeg = (r) => (r * 180) / Math.PI;
    const y = Math.sin(toRad(lng2 - lng1)) * Math.cos(toRad(lat2));
    const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) -
      Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(toRad(lng2 - lng1));
    return (toDeg(Math.atan2(y, x)) + 360) % 360;
  }

  // Decide qué rumbo usar: el que reporta el GPS si viene, o si no,
  // lo calculamos comparando contra el punto anterior (solo si nos movimos
  // lo suficiente, para no "bailar" el ícono por el ruido del GPS parado).
  function resolveHeading(prevLat, prevLng, newLat, newLng, gpsHeading) {
    // El backend valida "between:0,359": 360 (redondeo de 359.6°) debe pasar a 0.
    const clamp360 = (deg) => Math.round(deg) % 360;

    if (gpsHeading !== null && gpsHeading !== undefined) return clamp360(gpsHeading);

    if (prevLat !== null && prevLng !== null) {
      const moved = distanceMeters(prevLat, prevLng, newLat, newLng);
      if (moved >= 3) {
        return clamp360(bearingDegrees(prevLat, prevLng, newLat, newLng));
      }
    }

    return lastHeading; // sin movimiento suficiente: conservamos el rumbo anterior
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

  // El emoji 🏍️ se dibuja "mirando" hacia la izquierda en la mayoría de
  // fuentes (Noto/Apple), que equivale a un rumbo de 270°. MOTO_ICON_OFFSET
  // corrige eso para que, al rotar según el heading real, el frente de la
  // moto apunte hacia donde se está moviendo.
  const MOTO_ICON_OFFSET = 270;

  function motoIcon(heading) {
    const rotation = (typeof heading === 'number') ? (heading - MOTO_ICON_OFFSET) : 0;
    return L.divIcon({
      html: '<div class="moto-rotor" style="font-size:20px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4)); ' +
        'transform: rotate(' + rotation + 'deg); transition: transform 0.3s ease-out;">🏍️</div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12],
      className: '',
    });
  }

  // Rota el ícono de un marcador ya existente sin reemplazarlo (evita
  // parpadeos), manipulando directamente el elemento DOM que Leaflet creó.
  function rotateMarkerIcon(marker, heading) {
    if (typeof heading !== 'number') return;
    const el = marker.getElement ? marker.getElement() : null;
    const rotor = el ? el.querySelector('.moto-rotor') : null;
    if (rotor) {
      rotor.style.transform = 'rotate(' + (heading - MOTO_ICON_OFFSET) + 'deg)';
    } else {
      marker.setIcon(motoIcon(heading));
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

  function initDriverMap(lat, lng) {
    if (map) return;
    map = L.map('driverMap').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);

    // Si el conductor arrastra el mapa con la mano, dejamos de recentrarlo
    // automáticamente (para no "pelearnos" con lo que está viendo); vuelve a
    // seguirlo solo cuando toca "Centrar" o empieza un nuevo tramo del viaje.
    map.on('dragstart', () => { followMode = false; });
  }

  function updateOwnMarker(lat, lng, heading) {
    if (!map) return;
    if (!driverOwnMarker) {
      driverOwnMarker = L.marker([lat, lng], { icon: motoIcon(heading) }).addTo(map);
    } else {
      animateMarkerTo(driverOwnMarker, lat, lng);
      rotateMarkerIcon(driverOwnMarker, heading);
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

  let lastRouteCoords = null; // [[lat,lng], ...] de la última ruta real que trazamos
  const ROUTE_RECALC_THRESHOLD_M = 35; // cuánto se tiene que desviar de la ruta trazada para recalcular

  async function drawTripRoute(from, to, color, force, cacheKey) {
    // El servidor demo de OSRM es gratuito pero limitado: no tiene caso
    // pedir una ruta nueva solo porque el conductor avanzó un poco sobre
    // la MISMA ruta ya trazada. Solo recalculamos si se desvió de verdad
    // (dio vuelta, tomó otra calle...) más de ROUTE_RECALC_THRESHOLD_M.
    if (!force && lastRouteCoords && routeLine) {
      const deviation = distanceToPolylineMeters(from.lat, from.lng, lastRouteCoords);
      if (deviation < ROUTE_RECALC_THRESHOLD_M) return;
    }

    if (![from.lat, from.lng, to.lat, to.lng].every((v) => typeof v === 'number' && isFinite(v))) {
      log('No se pudo calcular la ruta: coordenadas inválidas (' + JSON.stringify({ from, to }) + ').');
      return;
    }

    try {
      const url = 'https://router.project-osrm.org/route/v1/driving/' + from.lng + ',' + from.lat + ';' + to.lng + ',' + to.lat + '?overview=full&geometries=geojson';
      const res = await fetch(url);
      const data = await res.json().catch(() => null);
      if (!res.ok) throw new Error('HTTP ' + res.status + (data && data.message ? ': ' + data.message : (data && data.code ? ': ' + data.code : '')));
      if (!data) throw new Error('respuesta no es JSON válido');
      if (data.code !== 'Ok' || !data.routes || !data.routes[0]) throw new Error(data.message || data.code || 'sin ruta');
      const route = data.routes[0];
      const coords = route.geometry.coordinates.map((c) => [c[1], c[0]]);

      if (routeLine) map.removeLayer(routeLine);
      routeLine = L.polyline(coords, { color: color, weight: 4, opacity: 0.7 }).addTo(map);
      lastRouteCoords = coords;
      saveRouteCache(cacheKey, { coords: coords, distanceM: route.distance, durationS: route.duration });
      updateTripRouteInfo(formatRouteSummary(route.distance, route.duration));
    } catch (e) {
      // Sin conexión a OSRM: si tenemos una ruta real guardada de este
      // mismo tramo (de una consulta anterior), la reutilizamos como
      // referencia en vez de una línea recta "a ciegas".
      const cached = loadRouteCache(cacheKey);
      log('No se pudo calcular la ruta por calles (' + e.message + '); ' + (cached ? 'mostrando la última ruta conocida.' : 'mostrando línea directa.'));
      if (routeLine) map.removeLayer(routeLine);
      if (cached) {
        routeLine = L.polyline(cached.coords, { color: color, weight: 4, opacity: 0.45 }).addTo(map);
        lastRouteCoords = cached.coords;
        updateTripRouteInfo(formatRouteSummary(cached.distanceM, cached.durationS) + ' (ruta no actualizada)');
      } else {
        routeLine = L.polyline([[from.lat, from.lng], [to.lat, to.lng]], { color: color, weight: 3, opacity: 0.5, dashArray: '6 6' }).addTo(map);
        lastRouteCoords = null; // para reintentar la próxima vez, sin esperar a que se desvíe
        updateTripRouteInfo('~' + formatRouteSummary(distanceMeters(from.lat, from.lng, to.lat, to.lng)) + ' en línea recta');
      }
    }
  }

  // Actualiza el texto "X km · Y min" debajo del estado del viaje.
  function updateTripRouteInfo(text) {
    const el = document.getElementById('tripRouteInfo');
    if (el) el.textContent = text || '';
  }

  // Clave de caché para la ruta del tramo actual (cambia por viaje y por
  // tramo: ir al punto de recogida vs. ir al destino).
  function currentLegCacheKey() {
    if (!currentTrip) return null;
    const leg = ['accepted', 'en_route_to_pickup'].includes(currentTrip.status) ? 'pickup' : 'destination';
    return 'driver_' + currentTrip.id + '_' + leg;
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
    map.fitBounds(bounds, { padding: [30, 120] });

    // Tras ese vistazo general de 2 segundos, pasamos a modo "navegación":
    // acercamos el mapa sobre la moto para que se alcancen a ver las calles
    // y las vueltas, y lo mantenemos siguiéndola mientras avanza.
    followMode = true;
    show('recenterBtn');
    setTimeout(() => {
      if (followMode && lastLat && currentTrip) map.setView([lastLat, lastLng], NAV_ZOOM, { animate: true });
    }, 2000);

    lastRouteCoords = null; // viaje nuevo: forzamos a calcular la ruta real desde cero
    updateTripRoute(true);
  }

  // force=true recalcula sí o sí (ignora el umbral de desviación); se usa
  // al iniciar el viaje y al cambiar de estado, porque en esos casos
  // cambia por completo el tramo que hay que trazar.
  function updateTripRoute(force) {
    if (!map || !currentTrip || !currentTrip.origin) return;
    const driverPos = driverOwnMarker ? driverOwnMarker.getLatLng() : (lastLat ? { lat: lastLat, lng: lastLng } : null);

    if (['accepted', 'en_route_to_pickup'].includes(currentTrip.status) && driverPos) {
      drawTripRoute(driverPos, currentTrip.origin, '#16a34a', force, currentLegCacheKey());
    } else if (currentTrip.status === 'arrived') {
      if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
      lastRouteCoords = null;
      updateTripRouteInfo('');
    } else if (currentTrip.status === 'started') {
      drawTripRoute(currentTrip.origin, currentTrip.destination, '#1d4ed8', force, currentLegCacheKey());
    }
  }

  function clearTripMapLayers() {
    if (originMarker) { map.removeLayer(originMarker); originMarker = null; }
    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
    if (currentTrip) {
      clearRouteCache('driver_' + currentTrip.id + '_pickup');
      clearRouteCache('driver_' + currentTrip.id + '_destination');
    }
    lastRouteCoords = null;
    updateTripRouteInfo('');
    followMode = true;
    hide('recenterBtn');
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
    show('authScreen');
  }

  function stopEverything() {
    if (locationInterval) clearInterval(locationInterval);
    if (pusher) { pusher.disconnect(); pusher = null; }
    subscribedChannels = [];
    releaseWakeLock();
  }

  // ---------- Mantener la pantalla encendida mientras está disponible/en viaje ----------
  // Importante: esto evita que la pantalla se apague SOLA por inactividad
  // mientras la app sigue abierta y visible. No hace que la app siga
  // corriendo si el conductor la manda a segundo plano o apaga la pantalla
  // a propósito (los navegadores móviles pausan la pestaña en ese caso;
  // eso ya no se puede resolver desde una página web, requeriría una app
  // nativa con seguimiento de ubicación en segundo plano).
  async function requestWakeLock() {
    if (!('wakeLock' in navigator)) return;
    try {
      wakeLock = await navigator.wakeLock.request('screen');
      wakeLock.addEventListener('release', () => { wakeLock = null; });
    } catch (e) {
      log('No se pudo mantener la pantalla encendida: ' + e.message);
    }
  }

  function releaseWakeLock() {
    if (wakeLock) { wakeLock.release().catch(() => {}); wakeLock = null; }
  }

  // Si el sistema soltó el wake lock al cambiar de pestaña/app, lo
  // recuperamos en cuanto la pantalla vuelve a estar visible.
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !wakeLock && document.getElementById('availabilityPill')?.textContent === 'Disponible') {
      requestWakeLock();
    }
  });

  // Vuelve a centrar el mapa sobre la moto y reactiva el seguimiento automático.
  function recenterMap() {
    followMode = true;
    const pos = driverOwnMarker ? driverOwnMarker.getLatLng() : null;
    if (map && pos) map.setView(pos, NAV_ZOOM, { animate: true });
  }

  // ---------- Documentos ----------
  const DOC_STATUS_LABELS = {
    pending: 'Pendiente de revisión',
    approved: 'Aprobado',
    rejected: 'Rechazado',
    expired: 'Vencido',
  };

  function openDocuments() {
    hide('availabilityCard');
    hide('requestsView');
    hide('tripView');
    show('documentsView');
    if (sheet) sheet.snapTo('full');
    loadDriverDocuments();
  }

  // Al volver, restauramos la tarjeta que corresponda según el estado
  // actual (si hay un viaje en curso, o si está disponible esperando
  // solicitudes) — el mismo criterio que usa el resto de la app.
  function closeDocuments() {
    hide('documentsView');
    if (currentTrip) {
      show('tripView');
    } else {
      show('availabilityCard');
      if (document.getElementById('availabilityPill').textContent === 'Disponible') show('requestsView');
    }
    if (sheet) sheet.snapTo('half');
  }

  async function loadDriverDocuments() {
    const list = document.getElementById('documentsList');
    list.innerHTML = '<p class="hint">Cargando...</p>';
    try {
      const catalog = await api('/driver/documents');
      renderDocuments(catalog);
    } catch (e) {
      list.innerHTML = '<p class="hint">No se pudo cargar: ' + e.message + '</p>';
    }
  }

  function renderDocuments(catalog) {
    const list = document.getElementById('documentsList');
    list.innerHTML = catalog.map((item) => {
      const doc = item.document;
      const status = doc ? doc.display_status : null;
      const tagHtml = status
        ? '<span class="status-badge tag-' + status + '">' + (DOC_STATUS_LABELS[status] || status) + '</span>'
        : '<span class="status-badge">Falta subir</span>';
      const reasonHtml = (doc && doc.status === 'rejected' && doc.rejection_reason)
        ? '<p class="doc-reason">Motivo del rechazo: ' + doc.rejection_reason + '</p>'
        : '';
      const expiryHtml = (doc && doc.expires_at)
        ? '<p class="doc-expiry">Vence: ' + doc.expires_at + '</p>'
        : '';
      const fileInputId = 'docfile_' + item.type;
      const expiryInputId = 'docexp_' + item.type;
      const expiryFieldHtml = item.requires_expiry
        ? '<label for="' + expiryInputId + '">Fecha de vencimiento</label><input type="date" id="' + expiryInputId + '">'
        : '';

      return '' +
        '<div class="doc-item">' +
          '<div class="doc-head"><strong>' + item.label + '</strong>' + tagHtml + '</div>' +
          reasonHtml +
          expiryHtml +
          '<div class="doc-upload">' +
            expiryFieldHtml +
            '<input type="file" id="' + fileInputId + '" accept="image/*,application/pdf">' +
            '<button onclick="uploadDocument(\'' + item.type + '\')">' + (doc ? 'Volver a subir' : 'Subir') + '</button>' +
          '</div>' +
        '</div>';
    }).join('');
  }

  async function uploadDocument(type) {
    const fileInput = document.getElementById('docfile_' + type);
    const expInput = document.getElementById('docexp_' + type);
    const file = fileInput.files[0];

    if (!file) {
      alert('Selecciona una foto o PDF primero.');
      return;
    }

    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);
    if (expInput && expInput.value) formData.append('expires_at', expInput.value);

    try {
      await api('/driver/documents', { method: 'POST', body: formData });
      log('Documento subido, queda pendiente de revisión.');
      loadDriverDocuments();
    } catch (e) {
      alert('No se pudo subir: ' + e.message);
    }
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
      if (map) clearRequestMarkers();
      lastHeading = null;
      if (sheet) sheet.snapTo('collapsed');
      log('Te desconectaste. Ya no recibirás solicitudes nuevas.');
      return;
    }

    // Pasar a online: primero necesitamos ubicación
    document.getElementById('toggleBtn').disabled = true;
    try {
      const { lat, lng, heading } = await getLocation();
      const resolvedHeading = resolveHeading(lastLat, lastLng, lat, lng, heading);
      lastLat = lat; lastLng = lng; lastHeading = resolvedHeading;

      await api('/driver/location', { method: 'POST', body: { latitude: lat, longitude: lng, ...(resolvedHeading !== null ? { heading: resolvedHeading } : {}) } });
      await api('/driver/availability', { method: 'POST', body: { availability_status: 'available' } });

      pill.textContent = 'Disponible';
      pill.className = 'pill pill-on';
      document.getElementById('toggleBtn').textContent = 'Desconectarme';
      document.getElementById('locationHint').textContent = `Ubicación reportada: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;

      show('requestsView');
      map.setView([lat, lng], 15);
      updateOwnMarker(lat, lng, resolvedHeading);
      if (sheet) sheet.snapTo('half');
      await refreshZoneAndSubscribe(lat, lng);
      await loadNearbyRequests(lat, lng);
      requestWakeLock();

      locationInterval = setInterval(async () => {
        try {
          const prevLat = lastLat, prevLng = lastLng;
          const pos = await getLocation();
          const heading = resolveHeading(prevLat, prevLng, pos.lat, pos.lng, pos.heading);
          lastLat = pos.lat; lastLng = pos.lng; lastHeading = heading;

          await api('/driver/location', { method: 'POST', body: { latitude: pos.lat, longitude: pos.lng, ...(heading !== null ? { heading } : {}) } });
          await refreshZoneAndSubscribe(pos.lat, pos.lng);
          updateOwnMarker(pos.lat, pos.lng, heading);
          if (currentTrip) {
            updateTripRoute();
            if (followMode) map.setView([pos.lat, pos.lng], NAV_ZOOM, { animate: true });
          }
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
          // El conductor puede estar suscrito a varias celdas vecinas que
          // reciben el mismo evento; solo registramos/logueamos la primera vez.
          if (!openRequests[data.id]) {
            log(`Nueva solicitud cercana recibida (#${data.id})`);
          }
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
      hide('availabilityCard');
      clearRequestMarkers();
      setupTripMap();
      renderTrip();
      if (sheet) sheet.snapTo('collapsed'); // priorizamos ver el mapa/ruta; el conductor puede arrastrar para ver las acciones
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
        show('availabilityCard');
        clearTripMapLayers();
        if (sheet) sheet.snapTo('half');
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
      lastRouteCoords = null; // cambió el estado: es un tramo distinto, recalculamos sí o sí
      updateTripRoute(true);
    } catch (e) {
      alert('Error al actualizar el viaje: ' + e.message);
    }
  }

  // ---------- Arranque ----------
  function boot() {
    hide('authScreen');
    show('appView');
    document.getElementById('userGreeting').textContent = `Hola, ${user?.name || ''}`;

    initDriverMap(DEFAULT_CENTER[0], DEFAULT_CENTER[1]);
    setTimeout(() => { if (map) map.invalidateSize(); }, 150);

    if (!sheet) {
      sheet = initBottomSheet(document.getElementById('sheet'), document.getElementById('sheetDrag'), SHEET_SNAPS, 'collapsed');
    }
  }

  if (token && user) {
    boot();
  }
</script>
</body>
</html>
