const state = { role: null, estructura: [], resumen: null, autoevaluacionId: null };
const $app = document.getElementById('app');

const api = async (action, method = 'GET', body = null, isForm = false) => {
  const options = { method, credentials: 'same-origin' };
  if (body) {
    options.body = isForm ? body : JSON.stringify(body);
    if (!isForm) options.headers = { 'Content-Type': 'application/json' };
  }
  const res = await fetch(`api.php?action=${encodeURIComponent(action)}`, options);
  if (res.headers.get('content-type')?.includes('application/json')) {
    const data = await res.json();
    if (!data.ok) throw new Error(data.message || 'Error');
    return data.data;
  }
  return res;
};

const shell = (content) => `<div class="container"><div class="card"><h1>${window.APP_CONFIG.appName}</h1><span class="badge">SPA Autoevaluación</span></div>${content}</div>`;

function renderHome() {
  $app.innerHTML = shell(`
  <div class="row">
    <div class="card"><h2>Ingreso Estudiante</h2><form id="f-estudiante">
      <label>Código</label><input name="codigo" required>
      <div class="row"><div><label>Grado</label><input name="grado" required></div><div><label>Curso (ej: 8-1)</label><input name="curso" required></div></div>
      <label>Contraseña del período</label><input type="password" name="password_periodo" required>
      <button>Ingresar</button>
    </form></div>
    <div class="card"><h2>Ingreso Docente</h2><form id="f-docente">
      <label>Usuario</label><input name="usuario" required>
      <label>Contraseña</label><input type="password" name="password" required>
      <button>Ingresar</button>
    </form></div>
  </div>`);

  document.getElementById('f-docente').onsubmit = async (e) => {
    e.preventDefault();
    try {
      const body = Object.fromEntries(new FormData(e.target));
      await api('docente.login', 'POST', body);
      state.role = 'docente';
      renderDocente();
    } catch (err) { Swal.fire('Error', err.message, 'error'); }
  };

  document.getElementById('f-estudiante').onsubmit = async (e) => {
    e.preventDefault();
    try {
      const body = Object.fromEntries(new FormData(e.target));
      const data = await api('estudiante.login', 'POST', body);
      state.role = 'estudiante';
      if (data.ya_enviado) return renderResumenEstudiante();
      renderFormularioEstudiante();
    } catch (err) { Swal.fire('Error', err.message, 'error'); }
  };
}

async function renderFormularioEstudiante() {
  try {
    state.estructura = await api('estudiante.estructura');
  } catch { return renderHome(); }

  const dims = state.estructura.map((d, di) => `
    <div class="card"><h3>${di + 1}. ${d.nombre}</h3>
      ${d.items.map((item, ii) => `
        <div style="margin-bottom:12px"><div>${item}</div>
          <div class="radio-group">${[1,2,3,4,5].map(v=>`<label><input type="radio" name="${d.id}_item_${ii+1}" value="${v}" required>${v}</label>`).join('')}</div>
        </div>`).join('')}
    </div>`).join('');

  $app.innerHTML = shell(`<div class="card"><h2>Formulario de Autoevaluación</h2><p>Escala de 1 a 5.</p><button class="secondary" id="logout-est">Cerrar sesión</button></div>
    <form id="f-auto">${dims}<button>Guardar autoevaluación</button></form>`);

  document.getElementById('logout-est').onclick = async () => { await api('estudiante.logout','POST',{}); renderHome(); };

  document.getElementById('f-auto').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const respuestas = {};
    state.estructura.forEach(d => {
      respuestas[d.id] = {};
      d.items.forEach((_item, i) => {
        respuestas[d.id][`item_${i+1}`] = Number(fd.get(`${d.id}_item_${i+1}`));
      });
    });

    try {
      const data = await api('estudiante.guardar', 'POST', { respuestas });
      state.autoevaluacionId = data.autoevaluacion_id;
      Swal.fire('Listo', 'Autoevaluación guardada exitosamente.', 'success');
      renderResumenEstudiante();
    } catch (err) { Swal.fire('Error', err.message, 'error'); }
  };
}

