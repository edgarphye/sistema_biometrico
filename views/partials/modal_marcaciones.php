<div class="modal fade" id="modalMarcaciones" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-clock me-2"></i>Marcaciones - Modificación de Justificaciones
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Selector de Empleado -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Buscar Empleado</label>
                        <select class="form-select" id="marc_empleado_id" onchange="cargarMarcacionesEmpleado()">
                            <option value="">Seleccione un empleado...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fecha</label>
                        <input type="date" class="form-control" id="marc_fecha" onchange="cargarMarcacionesEmpleado()" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <button type="button" class="btn btn-outline-primary w-100" onclick="cargarMarcacionesEmpleado()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
                
                <hr>
                
                <!-- Info del Empleado -->
                <div id="marc_info_empleado" class="d-none">
                    <div class="alert alert-info">
                        <strong>Empleado:</strong> <span id="marc_nombre_empleado"></span>
                        <span class="ms-3"><strong>No. Empleado:</strong> <span id="marc_num_empleado"></span></span>
                    </div>
                </div>
                
                <!-- Tabs para tipo de registro -->
                <ul class="nav nav-tabs mb-3" id="marcTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="tab-retardos-tab" data-bs-toggle="tab" data-bs-target="#tab-marc-retardos" type="button">
                            <i class="fas fa-clock me-1"></i> Retardos
                            <span class="badge bg-danger ms-1" id="marc_count_retardos">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-asistencia-tab" data-bs-toggle="tab" data-bs-target="#tab-marc-asistencia" type="button">
                            <i class="fas fa-calendar-check me-1"></i> Asistencia
                            <span class="badge bg-warning ms-1" id="marc_count_asistencia">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="tab-dias-eco-tab" data-bs-toggle="tab" data-bs-target="#tab-marc-dias-eco" type="button">
                            <i class="fas fa-coins me-1"></i> Días Económicos
                            <span class="badge bg-success ms-1" id="marc_count_dias_eco">0</span>
                        </button>
                    </li>
                </ul>
                
                <!-- Contenido de Tabs -->
                <div class="tab-content" id="marcTabsContent">
                    <!-- Retardos -->
                    <div class="tab-pane fade show active" id="tab-marc-retardos" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="marc_tabla_retardos">
                                <thead class="table-danger">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Minutos</th>
                                        <th>Tipo</th>
                                        <th>Justificación Actual</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="marc_body_retardos">
                                    <tr><td colspan="6" class="text-center text-muted py-3">Seleccione un empleado para ver retardos</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Asistencia -->
                    <div class="tab-pane fade" id="tab-marc-asistencia" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="marc_tabla_asistencia">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Tipo Asistencia</th>
                                        <th>Justificación Actual</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="marc_body_asistencia">
                                    <tr><td colspan="6" class="text-center text-muted py-3">Seleccione un empleado para ver asistencia</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Días Económicos -->
                    <div class="tab-pane fade" id="tab-marc-dias-eco" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="marc_tabla_dias_eco">
                                <thead class="table-success">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Motivo</th>
                                        <th>Modalidad</th>
                                        <th>Estatus</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="marc_body_dias_eco">
                                    <tr><td colspan="5" class="text-center text-muted py-3">Seleccione un empleado para ver días económicos</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Justificación -->
