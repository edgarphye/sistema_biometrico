/**
 * JavaScript para el Sistema de Validación por Jefes
 * Maneja toda la interactividad dinámica del formulario
 */

// Variables globales
let validacionesPendientes = [];
let validacionesSeleccionadas = new Set();
let empleadosSeleccionados = new Set();
let empleadosACargo = [];
let paginaActual = 1;
let registrosPorPagina = 25;
let filtrosActuales = {};
let soloPendientes = true;

/**
 * Obtener CSRF token para peticiones POST
 */
function getCsrfToken() {
    const token = document.getElementById('csrf_token')?.value || 
                  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                  '';
    return token;
}

/**
 * Global Fetch Interceptor
 * Automatically adds CSRF token to all POST requests with a JSON body.
 * Also logs AJAX errors to the error logging system.
 */
(function() {
    const originalFetch = window.fetch;

    window.fetch = async function(resource, options) {
        // We only intercept POST requests with a JSON body
        if (options && options.method && options.method.toUpperCase() === 'POST') {
            const headers = new Headers(options.headers);
            if (headers.get('Content-Type') === 'application/json') {
                
                let bodyData = {};
                if (options.body) {
                    try {
                        bodyData = JSON.parse(options.body);
                    } catch (e) {
                        console.warn('Could not parse fetch body as JSON to inject CSRF token.', e);
                        return originalFetch.apply(this, arguments);
                    }
                }
                
                if (!bodyData.csrf_token) {
                    bodyData.csrf_token = getCsrfToken();
                }
                
                options.body = JSON.stringify(bodyData);
            }
        }

        try {
            const response = await originalFetch.apply(this, [resource, options]);
            
            // Log AJAX errors (4xx, 5xx)
            if (!response.ok && response.status >= 400) {
                const contentType = response.headers.get('content-type');
                let errorDetails = '';
                
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const data = await response.clone().json();
                        errorDetails = JSON.stringify(data);
                    } catch (e) {
                        errorDetails = await response.text();
                    }
                } else {
                    errorDetails = response.statusText;
                }
                
                // Send to error logger
                const url = typeof resource === 'string' ? resource : resource.url || '';
                if (typeof sendErrorToPHP === 'function') {
                    sendErrorToPHP(
                        'AJAX Error ' + response.status + ': ' + response.statusText,
                        'ajax_error',
                        url,
                        '',
                        errorDetails.substring(0, 1000)
                    );
                }
            }
            
            return response;
        } catch (error) {
            // Log network errors
            const url = typeof resource === 'string' ? resource : resource.url || '';
            if (typeof sendErrorToPHP === 'function') {
                sendErrorToPHP(
                    'Fetch Error: ' + error.message,
                    'fetch_error',
                    url,
                    '',
                    error.stack || ''
                );
            }
            throw error;
        }
    };
})();

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado, iniciando sistema de validaciones...');
    
    try {
        inicializarComponentes();
        crearModalEvidencia(); // Inicializar modal de evidencia
        console.log('✅ Componentes inicializados');
        
        // Cargar datos en paralelo
        Promise.all([
            cargarValidacionesPendientes(),
            cargarEmpleadosACargo()
        ]).then(() => {
            console.log('✅ Datos iniciales cargados');
            actualizarEstadisticas();
            cargarAreas();
        }).catch(error => {
            console.error('❌ Error cargando datos iniciales:', error);
        });
        
        // Configurar autorefresco cada 30 segundos
        setInterval(actualizarEstadisticas, 30000);
        
    } catch (error) {
        console.error('❌ Error en inicialización:', error);
    }
});

/**
 * Inicializar componentes y event listeners
 */
