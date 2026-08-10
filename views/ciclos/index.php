<?php ob_start(); ?>
<?php 
require_once 'helpers/permisos_helper.php';
require_once 'models/Usuario.php';
?>

<style>
    :root {
        --pantone-vino: #9F2241;
        --pantone-vino-oscuro: #691C32;
        --pantone-verde: #235B4E;
        --pantone-verde-oscuro: #10312B;
        --pantone-dorado: #DDC9A3;
        --pantone-dorado-oscuro: #BC955C;
        --pantone-gris: #98989A;
        --pantone-gris-oscuro: #6F7271;
        --schedule-bar-color: #235B4E;
    }
    .admin-window {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 185px);
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #d0d0d2;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-top: 20px;
        border-radius: 12px;
        overflow: hidden;
    }
    .main-content-ciclos {
        display: flex;
        flex-grow: 1;
        overflow: hidden;
    }
    .panel-left {
        width: 380px;
        border-right: 1px solid #d0d0d2;
        display: flex;
        flex-direction: column;
        background: white;
    }
    .panel-left-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
        color: white;
        border-bottom: 3px solid #BC955C;
    }
    .panel-left-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .cycles-table-container {
        flex-grow: 1;
        overflow: auto;
    }
    .cycles-table {
        width: 100%;
        border-collapse: collapse;
    }
    .cycles-table th, .cycles-table td {
        border-bottom: 1px solid #e9ecef;
        padding: 12px 16px;
        text-align: left;
        white-space: nowrap;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 0.8rem;
    }
    .cycles-table th {
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6F7271;
    }
    .cycles-table tbody tr { 
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .cycles-table tbody tr:hover { 
        background: linear-gradient(90deg, rgba(159, 34, 65, 0.05) 0%, rgba(159, 34, 65, 0.02) 100%);
    }
    .cycles-table tbody tr.selected {
        background: linear-gradient(90deg, #9F2241 0%, #691C32 100%);
        color: #ffffff;
    }
    .cycles-table tbody tr.selected td {
        color: #ffffff;
    }
    .panel-right {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: white;
    }
    .schedule-editor {
        flex-grow: 1;
        overflow: auto;
        padding: 15px;
        position: relative;
    }
    .time-grid-header {
        display: flex;
        padding-left: 100px;
        background: linear-gradient(135deg, #235B4E 0%, #10312B 100%);
        border-bottom: 3px solid #DDC9A3;
        position: sticky;
        top: 0;
        z-index: 10;
        border-radius: 8px 8px 0 0;
    }
    .time-grid-hour {
        flex: 1;
        text-align: center;
        font-size: 11px;
        padding: 8px 0;
        border-left: 1px solid rgba(255,255,255,0.1);
        min-width: 35px;
        color: #DDC9A3;
        font-weight: 500;
    }
    .day-row {
        display: flex;
        align-items: center;
        height: 55px;
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.2s;
    }
    .day-row:hover {
        background-color: rgba(35, 91, 78, 0.03);
    }
    .day-label {
        width: 100px;
        flex-shrink: 0;
        text-align: right;
        padding-right: 15px;
        font-weight: 600;
        font-size: 12px;
        text-transform: capitalize;
        border-right: 2px solid #DDC9A3;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
        color: #10312B;
    }
    .schedule-track {
        flex-grow: 1;
        position: relative;
        background-image: linear-gradient(to right, #f0f0f0 1px, transparent 1px);
        background-size: calc(100% / 24) 100%;
        height: 100%;
    }
    .schedule-block {
        position: absolute;
        top: 8%;
        height: 84%;
        background-color: #235B4E;
        background-image: none;
        border: 1px solid rgba(0,0,0,0.2);
        border-radius: 6px;
        color: white;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        overflow: hidden;
        padding: 0 4px;
        box-shadow: 0 2px 8px rgba(16, 49, 43, 0.3);
        z-index: 10;
        font-weight: 500;
    }
    .schedule-block[data-color] {
        background-image: linear-gradient(135deg, var(--block-color, currentColor) 0%, var(--block-color-dark, currentColor) 100%);
    }
    .action-bar {
        border-top: 1px solid #d0d0d2;
        padding: 12px 20px;
        background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .action-bar .btn {
        border-radius: 6px;
        font-weight: 500;
        padding: 8px 16px;
        transition: all 0.2s ease;
    }
    .action-bar .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .btn-pantone-vino {
        background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
        border: none;
        color: white;
    }
    .btn-pantone-vino:hover {
        background: linear-gradient(135deg, #691C32 0%, #9F2241 100%);
        color: white;
    }
    .btn-pantone-verde {
        background: linear-gradient(135deg, #235B4E 0%, #10312B 100%);
        border: none;
        color: white;
    }
    .btn-pantone-verde:hover {
        background: linear-gradient(135deg, #10312B 0%, #235B4E 100%);
        color: white;
    }
    .btn-pantone-outline {
        background: transparent;
        border: 2px solid #9F2241;
        color: #9F2241;
    }
    .btn-pantone-outline:hover {
        background: #9F2241;
        color: white;
    }
    .btn-pantone-danger {
        background: linear-gradient(135deg, #6F7271 0%, #424242 100%);
        border: none;
        color: white;
    }
    .btn-pantone-danger:hover {
        background: linear-gradient(135deg, #424242 0%, #6F7271 100%);
        color: white;
    }

    /* Estilos para Modo Oscuro */
    body.dark-theme .admin-window {
        background: linear-gradient(135deg, #1a1a1a 0%, #2b2b2b 100%);
        border-color: #404040;
    }
    body.dark-theme .panel-left {
        background-color: #242424;
        border-right-color: #404040;
    }
    body.dark-theme .panel-left-header {
        background: linear-gradient(135deg, #691C32 0%, #9F2241 100%);
    }
    body.dark-theme .panel-right {
        background-color: #1e1e1e;
    }
    body.dark-theme .cycles-table th {
        background-color: #2a2a2a;
        color: #98989A;
        border-bottom-color: #404040;
    }
    body.dark-theme .cycles-table td {
        border-bottom-color: #333;
        color: #e9ecef;
    }
    body.dark-theme .cycles-table tbody tr:hover {
        background: linear-gradient(90deg, rgba(159, 34, 65, 0.15) 0%, rgba(159, 34, 65, 0.05) 100%);
    }
    body.dark-theme .time-grid-header {
        background: linear-gradient(135deg, #10312B 0%, #235B4E 100%);
    }
    body.dark-theme .time-grid-hour {
        border-left-color: rgba(255,255,255,0.1);
        color: #DDC9A3;
    }
    body.dark-theme .day-row {
        border-bottom-color: #333;
    }
    body.dark-theme .day-row:hover {
        background-color: rgba(35, 91, 78, 0.1);
    }
    body.dark-theme .day-label {
        background: linear-gradient(90deg, #2a2a2a 0%, #1e1e1e 100%);
        border-right-color: #BC955C;
        color: #DDC9A3;
    }
    body.dark-theme .schedule-track {
        background-image: linear-gradient(to right, #333 1px, transparent 1px);
    }
    body.dark-theme .action-bar {
        background: linear-gradient(180deg, #2a2a2a 0%, #1e1e1e 100%);
        border-top-color: #404040;
    }
    body.dark-theme .modal-content {
        background-color: #242424;
        color: #e9ecef;
        border-color: #404040;
        border-radius: 12px;
    }
    body.dark-theme .modal-header {
        border-bottom-color: #404040;
        border-radius: 12px 12px 0 0;
    }
    body.dark-theme .modal-footer {
        border-top-color: #404040;
    }
    body.dark-theme .form-control, 
    body.dark-theme .form-select {
        background-color: #2a2a2a;
        border-color: #404040;
        color: #e9ecef;
    }
    body.dark-theme .form-control:focus, 
    body.dark-theme .form-select:focus {
        border-color: #9F2241;
        box-shadow: 0 0 0 0.2rem rgba(159, 34, 65, 0.25);
    }
    body.dark-theme .form-check-input:checked {
        background-color: #9F2241;
        border-color: #9F2241;
    }
    body.dark-theme .cycles-table tbody tr.selected td {
        color: #ffffff;
    }
    body.dark-theme #ciclo-stats {
        color: #DDC9A3 !important;
    }
    body.dark-theme #total-horas {
        color: #DDC9A3 !important;
    }
    .modal-header {
        border-radius: 12px 12px 0 0;
    }
    .modal-body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .modal-body .form-label {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .modal-footer {
        background-color: #f8f7f5;
        border-top: 1px solid #d0d0d2;
    }
    body.dark-theme .modal-footer {
        background-color: #2a2a2a;
        border-top-color: #404040;
    }
</style>

<div class="container-fluid">
    <div class="admin-window">
        <div class="main-content-ciclos">
            <!-- PANEL IZQUIERDO -->
            <div class="panel-left">
                <div class="panel-left-header">
                    <h5><i class="fas fa-sync-alt"></i> Ciclos Laborales</h5>
                </div>
                <div class="cycles-table-container">
                    <table class="cycles-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Inicio</th>
                                <th>#</th>
                                <th>Unidad</th>
                            </tr>
                        </thead>
                        <tbody id="cycles-tbody">
                            <!-- Cargado vía JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PANEL DERECHO -->
            <div class="panel-right">
                <div class="panel-left-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); border-radius: 0;">
                    <h5><i class="fas fa-calendar-week"></i> Configuración de Horarios</h5>
                </div>
                <div class="schedule-editor">
                    <div class="time-grid-header">
                        <?php for ($i = 0; $i < 24; $i++): ?>
                            <div class="time-grid-hour"><?= sprintf("%02d", $i) ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="time-grid-body" id="time-grid-body">
                        <?php 
                        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        foreach ($dias as $idx => $dia): 
                        ?>
                        <div class="day-row">
                            <div class="day-label"><?= $dia ?></div>
                            <div class="schedule-track" data-day="<?= $idx ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARRA DE ACCIONES -->
        <div class="action-bar">
            <div>
                <?php if (tienePermiso('ciclos_crear')): ?>
                <button class="btn btn-sm btn-pantone-verde" onclick="openModal('modal-ciclo')"><i class="fas fa-plus me-1"></i> Añadir Ciclo</button>
                <?php endif; ?>
                <?php if (tienePermiso('ciclos_eliminar')): ?>
                <button class="btn btn-sm btn-pantone-danger" id="btn-borrar-ciclo" disabled onclick="deleteCiclo()"><i class="fas fa-trash me-1"></i> Borrar Ciclo</button>
                <?php endif; ?>
            </div>
            <div id="ciclo-stats" style="display:none; font-weight: bold;">
                Total Semanal: <span id="total-horas" style="color: #235B4E;">0</span> hrs
            </div>
            <div>
                <button class="btn btn-sm btn-pantone-vino" id="btn-add-tiempo" disabled onclick="openModal('modal-bloque')"><i class="fas fa-clock me-1"></i> Añadir Horario</button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-borrar-todo" disabled onclick="deleteAllBloques()"><i class="fas fa-eraser me-1"></i> Limpiar Todo</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CREAR CICLO -->
<div class="modal fade" id="modal-ciclo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Nuevo Ciclo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="ciclo-nombre">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unidad</label>
                        <select class="form-select" id="ciclo-unidad">
                            <option value="Semana">Semana</option>
                            <option value="Mes">Mes</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="ciclo-fecha">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número Ciclo</label>
                        <input type="number" class="form-control" id="ciclo-num" value="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-pantone-verde" onclick="saveCiclo()"><i class="fas fa-save me-1"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AÑADIR BLOQUE -->
<div class="modal fade" id="modal-bloque" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Asignar Horario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Horario Base</label>
                        <select class="form-select" id="bloque-horario"></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Días</label>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="form-check">
                                <input class="form-check-input day-check" type="checkbox" value="0" checked id="dia-lun">
                                <label class="form-check-label" for="dia-lun">Lun</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-check" type="checkbox" value="1" checked id="dia-mar">
                                <label class="form-check-label" for="dia-mar">Mar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-check" type="checkbox" value="2" checked id="dia-mie">
                                <label class="form-check-label" for="dia-mie">Mié</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-check" type="checkbox" value="3" checked id="dia-jue">
                                <label class="form-check-label" for="dia-jue">Jue</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input day-check" type="checkbox" value="4" checked id="dia-vie">
                                <label class="form-check-label" for="dia-vie">Vie</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="limpiar-dia">
                            <label class="form-check-label" for="limpiar-dia"><i class="fas fa-eraser me-1"></i>Limpiar día antes de asignar</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-pantone-vino" onclick="addBloques()"><i class="fas fa-plus me-1"></i> Añadir</button>
            </div>
        </div>
    </div>
</div>

<script>
    const API_BASE = '<?= BASE_URL ?>/ciclos';
    let selectedCicloId = null;
    let modalCiclo, modalBloque;
    let horariosCatalogo = [];
    let currentBloques = [];
    let initialTotalText = '0';
    let guardadoExitoso = false;

    document.addEventListener('DOMContentLoaded', () => {
        modalCiclo = new bootstrap.Modal(document.getElementById('modal-ciclo'));
        modalBloque = new bootstrap.Modal(document.getElementById('modal-bloque'));
        const modalBloqueEl = document.getElementById('modal-bloque');
        
        loadCiclos();
        loadHorariosCatalogo();

        // Listeners para el preview en tiempo real
        document.getElementById('bloque-horario').addEventListener('change', actualizarTotalPreview);
        document.querySelectorAll('.day-check').forEach(check => {
            check.addEventListener('change', actualizarTotalPreview);
        });

        // Resetear el contador y la cuadrícula cuando el modal se cierra sin guardar
        modalBloqueEl.addEventListener('hidden.bs.modal', () => {
            if (!guardadoExitoso) {
                // Restore original view from local data only if not saved
                clearGrid();
                currentBloques.forEach(renderBloque);
                const statsDiv = document.getElementById('ciclo-stats');
                const totalSpan = document.getElementById('total-horas');
                if (statsDiv && totalSpan) {
                    totalSpan.textContent = initialTotalText;
                    statsDiv.style.display = currentBloques.length > 0 ? 'block' : 'none';
                }
            }
        });
    });

    // --- API CALLS ---
    async function api(endpoint, method = 'GET', body = null) {
        const headers = { 'Accept': 'application/json' };
        const opts = { method, headers };

        if (method === 'POST') {
            body = body || {};
            // Obtener e inyectar el token CSRF desde el meta tag del layout
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                body.csrf_token = csrfToken;
            }
            opts.body = JSON.stringify(body);
            opts.headers['Content-Type'] = 'application/json';
        }
        const res = await fetch(`${API_BASE}${endpoint}`, opts);
        // Manejar errores de red o servidor (como 403, 500) para evitar SyntaxError
        if (!res.ok) {
            const text = await res.text();
            return { success: false, message: `Error ${res.status}: ${text}` };
        }
        return await res.json();
    }

    // --- CICLOS ---
    async function loadCiclos() {
        const res = await api('/json');
        const tbody = document.getElementById('cycles-tbody');
        tbody.innerHTML = '';
        if(res.success) {
            res.data.forEach(c => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${c.nombre}</td><td>${c.fecha_inicio}</td><td>${c.num_ciclo}</td><td>${c.unidad_ciclo}</td>`;
                tr.onclick = () => selectCiclo(tr, c.id);
                tbody.appendChild(tr);
            });
        }
    }

    function selectCiclo(tr, id) {
        document.querySelectorAll('.cycles-table tr').forEach(r => r.classList.remove('selected'));
        tr.classList.add('selected');
        selectedCicloId = id;
        
        document.getElementById('btn-borrar-ciclo').disabled = false;
        document.getElementById('btn-add-tiempo').disabled = false;
        document.getElementById('btn-borrar-todo').disabled = false;
        
        loadBloques(id);
    }

    async function saveCiclo() {
        const data = {
            nombre: document.getElementById('ciclo-nombre').value,
            fecha_inicio: document.getElementById('ciclo-fecha').value,
            num_ciclo: document.getElementById('ciclo-num').value,
            unidad_ciclo: document.getElementById('ciclo-unidad').value
        };
        const res = await api('/create', 'POST', data);
        if(res.success) {
            modalCiclo.hide();
            loadCiclos();
        } else {
            alert('Error: ' + res.message);
        }
    }

    async function deleteCiclo() {
        if(!selectedCicloId || !confirm('¿Eliminar ciclo seleccionado?')) return;
        const res = await api('/delete', 'POST', { id: selectedCicloId });
        if(res.success) {
            selectedCicloId = null;
            loadCiclos();
            clearGrid();
        }
    }

    // --- BLOQUES / HORARIOS ---
    async function loadHorariosCatalogo() {
        const res = await api('/horarios-catalogo');
        const sel = document.getElementById('bloque-horario');
        sel.innerHTML = ''; // Limpiar opciones previas
        if(res.success) {
            horariosCatalogo = res.data; // Guardar datos completos
            res.data.forEach(h => {
                const opt = document.createElement('option');
                opt.value = h.id;
                opt.textContent = `${h.nombre} (${h.hora_entrada || h.hora_ent} - ${h.hora_salida || h.hora_sal})`;
                sel.appendChild(opt);
            });
        }
    }

    async function loadBloques(cicloId) {
        clearGrid();
        // Agregar timestamp para evitar caché del navegador y forzar la recarga visual
        const res = await api(`/bloques/json/${cicloId}?_=${new Date().getTime()}`);
        currentBloques = []; // Limpiar bloques actuales
        let totalMinutos = 0;

        if(res.success) {
            currentBloques = res.data; // Guardar bloques para el preview
            res.data.forEach(b => {
                renderBloque(b);
                
                // Calcular duración para el total de manera segura
                const partsInicio = b.hora_inicio.split(':');
                const partsFin = b.hora_fin.split(':');
                
                const hInicio = parseInt(partsInicio[0], 10);
                const mInicio = parseInt(partsInicio[1], 10);
                const hFin = parseInt(partsFin[0], 10);
                const mFin = parseInt(partsFin[1], 10);
                
                let inicio = hInicio * 60 + mInicio;
                let fin = hFin * 60 + mFin;
                
                if (fin < inicio) fin += 24 * 60; // Cruce de medianoche
                
                if (!isNaN(inicio) && !isNaN(fin)) {
                    totalMinutos += (fin - inicio);
                }
            });
        }
        
        // Actualizar UI del total
        const horas = Math.floor(totalMinutos / 60);
        const minutos = totalMinutos % 60;
        const textoTotal = minutos > 0 ? `${horas}h ${minutos}m` : `${horas}`;
        
        const statsDiv = document.getElementById('ciclo-stats');
        const totalSpan = document.getElementById('total-horas');
        
        if (statsDiv && totalSpan) {
            totalSpan.textContent = textoTotal;
            statsDiv.style.display = 'block';
            initialTotalText = textoTotal; // Guardar el total inicial
        }
    }

    function renderBloque(b) {
        const track = document.querySelector(`.schedule-track[data-day="${b.dia_semana}"]`);
        if(!track) return;

        const startParts = b.hora_inicio.split(':');
        const endParts = b.hora_fin.split(':');
        const startH = parseInt(startParts[0], 10) + parseInt(startParts[1], 10)/60;
        const endH = parseInt(endParts[0], 10) + parseInt(endParts[1], 10)/60;
        
        // Ajuste visual: extender 1 hora para cubrir el cuadro de la hora de salida
        const visualEndH = endH + 1;

        if (endH < startH) {
            // Turno nocturno (cruza medianoche): Renderizar dos bloques visuales
            createBlock(track, b, startH, 24);
            createBlock(track, b, 0, visualEndH);
        } else {
            createBlock(track, b, startH, visualEndH);
        }
    }

    function createBlock(track, b, start, end) {
        const div = document.createElement('div');
        div.className = 'schedule-block';
        div.textContent = `${b.nombre_horario} (${b.hora_inicio.substr(0,5)} - ${b.hora_fin.substr(0,5)})`;
        div.title = `${b.nombre_horario}: ${b.hora_inicio} - ${b.hora_fin}`;
        
        if (b.color) {
            div.setAttribute('data-color', b.color);
            div.style.setProperty('--block-color', b.color);
            div.style.setProperty('--block-color-dark', adjustColor(b.color, -30));
            div.style.backgroundColor = b.color;
            div.style.borderColor = 'rgba(0,0,0,0.2)';
            div.style.color = '#fff';
        }
        
        div.style.left = (start / 24 * 100) + '%';
        div.style.width = ((end - start) / 24 * 100) + '%';
        
        track.appendChild(div);
    }
    
    function adjustColor(hex, amount) {
        let usePound = false;
        if (hex[0] === "#") {
            hex = hex.slice(1);
            usePound = true;
        }
        let num = parseInt(hex, 16);
        let r = (num >> 16) + amount;
        if (r > 255) r = 255;
        else if (r < 0) r = 0;
        let b = ((num >> 8) & 0x00FF) + amount;
        if (b > 255) b = 255;
        else if (b < 0) b = 0;
        let g = (num & 0x0000FF) + amount;
        if (g > 255) g = 255;
        else if (g < 0) g = 0;
        return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16).padStart(6, '0');
    }

    async function addBloques() {
        if(!selectedCicloId) return;
        const dias = Array.from(document.querySelectorAll('.day-check:checked')).map(c => parseInt(c.value));
        const horarioId = document.getElementById('bloque-horario').value;
        const limpiarDia = document.getElementById('limpiar-dia').checked;

        const res = await api('/bloques/add', 'POST', { ciclo_id: selectedCicloId, horario_id: horarioId, dias, limpiar_dia: limpiarDia });
        if(res.success) {
            guardadoExitoso = true;

            // La vista previa ya es correcta. En lugar de recargar desde el servidor (lo que causa un parpadeo),
            // simplemente actualizamos nuestro estado local ('currentBloques') para que coincida con la vista previa.
            const horarioSeleccionado = horariosCatalogo.find(h => h.id == horarioId);
            if (horarioSeleccionado) {
                const nuevosBloques = currentBloques.filter(b => !dias.includes(parseInt(b.dia_semana)));
                dias.forEach(dia => {
                    nuevosBloques.push({
                        dia_semana: dia,
                        hora_inicio: horarioSeleccionado.hora_entrada,
                        hora_fin: horarioSeleccionado.hora_salida,
                        nombre_horario: horarioSeleccionado.nombre,
                        color: horarioSeleccionado.color
                    });
                });
                currentBloques = nuevosBloques; // Actualizar la "fuente de verdad" del estado.
            }
            initialTotalText = document.getElementById('total-horas').textContent; // Guardar el nuevo total como base.

            modalBloque.hide();
            showNotification('Horario asignado correctamente.', 'success');
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo asignar el horario'), 'error');
        }
    }

    async function deleteAllBloques() {
        if(!selectedCicloId || !confirm('¿Borrar todos los horarios de este ciclo?')) return;
        await api('/bloques/delete-all', 'POST', { ciclo_id: selectedCicloId });
        loadBloques(selectedCicloId);
    }

    function clearGrid() {
        document.querySelectorAll('.schedule-track').forEach(t => t.innerHTML = '');
        // Ocultar stats al limpiar
        const statsDiv = document.getElementById('ciclo-stats');
        if(statsDiv) statsDiv.style.display = 'none';
    }

    function actualizarTotalPreview() {
        const selectedHorarioId = document.getElementById('bloque-horario').value;
        const diasSeleccionados = Array.from(document.querySelectorAll('.day-check:checked')).map(c => parseInt(c.value));

        // If no schedule is selected or no days are checked, restore the original state from memory
        if (!selectedHorarioId || diasSeleccionados.length === 0) {
            clearGrid();
            currentBloques.forEach(renderBloque);
            const statsDiv = document.getElementById('ciclo-stats');
            const totalSpan = document.getElementById('total-horas');
            if (statsDiv && totalSpan) {
                totalSpan.textContent = initialTotalText;
                statsDiv.style.display = currentBloques.length > 0 ? 'block' : 'none';
            }
            return;
        }

        const horarioSeleccionado = horariosCatalogo.find(h => h.id == selectedHorarioId);
        if (!horarioSeleccionado) return;

        // Create a preview list of blocks
        const previewBloques = currentBloques.filter(b => !diasSeleccionados.includes(parseInt(b.dia_semana)));

        diasSeleccionados.forEach(dia => {
            previewBloques.push({
                dia_semana: dia,
                hora_inicio: horarioSeleccionado.hora_entrada,
                hora_fin: horarioSeleccionado.hora_salida,
                nombre_horario: horarioSeleccionado.nombre,
                color: horarioSeleccionado.color
            });
        });

        // --- RENDER PREVIEW AND RECALCULATE TOTAL ---
        clearGrid();
        let totalMinutosPreview = 0;

        previewBloques.forEach(b => {
            renderBloque(b); // Render the block

            // Calculate its duration for the total
            const partsInicio = b.hora_inicio.split(':');
            const partsFin = b.hora_fin.split(':');
            
            const hInicio = parseInt(partsInicio[0], 10);
            const mInicio = parseInt(partsInicio[1], 10);
            const hFin = parseInt(partsFin[0], 10);
            const mFin = parseInt(partsFin[1], 10);
            
            let inicio = hInicio * 60 + mInicio;
            let fin = hFin * 60 + mFin;
            
            if (fin < inicio) fin += 24 * 60; // Cruce de medianoche
            
            if (!isNaN(inicio) && !isNaN(fin)) {
                totalMinutosPreview += (fin - inicio);
            }
        });

        // Update the total hours UI
        const horas = Math.floor(totalMinutosPreview / 60);
        const minutos = totalMinutosPreview % 60;
        const textoTotal = minutos > 0 ? `${horas}h ${minutos}m` : `${horas}`;
        
        const statsDiv = document.getElementById('ciclo-stats');
        const totalSpan = document.getElementById('total-horas');
        
        if (statsDiv && totalSpan) {
            totalSpan.textContent = textoTotal;
            statsDiv.style.display = 'block';
        }
    }

    // --- NOTIFICACIONES (TOAST) ---
    function showNotification(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1060;';
            document.body.appendChild(container);
        }

        const toastEl = document.createElement('div');
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        
        toastEl.className = `toast align-items-center text-white ${bgClass} border-0 mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${icon} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    // --- UI HELPERS ---
    window.openModal = (id) => {
        if (id === 'modal-ciclo') {
            modalCiclo.show();
        }
        if (id === 'modal-bloque') {
            // Resetear el estado de guardado al abrir el modal
            guardadoExitoso = false;
            // Guardar el total actual antes de mostrar el modal y empezar a previsualizar
            initialTotalText = document.getElementById('total-horas').textContent;
            modalBloque.show();
        }
    };
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>