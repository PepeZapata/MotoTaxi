{{--
  Componente compartido: mapa a pantalla completa + tarjeta inferior
  deslizable ("bottom sheet"), al estilo DiDi/Uber.

  Lo usan tanto conductor.blade.php como ciudadano.blade.php. Cada página
  define su propio contenido dentro del sheet; este partial solo aporta
  el esqueleto visual (.app-shell / .topbar / .sheet) y la lógica genérica
  de arrastre (initBottomSheet).
--}}
<style>
  .app-shell {
    position: fixed;
    inset: 0;
    overflow: hidden;
  }

  .map-layer {
    position: absolute;
    inset: 0;
    z-index: 0;
  }

  .topbar {
    position: absolute;
    top: 0; left: 0; right: 0;
    z-index: 1100;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 14px;
    pointer-events: none; /* el espacio vacío del topbar deja pasar los gestos del mapa */
  }
  .topbar > * { pointer-events: auto; }

  .topbar-group { display: flex; align-items: center; gap: 6px; }

  .topbar .pill, .topbar-btn, .topbar-chip {
    box-shadow: 0 2px 8px rgba(0,0,0,0.18);
  }

  .topbar-btn {
    width: auto;
    margin: 0;
    padding: 9px 14px;
    border-radius: 999px;
    background: white;
    color: var(--text);
    font-size: 12px;
    font-weight: 600;
    border: none;
  }
  .topbar-btn:hover { background: #f8fafc; }

  .topbar-chip {
    background: white;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    display: flex;
    align-items: center;
  }

  /* ---------- Bottom sheet ---------- */
  .sheet {
    position: absolute;
    left: 0; right: 0; bottom: 0;
    max-width: 480px;
    margin: 0 auto;
    height: 100%;
    background: var(--card);
    border-radius: 18px 18px 0 0;
    box-shadow: 0 -6px 28px rgba(0,0,0,0.18);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    transform: translateY(calc(100% - 150px));
    transition: transform 0.28s cubic-bezier(0.22, 0.8, 0.3, 1);
    touch-action: none;
  }
  .sheet.dragging { transition: none; }

  .sheet-drag {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0 2px;
    cursor: grab;
    touch-action: none;
  }
  .sheet-handle {
    width: 36px;
    height: 4px;
    border-radius: 999px;
    background: #cbd5e1;
  }

  .sheet-content {
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 2px 16px 24px;
    touch-action: pan-y;
  }

  /* Dentro del sheet las ".card" existentes se "aplanan": el sheet ya
     actúa como la tarjeta visualmente, así que quitamos su propio borde y
     sombra para no anidar dos recuadros. */
  .sheet-content .card {
    background: transparent;
    border: none;
    box-shadow: none;
    border-radius: 0;
    padding: 14px 0;
    margin: 0;
    border-bottom: 1px solid var(--border);
  }
  .sheet-content .card:last-child { border-bottom: none; }

  .log-details { margin-top: 4px; }
  .log-details summary {
    cursor: pointer;
    font-size: 12px;
    color: var(--muted);
    padding: 6px 0;
    user-select: none;
  }
</style>

<script>
  /**
   * Inicializa el comportamiento de arrastre de un bottom sheet.
   *
   * @param {HTMLElement} sheetEl   El contenedor ".sheet".
   * @param {HTMLElement} dragEl    La zona arrastrable (normalmente ".sheet-drag").
   * @param {Object} snapPoints     Mapa nombre -> altura visible. Un valor
   *                                <= 1 se interpreta como fracción de
   *                                innerHeight; un valor > 1, como píxeles.
   * @param {string} initial        Nombre del snap inicial.
   */
  function initBottomSheet(sheetEl, dragEl, snapPoints, initial) {
    const state = { current: initial, visible: 0, dragging: false, startY: 0, startVisible: 0 };

    function heightFor(name) {
      const v = snapPoints[name];
      return v <= 1 ? window.innerHeight * v : v;
    }

    function setTransform(px, animate) {
      sheetEl.classList.toggle('dragging', !animate);
      sheetEl.style.transform = 'translateY(calc(100% - ' + Math.round(px) + 'px))';
      state.visible = px;
    }

    function snapTo(name) {
      if (!snapPoints[name]) return;
      state.current = name;
      setTransform(heightFor(name), true);
    }

    function nearestSnapName(px) {
      let best = state.current;
      let bestDist = Infinity;
      Object.keys(snapPoints).forEach((name) => {
        const d = Math.abs(heightFor(name) - px);
        if (d < bestDist) { bestDist = d; best = name; }
      });
      return best;
    }

    function onDown(e) {
      state.dragging = true;
      state.startY = e.clientY;
      state.startVisible = state.visible || heightFor(state.current);
      if (dragEl.setPointerCapture) {
        try { dragEl.setPointerCapture(e.pointerId); } catch (err) { /* no-op */ }
      }
    }

    function onMove(e) {
      if (!state.dragging) return;
      const delta = state.startY - e.clientY; // arrastrar hacia arriba = sheet más alto
      let px = state.startVisible + delta;
      const minV = heightFor('collapsed' in snapPoints ? 'collapsed' : state.current) * 0.5;
      const maxV = window.innerHeight * 0.96;
      px = Math.max(minV, Math.min(maxV, px));
      setTransform(px, false);
    }

    function onUp() {
      if (!state.dragging) return;
      state.dragging = false;
      snapTo(nearestSnapName(state.visible));
    }

    dragEl.addEventListener('pointerdown', onDown);
    dragEl.addEventListener('pointermove', onMove);
    dragEl.addEventListener('pointerup', onUp);
    dragEl.addEventListener('pointercancel', onUp);

    window.addEventListener('resize', () => snapTo(state.current));

    snapTo(initial);

    return { snapTo, get current() { return state.current; } };
  }
</script>