function inicializarComponentes() {
    // Event listeners para filtros
    document.getElementById('filtroArea')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroEmpleado')?.addEventListener('input', debounce(aplicarFiltros, 500));
    document.getElementById('filtroTipo')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroFechaInicio')?.addEventListener('change', aplicarFiltros);
    document.getElementById('filtroFechaFin')?.addEventListener('change', aplicarFiltros);
    
    // Event listeners para botones de acción masiva
    document.getElementById('btnExpandirTodos')?.addEventListener('click', expandirContraerTodos(true));
    document.getElementById('btnContraerTodos')?.addEventListener('click', expandirContraerTodos(false));
    document.getElementById('btnAprobarMasivo')?.addEventListener('click', () => procesarMasivo('aprobado'));
    document.getElementById('btnRechazarMasivo')?.addEventListener('click', () => procesarMasivo('rechazado'));
    document.getElementById('btnSolicitarInfoMasivo')?.addEventListener('click', () => procesarMasivo('requiere_info'));
    document.getElementById('btnActualizar')?.addEventListener('click', cargarValidacionesPendientes);
    
    // Event listeners para selección de empleados
    document.getElementById('btnValidarSeleccionados')?.addEventListener('click', validarEmpleadosSeleccionados);
    document.getElementById('btnSeleccionarTodosEmpleados')?.addEventListener('click', seleccionarTodosEmpleados);
    document.getElementById('btnLimpiarSeleccion')?.addEventListener('click', limpiarSeleccionEmpleados);
    document.getElementById('soloPendientes')?.addEventListener('change', toggleSoloPendientes);
    
    // Event listeners para selección
    document.getElementById('selectAllMain')?.addEventListener('change', toggleSeleccionTodos);
    document.getElementById('selectAllCheckbox')?.addEventListener('change', toggleSeleccionTodos);
    
    // Event listeners para modal
    document.getElementById('btnGuardarDecision')?.addEventListener('click', guardarDecisionModal);
    
    // Event listeners para radio buttons en modal
    document.querySelectorAll('input[name="estadoValidacion"]').forEach(radio => {
        radio.addEventListener('change', mostrarOcultarCamposModal);
    });
    
    // Event listeners para reportes
    document.getElementById('btnDescargarPDF')?.addEventListener('click', () => descargarReporte('pdf'));
    document.getElementById('btnDescargarExcel')?.addEventListener('click', () => descargarReporte('excel'));
}

/**
 * Cargar validaciones pendientes desde el servidor
 */
async function cargarValidacionesPendientes() {
    console.log('🔄 Iniciando carga de validaciones pendientes...');
    
    try {
        mostrarCarga(true);
        
const url = `${window.BASE_URL}/validaciones/buscar-pendientes`;
        const requestData = {
            ...filtrosActuales,
            pagina: paginaActual,
            limite: registrosPorPagina
        };
        
        console.log('📡 Solicitando:', url);
        console.log('📤 Request data:', requestData);
        console.log('🔐 CSRF Token:', getCsrfToken());
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        });
        
        console.log('📥 Response status:', response.status);
        
        const data = await response.json();
        console.log('📊 Response data:', data);
        
        if (data.success) {
            validacionesPendientes = data.validaciones;
            console.log('✅ Validaciones cargadas:', data.validaciones.length);
            renderizarTablaValidaciones(data.validaciones);
            renderizarPaginacion(data.total);
            actualizarContadores();
        } else {
            console.error('❌ Error en respuesta:', data.error);
            mostrarError('Error cargando validaciones: ' + data.error);
        }
    } catch (error) {
        console.error('❌ Error cargando validaciones:', error);
        mostrarError('Error de conexión al cargar validaciones');
    } finally {
        mostrarCarga(false);
    }
}

/**
 * Renderizar la tabla de validaciones
 */
