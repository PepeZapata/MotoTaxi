<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mototaxi - Admin</title>
<style>
  :root {
    --primary: #7c3aed;
    --primary-dark: #6d28d9;
    --bg: #f8fafc;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
    --success: #16a34a;
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
  .app { max-width: 720px; margin: 0 auto; padding: 24px 16px 60px; }
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

  button {
    padding: 9px 16px;
    border: none;
    border-radius: 8px;
    background: var(--primary);
    color: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
  }
  button:hover { background: var(--primary-dark); }
  button.full { width: 100%; margin-top: 10px; }
  button.approve { background: var(--success); }
  button.approve:hover { background: #15803d; }
  button.reject { background: var(--danger); }
  button.reject:hover { background: #b91c1c; }
  button.secondary { background: transparent; color: var(--primary); border: 1px solid var(--primary); }

  .error { color: var(--danger); font-size: 13px; margin-top: 8px; }
  .hint { color: var(--muted); font-size: 12px; margin: 0 0 12px; }
  .hidden { display: none !important; }

  .userbar { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--muted); margin-bottom: 16px; }

  .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
  @media (min-width: 500px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
  .stat-box { background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 12px; text-align: center; }
  .stat-box .n { font-size: 22px; font-weight: 700; }
  .stat-box .l { font-size: 11px; color: var(--muted); margin-top: 2px; }

  .tabs { display: flex; gap: 8px; margin-bottom: 14px; }
  .tab { padding: 7px 14px; border-radius: 999px; font-size: 13px; cursor: pointer; border: 1px solid var(--border); color: var(--muted); }
  .tab.active { background: var(--primary); color: white; border-color: var(--primary); }

  .driver-row {
    display: flex; justify-content: space-between; align-items: center;
    border: 1px solid var(--border); border-radius: 10px; padding: 12px; margin-bottom: 8px;
  }
  .driver-row .info p { margin: 2px 0; font-size: 13px; }
  .driver-row .name { font-weight: 600; font-size: 14px; }
  .driver-row .actions { display: flex; gap: 6px; }
  .driver-row .actions button { padding: 7px 12px; font-size: 12px; }

  .status-tag { font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; }
  .tag-pending { background: #fef9c3; color: #854d0e; }
  .tag-approved { background: #dcfce7; color: #166534; }
  .tag-rejected { background: #fee2e2; color: #991b1b; }
  .tag-expired { background: #ffedd5; color: #9a3412; }

  .empty { color: var(--muted); font-size: 13px; text-align: center; padding: 20px 0; }

  /* ---------- Detalle de conductor / documentos ---------- */
  .doc-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 8px;
  }
  .doc-item .doc-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
  .doc-item .doc-head strong { font-size: 13px; }
  .doc-item .doc-meta { color: var(--muted); font-size: 12px; margin: 4px 0 0; }
  .doc-item .doc-reason { color: var(--danger); font-size: 12px; margin: 4px 0 0; }
  .doc-item .doc-actions { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
  .doc-item .doc-actions button { padding: 6px 10px; font-size: 12px; }
  .doc-item .doc-actions button.secondary { padding: 6px 10px; }
</style>
</head>
<body>
<div class="app">
  <header>
    <h1>🛡️ Mototaxi</h1>
    <p>Panel de administrador</p>
  </header>

  <!-- ===================== LOGIN ===================== -->
  <div id="loginView" class="card">
    <h2>Iniciar sesión</h2>
    <label>Correo</label>
    <input id="loginEmail" type="email" value="admin@mototaxi.test">
    <label>Contraseña</label>
    <input id="loginPassword" type="password" value="password">
    <button class="full" onclick="login()">Entrar</button>
    <div id="loginError" class="error hidden"></div>
  </div>

  <!-- ===================== APP (logueado) ===================== -->
  <div id="appView" class="hidden">
    <div class="userbar">
      <span id="userGreeting"></span>
      <button class="secondary" onclick="logout()">Cerrar sesión</button>
    </div>

    <!-- Métricas -->
    <div class="card">
      <h2>Resumen</h2>
      <div id="statsGrid" class="stats-grid"></div>
    </div>

    <!-- Conductores -->
    <div class="card" id="driversCard">
      <h2>Conductores</h2>
      <div class="tabs">
        <span class="tab active" data-filter="pending" onclick="setFilter('pending')">Pendientes</span>
        <span class="tab" data-filter="all" onclick="setFilter('all')">Todos</span>
      </div>
      <div id="driversList"></div>
      <p id="driversEmpty" class="empty hidden">No hay conductores en esta lista.</p>
    </div>

    <!-- Detalle de un conductor: documentos -->
    <div class="card hidden" id="driverDetailView">
      <h2 id="driverDetailName">Documentos del conductor</h2>
      <p class="hint" id="driverDetailAccountStatus"></p>
      <div id="driverDocsList"></div>
      <button class="secondary" onclick="closeDriverDetail()">Volver</button>
    </div>
  </div>
</div>

<script>
  const API_BASE = window.location.origin + '/api';

  let token = localStorage.getItem('mototaxi_admin_token') || null;
  let user = JSON.parse(localStorage.getItem('mototaxi_admin_user') || 'null');
  let currentFilter = 'pending';

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
      const err = new Error(msg);
      // Ej. el 422 de "no se puede aprobar, faltan documentos" trae esta
      // lista además del mensaje; la colgamos del Error para que el caller
      // la pueda mostrar sin tener que volver a parsear la respuesta.
      if (data.missing_documents) err.missingDocuments = data.missing_documents;
      throw err;
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
      if (data.user.role !== 'admin') {
        throw new Error('Esta cuenta no tiene permisos de administrador.');
      }
      token = data.token;
      user = data.user;
      localStorage.setItem('mototaxi_admin_token', token);
      localStorage.setItem('mototaxi_admin_user', JSON.stringify(user));
      boot();
    } catch (e) {
      const el = document.getElementById('loginError');
      el.textContent = e.message;
      show('loginError');
    }
  }

  function logout() {
    localStorage.removeItem('mototaxi_admin_token');
    localStorage.removeItem('mototaxi_admin_user');
    token = null; user = null;
    hide('appView');
    show('loginView');
  }

  async function loadStats() {
    const stats = await api('/admin/stats');
    const grid = document.getElementById('statsGrid');
    grid.innerHTML = `
      <div class="stat-box"><div class="n">${stats.drivers.pending}</div><div class="l">Conductores pendientes</div></div>
      <div class="stat-box"><div class="n">${stats.drivers.online_now}</div><div class="l">Conductores en línea</div></div>
      <div class="stat-box"><div class="n">${stats.trips.in_progress}</div><div class="l">Viajes en curso</div></div>
      <div class="stat-box"><div class="n">${stats.trips.completed}</div><div class="l">Viajes completados</div></div>
    `;
  }

  function setFilter(filter) {
    currentFilter = filter;
    document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.filter === filter));
    loadDrivers();
  }

  async function loadDrivers() {
    const path = currentFilter === 'pending' ? '/admin/drivers/pending' : '/admin/drivers';
    const drivers = await api(path);
    renderDrivers(drivers);
  }

  function renderDrivers(drivers) {
    const container = document.getElementById('driversList');

    if (!drivers.length) {
      container.innerHTML = '';
      show('driversEmpty');
      return;
    }
    hide('driversEmpty');

    const tagClass = { pending: 'tag-pending', approved: 'tag-approved', rejected: 'tag-rejected' };
    const tagLabel = { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' };

    container.innerHTML = drivers.map((d) => `
      <div class="driver-row">
        <div class="info">
          <p class="name">${d.user.name} <span class="status-tag ${tagClass[d.approval_status]}">${tagLabel[d.approval_status]}</span></p>
          <p>Licencia: ${d.license_number} · Tel: ${d.user.phone || '—'}</p>
          <p>Vehículo: ${d.vehicles[0] ? d.vehicles[0].plate + ' (' + d.vehicles[0].model + ')' : 'sin registrar'}</p>
        </div>
        <div class="actions">
          <button class="secondary" onclick="openDriverDetail(${d.id}, '${(d.user.name || '').replace(/'/g, "\\'")}')">Ver documentos</button>
          ${d.approval_status === 'pending' ? `
            <button class="approve" onclick="decide(${d.id}, 'approved')">Aprobar</button>
            <button class="reject" onclick="decide(${d.id}, 'rejected')">Rechazar</button>
          ` : ''}
        </div>
      </div>
    `).join('');
  }

  async function decide(driverProfileId, decision) {
    try {
      await api(`/admin/drivers/${driverProfileId}/decision`, { method: 'POST', body: { decision } });
      await loadDrivers();
      await loadStats();
    } catch (e) {
      // Si el backend bloqueó la aprobación por documentos faltantes,
      // "e.message" ya trae el mensaje principal; agregamos la lista
      // específica de qué falta si vino en la respuesta (ver api()).
      alert('Error: ' + e.message + (e.missingDocuments && e.missingDocuments.length ? '\n\nFalta:\n- ' + e.missingDocuments.join('\n- ') : ''));
    }
  }

  // ---------- Detalle de conductor: documentos ----------
  const DOC_STATUS_LABELS = {
    pending: 'Pendiente de revisión',
    approved: 'Aprobado',
    rejected: 'Rechazado',
    expired: 'Vencido',
  };
  const DOC_TAG_CLASS = { pending: 'tag-pending', approved: 'tag-approved', rejected: 'tag-rejected', expired: 'tag-expired' };

  let currentDriverDetailId = null;

  function openDriverDetail(driverProfileId, name) {
    currentDriverDetailId = driverProfileId;
    document.getElementById('driverDetailName').textContent = 'Documentos de ' + name;
    hide('driversCard');
    show('driverDetailView');
    loadDriverDocs();
  }

  function closeDriverDetail() {
    currentDriverDetailId = null;
    hide('driverDetailView');
    show('driversCard');
    loadDrivers();
  }

  async function loadDriverDocs() {
    const list = document.getElementById('driverDocsList');
    list.innerHTML = '<p class="hint">Cargando...</p>';
    try {
      const catalog = await api(`/admin/drivers/${currentDriverDetailId}/documents`);
      renderDriverDocs(catalog);
    } catch (e) {
      list.innerHTML = '<p class="hint">No se pudo cargar: ' + e.message + '</p>';
    }
  }

  function renderDriverDocs(catalog) {
    const list = document.getElementById('driverDocsList');
    const missing = catalog.filter((item) => !item.document || item.document.display_status !== 'approved').length;
    document.getElementById('driverDetailAccountStatus').textContent = missing
      ? `Faltan ${missing} documento(s) aprobados y vigentes para poder aprobar la cuenta completa.`
      : 'Todos los documentos están aprobados y vigentes: ya se puede aprobar la cuenta.';

    list.innerHTML = catalog.map((item) => {
      const doc = item.document;
      const status = doc ? doc.display_status : null;
      const tagHtml = status
        ? `<span class="status-tag ${DOC_TAG_CLASS[status] || ''}">${DOC_STATUS_LABELS[status] || status}</span>`
        : '<span class="status-tag">No subido</span>';
      const reasonHtml = (doc && doc.status === 'rejected' && doc.rejection_reason)
        ? `<p class="doc-reason">Motivo del rechazo: ${doc.rejection_reason}</p>`
        : '';
      const metaHtml = doc
        ? `<p class="doc-meta">${doc.expires_at ? 'Vence: ' + doc.expires_at + ' · ' : ''}Subido: ${new Date(doc.uploaded_at).toLocaleDateString()}</p>`
        : '<p class="doc-meta">El conductor todavía no sube este documento.</p>';

      const actionsHtml = doc ? `
        <div class="doc-actions">
          <button class="secondary" onclick="viewDocumentFile(${doc.id})">Ver archivo</button>
          ${doc.status !== 'approved' ? `<button class="approve" onclick="decideDocument(${doc.id}, 'approved')">Aprobar</button>` : ''}
          ${doc.status !== 'rejected' ? `<button class="reject" onclick="rejectDocumentPrompt(${doc.id})">Rechazar</button>` : ''}
        </div>
      ` : '';

      return `
        <div class="doc-item">
          <div class="doc-head"><strong>${item.label}</strong>${tagHtml}</div>
          ${metaHtml}
          ${reasonHtml}
          ${actionsHtml}
        </div>
      `;
    }).join('');
  }

  async function viewDocumentFile(driverDocumentId) {
    try {
      const res = await fetch(`${API_BASE}/documents/${driverDocumentId}/file`, {
        headers: { Authorization: 'Bearer ' + token },
      });
      if (!res.ok) throw new Error('No se pudo abrir el archivo (HTTP ' + res.status + ').');
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank');
      // Liberamos la URL un poco después de abrirla, para no acumular
      // memoria si el admin revisa muchos documentos seguidos.
      setTimeout(() => URL.revokeObjectURL(url), 60000);
    } catch (e) {
      alert('Error: ' + e.message);
    }
  }

  function rejectDocumentPrompt(driverDocumentId) {
    const reason = prompt('Motivo del rechazo (el conductor lo va a ver):');
    if (reason === null) return; // canceló
    if (!reason.trim()) { alert('El motivo no puede quedar vacío.'); return; }
    decideDocument(driverDocumentId, 'rejected', reason.trim());
  }

  async function decideDocument(driverDocumentId, decision, reason) {
    try {
      await api(`/admin/documents/${driverDocumentId}/decision`, { method: 'POST', body: { decision, reason } });
      await loadDriverDocs();
    } catch (e) {
      alert('Error: ' + e.message);
    }
  }

  function boot() {
    hide('loginView');
    show('appView');
    document.getElementById('userGreeting').textContent = `Hola, ${user?.name || ''}`;
    loadStats();
    loadDrivers();
  }

  if (token && user) {
    boot();
  }
</script>
</body>
</html>
