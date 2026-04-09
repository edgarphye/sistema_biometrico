<?php include 'views/layout.php'; ?>
<?php require_once 'helpers/Csrf.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card" style="border-color: #d0d0d2;">
                <div class="card-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                    <h4 class="mb-0"><i class="fas fa-calendar-week me-2"></i> Gestión de Ciclos Semanales</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="background-color: #e6ecea; border-color: #235B4E; color: #10312B;">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background-color: #f5e6e9; border-color: #691C32; color: #4a1424;">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Lista de ciclos (izquierda) -->
                        <div class="col-md-4">
                            <div class="card" style="border-color: #d0d0d2;">
                                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f7f5; border-bottom: 2px solid #DDC9A3;">
                                    <h5 class="mb-0" style="color: #2d2d2d;"><i class="fas fa-list me-2" style="color: #9F2241;"></i>Ciclos Disponibles</h5>
                                    <button type="button" class="btn btn-primary" onclick="nuevoCiclo()" style="background-color: #235B4E; border-color: #235B4E;">
                                        <i class="fas fa-plus"></i> Nuevo Ciclo
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="list-group" id="ciclosList">
                                        <?php foreach ($ciclos as $ciclo): ?>
                                        <div class="list-group-item list-group-item-action <?php echo ($cicloSeleccionado && $cicloSeleccionado['id'] == $ciclo['id']) ? 'active' : ''; ?>" style="cursor: pointer;" onclick="seleccionarCiclo(<?php echo $ciclo['id']; ?>)">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($ciclo['nombre']); ?></h6>
                                                <small><?php echo htmlspecialchars($ciclo['num_ciclo'] ?? 1); ?> ciclo(s)</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-end">
                                                <p class="mb-1">
                                                    Inicio: <?php echo htmlspecialchars($ciclo['fecha_inicio'] ?? date('d/m/Y')); ?><br>
                                                    Unidad: <?php echo htmlspecialchars($ciclo['unidad_ciclo'] ?? $ciclo['unid_ciclo'] ?? 'Semana'); ?>
                                                </p>
                                                <button type="button" class="btn btn-sm btn-light border" onclick='event.stopPropagation(); editarCiclo(<?php echo htmlspecialchars(json_encode($ciclo), ENT_QUOTES, 'UTF-8'); ?>)' title="Modificar Ciclo">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline semanal (derecha) -->
                        <div class="col-md-8">
                            <div class="card" style="border-color: #d0d0d2;">
                                <div class="card-header" style="background-color: #f8f7f5; border-bottom: 2px solid #DDC9A3;">
                                    <h5 class="mb-0" style="color: #2d2d2d;"><i class="fas fa-clock me-2" style="color: #9F2241;"></i>Timeline Semanal
                                        <?php if ($cicloSeleccionado): ?>
                                            - <span style="color: #9F2241;"><?php echo htmlspecialchars($cicloSeleccionado['nombre']); ?></span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($cicloSeleccionado): ?>
                                        <div class="d-flex justify-content-end mb-3">
                                            <button type="button" class="btn btn-info btn-sm mr-2" onclick='editarCiclo(<?php echo htmlspecialchars(json_encode($cicloSeleccionado), ENT_QUOTES, 'UTF-8'); ?>)' style="background-color: #BC955C; border-color: #BC955C; color: #2d2d2d;">
                                                <i class="fas fa-edit"></i> Editar Ciclo
                                            </button>
                                            &nbsp;
                                            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarCiclo(<?php echo $cicloSeleccionado['id']; ?>)" style="background-color: #691C32; border-color: #691C32;">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>

                                        <div class="timeline-container">
                                            <!-- Encabezado de horas -->
                                            <div class="timeline-header">
                                                <div class="timeline-day-label"></div>
                                                <?php for ($hora = 0; $hora <= 23; $hora++): ?>
                                                    <div class="timeline-hour"><?php echo str_pad($hora, 2, '0', STR_PAD_LEFT); ?>:00</div>
                                                <?php endfor; ?>
                                            </div>

                                            <!-- Filas de días -->
                                            <?php
                                            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                                            $dias_map_indices = array_flip($dias); // ['lunes' => 0, ...]

                                            // Pre-procesar los bloques para agruparlos por día
                                            $bloquesPorDia = [];
                                            if (isset($bloques) && is_array($bloques)) {
                                                foreach ($bloques as $bloque) {
                                                    // dia_semana: 0=Lunes, 1=Martes, etc.
                                                    $diaIndex = $bloque['dia_semana'];
                                                    if ($diaIndex >= 0 && $diaIndex < count($dias)) {
                                                        $nombreDia = $dias[$diaIndex];
                                                        $bloquesPorDia[$nombreDia][] = $bloque;
                                                    }
                                                }
                                            }

                                            foreach ($dias as $dia):
                                            ?>
                                            <div class="timeline-row">
                                                <div class="timeline-day-label"><?php echo ucfirst($dia); ?></div>
                                                <div class="timeline-hours" id="timeline-<?php echo $dia; ?>">
                                                    <?php for ($hora = 0; $hora <= 23; $hora++): ?>
                                                        <div class="timeline-cell" data-hora="<?php echo $hora; ?>" data-dia-index="<?php echo $dias_map_indices[$dia]; ?>"></div>
                                                    <?php endfor; ?>

                                                    <?php // Renderizar los bloques de horario superpuestos ?>
                                                    <?php if (isset($bloquesPorDia[$dia])): ?>
                                                        <?php foreach ($bloquesPorDia[$dia] as $bloque): ?>
                                                            <?php
                                                            // Calcular posición y ancho en porcentaje
                                                            [$h_inicio, $m_inicio] = explode(':', $bloque['hora_inicio']);
                                                            [$h_fin, $m_fin] = explode(':', $bloque['hora_fin']);
                                                            $inicio_mins = ((int)$h_inicio * 60) + (int)$m_inicio;
                                                            $fin_mins = ((int)$h_fin * 60) + (int)$m_fin;
                                                            $duracion_mins = $fin_mins - $inicio_mins;

                                                            $left_perc = ($inicio_mins / 1440) * 100; // 1440 = 24 * 60
                                                            $width_perc = ($duracion_mins / 1440) * 100;
                                                            $color = $bloque['color'] ?? '#0d6efd';
                                                            $titulo = htmlspecialchars($bloque['nombre_horario']) . ' (' . substr($bloque['hora_inicio'], 0, 5) . ' - ' . substr($bloque['hora_fin'], 0, 5) . ')';
                                                            ?>
                                                            <div class="timeline-block" style="left: <?php echo $left_perc; ?>%; width: <?php echo $width_perc; ?>%; background-color: <?php echo $color; ?>;" title="<?php echo $titulo; ?>">
                                                                <span class="timeline-block-text"><?php echo htmlspecialchars($bloque['nombre_horario']); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="mt-3">
                                            <button type="button" class="btn btn-success" onclick="guardarHorarioSemanal()" style="background-color: #235B4E; border-color: #235B4E;">
                                                <i class="fas fa-save"></i> Guardar Horario Semanal
                                            </button>
                                            <button type="button" class="btn btn-warning" onclick="añadirBloqueTiempo()" style="background-color: #BC955C; border-color: #BC955C; color: #2d2d2d;">
                                                <i class="fas fa-plus"></i> Añadir Bloque
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="borrarBloqueTiempo()" style="background-color: #691C32; border-color: #691C32;">
                                                <i class="fas fa-minus"></i> Borrar Bloque
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="borrarTodo()" style="border-color: #6F7271; color: #6F7271;">
                                                <i class="fas fa-trash"></i> Borrar Todo
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-calendar-week fa-3x mb-3"></i>
                                            <p>Selecciona un ciclo para ver y editar su horario semanal</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para nuevo ciclo -->
                    <div class="modal fade" id="nuevoCicloModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: #ffffff;">
                                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nuevo Ciclo</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <form id="nuevoCicloForm" method="POST" action="/sistema_biometrico/horarios/procesar-ciclos">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                                    <input type="hidden" name="action" value="create_ciclo">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="nombre_ciclo">Nombre del Ciclo *</label>
                                            <input type="text" class="form-control" id="nombre_ciclo" name="nombre_ciclo" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="fecha_inicio">Fecha de Inicio *</label>
                                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="num_ciclo">Número de Ciclo</label>
                                            <input type="number" class="form-control" id="num_ciclo" name="num_ciclo" value="1" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label for="unid_ciclo">Unidad de Ciclo</label>
                                            <select class="form-control" id="unid_ciclo" name="unid_ciclo">
                                                <option value="Semana">Semana</option>
                                                <option value="Mes">Mes</option>
                                                <option value="Quincena">Quincena</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                                        <button type="submit" class="btn btn-primary" style="background-color: #235B4E; border-color: #235B4E;"><i class="fas fa-save me-1"></i> Crear Ciclo</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para editar ciclo -->
                    <div class="modal fade" id="editarCicloModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: #ffffff;">
                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Ciclo</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <form id="editarCicloForm" method="POST" action="/sistema_biometrico/horarios/procesar-ciclos">
                                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                                    <input type="hidden" name="action" value="update_ciclo">
                                    <input type="hidden" name="ciclo_id" id="edit_ciclo_id">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="edit_nombre_ciclo">Nombre del Ciclo *</label>
                                            <input type="text" class="form-control" id="edit_nombre_ciclo" name="nombre_ciclo" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_fecha_inicio">Fecha de Inicio *</label>
                                            <input type="date" class="form-control" id="edit_fecha_inicio" name="fecha_inicio" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_num_ciclo">Número de Ciclo</label>
                                            <input type="number" class="form-control" id="edit_num_ciclo" name="num_ciclo" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label for="edit_unid_ciclo">Unidad de Ciclo</label>
                                            <select class="form-control" id="edit_unid_ciclo" name="unid_ciclo">
                                                <option value="Semana">Semana</option>
                                                <option value="Mes">Mes</option>
                                                <option value="Quincena">Quincena</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="background-color: #f8f7f5; border-top: 1px solid #d0d0d2;">
                                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" style="border-color: #6F7271; color: #6F7271;">Cancelar</button>
                                        <button type="submit" class="btn btn-primary" style="background-color: #9F2241; border-color: #9F2241;"><i class="fas fa-save me-1"></i> Actualizar Ciclo</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-container {
    font-size: 12px;
}