async function renderResumenEstudiante() {
  try { state.resumen = await api('estudiante.resumen'); }
  catch { return renderHome(); }

  const r = state.resumen;
  $app.innerHTML = shell(`<div class="card"><h2>Resumen final</h2>
    <p><strong>Nota final:</strong> ${r.nota_final}</p>
    <div class="row3">
      <div class="card">Dim.1: ${r.promedio_dimension_1}</div><div class="card">Dim.2: ${r.promedio_dimension_2}</div><div class="card">Dim.3: ${r.promedio_dimension_3}</div>
      <div class="card">Dim.4: ${r.promedio_dimension_4}</div><div class="card">Dim.5: ${r.promedio_dimension_5}</div>
    </div>
    <button id="descargar-pdf">Descargar PDF</button>
    <button class="secondary" id="logout-est">Salir</button>
  </div>`);

  document.getElementById('descargar-pdf').onclick = () => {
    window.open(`api.php?action=estudiante.pdf&id=${r.id}`, '_blank');
  };
  document.getElementById('logout-est').onclick = async () => { await api('estudiante.logout','POST',{}); renderHome(); };
}

async function renderDocente() {
  let periodos = [];
  try { periodos = await api('docente.periodos.list'); } catch { return renderHome(); }

  $app.innerHTML = shell(`<div class="card"><h2>Panel Docente</h2><button class="secondary" id="logout-doc">Cerrar sesión</button></div>
  <div class="card"><h3>Períodos</h3><form id="f-periodo" class="row3">
    <input name="nombre" placeholder="Nombre período (ej: 2026-1)" required>
    <input name="password" type="password" placeholder="Contraseña general" required>
    <button>Crear período</button>
  </form>
  <div class="table-wrap"><table><thead><tr><th>ID</th><th>Nombre</th><th>Activo</th><th>Formulario</th><th>Acciones</th></tr></thead><tbody>
  ${periodos.map(p=>`<tr><td>${p.id}</td><td>${p.nombre}</td><td>${p.activo?'Sí':'No'}</td><td>${p.formulario_abierto?'Abierto':'Cerrado'}</td><td>
  <button data-activate="${p.id}">Activar</button></td></tr>`).join('')}
  </tbody></table></div></div>

  <div class="card"><h3>Importar estudiantes (CSV)</h3><form id="f-import"><input type="file" name="archivo" accept=".csv" required><button>Importar</button></form></div>

  <div class="card"><h3>Estudiantes</h3><form id="f-filtro" class="row3"><input name="curso" placeholder="Curso"><input name="codigo" placeholder="Código"><input name="nombre" placeholder="Nombre"><button>Filtrar</button></form><div id="tabla-estudiantes"></div></div>

  <div class="card"><h3>Reportes</h3><form id="f-reportes" class="row3"><input name="periodo_id" placeholder="ID período" required><input name="curso" placeholder="Curso (opcional)"><button>Consultar</button></form><div id="tabla-reportes"></div></div>`);

  document.getElementById('logout-doc').onclick = async ()=>{ await api('docente.logout','POST',{}); renderHome(); };
  document.getElementById('f-periodo').onsubmit = async (e)=>{
    e.preventDefault();
    const body = Object.fromEntries(new FormData(e.target));
    body.activo = true; body.formulario_abierto = true;
    await api('docente.periodos.create','POST', body);
    Swal.fire('Listo','Período creado','success'); renderDocente();
  };

  document.querySelectorAll('[data-activate]').forEach(btn=>btn.onclick=async()=>{
    await api('docente.periodos.update','POST',{periodo_id:Number(btn.dataset.activate),activo:true,formulario_abierto:true});
    renderDocente();
  });

  document.getElementById('f-import').onsubmit = async (e)=>{
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = await api('docente.estudiantes.import','POST',formData,true);
    Swal.fire('Importación completa',`Registros procesados: ${data.importados}`,'success');
  };

  document.getElementById('f-filtro').onsubmit = async (e)=>{
    e.preventDefault();
    const q = new URLSearchParams(Object.fromEntries(new FormData(e.target))).toString();
    const data = await api(`docente.estudiantes.list&${q}`);
    document.getElementById('tabla-estudiantes').innerHTML = tabla(data,['codigo','nombre','grado','curso']);
  };

  document.getElementById('f-reportes').onsubmit = async (e)=>{
    e.preventDefault();
    const params = Object.fromEntries(new FormData(e.target));
    const q = new URLSearchParams(params).toString();
    const rows = await api(`docente.reportes.list&${q}`);
    const exportUrl = `api.php?action=docente.reportes.export&${q}`;
    document.getElementById('tabla-reportes').innerHTML = `<button onclick="window.open('${exportUrl}','_blank')">Exportar XLSX</button>` + tabla(rows,['codigo','nombre','grado','curso','periodo','nota_final','fecha_envio']);
  };
}

const tabla = (rows, cols) => `<div class="table-wrap"><table><thead><tr>${cols.map(c=>`<th>${c}</th>`).join('')}</tr></thead><tbody>${rows.map(r=>`<tr>${cols.map(c=>`<td>${r[c]??''}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;

renderHome();
