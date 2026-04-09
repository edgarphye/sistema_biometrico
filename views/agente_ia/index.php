<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-robot"></i> Agente IA de Gestión de Asistencia</h2>
            <p class="text-muted">Sistema inteligente de clasificación, análisis y generación de documentos</p>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Retardos</h5>
                    <h3 id="total-retardos">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Faltas</h5>
                    <h3 id="total-faltas">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>No Validadas</h5>
                    <h3 id="total-no-validadas">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Anomalías</h5>
                    <h3 id="total-anomalias">-</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Procesar Período</h5>
                </div>
                <div class="card-body">
                    <form id="procesar-form">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Fecha Inicio</label>
                                <input type="date" class="form-control" name="fecha_inicio" value="<?= date('Y-m-01') ?>">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Fin</label>
                                <input type="date" class="form-control" name="fecha_fin" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3">
                                <label>Área</label>
                                <select class="form-control" name="area">
                                    <option value="">Todas</option>
                                    <option value="DIRECCIÓN GENERAL">Dirección General</option>
                                    <option value="RECURSOS HUMANOS">Recursos Humanos</option>
                                    <option value="ADMINISTRACIÓN">Administración</option>
                                    <option value="VENTAS">Ventas</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-cogs"></i> Procesar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Retardos No Justificados</h5>
                </div>
                <div class="card-body" id="retardos-container">
                    <p class="text-muted">Cargando...</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Incidencias No Validadas</h5>
                </div>
                <div class="card-body" id="no-validadas-container">
                    <p class="text-muted">Cargando...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="fas fa-lightbulb"></i> Recomendaciones Inteligentes del Agente IA</h5>
                </div>
                <div class="card-body" id="recomendaciones-container">
                    <p class="text-muted">Cargando recomendaciones...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Configuración del Agente</h5>
                    <button class="btn btn-sm btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="config-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="reglas-tab" data-toggle="tab" href="#reglas">Reglas de Negocio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="parametros-tab" data-toggle="tab" href="#parametros">Parámetros</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="documentos-tab" data-toggle="tab" href="#documentos">Plantillas</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="config-tabs-content">
                        <div class="tab-pane fade show active" id="reglas">
                            <button class="btn btn-primary btn-sm mb-3" onclick="modalNuevaRegla()">
                                <i class="fas fa-plus"></i> Nueva Regla
                            </button>
                            <div id="reglas-container">
                                <p class="text-muted">Cargando reglas...</p>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="parametros">
                            <form id="config-form">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>Tolerancia (minutos)</label>
                                        <input type="number" class="form-control" name="tolerancia_minutos" value="10">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Retardo menor mínimo</label>
                                        <input type="number" class="form-control" name="retardo_menor_min" value="11">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Retardo mayor mínimo</label>
                                        <input type="number" class="form-control" name="retardo_mayor_min" value="21">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label>Máx. retardos quincena</label>
                                        <input type="number" class="form-control" name="max_retardos_quincena" value="2">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Notas para oficio</label>
                                        <input type="number" class="form-control" name="notas_malas_oficio" value="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Notas para suspensión</label>
                                        <input type="number" class="form-control" name="notas_malas_suspension" value="5">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="documentos">
                            <p class="text-muted">Gestión de plantillas de documentos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let datosReporte = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarDashboard();
    cargarReglas();
});

function cargarDashboard() {
    const fecha_inicio = '<?= date('Y-m-01') ?>';
    const fecha_fin = '<?= date('Y-m-d') ?>';
    
    fetch('/agente-ia/reporte?fecha_inicio=' + fecha_inicio + '&fecha_fin=' + fecha_fin)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                datosReporte = data.data;
                document.getElementById('total-retardos').textContent = data.data.metricas.total_retardos;
                document.getElementById('total-faltas').textContent = data.data.metricas.total_faltas;
                document.getElementById('total-no-validadas').textContent = data.data.metricas.total_no_valificadas;
                document.getElementById('total-anomalias').textContent = data.data.anomalias.length;
                
                renderRetardos(data.data.retardos_no_justificados);
                renderNoValidadas(data.data.no_validadas);
                renderRecomendaciones(data.data.recomendaciones);
            }
        })
        .catch(console.error);
}