function renderizarTablaValidaciones(validaciones) {
    const tbody = document.getElementById('tablaValidacionesBody');
    if (!tbody) return;
    
    if (validaciones.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="14" class="text-center py-4">
                    <i class="fas fa-circle-check text-success fa-3x mb-3"></i>
                    <h5>No hay incidencias pendientes de validación</h5>
                    <p class="text-muted">Todas las incidencias están al día o ya han sido procesadas.</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = validaciones.map((validacion, index) => {
        const fechaFormateada = formatearFecha(validacion.fecha_incidencia);
        const prioridadClass = getPrioridadClass(validacion.prioridad_atencion);
        const urgenciaClass = getUrgenciaClass(validacion.nivel_urgencia);
        const estadoClass = getEstadoClass(validacion.estado_validacion_jefe);
        
        return `
            <tr class="fila-validacion" data-id="${validacion.incidencia_id}">
                <td>
                    <input type="checkbox" class="form-check-input chk-validacion" 
                           value="${validacion.incidencia_id}" 
                           data-tipo="${validacion.tipo_incidencia}">
                </td>
                <td>
                    <span class="badge bg-secondary">${validacion.empleado_id}</span>
                </td>
                <td>
                    <strong>${validacion.empleado_nombre}</strong>
                </td>
                <td>
                    <span class="badge bg-info">${validacion.empleado_area || 'N/A'}</span>
                </td>
                <td>
                    ${fechaFormateada}
                </td>
                <td>
                    ${(() => {
                        const diasingles = { 'Monday': 'Lunes', 'Tuesday': 'Martes', 'Wednesday': 'Miércoles', 'Thursday': 'Jueves', 'Friday': 'Viernes', 'Saturday': 'Sábado', 'Sunday': 'Domingo' };
                        const dia = validacion.dia_semana ? (diasingles[validacion.dia_semana] || validacion.dia_semana) : 'N/A';
                        return `<span class="badge bg-light text-dark">${dia}</span>`;
                    })()}
                </td>
                <td>
                    <small>${validacion.descripcion_incidencia}</small>
                </td>
                <td>
                    ${validacion.minutos_retardo ? `${validacion.minutos_retardo} min` : 'N/A'}
                </td>
                <td>
                    <small>${validacion.hora_entrada || 'N/A'} - ${validacion.hora_salida || 'N/A'}</small>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-decoration-none btn-ver-evidencia" 
                            data-id="${validacion.id}" title="Ver evidencia">
                        ${validacion.requiere_validacion_jefe ? 
                            '<i class="fas fa-paperclip text-warning fa-lg"></i>' : 
                            '<i class="fas fa-file-alt text-secondary"></i>'}
                    </button>
                </td>
                <td>
                    <span class="badge ${prioridadClass}">${getPrioridadText(validacion.prioridad_atencion)}</span>
                </td>
                <td>
                    <span class="badge ${estadoClass}">${getEstadoText(validacion.estado_validacion_jefe)}</span>
                    ${validacion.fecha_limite_validacion ? 
                        `<br><small class="text-muted">Límite: ${formatearFecha(validacion.fecha_limite_validacion)}</small>` : ''}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-validar" 
                                data-id="${validacion.id}" 
                                data-tipo="${validacion.tipo_incidencia}"
                                title="Validar incidencia">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info btn-expandir" 
                                data-id="${validacion.incidencia_id}"
                                title="Ver detalles">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    // Agregar event listeners a los botones dinámicos
    document.querySelectorAll('.btn-validar').forEach(btn => {
        btn.addEventListener('click', abrirModalValidacion);
    });
    
    document.querySelectorAll('.btn-ver-evidencia').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            verEvidencia(e.currentTarget.dataset.id);
        });
    });
    
    document.querySelectorAll('.btn-expandir').forEach(btn => {
        btn.addEventListener('click', toggleDetallesFila);
    });
    
    document.querySelectorAll('.chk-validacion').forEach(checkbox => {
        checkbox.addEventListener('change', actualizarSeleccionados);
    });
}

/**
 * Abrir modal de validación para una incidencia específica
 */
async function abrirModalValidacion(event) {
    const btn = event.currentTarget;
    const validacionId = btn.dataset.id;
    const tipoIncidencia = btn.dataset.tipo;
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/validar/${validacionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        });
        
        const data = await response.json();
        
        if (data.success) {
            llenarModalValidacion(data);
            const modal = new bootstrap.Modal(document.getElementById('modalValidacion'));
            modal.show();
        } else {
            mostrarError('Error cargando detalles de la validación: ' + data.error);
        }
    } catch (error) {
        console.error('Error cargando validación:', error);
        mostrarError('Error de conexión al cargar detalles');
    }
}

/**
 * Crear modal de evidencia en el DOM
 */
function crearModalEvidencia() {
    if (document.getElementById('modalEvidencia')) return;
    
    const modalHtml = `
    <div class="modal fade" id="modalEvidencia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-evidence me-2"></i>Evidencia de Justificación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalEvidenciaBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

/**
 * Ver evidencia de una validación
 */
async function verEvidencia(validacionId) {
    const modalEl = document.getElementById('modalEvidencia');
    if (!modalEl) return;

    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }
    modal.show();
    
    const modalBody = document.getElementById('modalEvidenciaBody');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando evidencia...</p></div>';
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/validar/${validacionId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({})
        });
        
        const data = await response.json();
        
        if (data.success) {
            const detalles = data.detalles || {};
            const evidencia = detalles.evidencia_adjunta ? 
                `<div class="alert alert-success"><i class="fas fa-check me-2"></i>Archivo adjunto: <a href="${window.BASE_URL}/${detalles.evidencia_adjunta}" target="_blank">Ver documento</a></div>` : 
                `<div class="alert alert-secondary"><i class="fas fa-info-circle me-2"></i>No hay archivo adjunto digital.</div>`;
                
            const motivo = detalles.motivo_justificacion || detalles.motivo_detalle || 'Sin motivo especificado';
            
            modalBody.innerHTML = `
                <div class="card mb-3">
                    <div class="card-header bg-light"><strong>Motivo de Justificación</strong></div>
                    <div class="card-body">
                        <p class="card-text">${motivo}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header bg-light"><strong>Evidencia Documental</strong></div>
                    <div class="card-body text-center">
                        ${evidencia}
                    </div>
                </div>`;
        } else {
            modalBody.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
        }
    } catch (error) {
        modalBody.innerHTML = `<div class="alert alert-danger">Error de conexión: ${error.message}</div>`;
    }
}

/**
 * Llenar el modal con los datos de la validación
 */
function llenarModalValidacion(data) {
    const validacion = data.validacion;
    const detalles = data.detalles;
    
    // Información básica
    document.getElementById('modalValidacionId').value = validacion.incidencia_id;
    document.getElementById('modalEmpleadoInfo').textContent = validacion.empleado_nombre;
    document.getElementById('modalAreaInfo').textContent = `${validacion.empleado_area || 'N/A'} - ${validacion.empleado_jerarquia || 'N/A'}`;
    
    // Detalles de la incidencia - usar detalles.fecha que viene de la tabla de incidencia
    const fechaIncidencia = detalles?.fecha || validacion.fecha_incidencia;
    document.getElementById('modalFechaIncidencia').textContent = formatearFecha(fechaIncidencia);
    
    // Traducir día de la semana al español - calcular desde la fecha si no viene
    const diasingles = { 'Monday': 'Lunes', 'Tuesday': 'Martes', 'Wednesday': 'Miércoles', 'Thursday': 'Jueves', 'Friday': 'Viernes', 'Saturday': 'Sábado', 'Sunday': 'Domingo' };
    let diaSemana = 'N/A';
    if (detalles?.dia_semana) {
        diaSemana = diasingles[detalles.dia_semana] || detalles.dia_semana;
    } else if (fechaIncidencia) {
        // Calcular día desde la fecha
        const fecha = new Date(fechaIncidencia);
        const diaIndex = fecha.getDay();
        const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        diaSemana = dias[diaIndex];
    }
    document.getElementById('modalDiaSemana').textContent = diaSemana;
    
    // Mostrar horario - solo entrada si no hay salida
    const horaEntrada = detalles?.hora_entrada ? detalles.hora_entrada.substring(0, 5) : 'N/A';
    const horaSalida = detalles?.hora_salida ? detalles.hora_salida.substring(0, 5) : '';
    document.getElementById('modalHorario').textContent = horaSalida ? `${horaEntrada} - ${horaSalida}` : horaEntrada;
    
    // Duración - mostrar minutos de retardo o tiempo
    const duracion = detalles?.minutos_retardo ? `${detalles.minutos_retardo} minutos` : 'N/A';
    document.getElementById('modalDuracion').textContent = duracion;
    
    // Descripción - para retardo menor/mayor mostrar "No Aplica"
    const tipoIncidencia = validacion.tipo_incidencia;
    const tipoRetraso = detalles?.tipo_retraso;
    let descripcion = validacion.descripcion_incidencia;
    
    // Si es retardo menor o mayor, mostrar "No Aplica"
    if ((tipoIncidencia === 'retardo' || tipoIncidencia === 'comision') && 
        (tipoRetraso === 'retardo_menor' || tipoRetraso === 'retardo_mayor')) {
        descripcion = 'No Aplica';
    }
    
    document.getElementById('modalDescripcion').textContent = descripcion;
    
    // Historial del empleado
    if (data.historial && data.historial.length > 0) {
        const historialHtml = data.historial.map(item => `
            <div class="border-bottom pb-2 mb-2">
                <small>
                    <strong>${formatearFecha(item.fecha_validacion || item.fecha_solicitud)}</strong>
                    <span class="badge ${getEstadoClass(item.estado)} ms-2">
                        ${getEstadoText(item.estado)}
                    </span>
                </small>
                <div class="text-muted">${item.motivo_validacion || item.descripcion_incidencia}</div>
            </div>
        `).join('');
        
        document.getElementById('modalHistorialEmpleado').innerHTML = historialHtml;
    } else {
        document.getElementById('modalHistorialEmpleado').innerHTML = 
            '<p class="text-muted text-center">No hay historial reciente</p>';
    }
    
    // Limpiar formulario de decisión
    document.querySelectorAll('input[name="estadoValidacion"]').forEach(radio => {
        radio.checked = false;
    });
    document.getElementById('motivoRechazo').value = '';
    document.getElementById('comentariosAdicionales').value = '';
    document.getElementById('evidenciaRecibida').checked = false;
    
    // Mostrar/ocultar campos según corresponda
    mostrarOcultarCamposModal();
}

/**
 * Guardar la decisión del modal
 */
async function guardarDecisionModal() {
    const validacionId = document.getElementById('modalValidacionId').value;
    const estadoSeleccionado = document.querySelector('input[name="estadoValidacion"]:checked');
    
    if (!estadoSeleccionado) {
        mostrarError('Debe seleccionar una decisión (aprobar/rechazar/solicitar información)');
        return;
    }
    
    if (estadoSeleccionado.value === 'rechazado' && !document.getElementById('motivoRechazo').value) {
        mostrarError('Debe seleccionar un motivo de rechazo');
        return;
    }
    
    const decision = {
        validacion_id: validacionId,
        estado: estadoSeleccionado.value,
        motivo: document.getElementById('motivoRechazo').value,
        comentarios: document.getElementById('comentariosAdicionales').value,
        evidencia_recibida: document.getElementById('evidenciaRecibida').checked
    };
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/procesar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(decision)
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarExito('Validación procesada exitosamente');
            const modalValidacion = bootstrap.Modal.getInstance(document.getElementById('modalValidacion'));
            if (modalValidacion) modalValidacion.hide();
            cargarValidacionesPendientes(); // Recargar la tabla
            actualizarEstadisticas(); // Actualizar estadísticas
        } else {
            mostrarError('Error procesando validación: ' + data.error);
        }
    } catch (error) {
        console.error('Error procesando validación:', error);
        mostrarError('Error de conexión al procesar la validación');
    }
}

