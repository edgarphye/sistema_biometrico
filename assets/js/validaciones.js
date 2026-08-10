/**
 * JavaScript para el Sistema de Validación por Jefes
 * Maneja toda la interactividad dinámica del formulario
 */

// Variables globales
let validacionesPendientes = [];
let validacionesAprobadas = [];
let validacionesRechazadas = [];
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
        console.log('✅ Componentes inicializados');
        
        // Mostrar mensaje para seleccionar empleados inicialmente
        mostrarMensajeSeleccionEmpleados();
        
        // Solo cargar empleados a cargo
        cargarEmpleadosACargo().then(() => {
            console.log('✅ Empleados cargados');
            cargarAreas();
            actualizarEstadisticas();
        }).catch(error => {
            console.error('❌ Error cargando empleados:', error);
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
    document.getElementById('btnActualizar')?.addEventListener('click', function() {
        if (empleadosSeleccionados.size > 0) {
            cargarIncidenciasEmpleadosSeleccionados();
        } else {
            // Si no hay empleados seleccionados, mostrar mensaje
            mostrarMensajeSeleccionEmpleados();
        }
    });
    
    // Event listeners para selección de empleados
    document.getElementById('btnValidarSeleccionados')?.addEventListener('click', validarEmpleadosSeleccionados);
    document.getElementById('btnSeleccionarTodosEmpleados')?.addEventListener('click', seleccionarTodosEmpleados);
    document.getElementById('btnLimpiarSeleccion')?.addEventListener('click', limpiarSeleccionEmpleados);
    document.getElementById('soloPendientes')?.addEventListener('change', toggleSoloPendientes);
    
    // Event listeners para selección
    document.getElementById('selectAllIncidencias')?.addEventListener('change', toggleSeleccionTodos);
    document.getElementById('selectAllRetardos')?.addEventListener('change', toggleSeleccionTodos);
    document.getElementById('selectAllAprobados')?.addEventListener('change', toggleSeleccionTodos);
    document.getElementById('selectAllRechazados')?.addEventListener('change', toggleSeleccionTodos);
    document.getElementById('selectAllCheckbox')?.addEventListener('change', toggleSeleccionTodos);
    
    // Event listeners para modal
    document.getElementById('btnGuardarDecision')?.addEventListener('click', guardarDecisionModal);
    
    // Event delegation para botones de validación en tablas dinámicas (aprobados/rechazados)
    document.getElementById('tablaAprobadosBody')?.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-validar');
        if (btn) {
            console.log('✅ Delegación: clic en btn-validar de Aprobados', btn.dataset);
            abrirModalValidacion({ currentTarget: btn });
        }
    });
    document.getElementById('tablaRechazadosBody')?.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-validar');
        if (btn) {
            console.log('✅ Delegación: clic en btn-validar de Rechazados', btn.dataset);
            abrirModalValidacion({ currentTarget: btn });
        }
    });
    
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
        
        const data = await response.json();
        
        if (data.success) {
            validacionesPendientes = data.validaciones;
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
    // Obtener los cuerpos de ambas tablas
    const tbodyIncidencias = document.getElementById('tablaIncidenciasBody');
    const tbodyRetardos = document.getElementById('tablaRetardosBody');
    
    console.log('🔍 Renderizando tabla con', validaciones.length, 'registros');
    
    if (!tbodyIncidencias && !tbodyRetardos) {
        console.log('❌ No encontré tbody de las tablas');
        return;
    }
    
    // Pestaña "Incidencias" = TODOS EXCEPTO retardos
    // Pestaña "Retardos" = solo los que contienen "retardo"
    const incidencias = validaciones.filter(v => {
        const tipo = v.tipo_incidencia || '';
        return tipo.includes('retardo') === false;
    });
    
    const retardos = validaciones.filter(v => {
        const tipo = v.tipo_incidencia || '';
        return tipo.includes('retardo') === true;
    });
    
    console.log('📊 Incidencias (Comisiones/Días/Ausencias):', incidencias.length, 'Retardos:', retardos.length);
    
    // Actualizar contadores principales en las pestañas
    const countIncidenciasEl = document.getElementById('countIncidencias');
    const countRetardosEl = document.getElementById('countRetardos');
    if (countIncidenciasEl) countIncidenciasEl.textContent = incidencias.length;
    if (countRetardosEl) countRetardosEl.textContent = retardos.length;
    
    // Contadores por tipo de INCIDENCIAS
    const contadoresIncidencias = {
        comision_entrada: 0,
        comision_salida: 0,
        comision_todo_dia: 0,
        dia_economico: 0,
        ausencia: 0,
        licencia_medica: 0,
        vacaciones: 0,
        otros: 0
    };
    
    incidencias.forEach(inc => {
        const tipo = inc.tipo_incidencia || '';
        if (tipo.includes('comision_entrada')) contadoresIncidencias.comision_entrada++;
        else if (tipo.includes('comision_salida')) contadoresIncidencias.comision_salida++;
        else if (tipo.includes('comision_todo_dia')) contadoresIncidencias.comision_todo_dia++;
        else if (tipo.includes('dia_economico')) contadoresIncidencias.dia_economico++;
        else if (tipo.includes('ausencia')) contadoresIncidencias.ausencia++;
        else if (tipo.includes('licencia_medica')) contadoresIncidencias.licencia_medica++;
        else if (tipo.includes('vacaciones')) contadoresIncidencias.vacaciones++;
        else contadoresIncidencias.otros++;
    });
    
    // Actualizar contadores de incidencias en el DOM
    if (document.getElementById('countComisionEntrada')) document.getElementById('countComisionEntrada').textContent = contadoresIncidencias.comision_entrada;
    if (document.getElementById('countComisionSalida')) document.getElementById('countComisionSalida').textContent = contadoresIncidencias.comision_salida;
    if (document.getElementById('countComisionDia')) document.getElementById('countComisionDia').textContent = contadoresIncidencias.comision_todo_dia;
    if (document.getElementById('countDiaEconomico')) document.getElementById('countDiaEconomico').textContent = contadoresIncidencias.dia_economico;
    if (document.getElementById('countAusencia')) document.getElementById('countAusencia').textContent = contadoresIncidencias.ausencia;
    if (document.getElementById('countLicencia')) document.getElementById('countLicencia').textContent = contadoresIncidencias.licencia_medica;
    if (document.getElementById('countVacaciones')) document.getElementById('countVacaciones').textContent = contadoresIncidencias.vacaciones;
    if (document.getElementById('countOtrosIncidencia')) document.getElementById('countOtrosIncidencia').textContent = contadoresIncidencias.otros;
    
    // Contadores por tipo de RETARDOS
    let retardoMenor = 0;
    let retardoMayor = 0;
    
    retardos.forEach(ret => {
        const tipo = ret.tipo_incidencia || '';
        if (tipo === 'retardo_menor') retardoMenor++;
        else if (tipo === 'retardo_mayor') retardoMayor++;
    });
    
    // Actualizar contadores de retardos en el DOM
    if (document.getElementById('countRetardoMenor')) document.getElementById('countRetardoMenor').textContent = retardoMenor;
    if (document.getElementById('countRetardoMayor')) document.getElementById('countRetardoMayor').textContent = retardoMayor;
    
    // Guardar datos para usar en los modales de resumen
    window.datosResumenIncidencias = { contadores: contadoresIncidencias, total: incidencias.length };
    window.datosResumenRetardos = { menor: retardoMenor, mayor: retardoMayor, total: retardos.length };
    
    // Renderizar tabla de Incidencias (TODOS excepto retardos)
    if (tbodyIncidencias) {
        if (incidencias.length === 0) {
            tbodyIncidencias.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center py-4">
                        <i class="fas fa-circle-check text-success fa-3x mb-3"></i>
                        <h5>No hay incidencias por validar</h5>
                        <p class="text-muted">No hay justificaciones pendientes (comisiones, días económicos, ausencias, etc.)</p>
                    </td>
                </tr>
            `;
        } else {
            tbodyIncidencias.innerHTML = incidencias.map((validacion, index) => {
                return renderizarFilaValidacion(validacion, 'incidencia');
            }).join('');
        }
    }
    
    // Renderizar tabla de Retardos
    if (tbodyRetardos) {
        if (retardos.length === 0) {
            tbodyRetardos.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center py-4">
                        <i class="fas fa-circle-check text-success fa-3x mb-3"></i>
                        <h5>No hay retardos pendientes</h5>
                        <p class="text-muted">No hay retardos por validar.</p>
                    </td>
                </tr>
            `;
        } else {
            tbodyRetardos.innerHTML = retardos.map((validacion, index) => {
                return renderizarFilaRetardo(validacion);
            }).join('');
        }
    }
    
    // Adjuntar eventos a los checkboxes
    adjuntarEventosCheckboxes();
}