function renderRetardos(retardos) {
    const container = document.getElementById('retardos-container');
    if (!retardos || retardos.length === 0) {
        container.innerHTML = '<p class="text-success">No hay retardos no justificados</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
    html += '<thead><tr><th>Empleado</th><th>Fecha</th><th>Minutos</th><th>Tipo</th></tr></thead>';
    html += '<tbody>';
    
    retardos.slice(0, 20).forEach(r => {
        html += '<tr>';
        html += '<td>' + (r.nombre || '') + ' ' + (r.apellido || '') + '</td>';
        html += '<td>' + r.fecha + '</td>';
        html += '<td>' + (r.minutos_retardo || 0) + '</td>';
        html += '<td><span class="badge badge-warning">' + (r.tipo_retraso || 'N/A') + '</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    if (retardos.length > 20) {
        html += '<p class="text-muted">Mostrando 20 de ' + retardos.length + ' registros</p>';
    }
    container.innerHTML = html;
}

function renderNoValidadas(noValidadas) {
    const container = document.getElementById('no-validadas-container');
    if (!noValidadas || noValidadas.length === 0) {
        container.innerHTML = '<p class="text-success">No hay incidencias pendientes de validación</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
    html += '<thead><tr><th>Empleado</th><th>Fecha</th><th>Tipo</th><th>Clasificación</th></tr></thead>';
    html += '<tbody>';
    
    noValidadas.slice(0, 20).forEach(n => {
        html += '<tr>';
        html += '<td>' + (n.nombre || '') + ' ' + (n.apellido || '') + '</td>';
        html += '<td>' + n.fecha + '</td>';
        html += '<td>' + n.tipo_incidencia + '</td>';
        html += '<td><span class="badge badge-info">' + n.clasificacion + '</span></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function renderRecomendaciones(recomendaciones) {
    const container = document.getElementById('recomendaciones-container');
    
    if (!recomendaciones || !recomendaciones.items || recomendaciones.items.length === 0) {
        container.innerHTML = '<p class="text-success"><i class="fas fa-check-circle"></i> No hay recomendaciones pendientes</p>';
        return;
    }
    
    let html = '<div class="row">';
    
    recomendaciones.items.forEach(r => {
        const prioridadClass = r.prioridad === 'alta' ? 'danger' : (r.prioridad === 'media' ? 'warning' : 'info');
        const iconClass = r.tipo === 'atencion' ? 'fa-user-clock' : (r.tipo === 'validacion' ? 'fa-user-check' : (r.tipo === 'sancion' ? 'fa-gavel' : 'fa-info-circle'));
        
        html += '<div class="col-md-6 mb-3">';
        html += '<div class="card border-' + prioridadClass + '">';
        html += '<div class="card-body py-2">';
        html += '<h6><span class="badge badge-' + prioridadClass + '">' + r.prioridad.toUpperCase() + '</span> ';
        html += '<i class="fas ' + iconClass + '"></i> ' + r.tipo + '</h6>';
        html += '<p class="mb-1 small">' + r.mensaje + '</p>';
        if (r.accion) {
            html += '<button class="btn btn-sm btn-' + prioridadClass + '" onclick="ejecutarAccion(\'' + r.accion + '\', ' + (r.empleado_id || 0) + ')">';
            html += '<i class="fas fa-cog"></i> Ejecutar</button>';
        }
        html += '</div></div></div>';
    });
    
    html += '</div>';
    
    if (recomendaciones.empleados_criticos && recomendaciones.empleados_criticos.length > 0) {
        html += '<div class="mt-3"><h6><i class="fas fa-exclamation-triangle text-danger"></i> Empleados con más incidencias:</h6>';
        html += '<ul class="list-group">';
        recomendaciones.empleados_criticos.forEach(emp => {
            html += '<li class="list-group-item d-flex justify-content-between align-items-center">';
            html += emp.empleado + ' <span class="badge badge-danger">' + emp.total + '</span>';
            html += '</li>';
        });
        html += '</ul></div>';
    }
    
    container.innerHTML = html;
}

function cargarReglas() {
    fetch('/agente-ia/reglas')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderReglas(data.data);
            }
        })
        .catch(console.error);
}

function renderReglas(reglas) {
    const container = document.getElementById('reglas-container');
    if (!reglas || reglas.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay reglas configuradas</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-sm table-bordered">';
    html += '<thead><tr><th>Nombre</th><th>Tipo</th><th>Categoría</th><th>Prioridad</th><th>Estado</th><th>Acciones</th></tr></thead>';
    html += '<tbody>';
    
    reglas.forEach(r => {
        html += '<tr>';
        html += '<td>' + r.nombre + '</td>';
        html += '<td>' + r.tipo + '</td>';
        html += '<td>' + r.categoria + '</td>';
        html += '<td>' + r.prioridad + '</td>';
        html += '<td>' + (r.activa ? '<span class="badge badge-success">Activa</span>' : '<span class="badge badge-secondary">Inactiva</span>') + '</td>';
        html += '<td>';
        html += '<button class="btn btn-xs btn-warning" onclick="editarRegla(' + r.id + ')">Editar</button> ';
        html += '<button class="btn btn-xs btn-danger" onclick="eliminarRegla(' + r.id + ')">Eliminar</button>';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function modalNuevaRegla() {
    alert('Funcionalidad de nueva regla en desarrollo');
}

function editarRegla(id) {
    alert('Editar regla ' + id + ' - En desarrollo');
}

function eliminarRegla(id) {
    if (!confirm('¿Eliminar esta regla?')) return;
    
    fetch('/agente-ia/eliminar-regla', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) cargarReglas();
    })
    .catch(console.error);
}

function exportarExcel() {
    const form = document.getElementById('procesar-form');
    const formData = new FormData(form);
    
    fetch('/agente-ia/exportar-excel', {
        method: 'POST',
        body: formData
    })
    .then(r => r.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'reporte_asistencia_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        a.click();
    })
    .catch(console.error);
}

function ejecutarAccion(accion, empleado_id) {
    switch(accion) {
        case 'generar_oficio':
            if (empleado_id > 0) {
                generarDocumento(empleado_id);
            } else {
                alert('Seleccione un empleado para generar el oficio');
            }
            break;
        case 'notificar_jefes':
            alert('Funcionalidad de notificación a jefes en desarrollo');
            break;
        case 'evaluar_sanciones':
            alert('Evaluando sanciones automáticas...');
            break;
        case 'monitorear':
            alert('Agregando a lista de monitoreo...');
            break;
        default:
            alert('Acción no reconocida');
    }
}

function generarDocumento(empleado_id) {
    const fecha_inicio = document.querySelector('input[name="fecha_inicio"]').value;
    const fecha_fin = document.querySelector('input[name="fecha_fin"]').value;
    const periodo = fecha_inicio.substring(0, 7);
    
    fetch('/agente-ia/generar-documento', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'empleado_id=' + empleado_id + '&periodo=' + periodo + '&fecha_inicio=' + fecha_inicio + '&fecha_fin=' + fecha_fin + '&tipo=oficio'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const win = window.open('', '_blank');
            win.document.write('<pre style="white-space: pre-wrap;">' + data.contenido + '</pre>');
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(console.error);
}

document.getElementById('procesar-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/agente-ia/procesar', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Procesamiento completado');
            cargarDashboard();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(console.error);
});
</script>