/**
 * Procesar validación masiva
 */
async function procesarMasivo(estado) {
    const seleccionados = Array.from(validacionesSeleccionadas);
    
    if (seleccionados.length === 0) {
        mostrarError('Debe seleccionar al menos una incidencia para procesar');
        return;
    }
    
    const confirmacion = confirm(`¿Está seguro de ${estado} las ${seleccionados.length} incidencias seleccionadas?`);
    if (!confirmacion) return;
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/procesar-validacion-masiva`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                validaciones_ids: seleccionados,
                estado: estado,
                motivo: `Procesamiento masivo - ${estado}`
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarExito(`Se procesaron ${data.procesadas} validaciones exitosamente`);
            if (data.errores.length > 0) {
                mostrarAdvertencia(`Errores: ${data.errores.join(', ')}`);
            }
            cargarValidacionesPendientes();
            actualizarEstadisticas();
            validacionesSeleccionadas.clear();
            actualizarBotonesMasivos();
        } else {
            mostrarError('Error procesando validaciones masivas: ' + data.error);
        }
    } catch (error) {
        console.error('Error procesando validaciones masivas:', error);
        mostrarError('Error de conexión al procesar validaciones');
    }
}

/**
 * Aplicar filtros actuales
 */
function aplicarFiltros() {
    filtrosActuales = {
        area: document.getElementById('filtroArea')?.value,
        empleado: document.getElementById('filtroEmpleado')?.value,
        tipo_incidencia: document.getElementById('filtroTipo')?.value,
        fecha_inicio: document.getElementById('filtroFechaInicio')?.value,
        fecha_fin: document.getElementById('filtroFechaFin')?.value
    };
    
    paginaActual = 1; // Reiniciar a la primera página
    cargarValidacionesPendientes();
}

/**
 * Actualizar estadísticas en tiempo real
 */
async function actualizarEstadisticas() {
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/estadisticas`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const stats = data.estadisticas;
            document.getElementById('statPendientes').textContent = stats.pendientes || 0;
            document.getElementById('statAprobados').textContent = stats.aprobados || 0;
            document.getElementById('statRechazados').textContent = stats.rechazados || 0;
            document.getElementById('statTasaAprobacion').textContent = stats.tasa_aprobacion ? `${stats.tasa_aprobacion}%` : '0%';
        }
    } catch (error) {
        console.error('Error actualizando estadísticas:', error);
    }
}