.timeline-header {
    display: flex;
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 10px;
}

.timeline-day-label {
    width: 80px;
    padding: 8px;
    font-weight: bold;
    background-color: #f8f9fa;
    border-right: 1px solid #dee2e6;
}

.timeline-hour {
    flex: 1;
    text-align: center;
    padding: 4px;
    border-right: 1px solid #dee2e6;
    background-color: #f8f9fa;
    font-weight: bold;
}

.timeline-row {
    display: flex;
    margin-bottom: 5px;
    align-items: center;
}

.timeline-hours {
    flex: 1;
    display: flex;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    position: relative; /* Clave para posicionar los bloques */
    overflow: hidden;
}

.timeline-cell {
    flex: 1;
    height: 30px;
    border-right: 1px solid #e9ecef;
    cursor: pointer;
    transition: background-color 0.2s;
}

.timeline-cell:hover {
    background-color: #e3f2fd;
}

.timeline-cell.active {
    background-color: #007bff;
}

.timeline-cell.selected {
    background-color: #ffc107;
    border: 2px solid #ff6b35;
}

.timeline-block {
    position: absolute;
    top: 0;
    height: 100%;
    border-radius: 3px;
    color: white;
    padding: 4px 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    box-sizing: border-box;
    border: 1px solid rgba(0,0,0,0.2);
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    opacity: 0.9;
    z-index: 10;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
}