<div class="modal fade" id="modalEditarMarcacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Justificación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarMarcacion">
                    <input type="hidden" id="edit_tabla" name="tabla">
                    <input type="hidden" id="edit_registro_id" name="registro_id">
                    <input type="hidden" id="edit_empleado_id" name="empleado_id">
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo Csrf::token(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Justificación</label>
                        <select class="form-select" id="edit_tipo_justificacion" name="tipo_justificacion_id" required>
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Motivo / Observaciones</label>
                        <textarea class="form-control" id="edit_motivo" name="motivo" rows="3" placeholder="Describa el motivo de la justificación"></textarea>
                    </div>
                    
                    <div class="mb-3" id="wrap_justificado">
                        <label class="form-label fw-bold">Estado de Justificación</label>
                        <select class="form-select" id="edit_justificado" name="justificado">
                            <option value="0">No Justificado</option>
                            <option value="1">Justificado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="wrap_estatus_eco">
                        <label class="form-label fw-bold">Estatus</label>
                        <select class="form-select" id="edit_estatus" name="estatus">
                            <option value="pendiente">Pendiente</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="rechazado">Rechazado</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle me-1"></i> Este cambio será registrado y podrá ser consultado por Recursos Humanos.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarMarcacion()">
                    <i class="fas fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let marc_tipos_justificacion = [];
let marc_datos_actuales = { retardos: [], asistencia: [], dias_economicos: [] };

// Abrir modal de Marcaciones
function abrirModalMarcaciones() {
    const modal = new bootstrap.Modal(document.getElementById('modalMarcaciones'));
    modal.show();
    cargarListaEmpleadosMarcaciones();
}

// Cargar lista de empleados
async function cargarListaEmpleadosMarcaciones() {
    try {
        const response = await fetch(BASE_URL + '/api/lista-empleados');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('marc_empleado_id');
            select.innerHTML = '<option value="">Seleccione un empleado...</option>';
            
            data.empleados.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.id;
                option.textContent = `${emp.num_empleado || emp.id} - ${emp.nombre} ${emp.apellido_paterno || ''}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando empleados:', error);
    }
}

// Cargar marcaciones del empleado
async function cargarMarcacionesEmpleado() {
    const empleadoId = document.getElementById('marc_empleado_id').value;
    const fecha = document.getElementById('marc_fecha').value;
    
    if (!empleadoId) {
        document.getElementById('marc_info_empleado').classList.add('d-none');
        return;
    }
    
    try {
        const response = await fetch(`${BASE_URL}/api/obtener_registros_justificacion.php?empleado_id=${empleadoId}&fecha=${fecha}`);
        const data = await response.json();
        
        if (data.success) {
            // Mostrar info del empleado
            document.getElementById('marc_info_empleado').classList.remove('d-none');
            document.getElementById('marc_nombre_empleado').textContent = data.datos.empleado?.nombre + ' ' + data.datos.empleado?.apellido_paterno;
            document.getElementById('marc_num_empleado').textContent = data.datos.empleado?.num_empleado || data.datos.empleado?.id;
            
            // Guardar tipos de justificación
            marc_tipos_justificacion = data.datos.tipos_justificacion || [];
            
            // Guardar datos
            marc_datos_actuales = {
                retardos: data.datos.retardos || [],
                asistencia: data.datos.asistencia || [],
                dias_economicos: data.datos.dias_economicos || []
            };
            
            // Renderizar tablas
            renderTablaMarcaciones('retardos');
            renderTablaMarcaciones('asistencia');
            renderTablaMarcaciones('dias_economicos');
            
            // Actualizar contadores
            document.getElementById('marc_count_retardos').textContent = marc_datos_actuales.retardos.length;
            document.getElementById('marc_count_asistencia').textContent = marc_datos_actuales.asistencia.length;
            document.getElementById('marc_count_dias_eco').textContent = marc_datos_actuales.dias_economicos.length;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar datos');
    }
}

// Renderizar tabla de marcaciones
function renderTablaMarcaciones(tipo) {
    let html = '';
    const datos = marc_datos_actuales[tipo] || [];
    const tbody = document.getElementById(`marc_body_${tipo === 'dias_economicos' ? 'dias_eco' : tipo}`);
    
    if (datos.length === 0) {
        html = `<tr><td colspan="${tipo === 'dias_economicos' ? 5 : 6}" class="text-center text-muted py-3">No hay registros</td></tr>`;
    } else {
        datos.forEach(reg => {
            if (tipo === 'retardos') {
                const justificado = reg.justificado == 1;
                html += `
                    <tr>
                        <td>${reg.fecha || '-'}</td>
                        <td><span class="badge bg-danger">${reg.minutos || 0} min</span></td>
                        <td><span class="badge bg-secondary">${reg.tipo || '-'}</span></td>
                        <td>${reg.tipo_justificacion_nombre || '<span class="text-muted">Sin justificación</span>'}</td>
                        <td>${justificado ? '<span class="badge bg-success">Justificado</span>' : '<span class="badge bg-warning">Pendiente</span>'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="abrirEditarMarcacion('retardos', ${reg.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>`;
            } else if (tipo === 'asistencia') {
                html += `
                    <tr>
                        <td>${reg.fecha || '-'}</td>
                        <td>${reg.hora_entrada ? reg.hora_entrada.substring(0,5) : '-'}</td>
                        <td>${reg.hora_salida ? reg.hora_salida.substring(0,5) : '-'}</td>
                        <td><span class="badge bg-info">${reg.tipo_asistencia || 'normal'}</span></td>
                        <td>${reg.tipo_justificacion_nombre || '<span class="text-muted">Sin justificación</span>'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="abrirEditarMarcacion('asistencia', ${reg.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>`;
            } else if (tipo === 'dias_economicos') {
                const estatusBadge = {
                    'aprobado': '<span class="badge bg-success">Aprobado</span>',
                    'pendiente': '<span class="badge bg-warning">Pendiente</span>',
                    'rechazado': '<span class="badge bg-danger">Rechazado</span>'
                };
                html += `
                    <tr>
                        <td>${reg.fecha || '-'}</td>
                        <td>${reg.motivo || '-'}</td>
                        <td>${reg.modalidad || '-'}</td>
                        <td>${estatusBadge[reg.estatus] || '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="abrirEditarMarcacion('dias_economicos', ${reg.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>`;
            }
        });
    }
    
    tbody.innerHTML = html;
}