/**
 * Cargar áreas disponibles
 */
async function cargarAreas() {
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/obtener-areas`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.areas) {
            const select = document.getElementById('filtroArea');
            const currentValue = select.value;
            
            select.innerHTML = '<option value="">Todas las áreas</option>';
            data.areas.forEach(area => {
                select.innerHTML += `<option value="${area}">${area}</option>`;
            });
            
            select.value = currentValue; // Mantener selección actual
        }
    } catch (error) {
        console.error('Error cargando áreas:', error);
    }
}

/**
 * Funciones auxiliares
 */

function formatearFecha(fechaString) {
    if (!fechaString) return 'N/A';
    
    const fecha = new Date(fechaString);
    return fecha.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function getPrioridadClass(prioridad) {
    const clases = {
        1: 'bg-danger',      // Alta
        2: 'bg-warning',    // Media alta
        3: 'bg-info',       // Media
        4: 'bg-secondary',  // Media baja
        5: 'bg-light',      // Baja
        6: 'bg-light'       // Muy baja
    };
    return clases[prioridad] || 'bg-light';
}

function getPrioridadText(prioridad) {
    const textos = {
        1: 'Alta',
        2: 'Media Alta',
        3: 'Media',
        4: 'Media Baja',
        5: 'Baja',
        6: 'Muy Baja'
    };
    return textos[prioridad] || 'Normal';
}

function getEstadoClass(estado) {
    const clases = {
        'pendiente': 'bg-warning',
        'aprobado': 'bg-success',
        'rechazado': 'bg-danger',
        'requiere_info': 'bg-info',
        'no_requiere': 'bg-secondary'
    };
    return clases[estado] || 'bg-light';
}

function getEstadoText(estado) {
    const textos = {
        'pendiente': 'Pendiente',
        'aprobado': 'Aprobado',
        'rechazado': 'Rechazado',
        'requiere_info': 'Requiere Info',
        'no_requiere': 'No Requiere'
    };
    return textos[estado] || 'Desconocido';
}

function getUrgenciaClass(urgencia) {
    const clases = {
        'urgente': 'bg-danger',
        'atencion': 'bg-warning',
        'normal': 'bg-success'
    };
    return clases[urgencia] || 'bg-secondary';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function mostrarCarga(mostrar) {
    const tbody = document.getElementById('tablaValidacionesBody');
    if (!tbody) return;
    
    if (mostrar) {
        tbody.innerHTML = `
            <tr>
                <td colspan="14" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div class="mt-2">Cargando incidencias pendientes...</div>
                </td>
            </tr>
        `;
    }
    // Nota: No limpiamos el contenido aquí, se hará en renderizarTablaValidaciones()
}

function mostrarCargaEmpleados(mostrar) {
    const container = document.getElementById('empleadosContainer');
    if (!container) return;
    
    if (mostrar) {
        container.innerHTML = `
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div class="mt-3">Cargando lista de empleados...</div>
                </div>
            </div>
        `;
    }
    // Nota: No limpiamos el contenido aquí, se hará en renderizarEmpleados()
}

function mostrarExito(mensaje) {
    // Implementar notificación de éxito (puede ser con Bootstrap toast)
    console.log('Éxito:', mensaje);
}

function mostrarError(mensaje) {
    // Implementar notificación de error (puede ser con Bootstrap toast)
    console.error('Error:', mensaje);
}

function mostrarAdvertencia(mensaje) {
    // Implementar notificación de advertencia
    console.warn('Advertencia:', mensaje);
}

function toggleSeleccionTodos() {
    const checkboxes = document.querySelectorAll('.chk-validacion');
    const mainCheckbox = document.getElementById('selectAllMain') || document.getElementById('selectAllCheckbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = mainCheckbox.checked;
    });
    
    actualizarSeleccionados();
}

function actualizarSeleccionados() {
    const checkboxes = document.querySelectorAll('.chk-validacion:checked');
    validacionesSeleccionadas.clear();
    
    checkboxes.forEach(checkbox => {
        validacionesSeleccionadas.add(checkbox.value);
    });
    
    actualizarBotonesMasivos();
}

function actualizarBotonesMasivos() {
    const tieneSeleccionados = validacionesSeleccionadas.size > 0;
    
    document.getElementById('btnAprobarMasivo').disabled = !tieneSeleccionados;
    document.getElementById('btnRechazarMasivo').disabled = !tieneSeleccionados;
    document.getElementById('btnSolicitarInfoMasivo').disabled = !tieneSeleccionados;
}

function actualizarContadores() {
    const total = validacionesPendientes.length;
    document.getElementById('totalCount').textContent = total;
    
    const inicio = (paginaActual - 1) * registrosPorPagina + 1;
    const fin = Math.min(inicio + registrosPorPagina - 1, total);
    
    document.getElementById('registroInicio').textContent = inicio;
    document.getElementById('registroFin').textContent = fin;
    document.getElementById('registroTotal').textContent = total;
}

function mostrarOcultarCamposModal() {
    const estado = document.querySelector('input[name="estadoValidacion"]:checked')?.value;
    const divMotivoRechazo = document.getElementById('divMotivoRechazo');
    const divEvidenciaRecibida = document.getElementById('divEvidenciaRecibida');
    
    if (divMotivoRechazo) {
        divMotivoRechazo.style.display = estado === 'rechazado' ? 'block' : 'none';
    }
    
    if (divEvidenciaRecibida) {
        divEvidenciaRecibida.style.display = estado === 'requiere_info' ? 'block' : 'none';
    }
}

function expandirContraerTodos(expandir) {
    const botones = document.querySelectorAll('.btn-expandir');
    
    botones.forEach(btn => {
        const icono = btn.querySelector('i');
        if (expandir) {
            icono.classList.remove('fa-chevron-down');
            icono.classList.add('fa-chevron-up');
        } else {
            icono.classList.remove('fa-chevron-up');
            icono.classList.add('fa-chevron-down');
        }
    });
}

function toggleDetallesFila(event) {
    const btn = event.currentTarget;
    const icono = btn.querySelector('i');
    const fila = btn.closest('tr');
    
    // Toggle del icono
    if (icono.classList.contains('fa-chevron-down')) {
        icono.classList.remove('fa-chevron-down');
        icono.classList.add('fa-chevron-up');
    } else {
        icono.classList.remove('fa-chevron-up');
        icono.classList.add('fa-chevron-down');
    }
    
    // Toggle de detalles (aquí se puede expandir información adicional de la fila)
}

function renderizarPaginacion(total) {
    const totalPages = Math.ceil(total / registrosPorPagina);
    const paginacion = document.getElementById('paginacion');
    
    if (!paginacion || totalPages <= 1) {
        paginacion.innerHTML = '';
        return;
    }
    
    let html = `
        <li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cambiarPagina(${paginaActual - 1})">Anterior</a>
        </li>
    `;
    
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${i === paginaActual ? 'active' : ''}">
                <a class="page-link" href="#" onclick="cambiarPagina(${i})">${i}</a>
            </li>
        `;
    }
    
    html += `
        <li class="page-item ${paginaActual === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="cambiarPagina(${paginaActual + 1})">Siguiente</a>
        </li>
    `;
    
    paginacion.innerHTML = html;
}