.timeline-block:hover {
    opacity: 1;
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
    z-index: 11;
}

/* ===== TEMA OSCURO - CICLOS ===== */
body.dark-theme .card {
    background-color: #1a3d35;
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .card-header {
    background-color: #235B4E;
    border-color: #10312B;
    color: #DDC9A3;
}

body.dark-theme .list-group-item {
    background-color: #1a3d35;
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .list-group-item:hover {
    background-color: #235B4E;
}

body.dark-theme .list-group-item.active {
    background-color: #9F2241;
    border-color: #691C32;
    color: #ffffff;
}

body.dark-theme .timeline-day-label,
body.dark-theme .timeline-hour {
    background-color: #10312B;
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .timeline-hours {
    border-color: #235B4E;
    background-color: #1a3d35;
}

body.dark-theme .timeline-cell {
    border-color: #235B4E;
}

body.dark-theme .timeline-cell:hover {
    background-color: rgba(151, 34, 65, 0.3);
}

body.dark-theme .modal-content {
    background-color: #1a3d35;
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .modal-header {
    background-color: #235B4E;
    border-color: #10312B;
    color: #ffffff;
}

body.dark-theme .modal-body {
    background-color: #1a3d35;
    color: #DDC9A3;
}

body.dark-theme .modal-footer {
    background-color: #10312B;
    border-color: #235B4E;
}

body.dark-theme .form-control,
body.dark-theme .form-select {
    background-color: #10312B;
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .form-control:focus,
body.dark-theme .form-select:focus {
    background-color: #10312B;
    border-color: #9F2241;
    color: #DDC9A3;
}

body.dark-theme .form-label {
    color: #DDC9A3;
}

body.dark-theme .text-muted {
    color: #98989A !important;
}

body.dark-theme .btn-light {
    background-color: #10312B;
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .btn-light:hover {
    background-color: #235B4E;
    color: #ffffff;
}

body.dark-theme .alert-success {
    background-color: rgba(35, 91, 78, 0.3);
    border-color: #235B4E;
    color: #DDC9A3;
}

body.dark-theme .alert-danger {
    background-color: rgba(105, 28, 50, 0.3);
    border-color: #691C32;
    color: #DDC9A3;
}
</style>

<script>
let horarioSemanal = {};
let cicloSeleccionado = <?php echo $cicloSeleccionado ? $cicloSeleccionado['id'] : 'null'; ?>;

function seleccionarCiclo(cicloId) {
    window.location.href = '/sistema_biometrico/horarios/ciclos?ciclo=' + cicloId;
}

function nuevoCiclo() {
    $('#nuevoCicloModal').modal('show');
}

function editarCiclo(ciclo) {
    $('#edit_ciclo_id').val(ciclo.id);
    $('#edit_nombre_ciclo').val(ciclo.nombre);
    $('#edit_fecha_inicio').val(ciclo.fecha_inicio);
    $('#edit_num_ciclo').val(ciclo.num_ciclo);
    $('#edit_unid_ciclo').val(ciclo.unidad_ciclo || ciclo.unid_ciclo || 'Semana');
    $('#editarCicloModal').modal('show');
}

function toggleHorario(dia, hora) {
    const cell = document.querySelector(`[data-dia="${dia}"][data-hora="${hora}"]`);

    if (!horarioSemanal[dia]) {
        horarioSemanal[dia] = [];
    }

    const index = horarioSemanal[dia].indexOf(hora);
    if (index > -1) {
        horarioSemanal[dia].splice(index, 1);
        cell.classList.remove('active');
    } else {
        horarioSemanal[dia].push(hora);
        horarioSemanal[dia].sort((a, b) => a - b);
        cell.classList.add('active');
    }
}

function guardarHorarioSemanal() {
    if (!cicloSeleccionado) {
        alert('Selecciona un ciclo primero');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/sistema_biometrico/horarios/procesar-ciclos';

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '_token';
    tokenInput.value = '<?php echo htmlspecialchars(Csrf::token()); ?>';
    form.appendChild(tokenInput);

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'update_horario';
    form.appendChild(actionInput);

    const cicloInput = document.createElement('input');
    cicloInput.type = 'hidden';
    cicloInput.name = 'ciclo_id';
    cicloInput.value = cicloSeleccionado;
    form.appendChild(cicloInput);

    // Convertir horario semanal a JSON
    const horariosInput = document.createElement('input');
    horariosInput.type = 'hidden';
    horariosInput.name = 'horarios';
    horariosInput.value = JSON.stringify(horarioSemanal);
    form.appendChild(horariosInput);

    document.body.appendChild(form);
    form.submit();
}

function añadirBloqueTiempo() {
    // Implementar lógica para añadir bloques de tiempo automáticamente
    alert('Funcionalidad para añadir bloques de tiempo - próximamente');
}

function borrarBloqueTiempo() {
    // Implementar lógica para borrar bloques de tiempo
    alert('Funcionalidad para borrar bloques de tiempo - próximamente');
}

function borrarTodo() {
    if (confirm('¿Estás seguro de borrar todo el horario semanal?')) {
        horarioSemanal = {};
        document.querySelectorAll('.timeline-cell.active').forEach(cell => {
            cell.classList.remove('active');
        });
    }
}

function eliminarCiclo(id) {
    if (confirm('¿Estás seguro de eliminar este ciclo?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/sistema_biometrico/horarios/procesar-ciclos';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = '<?php echo htmlspecialchars(Csrf::token()); ?>';
        form.appendChild(tokenInput);

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_ciclo';
        form.appendChild(actionInput);

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'ciclo_id';
        idInput.value = id;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    }
}

</script>

</body>
</html>