/**
 * Renderizar fila de incidencia
 */
function renderizarFilaValidacion(validacion, tipo) {
    const btnId = String(validacion.id);
    const btnTipo = String(validacion.tipo_incidencia);
    
    const nombreCompleto = validacion.nombre_completo || 
                          ((validacion.empleado_nombre || '') + ' ' + (validacion.empleado_apellido || '')).trim() || 
                          'Sin nombre';
    
    const fechaFormateada = formatearFecha(validacion.fecha_incidencia);
    const prioridadClass = getPrioridadClass(validacion.prioridad_atencion);
    const urgenciaClass = getUrgenciaClass(validacion.nivel_urgencia);
    const estadoClass = getEstadoClass(validacion.estado_validacion_jefe);
    
    // Combinar área y área física
    const areaCompleta = validacion.empleado_area || 'N/A';
    const areaFisica = validacion.empleado_area_fisica || '';
    const areaMostrar = areaFisica ? `${areaCompleta} - ${areaFisica}` : areaCompleta;
    
    const tiposIncidencia = {
        'comision_entrada': 'Comisión de Entrada',
        'comision_salida': 'Comisión de Salida',
        'comision_todo_dia': 'Comisión Todo el Día',
        'dia_economico': 'Día Económico',
        'ausencia': 'Ausencia',
        'licencia_medica': 'Licencia Médica',
        'vacaciones': 'Vacaciones',
        'con_retardo': 'Entrada con Retardo',
        'con_ausencia': 'Ausencia Registrada',
        'sin_registro': 'Sin Registro',
        'cuidados_parentales': 'Cuidados Parentales',
        'falta': 'Falta',
        'comision': 'Comisión'
    };
    
    const getTipoLabel = (tipo) => {
        if (!tipo) return 'Otro';
        for (const [key, value] of Object.entries(tiposIncidencia)) {
            if (tipo.includes(key)) return value;
        }
        return tipo.charAt(0).toUpperCase() + tipo.slice(1);
    };
    
    // Para tablas de aprobados/rechazados, mostrar motivo de validación
    const motivoValidacion = validacion.motivo_validacion || validacion.comentarios_adicionales || '-';
    
    // Renderizar según el tipo de tabla
    if (tipo === 'aprobado') {
        return `
            <tr class="fila-validacion" data-id="${validacion.id}" data-incidencia-id="${validacion.incidencia_id}" data-origen="${validacion.origen || 'retardos'}">
                <td>
                    <input type="checkbox" class="form-check-input chk-validacion" 
                           value="${validacion.incidencia_id || validacion.id}" 
                           data-tipo="${validacion.tipo_incidencia}"
                           data-origen="${validacion.origen || 'retardos'}">
                </td>
                <td>
                    <span class="badge" style="background: var(--pantone-secondary, #235B4E); color: white;">${validacion.empleado_id}</span>
                </td>
                <td>
                    <strong>${nombreCompleto}</strong>
                </td>
                <td style="min-width: 130px;">
                    <span class="badge" style="background: var(--pantone-secondary, #235B4E); color: white; font-size: 0.7rem;">
                        ${truncarTexto(validacion.empleado_area || 'N/A', 20)}
                    </span>
                </td>
                <td>
                    ${fechaFormateada}
                </td>
                <td>
                    ${(() => {
                        const diasingles = { 'Monday': 'Lun', 'Tuesday': 'Mar', 'Wednesday': 'Mié', 'Thursday': 'Jue', 'Friday': 'Vie', 'Saturday': 'Sáb', 'Sunday': 'Dom' };
                        const dia = validacion.dia_semana ? (diasingles[validacion.dia_semana] || validacion.dia_semana.charAt(0).toUpperCase() + validacion.dia_semana.slice(1)) : 'N/A';
                        return `<span class="badge bg-secondary">${dia}</span>`;
                    })()}
                </td>
                <td>
                    <span class="badge" style="background: var(--pantone-accent-dark, #BC955C); color: white;">${getTipoLabel(validacion.tipo_incidencia)}</span>
                </td>
                <td>
                    ${validacion.evidencia_adjunta ? '<i class="fas fa-paperclip text-success"></i>' : '<i class="fas fa-minus text-muted"></i>'}
                </td>
                <td>
                    <small class="text-success" title="${motivoValidacion}">${truncarTexto(motivoValidacion, 30)}</small>
                </td>
                <td>
                    <span class="badge bg-success">Aprobado</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-validar" 
                                data-id="${btnId}" 
                                data-tipo="${btnTipo}"
                                data-origen="${validacion.origen || 'retardos'}"
                                title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    if (tipo === 'rechazado') {
        return `
            <tr class="fila-validacion" data-id="${validacion.id}" data-incidencia-id="${validacion.incidencia_id}" data-origen="${validacion.origen || 'retardos'}">
                <td>
                    <input type="checkbox" class="form-check-input chk-validacion" 
                           value="${validacion.incidencia_id || validacion.id}" 
                           data-tipo="${validacion.tipo_incidencia}"
                           data-origen="${validacion.origen || 'retardos'}">
                </td>
                <td>
                    <span class="badge" style="background: var(--pantone-danger, #691C32); color: white;">${validacion.empleado_id}</span>
                </td>
                <td>
                    <strong>${nombreCompleto}</strong>
                </td>
                <td style="min-width: 130px;">
                    <span class="badge" style="background: var(--pantone-secondary, #235B4E); color: white; font-size: 0.7rem;">
                        ${truncarTexto(validacion.empleado_area || 'N/A', 20)}
                    </span>
                </td>
                <td>
                    ${fechaFormateada}
                </td>
                <td>
                    ${(() => {
                        const diasingles = { 'Monday': 'Lun', 'Tuesday': 'Mar', 'Wednesday': 'Mié', 'Thursday': 'Jue', 'Friday': 'Vie', 'Saturday': 'Sáb', 'Sunday': 'Dom' };
                        const dia = validacion.dia_semana ? (diasingles[validacion.dia_semana] || validacion.dia_semana.charAt(0).toUpperCase() + validacion.dia_semana.slice(1)) : 'N/A';
                        return `<span class="badge bg-secondary">${dia}</span>`;
                    })()}
                </td>
                <td>
                    <span class="badge" style="background: var(--pantone-accent-dark, #BC955C); color: white;">${getTipoLabel(validacion.tipo_incidencia)}</span>
                </td>
                <td>
                    ${validacion.evidencia_adjunta ? '<i class="fas fa-paperclip text-success"></i>' : '<i class="fas fa-minus text-muted"></i>'}
                </td>
                <td>
                    <small class="text-danger" title="${motivoValidacion}">${truncarTexto(motivoValidacion, 30)}</small>
                </td>
                <td>
                    <span class="badge bg-danger">Rechazado</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-validar" 
                                data-id="${btnId}" 
                                data-tipo="${btnTipo}"
                                data-origen="${validacion.origen || 'retardos'}"
                                title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Renderizado original para pendientes (incidencia)
    return `
        <tr class="fila-validacion" data-id="${validacion.id}" data-incidencia-id="${validacion.incidencia_id}" data-origen="${validacion.origen || 'retardos'}">
            <td>
                <input type="checkbox" class="form-check-input chk-validacion" 
                       value="${validacion.incidencia_id || validacion.id}" 
                       data-tipo="${validacion.tipo_incidencia}"
                       data-origen="${validacion.origen || 'retardos'}">
            </td>
            <td>
                <span class="badge" style="background: var(--pantone-primary, #9F2241); color: white;">${validacion.empleado_id}</span>
            </td>
            <td>
                <strong>${nombreCompleto}</strong>
            </td>
            <td style="min-width: 130px;">
                <div title="${areaMostrar}">
                    <span class="badge" style="background: var(--pantone-secondary, #235B4E); color: white; font-size: 0.7rem; white-space: normal; text-align: center;">
                        ${truncarTexto(validacion.empleado_area || 'N/A', 20)}
                    </span>
                    ${areaFisica ? `<br><small class="text-muted" style="font-size: 0.65rem;">${truncarTexto(areaFisica, 18)}</small>` : ''}
                </div>
            </td>
            <td>
                ${fechaFormateada}
            </td>
            <td>
                ${(() => {
                    const diasingles = { 'Monday': 'Lun', 'Tuesday': 'Mar', 'Wednesday': 'Mié', 'Thursday': 'Jue', 'Friday': 'Vie', 'Saturday': 'Sáb', 'Sunday': 'Dom' };
                    const dia = validacion.dia_semana ? (diasingles[validacion.dia_semana] || validacion.dia_semana.charAt(0).toUpperCase() + validacion.dia_semana.slice(1)) : 'N/A';
                    return `<span class="badge bg-secondary">${dia}</span>`;
                })()}
            </td>
            <td>
                <span class="badge" style="background: var(--pantone-accent-dark, #BC955C); color: white;">${getTipoLabel(validacion.tipo_incidencia)}</span>
            </td>
            <td>
                ${validacion.minutos_retardo && validacion.minutos_retardo > 0 ? `${validacion.minutos_retardo} min` : '-'}
            </td>
            <td>
                <small>${validacion.hora_entrada ? validacion.hora_entrada.substring(0,5) : 'N/A'} - ${validacion.hora_salida ? validacion.hora_salida.substring(0,5) : 'N/A'}</small>
            </td>
            <td>
                ${validacion.tipo_incidencia && validacion.tipo_incidencia.includes('comision') ? 
                    '<i class="fas fa-comment-dots text-info" title="Solo requiere motivo/lugar"></i>' :
                    (validacion.requiere_validacion_jefe ? 
                        '<i class="fas fa-paperclip text-warning" title="Requiere evidencia">' : 
                        '<i class="fas fa-check text-success" title="Sin evidencia requerida">')}
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
                            data-id="${btnId}" 
                            data-tipo="${btnTipo}"
                            data-origen="${validacion.origen || 'retardos'}"
                            title="Validar">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

/**
 * Renderizar fila de retardo
 */
function renderizarFilaRetardo(validacion) {
    const btnId = String(validacion.id);
    const btnTipo = 'retardo';
    const sinSolicitud = validacion.tipo_sin_solicitud === true || validacion.sin_solicitud === 1 || validacion.estado_validacion_jefe === 'sin_solicitud';
    
    const nombreCompleto = validacion.nombre_completo || 
                          ((validacion.empleado_nombre || '') + ' ' + (validacion.empleado_apellido || '')).trim() || 
                          'Sin nombre';
    
    const fechaFormateada = formatearFecha(validacion.fecha_incidencia);
    const prioridadClass = getPrioridadClass(validacion.prioridad_atencion);
    const estadoClass = getEstadoClass(validacion.estado_validacion_jefe);
    
    // Estilo especial para incidencias sin solicitud
    const rowClass = sinSolicitud ? 'fila-validacion fila-sin-solicitud' : 'fila-validacion';
    const rowStyle = sinSolicitud ? 'background: linear-gradient(90deg, rgba(255,193,7,0.15) 0%, rgba(255,193,7,0.05) 100%); border-left: 4px solid #FFC107;' : '';
    
    // Combinar área y área física
    const areaCompleta = validacion.empleado_area || 'N/A';
    const areaFisica = validacion.empleado_area_fisica || '';
    const areaMostrar = areaFisica ? `${areaCompleta} - ${areaFisica}` : areaCompleta;
    
    // Descripción diferente si es sin solicitud
    const descripcionMostrar = sinSolicitud 
        ? '<span class="text-warning fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Sin solicitud - El empleado NO ha justificado</span>'
        : (validacion.descripcion_incidencia || 'Sin motivo');
    
    // Tipo label diferente para sin solicitud
    const tipoLabel = sinSolicitud 
        ? '<span class="badge bg-warning text-dark"><i class="fas fa-question-circle me-1"></i>Sin Justificar</span>'
        : '<span class="badge bg-primary" title="Hora de entrada">' + (validacion.hora_entrada ? validacion.hora_entrada.substring(0,5) : 'N/A') + '</span>';
    
    // Estado diferente para sin solicitud
    const estadoLabel = sinSolicitud
        ? '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Sin Solicitud</span>'
        : getEstadoText(validacion.estado_validacion_jefe);
    
    return `
        <tr class="${rowClass}" data-id="${validacion.id}" data-incidencia-id="${validacion.incidencia_id}" ${rowStyle ? `style="${rowStyle}"` : ''}>
            <td>
                <input type="checkbox" class="form-check-input chk-validacion" 
                       value="${validacion.incidencia_id}" 
                       data-tipo="retardo"
                       data-sin-solicitud="${sinSolicitud ? '1' : '0'}">
            </td>
            <td>
                <span class="badge" style="background: var(--pantone-primary, #9F2241); color: white;">${validacion.empleado_id}</span>
            </td>
            <td>
                <strong>${nombreCompleto}</strong>
                ${sinSolicitud ? '<br><small class="text-warning"><i class="fas fa-bell"></i> Pendiente de justificación</small>' : ''}
            </td>
            <td style="min-width: 130px;">
                <div class="area-cell" title="${areaMostrar}">
                    <span class="badge" style="background: var(--pantone-secondary, #235B4E); color: white; font-size: 0.7rem; white-space: normal; text-align: center;">
                        ${truncarTexto(validacion.empleado_area || 'N/A', 20)}
                    </span>
                    ${areaFisica ? `<br><small class="text-muted" style="font-size: 0.65rem;">${truncarTexto(areaFisica, 18)}</small>` : ''}
                </div>
            </td>
            <td>
                ${fechaFormateada}
            </td>
            <td>
                ${(() => {
                    const diasingles = { 'Monday': 'Lun', 'Tuesday': 'Mar', 'Wednesday': 'Mié', 'Thursday': 'Jue', 'Friday': 'Vie', 'Saturday': 'Sáb', 'Sunday': 'Dom' };
                    const dia = validacion.dia_semana ? (diasingles[validacion.dia_semana] || validacion.dia_semana.charAt(0).toUpperCase() + validacion.dia_semana.slice(1)) : 'N/A';
                    return `<span class="badge bg-secondary">${dia}</span>`;
                })()}
            </td>
            <td>
                ${tipoLabel}
            </td>
            <td>
                <span class="badge ${validacion.minutos_retardo > 10 ? 'bg-danger' : 'bg-warning'}">
                    ${validacion.minutos_retardo || 0} min
                </span>
            </td>
            <td>
                <small>
                    ${validacion.horario_entrada ? 
                        `<span class="text-success">${validacion.horario_entrada.substring(0,5)}</span>` : 
                        '---'}
                    ${validacion.horario_salida ? 
                        ` - <span class="text-success">${validacion.horario_salida.substring(0,5)}</span>` : 
                        ''}
                    <br>
                    <span class="text-muted">
                        Real: ${validacion.hora_salida ? validacion.hora_salida.substring(0,5) : 'N/A'}
                    </span>
                </small>
            </td>
            <td>
                <small>${descripcionMostrar}</small>
            </td>
            <td>
                <span class="badge ${prioridadClass}">${getPrioridadText(validacion.prioridad_atencion)}</span>
            </td>
            <td>
                <span class="badge ${estadoClass}">${estadoLabel}</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn ${sinSolicitud ? 'btn-warning' : 'btn-outline-primary'} btn-validar" 
                            data-id="${btnId}" 
                            data-tipo="${btnTipo}"
                            data-origen="retardos"
                            data-sin-solicitud="${sinSolicitud ? '1' : '0'}"
                            title="${sinSolicitud ? 'Notificar al empleado' : 'Validar'}">
                        <i class="fas ${sinSolicitud ? 'fa-comment' : 'fa-eye'}"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

/**
 * Adjuntar eventos a checkboxes y botones de validación
 */
function adjuntarEventosCheckboxes() {
    // Checkbox principal de Incidencias
    document.getElementById('selectAllIncidencias')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#tablaIncidenciasBody .chk-validacion');
        checkboxes.forEach(cb => cb.checked = this.checked);
        actualizarBotonesMasivos();
    });
    
    // Checkbox principal de Retardos
    document.getElementById('selectAllRetardos')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#tablaRetardosBody .chk-validacion');
        checkboxes.forEach(cb => cb.checked = this.checked);
        actualizarBotonesMasivos();
    });
    
    // Eventos change en checkboxes individuales
    document.querySelectorAll('.chk-validacion').forEach(cb => {
        cb.addEventListener('change', actualizarBotonesMasivos);
    });
    
    // Event listeners para botones de validación (ícono de ojo)
    document.querySelectorAll('.btn-validar').forEach(btn => {
        console.log('🔗 Adjuntando evento click a botón:', btn.dataset.id);
        btn.addEventListener('click', function(e) {
            console.log('✅ Click detectado en botón validar');
            abrirModalValidacion(e);
        });
    });
}