// Abrir modal de edición
function abrirEditarMarcacion(tabla, registroId) {
    let registro = null;
    
    if (tabla === 'retardos') {
        registro = marc_datos_actuales.retardos.find(r => r.id === registroId);
    } else if (tabla === 'asistencia') {
        registro = marc_datos_actuales.asistencia.find(r => r.id === registroId);
    } else if (tabla === 'dias_economicos') {
        registro = marc_datos_actuales.dias_economicos.find(r => r.id === registroId);
    }
    
    if (!registro) {
        alert('Registro no encontrado');
        return;
    }
    
    // Llenar formulario
    document.getElementById('edit_tabla').value = tabla;
    document.getElementById('edit_registro_id').value = registroId;
    document.getElementById('edit_empleado_id').value = registro.empleado_id;
    
    // Llenar tipos de justificación
    const selectTipo = document.getElementById('edit_tipo_justificacion');
    selectTipo.innerHTML = '<option value="">Seleccione...</option>';
    marc_tipos_justificacion.forEach(tj => {
        const selected = tj.id == registro.tipo_justificacion_id ? 'selected' : '';
        selectTipo.innerHTML += `<option value="${tj.id}" ${selected}>${tj.nombre}</option>`;
    });
    
    document.getElementById('edit_motivo').value = registro.motivo_justificacion || registro.motivo || '';
    
    // Mostrar campos según tabla
    if (tabla === 'dias_economicos') {
        document.getElementById('wrap_justificado').classList.add('d-none');
        document.getElementById('wrap_estatus_eco').classList.remove('d-none');
        document.getElementById('edit_estatus').value = registro.estatus || 'pendiente';
    } else {
        document.getElementById('wrap_justificado').classList.remove('d-none');
        document.getElementById('wrap_estatus_eco').classList.add('d-none');
        document.getElementById('edit_justificado').value = registro.justificado == 1 ? '1' : '0';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarMarcacion'));
    modal.show();
}

// Guardar marcación
async function guardarMarcacion() {
    const form = document.getElementById('formEditarMarcacion');
    const formData = new FormData(form);
    
    try {
        const response = await fetch(BASE_URL + '/api/actualizar_justificacion.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Cambios guardados correctamente');
            bootstrap.Modal.getInstance(document.getElementById('modalEditarMarcacion')).hide();
            cargarMarcacionesEmpleado();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar');
    }
}

// Exportar funciones
window.abrirModalMarcaciones = abrirModalMarcaciones;
window.cargarMarcacionesEmpleado = cargarMarcacionesEmpleado;
window.abrirEditarMarcacion = abrirEditarMarcacion;
window.guardarMarcacion = guardarMarcacion;
</script>
