<?php
$content = '
<div class="container mt-4">
    <h2>Control de Asistencia</h2>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Filtros de Búsqueda</h5>
                </div>
                <div class="card-body">
                    <form id="filtro-form" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="fecha_inicio" class="mr-2">Fecha Inicio:</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="' . date('Y-m-d', strtotime('-8 day')) . '">
                        </div>
                        <div class="form-group mr-3">
                            <label for="fecha_fin" class="mr-2">Fecha Fin:</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="' . date('Y-m-d') . '">
                        </div>
                        <div class="form-group mr-3">
                            <label for="area" class="mr-2">Área:</label>
                            <select class="form-control" id="area" name="area">
                                <option value="">Todas las áreas</option>
                                ' . (isset($areas) ? implode('', array_map(function($area) {
                                    return '<option value="' . htmlspecialchars($area) . '">' . htmlspecialchars($area) . '</option>';
                                }, $areas)) : '') . '
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label for="dispositivo_id" class="mr-2">Dispositivo:</label>
                            <select class="form-control" id="dispositivo_id" name="dispositivo_id">
                                <option value="">Todos</option>
                                ' . implode('', array_map(function($i) {
                                    return '<option value="' . $i . '">Dispositivo ' . $i . '</option>';
                                }, range(1, 10))) . '
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label for="tipo_biometria" class="mr-2">Biometría:</label>
                            <select class="form-control" id="tipo_biometria" name="tipo_biometria">
                                <option value="">Todas</option>
                                <option value="huella">Huella</option>
                                <option value="cara">Cara</option>
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label for="tipo_asistencia" class="mr-2">Tipo:</label>
                            <select class="form-control" id="tipo_asistencia" name="tipo_asistencia">
                                <option value="">Todos</option>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="filtrarAsistencia()">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-success ml-2" onclick="exportarExcel()">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </button>
                        <button type="button" class="btn btn-info ml-2" onclick="exportarPDF()" title="Exportar reporte en formato PDF">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Estado de Dispositivos Biométricos</h5>
                </div>
                <div class="card-body">
                    <div id="dispositivos-status" class="row">
                        <!-- Los dispositivos se cargarán aquí dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-success btn-block mb-2" onclick="simularEntrada()">
                        <i class="fas fa-sign-in-alt"></i> Simular Entrada
                    </button>
                    <button class="btn btn-warning btn-block mb-2" onclick="simularSalida()">
                        <i class="fas fa-sign-out-alt"></i> Simular Salida
                    </button>
                    <button class="btn btn-info btn-block" onclick="actualizarEstado()">
                        <i class="fas fa-sync"></i> Actualizar Estado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas para diferentes vistas -->
    <div class="row mt-4">
        <div class="col-md-12">
            <ul class="nav nav-tabs" id="asistencia-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="registros-tab" data-toggle="tab" href="#registros" role="tab" aria-controls="registros" aria-selected="true">
                        <i class="fas fa-list"></i> Registros de Asistencia
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="horas-tab" data-toggle="tab" href="#horas" role="tab" aria-controls="horas" aria-selected="false">
                        <i class="fas fa-clock"></i> Cálculo de Horas Laborables
                    </a>
                </li>
            </ul>
            <div class="tab-content" id="asistencia-tab-content">
                <!-- Tab de Registros -->
                <div class="tab-pane fade show active" id="registros" role="tabpanel" aria-labelledby="registros-tab">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5>Registros de Asistencia Filtrados</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Área</th>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Tipo</th>
                                            <th>Dispositivo</th>
                                            <th>Biometría</th>
                                            <th>Calidad</th>
                                            <th>Tiempo Proc.</th>
                                        </tr>
                                    </thead>
                                    <tbody id="asistencia-table">
                                        <!-- Los registros se cargarán aquí dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="no-results" class="text-center text-muted mt-3" style="display: none;">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>No se encontraron registros de asistencia con los filtros aplicados.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab de Cálculo de Horas -->
                <div class="tab-pane fade" id="horas" role="tabpanel" aria-labelledby="horas-tab">
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Cálculo de Horas Laborables y Retardos</h5>
                            <button type="button" class="btn btn-info" onclick="calcularHorasLaborables()">
                                <i class="fas fa-calculator"></i> Calcular Horas
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Área</th>
                                            <th>Fecha</th>
                                            <th>Hora Entrada</th>
                                            <th>Hora Salida</th>
                                            <th>Hora Oficial Entrada</th>
                                            <th>Hora Oficial Salida</th>
                                            <th>Horas Laborables</th>
                                            <th>Horas Trabajadas</th>
                                            <th>Retardo (min)</th>
                                            <th>Tipo Retardo</th>
                                            <th>Tiempo Extra (min)</th>
                                            <th>Retardos Mes</th>
                                            <th>Justificaciones Pendientes</th>
                                            <th>Estado</th>
                                            <th>Comentarios</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="horas-table">
                                        <!-- Los cálculos se cargarán aquí dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="no-results-horas" class="text-center text-muted mt-3" style="display: none;">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>No se encontraron cálculos de horas con los filtros aplicados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarEstado() {
    fetch(window.BASE_URL + \'/asistencia/estado-dispositivos\')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById(\'dispositivos-status\');
            container.innerHTML = \'\';

            data.forEach(dispositivo => {
                const col = document.createElement(\'div\');
                col.className = \'col-md-6 mb-3\';

                col.innerHTML = `
                    <div class="card ${dispositivo.status === \'conectado\' ? \'border-success\' : \'border-danger\'}">
                        <div class="card-body">
                            <h6 class="card-title">Dispositivo ${dispositivo.dispositivo_id}</h6>
                            <p class="card-text">
                                <span class="badge ${dispositivo.status === \'conectado\' ? \'badge-success\' : \'badge-danger\'}">
                                    ${dispositivo.status}
                                </span>
                                <br>
                <small class="text-muted">${dispositivo.firmware_version}</small>
                            </p>
                        </div>
                    </div>
                `;

                container.appendChild(col);
            });
        })
        .catch(error => console.error(\'Error:\', error));
}

function simularEntrada() {
    const dispositivo_id = Math.floor(Math.random() * 10) + 1;

    fetch(\'<?php echo BASE_URL; ?>/asistencia/registrar-entrada\', {
        method: \'POST\',
        headers: {
            \'Content-Type\': \'application/x-www-form-urlencoded\',
        },
        body: `dispositivo_id=${dispositivo_id}`
    })
    .then(response => response.json())
    .then(data => {
        mostrarMensaje(data.message, \'success\');
        actualizarEstado();
        filtrarAsistencia();
    })
    .catch(error => {
        console.error(\'Error:\', error);
        mostrarMensaje(\'Error al simular entrada. Inténtelo de nuevo.\', \'danger\');
    });
}

function simularSalida() {
    const dispositivo_id = Math.floor(Math.random() * 10) + 1;

    fetch(\'<?php echo BASE_URL; ?>/asistencia/registrar-salida\', {
        method: \'POST\',
        headers: {
            \'Content-Type\': \'application/x-www-form-urlencoded\',
        },
        body: `dispositivo_id=${dispositivo_id}`
    })
    .then(response => response.json())
    .then(data => {
        mostrarMensaje(data.message, \'success\');
        actualizarEstado();
        filtrarAsistencia();
    })
    .catch(error => {
        console.error(\'Error:\', error);
        mostrarMensaje(\'Error al simular salida. Inténtelo de nuevo.\', \'danger\');
    });
}

function filtrarAsistencia() {
    const fechaInicio = document.getElementById(\'fecha_inicio\').value;
    const fechaFin = document.getElementById(\'fecha_fin\').value;
    const area = document.getElementById(\'area\').value;
    const dispositivoId = document.getElementById(\'dispositivo_id\').value;
    const tipoBiometria = document.getElementById(\'tipo_biometria\').value;
    const tipoAsistencia = document.getElementById(\'tipo_asistencia\').value;

    const formData = new URLSearchParams();
    formData.append(\'fecha_inicio\', fechaInicio);
    formData.append(\'fecha_fin\', fechaFin);
    if (area) formData.append(\'area\', area);
    if (dispositivoId) formData.append(\'dispositivo_id\', dispositivoId);
    if (tipoBiometria) formData.append(\'tipo_biometria\', tipoBiometria);
    if (tipoAsistencia) formData.append(\'tipo_asistencia\', tipoAsistencia);

    fetch(window.BASE_URL + \'/asistencia/filtrar-asistencia\', {
        method: \'POST\',
        headers: {
            \'Content-Type\': \'application/x-www-form-urlencoded\',
            \'X-CSRF-Token\': document.getElementById(\'csrf_token\')?.value || \'\'
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById(\'asistencia-table\');
        const noResults = document.getElementById(\'no-results\');

        tbody.innerHTML = \'\';

        if (data.length === 0) {
            noResults.style.display = \'block\';
            mostrarMensaje(\'No se encontraron registros de asistencia con los filtros aplicados.\', \'info\');
            return;
        }

        noResults.style.display = \'none\';
        mostrarMensaje(`Se encontraron \${data.length} registros de asistencia.`, \'success\');

        data.forEach(registro => {
            const row = document.createElement(\'tr\');

            row.innerHTML = `
                <td>${registro.empleado}</td>
                <td>${registro.area}</td>
                <td>${registro.fecha}</td>
                <td>${registro.hora}</td>
                <td>
                    <span class="badge ${registro.tipo === \'entrada\' ? \'badge-success\' : \'badge-warning\'}">
                        ${registro.tipo}
                    </span>
                </td>
                <td>Dispositivo ${registro.dispositivo_id}</td>
                <td>
                    <span class="badge ${registro.tipo_biometria === \'huella\' ? \'badge-primary\' : \'badge-info\'}">
                        ${registro.tipo_biometria}
                    </span>
                </td>
                <td>${registro.calidad_verificacion}</td>
                <td>${registro.tiempo_procesamiento}</td>
            `;

            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error(\'Error:\', error);
        const noResults = document.getElementById(\'no-results\');
        noResults.style.display = \'block\';
        noResults.innerHTML = `
            <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
            <p>Error al cargar los datos. Por favor, inténtelo de nuevo.</p>
        `;
        mostrarMensaje(\'Error al filtrar asistencia. Inténtelo de nuevo.\', \'danger\');
    });
}

function calcularHorasLaborables() {
    const fechaInicio = document.getElementById(\'fecha_inicio\').value;
    const fechaFin = document.getElementById(\'fecha_fin\').value;
    const area = document.getElementById(\'area\').value;

    const formData = new URLSearchParams();
    formData.append(\'fecha_inicio\', fechaInicio);
    formData.append(\'fecha_fin\', fechaFin);
    if (area) formData.append(\'area\', area);

    fetch(\'<?php echo BASE_URL; ?>/asistencia/calcular-horas-laborables\', {
        method: \'POST\',
        headers: {
            \'Content-Type\': \'application/x-www-form-urlencoded\',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById(\'horas-table\');
        const noResults = document.getElementById(\'no-results-horas\');

        tbody.innerHTML = \'\';

        if (data.error) {
            noResults.style.display = \'block\';
            noResults.innerHTML = `
                <i class="fas fa-exclamation-triangle fa-2x mb-2 text-danger"></i>
                <p>${data.error}</p>
            `;
            mostrarMensaje(data.error, \'danger\');
            return;
        }

        if (data.length === 0) {
            noResults.style.display = \'block\';
            noResults.innerHTML = `
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <p>No se encontraron cálculos de horas con los filtros aplicados.</p>
            `;
            mostrarMensaje(\'No se encontraron cálculos de horas con los filtros aplicados.\', \'info\');
            return;
        }

        noResults.style.display = \'none\';
        mostrarMensaje(`Se calcularon las horas para \${data.length} registros.`, \'success\');

        data.forEach(registro => {
            const row = document.createElement(\'tr\');

            // Determinar clase CSS para el estado
            let estadoClass = \'\';
            let estadoText = registro.estado;
            switch(registro.estado) {
                case \'normal\':
                    estadoClass = \'badge-success\';
                    estadoText = \'Normal\';
                    break;
                case \'retardo_menor\':
                    estadoClass = \'badge-warning\';
                    estadoText = \'Retardo Menor\';
                    break;
                case \'retardo_mayor\':
                    estadoClass = \'badge-danger\';
                    estadoText = \'Retardo Mayor\';
                    break;
                case \'pendiente_justificacion\':
                    estadoClass = \'badge-info\';
                    estadoText = \'Pendiente Justificación\';
                    break;
                case \'incompleto\':
                    estadoClass = \'badge-secondary\';
                    estadoText = \'Registro Incompleto\';
                    break;
                default:
                    estadoClass = \'badge-light\';
                    estadoText = \'Desconocido\';
            }

            row.innerHTML = `
                <td>${registro.empleado}</td>
                <td>${registro.area}</td>
                <td>${registro.fecha}</td>
                <td>${registro.hora_entrada}</td>
                <td>${registro.hora_salida}</td>
                <td>${registro.hora_oficial_entrada}</td>
                <td>${registro.hora_oficial_salida}</td>
                <td>${registro.horas_laborables}</td>
                <td>${registro.horas_trabajadas}</td>
                <td>${registro.retardo_minutos}</td>
                <td>${registro.tipo_retardo}</td>
                <td>${registro.tiempo_extra_minutos}</td>
                <td>${registro.retardos_mes}</td>
                <td>${registro.justificaciones_pendientes}</td>
                <td><span class="badge ${estadoClass}">${estadoText}</span></td>
                <td><small>${registro.comentarios}</small></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="verDetalles(${registro.id})">Ver Detalles</button>
                </td>
            `;

            tbody.appendChild(row);
        });
    })
    .catch(error => {
        console.error(\'Error:\', error);
        const noResults = document.getElementById(\'no-results-horas\');
        noResults.style.display = \'block\';
        noResults.innerHTML = `
            <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
            <p>Error al calcular las horas. Por favor, inténtelo de nuevo.</p>
        `;
        mostrarMensaje(\'Error al calcular las horas. Inténtelo de nuevo.\', \'danger\');
    });
}

function exportarExcel() {
    const fechaInicio = document.getElementById(\'fecha_inicio\').value;
    const fechaFin = document.getElementById(\'fecha_fin\').value;
    const area = document.getElementById(\'area\').value;

    const url = `<?php echo BASE_URL; ?>/asistencia/exportar-excel?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&area=${area}`;
    window.open(url, \'_blank\');
}

function exportarPDF() {
    const fechaInicio = document.getElementById(\'fecha_inicio\').value;
    const fechaFin = document.getElementById(\'fecha_fin\').value;
    const area = document.getElementById(\'area\').value;
    const dispositivoId = document.getElementById(\'dispositivo_id\').value;
    const tipoBiometria = document.getElementById(\'tipo_biometria\').value;
    const tipoAsistencia = document.getElementById(\'tipo_asistencia\').value;

    const formData = new URLSearchParams();
    formData.append(\'fecha_inicio\', fechaInicio);
    formData.append(\'fecha_fin\', fechaFin);
    if (area) formData.append(\'area\', area);
    if (dispositivoId) formData.append(\'dispositivo_id\', dispositivoId);
    if (tipoBiometria) formData.append(\'tipo_biometria\', tipoBiometria);
    if (tipoAsistencia) formData.append(\'tipo_asistencia\', tipoAsistencia);

    // Crear un formulario temporal para enviar POST
    const form = document.createElement(\'form\');
    form.method = \'POST\';
    form.action = \'<?php echo BASE_URL; ?>/asistencia/exportar-pdf\';
    form.target = \'_blank\';

    // Agregar los datos del formulario
    for (let pair of formData.entries()) {
        const input = document.createElement(\'input\');
        input.type = \'hidden\';
        input.name = pair[0];
        input.value = pair[1];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function verDetalles(id) {
    // Placeholder for viewing details, can be implemented later
    alert(\'Ver detalles del registro \' + id);
}

function mostrarMensaje(mensaje, tipo) {
    const messageDiv = document.createElement(\'div\');
    messageDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    messageDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    const container = document.querySelector(\'.container\');
    container.insertBefore(messageDiv, container.firstChild);
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Cargar estado inicial y datos
document.addEventListener(\'DOMContentLoaded\', function() {
    actualizarEstado();
    filtrarAsistencia();
});
</script>
<?php
';

include __DIR__ . '/../layout.php';
?>
