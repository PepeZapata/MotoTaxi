{{--
  Helpers de geometría + caché local para el trazado de rutas (OSRM).
  Compartido por conductor.blade.php y ciudadano.blade.php.
--}}
<script>
  // Distancia aproximada en metros entre dos coordenadas (fórmula haversine).
  function distanceMeters(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (d) => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  // Distancia mínima (en metros) de un punto a un segmento de recta.
  // Proyecta todo a un plano local en metros (centrado en el punto "a");
  // es una aproximación, pero más que suficiente a escala de ciudad.
  function distanceToSegmentMeters(plat, plng, aLat, aLng, bLat, bLng) {
    const mPerDegLat = 111320;
    const mPerDegLng = 111320 * Math.cos((aLat * Math.PI) / 180);

    const bx = (bLng - aLng) * mPerDegLng, by = (bLat - aLat) * mPerDegLat;
    const px = (plng - aLng) * mPerDegLng, py = (plat - aLat) * mPerDegLat;

    const abLenSq = bx * bx + by * by;
    let t = abLenSq > 0 ? (px * bx + py * by) / abLenSq : 0;
    t = Math.max(0, Math.min(1, t));

    const cx = bx * t, cy = by * t;
    const dx = px - cx, dy = py - cy;
    return Math.sqrt(dx * dx + dy * dy);
  }

  // Distancia mínima de un punto a una polyline completa ([[lat,lng], ...]).
  function distanceToPolylineMeters(lat, lng, points) {
    if (!points || points.length < 2) return Infinity;
    let min = Infinity;
    for (let i = 0; i < points.length - 1; i++) {
      const d = distanceToSegmentMeters(lat, lng, points[i][0], points[i][1], points[i + 1][0], points[i + 1][1]);
      if (d < min) min = d;
    }
    return min;
  }

  // ---------- Caché local de la última ruta dibujada ----------
  // Si la app se queda sin señal o se recarga, podemos mostrar de inmediato
  // la última ruta real conocida en vez de una línea recta "a ciegas".
  const ROUTE_CACHE_PREFIX = 'mototaxi_route_cache_';

  function saveRouteCache(key, coords) {
    if (!key) return;
    try {
      localStorage.setItem(ROUTE_CACHE_PREFIX + key, JSON.stringify(coords));
    } catch (e) { /* localStorage lleno o no disponible: no es crítico */ }
  }

  function loadRouteCache(key) {
    if (!key) return null;
    try {
      const raw = localStorage.getItem(ROUTE_CACHE_PREFIX + key);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function clearRouteCache(key) {
    if (!key) return;
    try { localStorage.removeItem(ROUTE_CACHE_PREFIX + key); } catch (e) { /* no-op */ }
  }
</script>