function cambiarPagina(pagina) {
    if (pagina < 1 || pagina > Math.ceil(validacionesPendientes.length / registrosPorPagina)) {
        return;
    }
    
    paginaActual = pagina;
    cargarValidacionesPendientes();
}

function descargarReporte(formato) {
    const url = `${window.BASE_URL}/validaciones/descargar-reporte?formato=${formato}`;
    
    // Agregar filtros actuales a la URL
    const params = new URLSearchParams(filtrosActuales);
    const urlCompleta = `${url}&${params.toString()}`;
    
    // Abrir en una nueva ventana para descargar
    window.open(urlCompleta, '_blank');
}

/**
 * Cargar empleados a cargo del jefe
 */
async function cargarEmpleadosACargo() {
    console.log('🔄 Iniciando carga de empleados...');
    
    try {
        mostrarCargaEmpleados(true);
        
        const url = `${window.BASE_URL}/validaciones/buscar-empleados`;
        console.log('📡 Solicitando:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('📥 Response status:', response.status);
        
        const data = await response.json();
        console.log('📊 Response data:', data);
        
        if (data.success) {
            empleadosACargo = data.empleados || [];
            console.log('✅ Empleados cargados:', empleadosACargo.length);
            renderizarEmpleados();
            actualizarContadoresSeleccion();
        } else {
            console.error('❌ Error en respuesta:', data.error);
            mostrarError('Error cargando empleados: ' + (data.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('❌ Error cargando empleados:', error);
        mostrarError('Error de conexión al cargar empleados');
    } finally {
        mostrarCargaEmpleados(false);
    }
}

/**
 * Renderizar la lista de empleados a cargo
 */
function renderizarEmpleados() {
    const container = document.getElementById('empleadosContainer');
    if (!container) return;
    
    if (empleadosACargo.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-triangle-exclamation me-2"></i>
                    No hay empleados asignados a su cargo
                </div>
            </div>
        `;
        return;
    }
    
    const empleadosHtml = empleadosACargo.map(empleado => {
        const isSelected = empleadosSeleccionados.has(empleado.id.toString());
        const nombreCompleto = empleado.nombre_completo || 'Empleado sin nombre';
        const area = empleado.area || 'N/A';
        
        return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card ${isSelected ? 'border-primary bg-light' : ''}" 
                     data-empleado-id="${empleado.id}" 
                     style="cursor: pointer; transition: all 0.3s ease;"
                     onclick="toggleEmpleadoSeleccion('${empleado.id}')">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" 
                                       id="empleado_${empleado.id}"
                                       value="${empleado.id}"
                                       ${isSelected ? 'checked' : ''}
                                       onclick="event.stopPropagation(); toggleEmpleadoSeleccion('${empleado.id}')">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">${nombreCompleto}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-id-badge me-1"></i>${empleado.id}
                                    <span class="ms-2"><i class="fas fa-building me-1"></i>${area}</span>
                                </small>
                            </div>
                            <i class="${isSelected ? 'fas fa-circle-check text-primary' : 'far fa-circle text-muted'}" 
                               id="icon_${empleado.id}"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = empleadosHtml;
}

/**
 * Toggle selección de empleado
 */
function toggleEmpleadoSeleccion(empleadoId) {
    const idStr = empleadoId.toString();
    const checkbox = document.getElementById(`empleado_${empleadoId}`);
    const card = document.querySelector(`[data-empleado-id="${empleadoId}"]`);
    const icon = document.getElementById(`icon_${empleadoId}`);
    
    if (empleadosSeleccionados.has(idStr)) {
        // Deseleccionar
        empleadosSeleccionados.delete(idStr);
        if (checkbox) checkbox.checked = false;
        if (card) {
            card.classList.remove('border-primary', 'bg-light');
        }
        if (icon) {
            icon.className = 'far fa-circle text-muted';
        }
    } else {
        // Seleccionar
        empleadosSeleccionados.add(idStr);
        if (checkbox) checkbox.checked = true;
        if (card) {
            card.classList.add('border-primary', 'bg-light');
        }
        if (icon) {
            icon.className = 'fas fa-circle-check text-primary';
        }
    }
    
    actualizarContadoresSeleccion();
    
    // Si hay empleados seleccionados, cargar sus incidencias
    if (empleadosSeleccionados.size > 0) {
        cargarIncidenciasEmpleadosSeleccionados();
    } else {
        mostrarMensajeSeleccionEmpleados();
    }
}

/**
 * Seleccionar todos los empleados
 */
function seleccionarTodosEmpleados() {
    if (empleadosACargo.length === 0) {
        mostrarError('No hay empleados cargados. Intente recargar la página.');
        return;
    }
    
    empleadosACargo.forEach(empleado => {
        empleadosSeleccionados.add(empleado.id.toString());
    });
    renderizarEmpleados();
    actualizarContadoresSeleccion();
    cargarIncidenciasEmpleadosSeleccionados();
}

/**
 * Limpiar selección de empleados
 */
function limpiarSeleccionEmpleados() {
    empleadosSeleccionados.clear();
    renderizarEmpleados();
    actualizarContadoresSeleccion();
    mostrarMensajeSeleccionEmpleados();
}

/**
 * Validar empleados seleccionados
 */
function validarEmpleadosSeleccionados() {
    if (empleadosSeleccionados.size === 0) {
        mostrarError('Seleccione al menos un empleado para validar');
        return;
    }
    
    // Enfocar la tabla de validaciones
    document.getElementById('tablaValidaciones')?.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Actualizar contadores de selección
 */
function actualizarContadoresSeleccion() {
    document.getElementById('empleadosSeleccionados').textContent = empleadosSeleccionados.size;
    
    // Habilitar/deshabilitar botón de validar
    const btnValidar = document.getElementById('btnValidarSeleccionados');
    if (btnValidar) {
        btnValidar.disabled = empleadosSeleccionados.size === 0;
    }
}

/**
 * Mostrar mensaje para seleccionar empleados
 */
function mostrarMensajeSeleccionEmpleados() {
    const tbody = document.getElementById('tablaValidacionesBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr>
            <td colspan="14" class="text-center py-4">
                <div class="alert alert-info">
                    <i class="fas fa-circle-info me-2"></i>
                    <strong>Seleccione empleados para ver sus incidencias</strong>
                    <br><small>Use la lista de empleados arriba para seleccionar los colaboradores que desea validar</small>
                </div>
            </td>
        </tr>
    `;
    
    // Resetear contadores de la tabla
    document.getElementById('totalCount').textContent = '0';
    document.getElementById('registroInicio').textContent = '0';
    document.getElementById('registroFin').textContent = '0';
    document.getElementById('registroTotal').textContent = '0';
}

/**
 * Cargar incidencias de empleados seleccionados
 */
async function cargarIncidenciasEmpleadosSeleccionados() {
    if (empleadosSeleccionados.size === 0) return;
    
    try {
        mostrarCarga(true);
        
        const empleadosArray = Array.from(empleadosSeleccionados);
        
        const response = await fetch(`${window.BASE_URL}/validaciones/incidencias-empleados`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                empleados_ids: empleadosArray,
                solo_pendientes: soloPendientes,
                ...filtrosActuales,
                pagina: paginaActual,
                limite: registrosPorPagina,
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            validacionesPendientes = data.incidencias || [];
            renderizarTablaValidaciones(data.incidencias);
            renderizarPaginacion(data.total);
            actualizarContadores();
        } else {
            mostrarError('Error cargando incidencias: ' + data.error);
        }
    } catch (error) {
        console.error('Error cargando incidencias:', error);
        mostrarError('Error de conexión al cargar incidencias');
    } finally {
        mostrarCarga(false);
    }
}

/**
 * Toggle solo pendientes
 */
function toggleSoloPendientes() {
    soloPendientes = document.getElementById('soloPendientes').checked;
    
    if (empleadosSeleccionados.size > 0) {
        cargarIncidenciasEmpleadosSeleccionados();
    }
}

// Exportar funciones globales para que sean accesibles desde el HTML
window.abrirModalValidacion = abrirModalValidacion;
window.cambiarPagina = cambiarPagina;
window.descargarReporte = descargarReporte;
window.toggleEmpleadoSeleccion = toggleEmpleadoSeleccion;
window.verEvidencia = verEvidencia;