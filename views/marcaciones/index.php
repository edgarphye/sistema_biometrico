<style>
    :root {
        --pantone-primary: #9F2241;
        --pantone-primary-dark: #691C32;
        --pantone-secondary: #235B4E;
    }

    .badge-justificacion {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .badge-justificacion:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .badge-justificacion.just-retardo_menor {
        background: linear-gradient(135deg, #FFF8E1, #FFECB3);
        color: var(--pantone-dorado-oscuro);
        border: 1px solid var(--pantone-dorado);
    }
    .badge-justificacion.just-retardo_mayor {
        background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
        color: var(--pantone-dorado-oscuro);
        border: 1px solid var(--pantone-dorado-oscuro);
    }
    .badge-justificacion.just-falta {
        background: linear-gradient(135deg, #FCE4EC, #F8BBD9);
        color: var(--pantone-vino-oscuro);
        border: 1px solid var(--pantone-vino);
    }
    .badge-justificacion.just-comision {
        background: linear-gradient(135deg, #F5F5F5, #E0E0E0);
        color: var(--pantone-gris-oscuro);
        border: 1px solid var(--pantone-gris);
    }
    .badge-justificacion.just-dia_economico {
        background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
        color: var(--pantone-verde-oscuro);
        border: 1px solid var(--pantone-verde);
    }
    .badge-justificacion.just-vacaciones {
        background: linear-gradient(135deg, #E0F2F1, #B2DFDB);
        color: var(--pantone-verde-oscuro);
        border: 1px solid var(--pantone-verde-oscuro);
    }
    .badge-justificacion.just-permiso {
        background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
        color: var(--pantone-verde-oscuro);
        border: 1px solid var(--pantone-verde);
    }
    .badge-justificacion.just-por_definir {
        background: linear-gradient(135deg, #FAFAFA, #F5F5F5);
        color: var(--pantone-gris-oscuro);
        border: 1px solid var(--pantone-gris);
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2"></i>Marcaciones</h2>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">ID Empleado</label>
                    <input type="number" id="filtroId" class="form-control" placeholder="ID">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nombre / RFC / CURP</label>
                    <input type="text" id="filtroNombre" class="form-control" placeholder="Buscar empleado...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" id="fechaInicio" class="form-control" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" id="fechaFin" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="cargarRegistros(1)">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover" id="tablaMarcaciones" style="width:100%;">
<thead class="table-dark">
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Hora Entrada</th>
                        <th>Hora Salida</th>
                        <th>Tipo Registro</th>
                        <th>Tipo Asistencia</th>
                        <th>Tipo Justificación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            <tbody id="tbodyMarcaciones"></tbody>
        </table>
    </div>
    
    <nav>
        <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Registro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditar">
                <div class="modal-body">
                    <input type="hidden" id="editTipo" name="tipo">
                    <input type="hidden" id="editId" name="id">
                    <input type="hidden" id="editEmpleadoId" name="empleado_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Empleado</label>
                            <input type="text" id="editEmpleado" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <input type="date" id="editFecha" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipo Asistencia</label>
                            <select id="editTipoAsistencia" name="tipo_asistencia" class="form-select">
                                <option value="por_definir">Por Definir</option>
                                <option value="retardo_menor">Retardo Menor (≤15 min)</option>
                                <option value="retardo_mayor">Retardo Mayor (>15 min)</option>
                                <option value="falta">Falta</option>
                                <option value="comision_entrada">Comisión Entrada</option>
                                <option value="comision_salida">Comisión Salida</option>
                                <option value="comision_todo_dia">Comisión Todo el Día</option>
                                <option value="dia_economico">Día Económico</option>
                                <option value="vacaciones">Vacaciones</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo Justificación</label>
                            <select id="editTipoJustificacion" name="tipo_justificacion_id" class="form-select">
                                <option value="">Ninguno</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Hora Entrada</label>
                            <input type="time" id="editHoraEntrada" name="hora_entrada" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hora Salida</label>
                            <input type="time" id="editHoraSalida" name="hora_salida" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Validado</label>
                            <select id="editValidado" name="validado" class="form-select">
                                <option value="0">No Validado</option>
                                <option value="1">Validado</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Motivo</label>
                            <textarea id="editMotivo" name="motivo" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo defined("BASE_URL") ? rtrim(BASE_URL, "/") : "/sistema_biometrico"; ?>';
let currentPage = 1;
let totalPages = 1;

$(document).ready(function() {
    cargarRegistros(1);
    $('#formEditar').on('submit', guardarCambios);
});

function cargarRegistros(page) {
    currentPage = page;
    const fechaInicio = $('#fechaInicio').val();
    const fechaFin = $('#fechaFin').val();
    const filtroId = $('#filtroId').val();
    const filtroNombre = $('#filtroNombre').val();
    
    $.get(BASE_URL + '/api/obtener_registros_marcaciones.php', {
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        filtro_id: filtroId,
        filtro_nombre: filtroNombre,
        page: page,
        per_page: 100
    }, function(resp) {
        if (resp.success) {
            mostrarRegistros(resp.datos);
        } else {
            alert('Error: ' + resp.error);
        }
    });
}

function mostrarRegistros(datos) {
    const tiposJust = datos.tipos_justificacion || [];
    $('#editTipoJustificacion').html('<option value="">Ninguno</option>');
    tiposJust.forEach(tj => {
        $('#editTipoJustificacion').append('<option value="' + tj.id + '">' + tj.nombre + '</option>');
    });
    
    $('#tbodyMarcaciones').empty();
    
    const registros = datos.registros || [];
    if (registros.length === 0) {
        $('#tbodyMarcaciones').html('<tr><td colspan="9" class="text-center">No se encontraron registros</td></tr>');
        return;
    }
    
    registros.forEach(reg => {
        const nombreCompleto = reg.nombre + ' ' + reg.apellido;
        
        let estadoHtml = '';
        if (reg.es_duplicado) {
            estadoHtml = '<span class="badge bg-danger">DUPLICADO</span>';
        } else if (reg.es_primera_entrada) {
            estadoHtml = '<span class="badge bg-success">PRIMERA</span>';
        } else {
            estadoHtml = '-';
        }
        
        const rowClass = (reg.tipo_registro === 'asistencia' && reg.es_duplicado) ? 'table-danger' : '';
        
        // Determinar nombre de justificación - primero el catálogo, luego inferido del tipo_asistencia
        let tipoJustNombre = reg.tipo_justificacion_nombre;
        if (!tipoJustNombre && reg.tipo_asistencia) {
            const mapaTipo = {
                'retardo_menor': 'Retardo menor',
                'retardo_mayor': 'Retardo mayor',
                'falta': 'Falta',
                'comision_entrada': 'Comisión de entrada',
                'comision_salida': 'Comisión de salida',
                'comision_todo_dia': 'Comisión todo el día',
                'dia_economico': 'Día económico',
                'vacaciones': 'Vacaciones'
            };
            tipoJustNombre = mapaTipo[reg.tipo_asistencia] || reg.tipo_asistencia;
        }
        const tipoJustHtml = tipoJustNombre || reg.motivo || '-';
        
        $('#tbodyMarcaciones').append(
            '<tr class="' + rowClass + '">' +
            '<td><strong>' + nombreCompleto + '</strong><br><small class="text-muted">' + (reg.num_empleado || '') + '</small></td>' +
            '<td>' + formatDate(reg.fecha) + '</td>' +
            '<td>' + (reg.hora_entrada ? reg.hora_entrada.substring(0,5) : '-') + '</td>' +
            '<td>' + (reg.hora_salida ? reg.hora_salida.substring(0,5) : '-') + '</td>' +
            '<td><span class="badge bg-' + getTipoBadge(reg.tipo_registro) + '">' + reg.tipo_registro + '</span></td>' +
            '<td><span class="badge bg-primary">' + (reg.tipo_asistencia || '-') + '</span></td>' +
            '<td><span class="badge-justificacion ' + getJustBadgeClass(reg.tipo_asistencia) + '">' + tipoJustHtml + '</span></td>' +
            '<td>' + estadoHtml + '</td>' +
            '<td><button class="btn btn-sm btn-primary" onclick="editarRegistro(\'' + reg.tipo_registro + '\', ' + reg.id + ', ' + reg.empleado_id + ')"><i class="fas fa-edit"></i></button></td>' +
            '</tr>'
        );
    });
    
    // Paginacion
    totalPages = datos.pagination?.total_pages || 1;
    currentPage = datos.pagination?.page || 1;
    renderPagination();
}

function renderPagination() {
    let html = '';
    html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
        '<a class="page-link" href="#" onclick="cargarRegistros(' + (currentPage - 1) + '); return false;">Anterior</a></li>';
    
    for (let i = 1; i <= totalPages; i++) {
        html += '<li class="page-item ' + (i === currentPage ? 'active' : '') + '">' +
            '<a class="page-link" href="#" onclick="cargarRegistros(' + i + '); return false;">' + i + '</a></li>';
    }
    
    html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">' +
        '<a class="page-link" href="#" onclick="cargarRegistros(' + (currentPage + 1) + '); return false;">Siguiente</a></li>';
    
    $('#pagination').html(html);
}

function getTipoBadge(tipo) {
    const badges = {'asistencia': 'primary', 'retardos': 'warning', 'dias_economicos': 'info', 'permisos': 'secondary', 'vacaciones': 'success'};
    return badges[tipo] || 'dark';
}

function getJustBadgeClass(tipoAsistencia) {
    const map = {
        'retardo_menor': 'just-retardo_menor',
        'retardo_mayor': 'just-retardo_mayor',
        'falta': 'just-falta',
        'comision_entrada': 'just-comision',
        'comision_salida': 'just-comision',
        'comision_todo_dia': 'just-comision',
        'dia_economico': 'just-dia_economico',
        'vacaciones': 'just-vacaciones',
        'permiso': 'just-permiso',
        'por_definir': 'just-por_definir',
    };
    return map[tipoAsistencia] || 'just-por_definir';
}

function editarRegistro(tipo, id, empleadoId) {
    $('#editTipo').val(tipo);
    $('#editId').val(id);
    $('#editEmpleadoId').val(empleadoId);
    
    $('#editHoraEntrada').val('');
    $('#editHoraSalida').val('');
    $('#editMotivo').val('');
    $('#editValidado').val('');
    $('#editTipoJustificacion').val('');
    
    $.get(BASE_URL + '/api/obtener_registros_marcaciones.php', {
        fecha_inicio: '2000-01-01',
        fecha_fin: '2030-12-31',
        filtro_id: empleadoId,
        page: 1,
        per_page: 300
    }, function(resp) {
        if (!resp.success) return;
        
        const registros = resp.datos.registros || [];
        const reg = registros.find(r => r.id == id && r.tipo_registro === tipo);
        
        if (reg) {
            $('#editEmpleado').val(reg.nombre + ' ' + reg.apellido);
            $('#editFecha').val(reg.fecha);
            $('#editHoraEntrada').val(reg.hora_entrada || '');
            $('#editHoraSalida').val(reg.hora_salida || '');
            $('#editValidado').val(reg.validado_por ? '1' : '0');
            $('#editTipoAsistencia').val(reg.tipo_asistencia || '');
            
            // Auto-seleccionar tipo justificación basado en tipo_asistencia
            const mapaTipoJustId = {
                'retardo_menor': '15',
                'retardo_mayor': '16',
                'falta': '24',
                'comision_entrada': '17',
                'comision_salida': '18',
                'comision_todo_dia': '19',
                'dia_economico': '20',
                'vacaciones': '22'
            };
            
            // Buscar motivo en cualquier registro del empleado en esa fecha
            const regFecha = reg.fecha;
            const regEmpleado = reg.empleado_id;
            const motivoRegistro = registros.find(r => r.empleado_id == regEmpleado && r.fecha === regFecha && r.motivo);
            
            const motivo = motivoRegistro ? motivoRegistro.motivo : '';
            const tipoJustId = reg.tipo_justificacion_id || mapaTipoJustId[reg.tipo_asistencia] || '';
            
            $('#editMotivo').val(motivo);
            $('#editTipoJustificacion').val(tipoJustId);
        }
        
        new bootstrap.Modal(document.getElementById('modalEditar')).show();
    });
}

function guardarCambios(e) {
    e.preventDefault();
    
    $.post(BASE_URL + '/api/actualizar_registro_marcaciones.php', $('#formEditar').serialize(), function(resp) {
        if (resp.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
            cargarRegistros(currentPage);
            alert('Registro actualizado correctamente');
        } else {
            alert('Error: ' + resp.error);
        }
    });
}

function formatDate(fecha) {
    if (!fecha) return '-';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-MX', {day: '2-digit', month: 'short', year: 'numeric'});
}
</script>