/**
 * Inicializar eventos de la UI
 */
function inicializarEventosUI() {
    // Los eventos ya se adjuntan en adjuntarEventosCheckboxes()
    // que se llama después de renderizar las tablas
}

/**
 * Abrir modal de validación desde HTML onclick
 */
async function abrirModalValidacionDesdeHTML(validacionId, tipoIncidencia) {
    console.log('🔍 DesdeHTML - validacionId:', validacionId, 'tipo:', tipoIncidencia);
    
    if (!validacionId || validacionId === 'undefined' || validacionId === '') {
        alert('Error: ID de validación no encontrado. ID=' + validacionId);
        return;
    }
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/validar-retardo/${validacionId}`, {
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
            mostrarError(data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    }
}

/**
 * Abrir modal de validación directamente (sin evento)
 */
async function abrirModalValidacionDirecto(validacionId, tipoIncidencia) {
    console.log('🔍 Abriendo modal - validacionId:', validacionId, 'tipo:', tipoIncidencia);
    
    if (!validacionId || validacionId === 'undefined') {
        alert('Error: ID de validación no encontrado');
        return;
    }
    
    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/validar-retardo/${validacionId}`, {
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
 * Abrir modal de validación para una incidencia específica (versión con evento)
 */
async function abrirModalValidacion(event) {
    const btn = event.currentTarget;
    const validacionId = btn.dataset.id || btn.getAttribute('data-id');
    const tipoIncidencia = btn.dataset.tipo || btn.getAttribute('data-tipo');
    const origen = btn.dataset.origen || 'retardos';
    
    console.log('🔍 Abriendo modal - validacionId:', validacionId, 'tipo:', tipoIncidencia, 'origen:', origen);
    console.log('🔍 Button element:', btn);
    console.log('🔍 BASE_URL:', window.BASE_URL);
    
    if (!validacionId || validacionId === 'undefined' || validacionId === '') {
        console.error('❌ ID de validación inválido');
        alert('Error: ID de validación no encontrado. ID=' + validacionId);
        return;
    }
    
    try {
        const url = `${window.BASE_URL}/validaciones/obtener-incidencia/${validacionId}?origen=${origen}`;
        console.log('📡 Fetch URL:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('📥 Response status:', response.status);
        console.log('📥 Response ok:', response.ok);
        
        const data = await response.json();
        console.log('📥 Response data:', data);
        
        if (data.success) {
            console.log('✅ Llenando modal con datos...');
            llenarModalValidacion(data);
            const modalElement = document.getElementById('modalValidacion');
            console.log('📋 Modal element:', modalElement);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('✅ Modal abierto');
        } else {
            console.error('❌ Error en respuesta:', data.error);
            mostrarError('Error cargando detalles de la validación: ' + data.error);
        }
    } catch (error) {
        console.error('Error cargando validación:', error);
        mostrarError('Error de conexión al cargar detalles');
    }
}

/**
 * Llenar el modal con los datos de la validación
 */
function llenarModalValidacion(data) {
    const validacion = data.validacion;
    const detalles = data.detalles;
    
    // Guardar datos de validación globalmente para usar en guardarDecisionModal
    window.modalValidacionData = validacion;
    
    const nombreCompleto = validacion.nombre_completo || 
                          ((validacion.empleado_nombre || '') + ' ' + (validacion.empleado_apellido || '')).trim() || 
                          'Sin nombre';
    
    // Verificar si es una incidencia sin solicitud
    const esSinSolicitud = validacion.tipo_sin_solicitud === true || 
                          validacion.sin_solicitud === 1 || 
                          validacion.estado_validacion_jefe === 'sin_solicitud' ||
                          validacion.estado === 'sin_solicitud';
    
    // Guardar el flag de sin solicitud globalmente
    window.esIncidenciaSinSolicitud = esSinSolicitud;
    
    // Información básica - usar validacion.id que es el ID de la tabla de validaciones
    const idValidacion = validacion.id || validacion.incidencia_id || 0;
    document.getElementById('modalValidacionId').value = idValidacion;
    document.getElementById('modalEmpleadoInfo').textContent = nombreCompleto;
    document.getElementById('modalEmpleadoId').textContent = `ID: ${validacion.empleado_id || 'N/A'}`;
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
    
    // Tipo de Incidencia - Mostrar catálogo para selección
    let tipoRetraso = detalles?.tipo_retraso || validacion.tipo_incidencia || '';
    
    // Normalizar: validaciones_jefe usa valores planos (comision, retardo, justificacion, ausencia)
    // mientras que el <select> usa subtipos (comision_entrada, retardo_menor, etc.)
    const tipoNormalized = {
        'retardo': 'retardo_menor',
        'comision': 'comision_entrada',
        'justificacion': 'ausencia',
    };
    tipoRetraso = tipoNormalized[tipoRetraso] || tipoRetraso;
    
    // Si es sin solicitud, mostrar mensaje especial en lugar del select
    if (esSinSolicitud) {
        document.getElementById('modalDescripcion').innerHTML = `
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Sin Solicitud de Justificación</h5>
                <p class="mb-0">El empleado <strong>${nombreCompleto}</strong> No ha realizado ninguna solicitud de justificación para este retardo.</p>
                <hr>
                <p class="mb-0"><strong>Recomendación:</strong> Contacte al empleado para que justifique su retraso o se le aplique la falta correspondiente.</p>
            </div>
        `;
    } else {
        const tiposOpciones = [
            { val: 'retardo_menor', text: 'Retardo Menor (≤15 min)' },
            { val: 'retardo_mayor', text: 'Retardo Mayor (>15 min)' },
            { val: 'comision_entrada', text: 'Comisión de Entrada' },
            { val: 'comision_salida', text: 'Comisión de Salida' },
            { val: 'comision_todo_dia', text: 'Comisión Día Completo' },
            { val: 'dia_economico', text: 'Día Económico' },
            { val: 'ausencia', text: 'Ausencia' },
            { val: 'licencia_medica', text: 'Licencia Médica' },
            { val: 'vacaciones', text: 'Vacaciones' }
        ];
        
        let selectHtml = `<select class="form-select" id="selectTipoIncidencia">`;
        tiposOpciones.forEach(opt => {
            let selected = (opt.val === tipoRetraso) ? 'selected' : '';
            selectHtml += `<option value="${opt.val}" ${selected}>${opt.text}</option>`;
        });
        selectHtml += `</select>`;
        
        document.getElementById('modalDescripcion').innerHTML = selectHtml;
    }
    
    // Ocultar campo motivo adicional ya que la descripción ya lo incluye
    const rowMotivoComision = document.getElementById('rowMotivoComision');
    if (rowMotivoComision) {
        rowMotivoComision.style.display = 'none';
    }
    
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
    
    // Mostrar evidencia adjunta
    const evidenciaContainer = document.getElementById('modalEvidenciaContainer');
    if (evidenciaContainer) {
        if (detalles && detalles.evidencia_adjunta) {
            // Asumiendo que la evidencia está en una ruta pública.
            // La ruta base '/uploads/evidencias/' puede necesitar ajuste.
            const evidenciaUrl = `${window.BASE_URL}/uploads/evidencias/${detalles.evidencia_adjunta}`;
            evidenciaContainer.innerHTML = `
                <h6 class="fw-bold">Evidencia Adjunta</h6>
                <a href="${evidenciaUrl}" target="_blank" class="btn btn-outline-info mt-2">
                    <i class="fas fa-paperclip me-2"></i>Ver Documento de Evidencia
                </a>
                <p class="text-muted small mt-1">El archivo se abrirá en una nueva pestaña.</p>
            `;
        } else {
            evidenciaContainer.innerHTML = `
                <h6 class="fw-bold">Evidencia Adjunta</h6>
                <p class="text-muted">No se adjuntó evidencia para esta incidencia.</p>
            `;
        }
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

    // Cargar conversación
    cargarConversacionJefe(validacion);
}

/**
 * Cargar conversación en el modal del jefe
 */
async function cargarConversacionJefe(validacion) {
    const idValidacion = validacion.id || validacion.incidencia_id || 0;
    const cardConv = document.getElementById('cardConversacionJefe');
    const container = document.getElementById('conversacionJefe');
    const badge = document.getElementById('mensajesCountBadge');

    if (!idValidacion || !cardConv || !container) return;

    try {
        const response = await fetch(`${window.BASE_URL}/validaciones/mensajes/${idValidacion}`);
        const result = await response.json();

        if (result.success && result.mensajes && result.mensajes.length > 0) {
            cardConv.style.display = 'block';
            badge.textContent = result.mensajes.length;

            container.innerHTML = result.mensajes.map(msg => {
                const esJefe = msg.remitente_tipo === 'jefe';
                const esSistema = msg.remitente_tipo === 'sistema';
                const avatar = esJefe ? 'fa-user-tie' : (esSistema ? 'fa-robot' : 'fa-user');
                const color = esJefe ? '#9F2241' : (esSistema ? '#6c757d' : '#235B4E');
                const alineacion = esJefe ? '' : 'flex-row-reverse';
                const bgColor = esJefe ? '#f8f0f2' : (esSistema ? '#f8f9fa' : '#f0f4f3');

                return `
                    <div class="d-flex ${alineacion} align-items-start gap-2 mb-3">
                        <div class="rounded-circle p-2" style="background: ${color}20;">
                            <i class="fas ${avatar}" style="color: ${color};"></i>
                        </div>
                        <div class="rounded p-3 flex-fill" style="background: ${bgColor}; max-width: 80%;">
                            <small class="fw-bold" style="color: ${color};">${msg.remitente_nombre || msg.remitente_tipo}</small>
                            <p class="mb-1">${escapeHtml(msg.mensaje || '')}</p>
                            ${msg.tiene_adjuntos ? '<small class="text-muted"><i class="fas fa-paperclip me-1"></i>Tiene adjunto(s)</small>' : ''}
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">${formatearFechaHora(msg.created_at)}</small>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            cardConv.style.display = 'none';
        }
    } catch (e) {
        console.warn('Error al cargar conversación:', e);
        cardConv.style.display = 'none';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Guardar la decisión del modal
 */
async function guardarDecisionModal() {
    const validacionId = document.getElementById('modalValidacionId').value;
    const estadoSeleccionado = document.querySelector('input[name="estadoValidacion"]:checked');
    const esSinSolicitud = window.esIncidenciaSinSolicitud || false;
    
    if (!estadoSeleccionado) {
        mostrarError('Debe seleccionar una decisión (aprobar/rechazar/solicitar información)');
        return;
    }
    
    if (estadoSeleccionado.value === 'rechazado' && !document.getElementById('motivoRechazo').value) {
        mostrarError('Debe seleccionar un motivo de rechazo');
        return;
    }
    
    // Para incidencias sin solicitud, verificar que haya un comentario
    if (esSinSolicitud && !document.getElementById('comentariosAdicionales').value.trim()) {
        mostrarError('Debe escribir un comentario para notificar al empleado');
        return;
    }
    
    const selectTipoIncidencia = document.getElementById('selectTipoIncidencia');
    const nuevoTipo = selectTipoIncidencia ? selectTipoIncidencia.value : null;
    
    const validacion = window.modalValidacionData || {};
    
    // Para incidencias sin solicitud, el estado será 'requiere_info' para notificar al empleado
    let estadoFinal = estadoSeleccionado.value;
    if (esSinSolicitud && estadoSeleccionado.value === 'requiere_info') {
        estadoFinal = 'notificar_empleado';
    }
    
    const decision = {
        validacion_id: validacionId,
        incidencia_id: validacion.incidencia_id || null,
        tipo_incidencia: validacion.tipo_incidencia || null,
        empleado_id: validacion.empleado_id || null,
        estado: estadoFinal,
        motivo: document.getElementById('motivoRechazo').value,
        comentarios: document.getElementById('comentariosAdicionales').value,
        evidencia_recibida: document.getElementById('evidenciaRecibida').checked,
        nuevo_tipo_incidencia: nuevoTipo,
        es_sin_solicitud: esSinSolicitud
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
            cargarValidacionesPendientes(); // Recargar pendientes
            cargarAprobadosYRechazados(); // Recargar aprobados y rechazados
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
        const response = await fetch(`${window.BASE_URL}/validaciones/procesar-masivo`, {
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
            cargarAprobadosYRechazados();
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

function truncarTexto(texto, maxLongitud) {
    if (!texto) return '';
    if (texto.length <= maxLongitud) return texto;
    return texto.substring(0, maxLongitud - 3) + '...';
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

function formatearFechaHora(fechaString) {
    if (!fechaString) return 'N/A';
    const fecha = new Date(fechaString);
    return fecha.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getPrioridadClass(prioridad) {
    const clases = {
        1: 'badge badge-estado badge-estado-rechazado',      // Alta
        2: 'badge badge-estado badge-estado-pendiente',     // Media alta
        3: 'badge badge-estado badge-estado-info',          // Media
        4: 'badge badge-estado badge-estado-aprobado',     // Media baja
        5: 'badge badge-estado badge-estado-aprobado',    // Baja
        6: 'badge badge-estado badge-estado-aprobado'     // Muy baja
    };
    return clases[prioridad] || 'badge badge-estado badge-estado-info';
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
        'pendiente': 'badge badge-estado badge-estado-pendiente',
        'aprobado': 'badge badge-estado badge-estado-aprobado',
        'rechazado': 'badge badge-estado badge-estado-rechazado',
        'requiere_info': 'badge badge-estado badge-estado-info',
        'no_requiere': 'badge badge-estado badge-estado-info',
        'sin_solicitud': 'badge bg-warning text-dark'
    };
    return clases[estado] || 'badge badge-estado badge-estado-info';
}

function getEstadoText(estado) {
    const textos = {
        'pendiente': 'Pendiente',
        'aprobado': 'Aprobado',
        'rechazado': 'Rechazado',
        'requiere_info': 'Requiere Info',
        'no_requiere': 'No Requiere',
        'sin_solicitud': 'Sin Solicitud'
    };
    return textos[estado] || 'Desconocido';
}

function getUrgenciaClass(urgencia) {
    const clases = {
        'urgente': 'badge badge-estado badge-estado-rechazado',
        'atencion': 'badge badge-estado badge-estado-pendiente',
        'normal': 'badge badge-estado badge-estado-aprobado'
    };
    return clases[urgencia] || 'badge badge-estado badge-estado-info';
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
    const tbodyIncidencias = document.getElementById('tablaIncidenciasBody');
    const tbodyRetardos = document.getElementById('tablaRetardosBody');
    if (!tbodyIncidencias && !tbodyRetardos) return;
    
    const htmlCarga = `
        <tr>
            <td colspan="13" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2">Cargando...</div>
            </td>
        </tr>
    `;
    
    if (mostrar) {
        if (tbodyIncidencias) tbodyIncidencias.innerHTML = htmlCarga;
        if (tbodyRetardos) tbodyRetardos.innerHTML = htmlCarga;
    }
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
    const modalHtml = `
        <div class="modal fade" id="modalError" tabindex="-1" aria-labelledby="modalErrorLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalErrorLabel">
                            <i class="fas fa-exclamation-circle me-2"></i>Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${mensaje}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const existingModal = document.getElementById('modalError');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('modalError'));
    modal.show();
    
    document.getElementById('modalError').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function mostrarAdvertencia(mensaje) {
    // Implementar notificación de advertencia
    console.warn('Advertencia:', mensaje);
}

function toggleSeleccionTodos() {
    // Verificar qué pestala está activa
    const activeTab = document.querySelector('.tab-pane.active').id;
    
    let checkboxes;
    let mainCheckbox;
    
    if (activeTab === 'panel-incidencias') {
        checkboxes = document.querySelectorAll('#tablaIncidenciasBody .chk-validacion');
        mainCheckbox = document.getElementById('selectAllIncidencias');
    } else if (activeTab === 'panel-retardos') {
        checkboxes = document.querySelectorAll('#tablaRetardosBody .chk-validacion');
        mainCheckbox = document.getElementById('selectAllRetardos');
    } else {
        checkboxes = document.querySelectorAll('.chk-validacion');
        mainCheckbox = document.getElementById('selectAllIncidencias') || document.getElementById('selectAllRetardos');
    }
    
    if (mainCheckbox) {
        checkboxes.forEach(checkbox => {
            checkbox.checked = mainCheckbox.checked;
        });
    }
    
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
    // Pestaña "Incidencias" = todos los registros
    // Pestaña "Retardos" = solo retardos
    const incidencias = validacionesPendientes;
    const retardos = validacionesPendientes.filter(v => {
        const tipo = v.tipo_incidencia || '';
        return tipo.includes('retardo');
    });
    
    const countIncidencias = document.getElementById('countIncidencias');
    const countRetardos = document.getElementById('countRetardos');
    
    if (countIncidencias) countIncidencias.textContent = incidencias.length;
    if (countRetardos) countRetardos.textContent = retardos.length;
    
    const total = validacionesPendientes.length;
    const inicio = (paginaActual - 1) * registrosPorPagina + 1;
    const fin = Math.min(inicio + registrosPorPagina - 1, total);
    
    const registroInicio = document.getElementById('registroInicio');
    const registroFin = document.getElementById('registroFin');
    const registroTotal = document.getElementById('registroTotal');
    
    if (registroInicio) registroInicio.textContent = inicio;
    if (registroFin) registroFin.textContent = fin;
    if (registroTotal) registroTotal.textContent = total;
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
        
        // Cargar TODOS los empleados (con contadores de incidencias)
        const url = `${window.BASE_URL}/validaciones/buscar-empleados?solo_con_incidencias=1`;
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
            
            // Contar cuántos tienen incidencias
            const conIncidencias = empleadosACargo.filter(e => e.total_incidencias > 0).length;
            console.log('📊 Empleados con incidencias:', conIncidencias);
            
            renderizarEmpleados();
            filtrarEmpleados(filtroActual);
            actualizarContadoresSeleccion();
            
            // Mostrar mensaje si no hay empleados
            if (empleadosACargo.length === 0) {
                console.log('ℹ️ No hay empleados asignados a su cargo');
            }
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
                    <i class="fas fa-users me-2"></i>
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
        const areaFisica = empleado.area_fisica || '';
        const cantidadRetardos = parseInt(empleado.cantidad_retardos) || 0;
        const cantidadAsistencias = parseInt(empleado.cantidad_asistencias) || 0;
        const totalIncidencias = cantidadRetardos + cantidadAsistencias;
        const tienePendientes = totalIncidencias > 0;
        const sinPendientes = !tienePendientes;
        
        // Estilo según si tiene incidencias - Colores Pantone institucionales
        const cardStyle = tienePendientes 
            ? 'border: 3px solid #9F2241 !important; background: linear-gradient(135deg, rgba(159, 34, 65, 0.08) 0%, rgba(159, 34, 65, 0.03) 100%);' 
            : (sinPendientes ? 'border: 2px solid #2E7D5E !important; background: linear-gradient(135deg, rgba(46, 125, 50, 0.08) 0%, rgba(46, 125, 50, 0.03) 100%);' : 'border: 1px solid #e0e0e0;');
        
        // Badge general de estado
        let badgeHtml = '';
        if (tienePendientes) {
            badgeHtml = `
                <span class="badge" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white;">
                    <i class="fas fa-exclamation-circle me-1"></i>${totalIncidencias} pendiente${totalIncidencias > 1 ? 's' : ''}
                </span>
            `;
        } else {
            badgeHtml = `<span class="badge" style="background: linear-gradient(135deg, #2E7D5E 0%, #1B5E3A 100%); color: white;"><i class="fas fa-check-circle me-1"></i>Al día</span>`;
        }
        
        // Icono según estado
        let iconClass = 'far fa-circle text-muted';
        let iconStyle = '';
        if (isSelected && tienePendientes) {
            iconClass = 'fas fa-check-circle text-success';
        } else if (isSelected) {
            iconClass = 'fas fa-check-circle text-success';
        } else if (sinPendientes) {
            iconClass = 'fas fa-check-circle';
            iconStyle = 'style="color: #2E7D5E;"';
        } else if (tienePendientes) {
            iconClass = 'fas fa-exclamation-circle';
            iconStyle = 'style="color: #9F2241;"';
        }
        
        // Mensajes separados para retardos e incidencias
        let mensajesIncidencias = '';
        let retardosHtml = '';
        let incidenciasHtml = '';
        
        if (cantidadRetardos > 0) {
            retardosHtml = `
                <div class="mb-1">
                    <i class="fas fa-clock me-1" style="color: #BC955C;"></i>
                    <span style="color: #8B6914;">Retardos: </span>
                    <span class="badge" style="background: #BC955C; color: white;">
                        ${cantidadRetardos}
                    </span>
                </div>
            `;
        }
        
        if (cantidadAsistencias > 0) {
            incidenciasHtml = `
                <div class="mb-1">
                    <i class="fas fa-calendar-times me-1" style="color: #9F2241;"></i>
                    <span style="color: #9F2241;">Incidencias: </span>
                    <span class="badge" style="background: #9F2241; color: white;">
                        ${cantidadAsistencias}
                    </span>
                </div>
            `;
        }
        
        if (cantidadRetardos > 0 || cantidadAsistencias > 0) {
            mensajesIncidencias = `
                <div class="mt-2 p-2 rounded" style="background: linear-gradient(135deg, rgba(159, 34, 65, 0.12) 0%, rgba(159, 34, 65, 0.06) 100%); border: 1px solid rgba(159, 34, 65, 0.3);">
                    <small class="fw-bold" style="color: #9F2241;">
                        <i class="fas fa-user-check me-1"></i>Validación de Jefe Requerida
                    </small>
                    <div class="mt-1">
                        ${badgeHtml}
                    </div>
                    <div class="mt-2">
                        ${retardosHtml}${incidenciasHtml}
                    </div>
                </div>
            `;
        } else {
            mensajesIncidencias = `
                <div class="mt-2">
                    ${badgeHtml}
                </div>
            `;
        }
        
        return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card" 
                     data-empleado-id="${empleado.id}" 
                     style="cursor: pointer; transition: all 0.3s ease; ${cardStyle}"
                     onclick="toggleEmpleadoSeleccion('${empleado.id}')">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start">
                            <div class="form-check me-3 mt-1">
                                <input class="form-check-input" type="checkbox" 
                                       id="empleado_${empleado.id}"
                                       value="${empleado.id}"
                                       ${isSelected ? 'checked' : ''}
                                       onclick="event.stopPropagation(); toggleEmpleadoSeleccion('${empleado.id}')">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">
                                    ${nombreCompleto}
                                </h6>
                                <small class="text-muted d-block">
                                    <i class="fas fa-id-badge me-1" style="color: #9F2241;"></i>ID: ${empleado.id}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-building me-1" style="color: #235B4E;"></i>${area}
                                    ${areaFisica ? ` - ${areaFisica}` : ''}
                                </small>
                                ${tienePendientes ? `
                                    <a href="#" class="text-primary small" onclick="event.stopPropagation(); toggleMensajesEmpleado(${empleado.id}); return false;">
                                        <i class="fas fa-chevron-down me-1" id="icon_mensajes_${empleado.id}"></i>
                                        <span id="texto_mensajes_${empleado.id}">Ver ${totalIncidencias} pendientes</span>
                                    </a>
                                    <div class="mt-2 p-2 rounded mensajes-empleado" id="mensajes_${empleado.id}" style="display: none; background: linear-gradient(135deg, rgba(159, 34, 65, 0.12) 0%, rgba(159, 34, 65, 0.06) 100%); border: 1px solid rgba(159, 34, 65, 0.3);">
                                        <small class="fw-bold" style="color: #9F2241;">
                                            <i class="fas fa-user-check me-1"></i>Validación de Jefe Requerida
                                        </small>
                                        <div class="mt-1">
                                            ${badgeHtml}
                                        </div>
                                        <div class="mt-2">
                                            ${retardosHtml}${incidenciasHtml}
                                        </div>
                                    </div>
                                ` : `
                                    <div class="mt-2 p-2 rounded" style="background: linear-gradient(135deg, rgba(46, 125, 50, 0.12) 0%, rgba(46, 125, 50, 0.06) 100%); border: 1px solid rgba(46, 125, 50, 0.3);">
                                        <small class="fw-bold" style="color: #2E7D32;">
                                            <i class="fas fa-check-circle me-1"></i>Al día - Sin pendientes
                                        </small>
                                        <div class="mt-1">
                                            <span class="badge" style="background: linear-gradient(135deg, #2E7D5E 0%, #1B5E3A 100%); color: white;">
                                                <i class="fas fa-thumbs-up me-1"></i>No requiere justificación
                                            </span>
                                        </div>
                                    </div>
                                `}
                            </div>
                            <i class="${iconClass}" ${iconStyle} id="icon_${empleado.id}"></i>
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
 * Toggle mensajes de empleado (expandir/colapsar)
 */
function toggleMensajesEmpleado(empleadoId) {
    const mensajesDiv = document.getElementById(`mensajes_${empleadoId}`);
    const icono = document.getElementById(`icon_mensajes_${empleadoId}`);
    const texto = document.getElementById(`texto_mensajes_${empleadoId}`);
    
    if (!mensajesDiv) return;
    
    if (mensajesDiv.style.display === 'none') {
        mensajesDiv.style.display = 'block';
        if (icono) icono.className = 'fas fa-chevron-up me-1';
        if (texto) texto.textContent = 'Ocultar pendientes';
    } else {
        mensajesDiv.style.display = 'none';
        if (icono) icono.className = 'fas fa-chevron-down me-1';
        if (texto) texto.textContent = 'Ver pendientes';
    }
}

/**
 * Filtrar empleados por tipo
 */
let filtroActual = 'todos';

function filtrarEmpleados(tipo) {
    filtroActual = tipo;
    
    // Actualizar botones
    document.querySelectorAll('.val-filtro-group button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mapear nombres de botones especiales
    const btnMap = {
        'todos': 'btnFiltroTodos',
        'pendientes': 'btnFiltroPendientes',
        'retardos': 'btnFiltroRetardos',
        'incidencias': 'btnFiltroIncidencias',
        'sin_pendientes': 'btnFiltroSinPendientes'
    };
    
    const btnActivo = document.getElementById(btnMap[tipo] || `btnFiltro${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    if (btnActivo) btnActivo.classList.add('active');
    
    const container = document.getElementById('empleadosContainer');
    if (!container) return;
    
    const cards = container.querySelectorAll('.col-md-6, .col-lg-4');
    let visibles = 0;
    let total = 0;
    
    cards.forEach(card => {
        const empleadoId = card.querySelector('[data-empleado-id]')?.dataset.empleadoId;
        const empleado = empleadosACargo.find(e => e.id.toString() === empleadoId?.toString());
        
        if (!empleado) return;
        
        total++;
        const cantidadRetardos = parseInt(empleado.cantidad_retardos) || 0;
        const cantidadAsistencias = parseInt(empleado.cantidad_asistencias) || 0;
        const totalIncidencias = cantidadRetardos + cantidadAsistencias;
        const tieneRetardos = cantidadRetardos > 0;
        const tieneIncidencias = cantidadAsistencias > 0;
        const tienePendientes = totalIncidencias > 0;
        const sinPendientes = !tienePendientes;
        
        let mostrar = false;
        
        switch (tipo) {
            case 'todos':
                mostrar = true;
                break;
            case 'retardos':
                mostrar = tieneRetardos;
                break;
            case 'incidencias':
                mostrar = tieneIncidencias;
                break;
            case 'pendientes':
                mostrar = tienePendientes;
                break;
            case 'sin_pendientes':
                mostrar = sinPendientes;
                break;
        }
        
        card.style.display = mostrar ? '' : 'none';
        if (mostrar) visibles++;
    });
    
    // Actualizar contador
    const contadorFiltro = document.getElementById('contadorFiltro');
    if (contadorFiltro) {
        const nombresFiltro = {
            'todos': 'todos los empleados',
            'retardos': 'empleados con retardos',
            'incidencias': 'empleados con incidencias',
            'pendientes': 'empleados por justificar',
            'sin_pendientes': 'empleados al día'
        };
        contadorFiltro.textContent = `Mostrando ${visibles} de ${empleadosACargo.length} - ${nombresFiltro[tipo] || tipo}`;
    }
    
    // Actualizar badge en el botón de pendientes
    const btnPendientes = document.getElementById('btnFiltroPendientes');
    if (btnPendientes) {
        const conPendientes = empleadosACargo.filter(e => {
            const r = parseInt(e.cantidad_retardos) || 0;
            const a = parseInt(e.cantidad_asistencias) || 0;
            return (r + a) > 0;
        }).length;
        if (conPendientes > 0) {
            btnPendientes.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>Por Justificar <span class="badge bg-warning text-dark ms-1">${conPendientes}</span>`;
        } else {
            btnPendientes.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>Por Justificar`;
        }
    }
    
    // Actualizar badge en el botón Al Día
    const btnSinPendientes = document.getElementById('btnFiltroSinPendientes');
    if (btnSinPendientes) {
        const alDia = empleadosACargo.filter(e => {
            const r = parseInt(e.cantidad_retardos) || 0;
            const a = parseInt(e.cantidad_asistencias) || 0;
            return (r + a) === 0;
        }).length;
        if (alDia > 0) {
            btnSinPendientes.innerHTML = `<i class="fas fa-check-circle me-1"></i>Al Día <span class="badge" style="background: #C8E6C9; color: #1B5E3A; margin-left: 4px;">${alDia}</span>`;
        } else {
            btnSinPendientes.innerHTML = `<i class="fas fa-check-circle me-1"></i>Al Día`;
        }
    }
}

/**
 * Seleccionar todos los empleados
 */
function seleccionarTodosEmpleados() {
    // Asegurar que empleadosACargo esté cargado
    if (empleadosACargo.length === 0) {
        mostrarError('No hay empleados cargados. Intente recargar la página.');
        return;
    }
    
    // Seleccionar todos los empleados
    empleadosACargo.forEach(empleado => {
        empleadosSeleccionados.add(empleado.id.toString());
    });
    
    // Renderizar la selección visual
    renderizarEmpleados();
    actualizarContadoresSeleccion();
    
    // Cargar las incidencias de todos los empleados seleccionados
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
    document.getElementById('tablaIncidencias')?.scrollIntoView({ behavior: 'smooth' });
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
    const tbodyIncidencias = document.getElementById('tablaIncidenciasBody');
    const tbodyRetardos = document.getElementById('tablaRetardosBody');
    
    const htmlMensaje = `
        <tr>
            <td colspan="13" class="text-center py-4">
                <div class="alert alert-info">
                    <i class="fas fa-circle-info me-2"></i>
                    <strong>Seleccione empleados para ver sus incidencias</strong>
                    <br><small>Use la lista de empleados arriba para seleccionar los colaboradores que desea validar</small>
                </div>
            </td>
        </tr>
    `;
    
    if (tbodyIncidencias) tbodyIncidencias.innerHTML = htmlMensaje;
    if (tbodyRetardos) tbodyRetardos.innerHTML = htmlMensaje;
    
    // Resetear contadores de la tabla
    const countIncidencias = document.getElementById('countIncidencias');
    const countRetardos = document.getElementById('countRetardos');
    if (countIncidencias) countIncidencias.textContent = '0';
    if (countRetardos) countRetardos.textContent = '0';
    
    const registroInicio = document.getElementById('registroInicio');
    const registroFin = document.getElementById('registroFin');
    const registroTotal = document.getElementById('registroTotal');
    if (registroInicio) registroInicio.textContent = '0';
    if (registroFin) registroFin.textContent = '0';
    if (registroTotal) registroTotal.textContent = '0';
}

/**
 * Cargar incidencias de empleados seleccionados
 */
async function cargarIncidenciasEmpleadosSeleccionados() {
    if (empleadosSeleccionados.size === 0) return;
    
    try {
        mostrarCarga(true);
        
        const empleadosArray = Array.from(empleadosSeleccionados);
        const csrfToken = getCsrfToken();
        
        const response = await fetch(`${window.BASE_URL}/validaciones/incidencias-empleados`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                empleados_ids: empleadosArray,
                solo_pendientes: soloPendientes,
                ...filtrosActuales,
                pagina: paginaActual,
                limite: registrosPorPagina,
            })
        });
        
        const data = await response.json();
        
        console.log('📋 Datos recibidos:', data);
        
        if (data.success) {
            validacionesPendientes = data.incidencias || [];
            console.log('📊 Total incidencias:', validacionesPendientes.length);
            renderizarTablaValidaciones(data.incidencias);
            renderizarPaginacion(data.total);
            actualizarContadores();
            
            // También cargar aprobados y rechazados para las pestañas correspondientes
            cargarAprobadosYRechazados();
        } else {
            mostrarError('Error cargando incidencias: ' + data.error);
        }
    } catch (error) {
        console.error('Error cargando incidencias:', error);
        mostrarError('Error cargando incidencias: ' + error.message);
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

/**
 * Mostrar modal de resumen de incidencias usando datos guardados
 */
function mostrarModalResumenIncidenciasFromData() {
    const datos = window.datosResumenIncidencias;
    if (!datos || datos.total === 0) {
        mostrarError('No hay incidencias para mostrar');
        return;
    }
    
    document.getElementById('resumenComisionEntrada').textContent = datos.contadores.comision_entrada;
    document.getElementById('resumenComisionSalida').textContent = datos.contadores.comision_salida;
    document.getElementById('resumenComisionDia').textContent = datos.contadores.comision_todo_dia;
    document.getElementById('resumenDiaEconomico').textContent = datos.contadores.dia_economico;
    document.getElementById('resumenAusencia').textContent = datos.contadores.ausencia;
    document.getElementById('resumenLicencia').textContent = datos.contadores.licencia_medica;
    document.getElementById('resumenVacaciones').textContent = datos.contadores.vacaciones;
    document.getElementById('resumenOtros').textContent = datos.contadores.otros;
    document.getElementById('resumenTotalIncidencias').textContent = datos.total;
    
    const modal = new bootstrap.Modal(document.getElementById('modalResumenIncidencias'));
    modal.show();
}

/**
 * Mostrar modal de resumen de retardos usando datos guardados
 */
function mostrarModalResumenRetardosFromData() {
    const datos = window.datosResumenRetardos;
    if (!datos || datos.total === 0) {
        mostrarError('No hay retardos para mostrar');
        return;
    }
    
    document.getElementById('resumenRetardoMenor').textContent = datos.menor;
    document.getElementById('resumenRetardoMayor').textContent = datos.mayor;
    document.getElementById('resumenTotalRetardos').textContent = datos.total;
    
    const modal = new bootstrap.Modal(document.getElementById('modalResumenRetardos'));
    modal.show();
}

// Exportar funciones globales
window.abrirModalValidacion = abrirModalValidacion;
window.abrirModalValidacionDesdeHTML = abrirModalValidacionDesdeHTML;
window.cambiarPagina = cambiarPagina;
window.descargarReporte = descargarReporte;
window.toggleEmpleadoSeleccion = toggleEmpleadoSeleccion;
window.mostrarModalResumenIncidenciasFromData = mostrarModalResumenIncidenciasFromData;
window.mostrarModalResumenRetardosFromData = mostrarModalResumenRetardosFromData;
window.cargarAprobadosYRechazados = cargarAprobadosYRechazados;

/**
 * Cargar validaciones aprobadas y rechazadas
 */
async function cargarAprobadosYRechazados() {
    if (empleadosSeleccionados.size === 0) return;
    
    try {
        const empleadosArray = Array.from(empleadosSeleccionados);
        const csrfToken = getCsrfToken();
        
        const response = await fetch(`${window.BASE_URL}/validaciones/incidencias-empleados`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf_token: csrfToken,
                empleados_ids: empleadosArray,
                solo_pendientes: false, // Cargar todos los estados
                ...filtrosActuales,
                pagina: 1,
                limite: registrosPorPagina,
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const todasIncidencias = data.incidencias || [];
            
            // Filtrar aprobados (estado validacion = 'aprobado')
            validacionesAprobadas = todasIncidencias.filter(inc => {
                const estado = inc.estado_validacion_jefe || '';
                return estado.toLowerCase() === 'aprobado';
            });
            
            // Filtrar rechazados (estado validacion = 'rechazado')
            validacionesRechazadas = todasIncidencias.filter(inc => {
                const estado = inc.estado_validacion_jefe || '';
                return estado.toLowerCase() === 'rechazado';
            });
            
            // Actualizar contadores en las pestañas
            const countAprobadosEl = document.getElementById('countAprobados');
            const countRechazadosEl = document.getElementById('countRechazados');
            if (countAprobadosEl) countAprobadosEl.textContent = validacionesAprobadas.length;
            if (countRechazadosEl) countRechazadosEl.textContent = validacionesRechazadas.length;
            
            // Renderizar tablas
            renderizarTablaAprobados();
            renderizarTablaRechazados();
        }
    } catch (error) {
        console.error('Error cargando aprobados/rechazados:', error);
    }
}

/**
 * Renderizar tabla de aprobados
 */
function renderizarTablaAprobados() {
    const tbodyAprobados = document.getElementById('tablaAprobadosBody');
    if (!tbodyAprobados) return;
    
    if (validacionesAprobadas.length === 0) {
        tbodyAprobados.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-4">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>No hay incidencias aprobadas</strong>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbodyAprobados.innerHTML = validacionesAprobadas.map(validacion => {
        return renderizarFilaValidacion(validacion, 'aprobado');
    }).join('');
}

/**
 * Renderizar tabla de rechazados
 */
function renderizarTablaRechazados() {
    const tbodyRechazados = document.getElementById('tablaRechazadosBody');
    if (!tbodyRechazados) return;
    
    if (validacionesRechazadas.length === 0) {
        tbodyRechazados.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-4">
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-times-circle me-2"></i>
                        <strong>No hay incidencias rechazadas</strong>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbodyRechazados.innerHTML = validacionesRechazadas.map(validacion => {
        return renderizarFilaValidacion(validacion, 'rechazado');
    }).join('');
}