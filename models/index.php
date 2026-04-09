<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definición de Ciclos y Horarios</title>
    <style>
        :root {
            --main-bg-color: #f0f0f0;
            --border-color: #cccccc;
            --header-bg-color: #e1e1e1;
            --text-color: #333333;
            --selected-bg-color: #0078d4;
            --selected-text-color: #ffffff;
            --schedule-bar-color: #2a9fd6;
            --button-bg-color: #f0f0f0;
            --button-border-color: #adadad;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #dcdcdc;
            margin: 0;
            padding: 10px;
            font-size: 13px;
            color: var(--text-color);
            height: 100vh;
            box-sizing: border-box;
            overflow: hidden;
        }
        .admin-window {
            display: flex;
            flex-direction: column;
            height: 100%;
            background-color: var(--main-bg-color);
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .main-content {
            display: flex;
            flex-grow: 1;
            overflow: hidden;
        }
        .panel-left {
            width: 350px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            background: white;
        }
        .cycles-table-container {
            flex-grow: 1;
            overflow-y: auto;
        }
        .cycles-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cycles-table th, .cycles-table td {
            border-bottom: 1px solid var(--border-color);
            padding: 6px 8px;
            text-align: left;
            white-space: nowrap;
        }
        .cycles-table th {
            background-color: var(--header-bg-color);
            position: sticky;
            top: 0;
            font-weight: 600;
            font-size: 12px;
        }
        .cycles-table tbody tr { cursor: pointer; }
        .cycles-table tbody tr:hover { background-color: #e9f5ff; }
        .cycles-table tbody tr.selected {
            background-color: var(--selected-bg-color);
            color: var(--selected-text-color);
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
            padding: 10px;
            position: relative;
        }
        .time-grid-header {
            display: flex;
            padding-left: 80px;
            background-color: var(--header-bg-color);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .time-grid-hour {
            flex: 1;
            text-align: center;
            font-size: 10px;
            padding: 4px 0;
            border-left: 1px solid var(--border-color);
            min-width: 30px;
        }
        .day-row {
            display: flex;
            align-items: center;
            height: 50px;
            border-bottom: 1px solid var(--border-color);
        }
        .day-label {
            width: 80px;
            flex-shrink: 0;
            text-align: right;
            padding-right: 10px;
            font-weight: 600;
            font-size: 12px;
            text-transform: capitalize;
            border-right: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background: #f9f9f9;
        }
        .schedule-track {
            flex-grow: 1;
            position: relative;
            background-image: linear-gradient(to right, #eee 1px, transparent 1px);
            background-size: calc(100% / 24) 100%;
            height: 100%;
        }
        .schedule-block {
            position: absolute;
            top: 10%;
            height: 80%;
            background-color: var(--schedule-bar-color);
            border: 1px solid #1c759c;
            border-radius: 2px;
            color: white;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            overflow: hidden;
            padding: 0 2px;
            box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        .action-bar {
            border-top: 1px solid var(--border-color);
            padding: 8px;
            background-color: var(--header-bg-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .action-bar button {
            font-size: 12px;
            padding: 5px 15px;
            border: 1px solid var(--button-border-color);
            background-color: var(--button-bg-color);
            border-radius: 2px;
            cursor: pointer;
            margin-right: 5px;
        }
        .action-bar button:hover { background-color: #e5e5e5; border-color: #0078d4; }
        .action-bar button:disabled { color: #999; cursor: not-allowed; }
        
        /* Modal simple */
        .modal {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 100;
            justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; padding: 20px; width: 300px;
            border: 1px solid #999; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 4px; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="admin-window">
    <div class="main-content">
        <!-- PANEL IZQUIERDO -->
        <div class="panel-left">
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
            <button onclick="openModal('modal-ciclo')">Añadir Ciclo</button>
            <button id="btn-borrar-ciclo" disabled onclick="deleteCiclo()">Borrar Ciclo</button>
        </div>
        <div>
            <button id="btn-add-tiempo" disabled onclick="openModal('modal-bloque')">Añadir Horario</button>
            <button id="btn-borrar-todo" disabled onclick="deleteAllBloques()">Limpiar Todo</button>
        </div>
    </div>
</div>

<!-- MODAL CREAR CICLO -->
<div id="modal-ciclo" class="modal">
    <div class="modal-content">
        <h3>Nuevo Ciclo</h3>
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" id="ciclo-nombre">
        </div>
        <div class="form-group">
            <label>Fecha Inicio</label>
            <input type="date" id="ciclo-fecha">
        </div>
        <div class="form-group">
            <label>Número Ciclo</label>
            <input type="number" id="ciclo-num" value="1">
        </div>
        <div class="form-group">
            <label>Unidad</label>
            <select id="ciclo-unidad">
                <option value="Semana">Semana</option>
                <option value="Mes">Mes</option>
            </select>
        </div>
        <div style="text-align: right; margin-top: 15px;">
            <button onclick="closeModal('modal-ciclo')">Cancelar</button>
            <button onclick="saveCiclo()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL AÑADIR BLOQUE -->
<div id="modal-bloque" class="modal">
    <div class="modal-content">
        <h3>Asignar Horario</h3>
        <div class="form-group">
            <label>Horario Base</label>
            <select id="bloque-horario"></select>
        </div>
        <div class="form-group">
            <label>Días</label>
            <div>
                <label><input type="checkbox" class="day-check" value="0" checked> Lun</label>
                <label><input type="checkbox" class="day-check" value="1" checked> Mar</label>
                <label><input type="checkbox" class="day-check" value="2" checked> Mié</label>
                <label><input type="checkbox" class="day-check" value="3" checked> Jue</label>
                <label><input type="checkbox" class="day-check" value="4" checked> Vie</label>
                <label><input type="checkbox" class="day-check" value="5"> Sáb</label>
                <label><input type="checkbox" class="day-check" value="6"> Dom</label>
            </div>
        </div>
        <div style="text-align: right; margin-top: 15px;">
            <button onclick="closeModal('modal-bloque')">Cancelar</button>
            <button onclick="addBloques()">Añadir</button>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '/sistema_biometrico/index.php?controller=ciclos';
    let selectedCicloId = null;

    // --- INICIALIZACIÓN ---
    document.addEventListener('DOMContentLoaded', () => {
        loadCiclos();
        loadHorariosCatalogo();
    });

    // --- API CALLS ---
    async function api(action, method = 'GET', body = null) {
        const opts = { method };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(`${BASE_URL}&action=${action}`, opts);
        return await res.json();
    }

    // --- CICLOS ---
    async function loadCiclos() {
        const res = await api('getCiclos');
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
        const res = await api('saveCiclo', 'POST', data);
        if(res.success) {
            closeModal('modal-ciclo');
            loadCiclos();
        } else {
            alert('Error: ' + res.message);
        }
    }

    async function deleteCiclo() {
        if(!selectedCicloId || !confirm('¿Eliminar ciclo seleccionado?')) return;
        const res = await api('deleteCiclo', 'POST', { id: selectedCicloId });
        if(res.success) {
            selectedCicloId = null;
            loadCiclos();
            clearGrid();
        }
    }

    // --- BLOQUES / HORARIOS ---
    async function loadHorariosCatalogo() {
        const res = await api('getHorariosCatalogo');
        const sel = document.getElementById('bloque-horario');
        if(res.success) {
            res.data.forEach(h => {
                const opt = document.createElement('option');
                opt.value = h.id;
                opt.textContent = `${h.nombre} (${h.hora_ent || h.hora_entrada} - ${h.hora_sal || h.hora_salida})`;
                sel.appendChild(opt);
            });
        }
    }

    async function loadBloques(cicloId) {
        clearGrid();
        const res = await api('getBloques&ciclo_id=' + cicloId);
        if(res.success) {
            res.data.forEach(renderBloque);
        }
    }

    function renderBloque(b) {
        const track = document.querySelector(`.schedule-track[data-day="${b.dia_semana}"]`);
        if(!track) return;

        const div = document.createElement('div');
        div.className = 'schedule-block';
        div.textContent = `${b.nombre_horario} (${b.hora_inicio.substr(0,5)} - ${b.hora_fin.substr(0,5)})`;
        
        // Calcular posición
        const startH = parseInt(b.hora_inicio.split(':')[0]) + parseInt(b.hora_inicio.split(':')[1])/60;
        const endH = parseInt(b.hora_fin.split(':')[0]) + parseInt(b.hora_fin.split(':')[1])/60;
        
        div.style.left = (startH / 24 * 100) + '%';
        div.style.width = ((endH - startH) / 24 * 100) + '%';
        
        track.appendChild(div);
    }

    async function addBloques() {
        if(!selectedCicloId) return;
        const dias = Array.from(document.querySelectorAll('.day-check:checked')).map(c => parseInt(c.value));
        const horarioId = document.getElementById('bloque-horario').value;

        const res = await api('addBloques', 'POST', { ciclo_id: selectedCicloId, horario_id: horarioId, dias });
        if(res.success) {
            closeModal('modal-bloque');
            loadBloques(selectedCicloId);
        } else {
            alert('Error: ' + (res.message || 'Solapamiento detectado'));
        }
    }

    async function deleteAllBloques() {
        if(!selectedCicloId || !confirm('¿Borrar todos los horarios de este ciclo?')) return;
        await api('deleteAllBloques', 'POST', { ciclo_id: selectedCicloId });
        loadBloques(selectedCicloId);
    }

    function clearGrid() {
        document.querySelectorAll('.schedule-track').forEach(t => t.innerHTML = '');
    }

    // --- UI HELPERS ---
    window.openModal = (id) => document.getElementById(id).style.display = 'flex';
    window.closeModal = (id) => document.getElementById(id).style.display = 'none';
</script>

</body>
</html>