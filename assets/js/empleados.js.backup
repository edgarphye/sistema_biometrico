/**
 * JavaScript para la vista de empleados con paginación y filtros optimizados
 * Proporciona una experiencia de usuario moderna y responsiva
 */

// Configuración global
const empleadosConfig = {
    baseUrl: (function() {
        if (window.BASE_URL) return window.BASE_URL;
        // Deducir URL base si estamos en un subdirectorio (ej: /sistema_biometrico/empleados)
        const path = window.location.pathname;
        const index = path.indexOf('/empleados');
        if (index > 0) {
            return window.location.origin + path.substring(0, index);
        }
        return window.location.origin;
    })(),
    currentPage: 1,
    currentLimit: 20,
    currentSearch: '',
    currentArea: '',
    currentJerarquia: '',
    isLoading: false,
    debounceDelay: 300
};

// Estado de la aplicación
const appState = {
    empleados: [],
    pagination: {},
    filters: {
        search: '',
        area: '',
        jerarquia: ''
    }
};

// Cache de elementos DOM
let domElements = {};

/**
 * Inicializa la aplicación, cacheando los elementos del DOM
 */
function initializeApp() {
    domElements = {
        searchInput: document.getElementById('search-empleados'),
        areaFilter: document.getElementById('filter-area'),
        pageSelect: document.getElementById('per-page'),
        empleadosList: document.getElementById('empleados-list'),
        resultsInfo: document.getElementById('results-info'),
        showingFrom: document.getElementById('showing-from'),
        showingTo: document.getElementById('showing-to'),
        totalResults: document.getElementById('total-results'),
        pagePrev: document.getElementById('page-prev'),
        pageNext: document.getElementById('page-next'),
        currentPage: document.getElementById('current-page'),
        paginationBottom: document.getElementById('pagination-bottom'),
        noResults: document.getElementById('no-results'),
        loadingIndicator: document.getElementById('loading-indicator'),
        refreshButton: document.getElementById('btn-refresh')
    };
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    setupEmployeeCardListeners();
    loadInitialData();
});

/**
 * Cargar datos iniciales
 */
function loadInitialData() {
    // Obtener parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    empleadosConfig.currentPage = parseInt(urlParams.get('page')) || 1;
    empleadosConfig.currentLimit = parseInt(urlParams.get('limit')) || 20;
    empleadosConfig.currentSearch = urlParams.get('search') || '';
    empleadosConfig.currentArea = urlParams.get('area') || '';
    empleadosConfig.currentJerarquia = urlParams.get('jerarquia') || '';
    
    // Aplicar filtros a los inputs
    if (domElements.searchInput) {
        domElements.searchInput.value = empleadosConfig.currentSearch;
    }
    if (domElements.areaFilter) {
        domElements.areaFilter.value = empleadosConfig.currentArea;
    }
    if (domElements.pageSelect) {
        domElements.pageSelect.value = empleadosConfig.currentLimit.toString();
    }
    
    // Cargar datos
    loadEmpleadosData(empleadosConfig.currentPage);
}

/**
 * Cargar datos de empleados con AJAX
 */
function loadEmpleadosData(page = 1) {
    if (window.loadingStates) {
        window.loadingStates.start();
    }
    
    const params = new URLSearchParams({
        page: page.toString(),
        limit: empleadosConfig.currentLimit,
        search: empleadosConfig.currentSearch,
        area: empleadosConfig.currentArea,
        jerarquia: empleadosConfig.currentJerarquia,
        ajax: '1'
    });
    
    const url = `${empleadosConfig.baseUrl}/empleados?${params.toString()}`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success === false) {
            throw new Error(data.message || 'Error desconocido');
        }
        
        updateUI(data);
        updateBrowserHistory();
        
        if (window.loadingStates) {
            window.loadingStates.stop();
        }
    })
    .catch(error => {
        console.error('Error cargando empleados:', error);
        if (window.loadingStates) {
            window.loadingStates.error(error.message);
        }
        if (window.loadingStates) {
            window.loadingStates.stop();
        }
    });
}

/**
 * Actualizar la interfaz de usuario
 */
function updateUI(data) {
    appState.empleados = data.empleados || [];
    appState.pagination = data.pagination || {};
    appState.filters = data.filters || {};
    
    // Actualizar lista de empleados
    renderEmpleados(appState.empleados);
    
    // Actualizar información de paginación
    updatePaginationInfo(appState.pagination);
    
    // Mostrar/ocultar mensajes de no resultados
    toggleNoResults(appState.empleados.length === 0);
    
    // Actualizar URL con los filtros actuales
    updateURL();
}

/**
 * Renderizar empleados en la lista
 */
function renderEmpleados(empleados) {
    if (!domElements.empleadosList) return;
    
    const empleadosHTML = empleados.map(empleado => {
        const fotoHtml = empleado.foto_cara
            ? `<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="${empleado.nombre}" class="rounded-circle mb-3 lazyload" data-src="${empleado.foto_cara}" style="width:100px;height:100px;object-fit:cover;">`
            : `<div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;"><i class="fas fa-user text-muted" style="font-size:2rem;"></i></div>`;
        
        const statusBadge = empleado.activo
            ? '<span class="badge bg-success" style="font-size:1rem; padding: 8px 20px;">Activo</span>'
            : '<span class="badge bg-danger" style="font-size:1rem; padding: 8px 20px;">Inactivo</span>';
        
        return `
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm employee-card" data-employee-id="${empleado.id}">
                    <div class="card-body text-center p-4">
                        ${fotoHtml}
                        
                        <div class="fw-bold mb-3" style="font-size:1.2rem;">${escapeHtml((empleado.nombre || '') + ' ' + (empleado.apellido || ''))}</div>
                        
                        <div class="text-start bg-light p-2 rounded mb-3 small">
                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                <strong>ID:</strong> <span>${empleado.id}</span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-1 mb-1">
                                <strong>Área:</strong> <span class="text-end">${escapeHtml(empleado.area || '-')}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>RFC:</strong> <span>${escapeHtml(empleado.rfc || '-')}</span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <button class="btn btn-lg btn-outline-primary ver-empleado" data-id="${empleado.id}" style="font-size:1rem;">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-lg btn-outline-warning editar-empleado" data-id="${empleado.id}" style="font-size:1rem;">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </div>
                    </div>
                    <div class="card-footer text-center py-3" style="font-size:1rem;">
                        ${statusBadge}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    domElements.empleadosList.innerHTML = empleadosHTML;
    
    // Inicializar lazy loading para las nuevas imágenes
    setTimeout(() => {
        if (window.initializeLazyLoading) {
            window.initializeLazyLoading();
        }
    }, 100);
    
    // Agregar event listeners a los nuevos botones
    setupEmployeeCardListeners();
}

/**
 * Configurar listeners para las tarjetas de empleados
 */
function setupEmployeeCardListeners() {
    document.querySelectorAll('.ver-empleado').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const employeeId = this.dataset.id;
            verEmpleado(employeeId);
        });
    });
    
    document.querySelectorAll('.editar-empleado').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const employeeId = this.dataset.id;
            window.location.href = `${empleadosConfig.baseUrl}/empleados/edit/${employeeId}`;
        });
    });
    
    // Permitir clic en toda la tarjeta para ver detalles
    document.querySelectorAll('.employee-card').forEach(card => {
        card.addEventListener('click', function() {
            const employeeId = this.dataset.employeeId;
            if (employeeId) verEmpleado(employeeId);
        });
    });
}

/**
 * Actualizar información de paginación
 */
function updatePaginationInfo(pagination) {
    if (!domElements.resultsInfo) return;
    
    const showingFrom = pagination.showing_from || 1;
    const showingTo = pagination.showing_to || pagination.per_page;
    const total = pagination.total || 0;
    const currentPage = pagination.current_page || 1;
    const totalPages = pagination.total_pages || 1;
    const hasNext = pagination.has_next || false;
    const hasPrev = pagination.has_prev || false;
    
    // Actualizar texto
    if (domElements.showingFrom) {
        domElements.showingFrom.textContent = showingFrom;
    }
    if (domElements.showingTo) {
        domElements.showingTo.textContent = showingTo;
    }
    if (domElements.totalResults) {
        domElements.totalResults.textContent = total;
    }
    
    // Actualizar controles de paginación
    if (domElements.pagePrev) {
        domElements.pagePrev.disabled = !hasPrev;
    }
    if (domElements.pageNext) {
        domElements.pageNext.disabled = !hasNext;
    }
    
    // Actualizar página actual
    if (domElements.currentPage) {
        domElements.currentPage.textContent = currentPage;
    }
    if (domElements.totalPages) {
        domElements.totalPages.textContent = totalPages;
    }
    
    // Actualizar estado en la configuración
    empleadosConfig.currentPage = currentPage;
    appState.pagination = pagination;
    
    // Actualizar paginación inferior
    updateBottomPagination(pagination);
}

/**
 * Actualizar paginación inferior
 */
function updateBottomPagination(pagination) {
    if (!domElements.paginationBottom) return;
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    const hasPrev = pagination.has_prev;
    const hasNext = pagination.has_next;
    
    // Generar HTML de paginación
    let paginationHTML = '<nav aria-label="Paginación de empleados"><ul class="pagination justify-content-center">';
    
    // Botón anterior
    paginationHTML += `
        <li class="page-item ${!hasPrev ? 'disabled' : ''}">
            <a class="page-link pagination-link" href="#" data-page="${currentPage - 1}" ${!hasPrev ? 'tabindex="-1"' : ''}>
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;
    
    // Página actual
    paginationHTML += `
        <li class="page-item active">
            <span class="page-link">
                ${currentPage}<span class="sr-only">(current)</span>
            </span>
        </li>
    `;
    
    // Páginas intermedias
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) continue;
        
        paginationHTML += `
            <li class="page-item">
                <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    paginationHTML += `
        <li class="page-item ${!hasNext ? 'disabled' : ''}">
            <a class="page-link pagination-link" href="#" data-page="${currentPage + 1}" ${!hasNext ? 'tabindex="-1"' : ''}>
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;
    
    paginationHTML += '</ul></nav>';
    
    domElements.paginationBottom.innerHTML = paginationHTML;
    
    // Agregar event listeners a los enlaces de paginación
    setupPaginationListeners();
}

/**
 * Configurar listeners de paginación inferior
 */
function setupPaginationListeners() {
    document.querySelectorAll('.pagination-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            if (page && page > 0) {
                changePage(page);
            }
        });
    });
}

/**
 * Cambiar de página
 */
function changePage(page) {
    if (page < 1) return;
    if (page > appState.pagination.total_pages) return;
    
    empleadosConfig.currentPage = page;
    loadEmpleadosData(page);
}

/**
 * Filtrar empleados
 */
function filterEmpleados() {
    const search = domElements.searchInput.value;
    const area = domElements.areaFilter ? domElements.areaFilter.value : '';
    
    if (search !== empleadosConfig.currentSearch || 
        area !== empleadosConfig.currentArea) {
        empleadosConfig.currentSearch = search;
        empleadosConfig.currentArea = area;
        empleadosConfig.currentPage = 1;
        loadEmpleadosData(1);
    }
}

/**
 * Mostrar/ocultar mensaje de no resultados
 */
function toggleNoResults(show) {
    if (domElements.noResults) {
        domElements.noResults.style.display = show ? 'block' : 'none';
    }
    if (domElements.empleadosList) {
        domElements.empleadosList.style.display = show ? 'none' : 'block';
    }
    if (domElements.resultsInfo) {
        domElements.resultsInfo.style.display = show ? 'none' : 'block';
    }
}

/**
 * Actualizar URL del navegador
 */
function updateURL() {
    const params = new URLSearchParams({
        page: empleadosConfig.currentPage,
        limit: empleadosConfig.currentLimit,
        search: empleadosConfig.currentSearch,
        area: empleadosConfig.currentArea,
        jerarquia: empleadosConfig.currentJerarquia
    });
    
    const newURL = `${empleadosConfig.baseUrl}/empleados?${params.toString()}`;
    
    // Actualizar URL sin recargar la página
    if (window.history && window.history.pushState) {
        window.history.pushState({}, '', newURL);
    }
}

/**
 * Actualizar historial del navegador
 */
function updateBrowserHistory() {
    const title = `Empleados - Página ${empleadosConfig.currentPage}`;
    if (document.title !== title) {
        document.title = title;
    }
}

/**
 * Escapar HTML para prevenir XSS
 */


function escapeHtml(text) {
    // Manejar todos los casos nulos/undefined/vacíos
    if (text === null || text === undefined || text === "") {
        return "";
    }
    
    // Forzar conversión a string
    const textStr = String(text);
    
    // Manejo seguro de replace
    try {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return textStr.replace(/[&<>"']/g, function(match) {
            return map[match] || match;
        });
    } catch (error) {
        console.warn('Error en escapeHtml:', error, 'para texto:', text);
        return textStr;
    }
}

/**
 * Mostrar mensaje de error
 */
function showError(title, message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>${title}:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
    `;
    
    const alertContainer = document.createElement('div');
    alertContainer.innerHTML = alertHtml;
    alertContainer.style.position = 'fixed';
    alertContainer.style.top = '20px';
    alertContainer.style.right = '20px';
    alertContainer.style.zIndex = '9999';
    alertContainer.style.maxWidth = '400px';
    
    document.body.appendChild(alertContainer);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (alertContainer.parentNode) {
            alertContainer.parentNode.removeChild(alertContainer);
        }
    }, 5000);
    
    // Inicializar tooltips del alerta
    if (window.bootstrap) {
        const alertElement = alertContainer.querySelector('.alert');
        new bootstrap.Alert(alertElement);
    }
}

/**
 * Ver detalles del empleado en modal
 */
async function verEmpleado(id) {
    try {
        // Mostrar loading
        if (window.loadingStates) window.loadingStates.start();
        
        // Eliminar solo el modal de empleado sin afectar otros modales
        const existingModal = document.getElementById('modalVerEmpleado');
        if (existingModal) {
            existingModal.remove();
        }
        // Limpiar backdrops anteriores del modal de empleado
        document.querySelectorAll('#empleado-modal-backdrop').forEach(b => b.remove());

        // Fetch datos (Resumen JSON y Horarios HTML)
        const [resumenResponse, horariosResponse] = await Promise.all([
            fetch(`${empleadosConfig.baseUrl}/empleados/resumen-completo/${id}`),
            fetch(`${empleadosConfig.baseUrl}/empleados/horarios-y-asistencia/${id}`)
        ]);

        if (!resumenResponse.ok) throw new Error(`Error cargando resumen del empleado (${resumenResponse.status})`);
        
        const data = await resumenResponse.json();
        
        let horariosHtml = '<div class="alert alert-warning">No se pudo cargar la información de horarios.</div>';
        if (horariosResponse.ok) {
            horariosHtml = await horariosResponse.text();
        }

        if (data.error) throw new Error(data.error);

        // Construir modal
        const modalHtml = construirModalEmpleado(data, horariosHtml);
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Mostrar modal usando Bootstrap
        const modalElement = document.getElementById('modalVerEmpleado');
        if (modalElement) {
            modalElement.style.zIndex = '1060';
            
            // Asegurar que los tabs funcionen correctamente
            modalElement.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('shown.bs.tab', function(e) {
                    // Actualizar contenido cuando se muestra un tab
                    e.target.classList.add('active');
                });
            });
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Cleanup when closed
            modalElement.addEventListener('hidden.bs.modal', function () {
                const backdrop = document.getElementById('empleado-modal-backdrop');
                if (backdrop) backdrop.remove();
                modalElement.remove();
            });
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error', 'No se pudo cargar la información del empleado: ' + error.message);
    } finally {
        if (window.loadingStates) window.loadingStates.stop();
    }
}

function cerrarModalEmpleado(modalElement, backdrop) {
    if (modalElement) {
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
    }
    if (backdrop) {
        backdrop.remove();
    }
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    if (modalElement) {
        modalElement.remove();
    }
}

function construirModalEmpleado(data, horariosHtml) {
    // Helpers para formatear
    const formatDate = (str) => {
        if (!str) return '-';
        // Evitar problema de timezone dividiendo la fecha directamente
        const [year, month, day] = str.split('-');
        return `${day}/${month}/${year}`;
    };
    const formatTime = (str) => str ? str.substring(0, 8) : '-';
    
    // Construir tabs
    const sidebar = document.querySelector('.sidebar');
    const isSidebarCollapsed = sidebar && (sidebar.classList.contains('collapsed') || !sidebar.classList.contains('show'));
    const maxWidth = isSidebarCollapsed ? '98vw' : 'calc(100vw - 260px)';
    
    return `
        <div class="modal fade" id="modalVerEmpleado" tabindex="-1" aria-hidden="true" style="z-index: 1050;">
            <div class="modal-dialog modal-dialog-scrollable" style="max-width: ${maxWidth}; margin: 0.5rem auto;">
                <div class="modal-content" style="min-height: 90vh;">
                    <div class="modal-header bg-primary text-white" style="flex-shrink: 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-user-circle me-2"></i>
                            ${escapeHtml(data.nombre)} ${escapeHtml(data.apellido)}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="row g-0 h-100">
                            <!-- Sidebar con foto y datos básicos -->
                            <div class="col-md-3 bg-light border-end p-3 text-center">
                                <div class="mb-3">
                                    ${data.foto_cara 
                                        ? `<img src="${data.foto_cara}" class="img-fluid rounded-circle shadow-sm" style="width:120px;height:120px;object-fit:cover;">`
                                        : `<div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width:120px;height:120px;"><i class="fas fa-user text-secondary fa-3x"></i></div>`
                                    }
                                </div>
                                <h6 class="fw-bold">${escapeHtml(data.area || 'Sin Área')}</h6>
                                <p class="text-muted small">${escapeHtml(data.jerarquia || '')}</p>
                                <hr>
                                <div class="text-start small">
                                    <p class="mb-1"><strong>ID:</strong> ${data.id}</p>
                                    <p class="mb-1"><strong>RFC:</strong> ${escapeHtml(data.rfc || '-')}</p>
                                    <p class="mb-1"><strong>CURP:</strong> ${escapeHtml(data.curp || '-')}</p>
                                    <p class="mb-1"><strong>Estado:</strong> ${data.activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>'}</p>
                                </div>
                            </div>
                            
                            <!-- Contenido Principal con Tabs -->
                            <div class="col-md-9">
                                <ul class="nav nav-tabs nav-fill bg-light flex-nowrap" id="empleadoTabs" role="tablist" style="overflow-x: auto; white-space: nowrap;">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info-general" type="button">Info. General</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-asistencia" type="button">Asistencia</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-incidencias" type="button">
                                            Incidencias <span class="badge bg-warning rounded-pill">${data.justificaciones ? data.justificaciones.length : 0}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-retardos" type="button">
                                            Retardos <span class="badge bg-danger rounded-pill">${data.estadisticas.retardos_mes || 0}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-comisiones" type="button">
                                            Comisiones <span class="badge bg-info rounded-pill">${data.estadisticas.comisiones_pendientes || 0}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vacaciones" type="button">
                                            Vacaciones <span class="badge bg-primary rounded-pill">${data.vacaciones ? data.vacaciones.length : 0}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-licencias" type="button">
                                            Licencias <span class="badge bg-secondary rounded-pill">${data.licencias_medicas ? data.licencias_medicas.length : 0}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dias-economicos" type="button">
                                            Días Econ.
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cuidados" type="button">
                                            Cuidados
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-constancias" type="button">
                                            Constancias
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sanciones" type="button">
                                            Sanciones <span class="badge bg-warning rounded-pill">${data.estadisticas.sanciones_activas || 0}</span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ciclos" type="button">Ciclos</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-horarios" type="button">Horarios</button>
                                    </li>
                                </ul>
                                 
                                <div class="accordion" id="empleadoAccordion">
                                    <!-- Info General -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#tab-info-general">
                                                <i class="fas fa-user me-2"></i>Info. General
                                            </button>
                                        </h2>
                                        <div id="tab-info-general" class="accordion-collapse collapse show" data-bs-parent="#empleadoAccordion">
                                            <div class="accordion-body p-3">
                                                <div id="modal_plazas_ciclo_container">
                                            ${(data.plazas && data.plazas.filter(p => p.HORAS && p.HORAS > 0).length > 0) ? `
                                            <div class="mb-4">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-briefcase me-2"></i>Plazas del Empleado
                                                    <button class="btn btn-sm btn-outline-info ms-2" data-bs-toggle="modal" data-bs-target="#modalCalculoPlazasModal" title="Ver cálculo normativo">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Plaza</th>
                                                                <th>Hrs/Sem</th>
                                                                <th>Hrs/Mes</th>
                                                                <th>Puesto</th>
                                                                <th>CCT</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${data.plazas.filter(p => p.HORAS && p.HORAS > 0).map(plaza => `
                                                            <tr>
                                                                <td>${escapeHtml(plaza.PLAZA || '-')}</td>
                                                                <td class="fw-bold">${plaza.HORAS ? parseFloat(plaza.HORAS).toFixed(1) : '-'}</td>
                                                                <td class="fw-bold">${plaza.HORAS_MENSUALES ? plaza.HORAS_MENSUALES.toFixed(1) : '-'}</td>
                                                                <td>${escapeHtml(plaza.puesto || '-')}</td>
                                                                <td>${escapeHtml(plaza.CCT || '-')}</td>
                                                            </tr>
                                                            `).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            ` : '<div class="alert alert-info">No hay plazas con horas asignadas</div>'}
                                            
                                            ${(data.plazas_horas_mensuales > 0 && data.ciclos_asignados && data.ciclos_asignados.length > 0 && data.ciclos_asignados[0].horas_requeridas > 0) ? `
                                            <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="fw-bold mb-0"><i class="fas fa-balance-scale me-2"></i>Comparación Plazas vs Ciclo (Normativa SEP)</h6>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalComparacionPlazasCiclo">
                                                        <i class="fas fa-expand-alt me-1"></i> Ver Detalle Completo
                                                    </button>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-2 rounded" style="background-color: #e9ecef;">
                                                            <small class="d-block text-muted">HRS PLAZA (Mes)</small>
                                                            <span class="fw-bold h5">${Math.round(data.plazas_horas_mensuales * 100) / 100}</span>
                                                            <small class="text-muted">hrs/mes</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-2 rounded" style="background-color: #e3f2fd;">
                                                            <small class="d-block text-muted">HRS REQUERIDAS</small>
                                                            <span class="fw-bold h5">${data.ciclos_asignados[0].horas_requeridas ? parseFloat(data.ciclos_asignados[0].horas_requeridas).toFixed(1) : '-'}</span>
                                                            <small class="text-muted">hrs/mes</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-2 rounded" style="background-color: #d4edda;">
                                                            <small class="d-block text-muted">HRS TRABAJADAS</small>
                                                            <span class="fw-bold h5">${data.ciclos_asignados[0].horas_trabajadas ? parseFloat(data.ciclos_asignados[0].horas_trabajadas).toFixed(1) : '-'}</span>
                                                            <small class="text-muted">hrs/mes</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="p-2 rounded" style="background-color: #fff3cd;">
                                                            <small class="d-block text-muted">CUMPLIMIENTO</small>
                                                            <span class="fw-bold h5">${Math.min(100, Math.round((data.plazas_horas_mensuales / parseFloat(data.ciclos_asignados[0].horas_requeridas)) * 100))}%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                ${(() => {
                                                    const hrsPlazaSem = data.plazas_horas_semanales;
                                                    const hrsPlazaMes = data.plazas_horas_mensuales;
                                                    const hrsRequeridas = parseFloat(data.ciclos_asignados[0].horas_requeridas);
                                                    const hrsTrabajadas = data.ciclos_asignados[0].horas_trabajadas ? parseFloat(data.ciclos_asignados[0].horas_trabajadas) : 0;
                                                    const hrsDia = hrsPlazaSem / 5;
                                                    const diff = hrsPlazaMes - hrsRequeridas;
                                                    const absDiff = Math.abs(Math.round(diff * 100) / 100);
                                                    let mensaje = '';
                                                    
                                                    if (absDiff < 1) {
                                                        mensaje = `<div class="alert alert-success py-2"><i class="fas fa-check-circle me-1"></i> <strong>PLAZA vs CICLO:</strong> Las hrs de plaza (${hrsPlazaSem.toFixed(1)} hrs/sem = ${hrsPlazaMes.toFixed(1)} hrs/mes) coinciden con hrs requeridas del ciclo (${hrsRequeridas.toFixed(1)} hrs/mes). ✓ CUMPLIMIENTO</div>`;
                                                    } else if (diff > 0) {
                                                        mensaje = `<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle me-1"></i> <strong>PLAZA vs CICLO:</strong> Plaza tiene +${absDiff} hrs/mes más que el ciclo.</div>`;
                                                    } else {
                                                        mensaje = `<div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i> <strong>PLAZA vs CICLO:</strong> Plaza tiene -${absDiff} hrs/mes menos que el ciclo.</div>`;
                                                    }
                                                    
                                                    if (hrsTrabajadas > 0 && hrsRequeridas > 0) {
                                                        const cumplimiento = Math.round((hrsTrabajadas / hrsRequeridas) * 100);
                                                        if (cumplimiento < 80) {
                                                            mensaje += `<div class="alert alert-danger py-2 mt-2"><i class="fas fa-clock me-1"></i> <strong>ASISTENCIA INSUFICIENTE:</strong> Solo has trabajado ${hrsTrabajadas.toFixed(1)} hrs/mes de ${hrsRequeridas.toFixed(1)} hrs/mes requeridas (${cumplimiento}%).</div>`;
                                                        }
                                                    }
                                                    
                                                    mensaje += `<div class="mt-2 p-2 rounded" style="background-color: #e7f3ff; border-left: 3px solid #0d6efd;">
                                                        <strong><i class="fas fa-lightbulb me-1"></i> Tu horario correcto según plaza (Normativa SEP):</strong><br>
                                                        <small class="text-muted">
                                                        • hrs/semana: <strong>${hrsPlazaSem.toFixed(1)} hrs</strong> (÷ 5 días = <strong>${hrsDia.toFixed(2)} hrs/día</strong>)<br>
                                                        • <strong>Horario sugerido L-V:</strong> ${hrsDia.toFixed(2)} hrs/día<br>
                                                        • Ejemplo para 7 hrs/día: <strong>9:00 a 16:00</strong> (con 1 hr comida)
                                                        </small>
                                                    </div>`;
                                                    
                                                    return mensaje;
                                                })()}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Asistencia -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tab-asistencia">
                                                <i class="fas fa-calendar-check me-2"></i>Asistencia
                                            </button>
                                        </h2>
                                        <div id="tab-asistencia" class="accordion-collapse collapse" data-bs-parent="#empleadoAccordion">
                                            <div class="accordion-body p-3">
                                                <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Fecha</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        <th>Tipo</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.asistencias && data.asistencias.length > 0 ? 
                                                        data.asistencias.map(a => `
                                                        <tr>
                                                            <td>${a.id}</td>
                                                            <td>${formatDate(a.fecha)}</td>
                                                            <td>${formatTime(a.hora_entrada)}</td>
                                                            <td>${formatTime(a.hora_salida)}</td>
                                                            <td>${a.tipo_asistencia || 'Normal'}</td>
                                                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificarIncidencia(${a.id}, 0, false, '${a.tipo_incidencia || 'por_definir'}', '${a.tipo_asistencia || ''}')"><i class="fas fa-eye"></i> Ver</button></td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="6" class="text-center">No hay registros</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Retardos -->
                                    <div class="tab-pane fade" id="tab-retardos" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Fecha</th>
                                                        <th>Minutos</th>
                                                        <th>Tipo</th>
                                                        <th>Estado</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.retardos && data.retardos.length > 0 ? 
                                                        data.retardos.map(r => `
                                                        <tr>
                                                            <td>${r.id}</td>
                                                            <td>${formatDate(r.fecha)}</td>
                                                            <td class="text-danger fw-bold">${r.minutos_retardo} min</td>
                                                            <td>${r.tipo_retraso}</td>
                                                            <td>${r.justificado ? '<span class="badge bg-success">Justificado</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>'}</td>
                                                            <td>${!r.justificado ? `<button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificarRetardo(${r.id}, 0, false, '${r.tipo_retraso}')"><i class="fas fa-edit me-1"></i>Justificar</button>` : '<span class="text-muted"><i class="fas fa-check"></i></span>'}</td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="6" class="text-center">Sin retardos registrados</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Comisiones -->
                                    <div class="tab-pane fade" id="tab-comisiones" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Descripción</th>
                                                        <th>Fecha</th>
                                                        <th>Monto</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.comisiones && data.comisiones.length > 0 ? data.comisiones.map(c => `
                                                        <tr>
                                                            <td>${c.descripcion || '-'}</td>
                                                            <td>${formatDate(c.fecha_asignacion)}</td>
                                                            <td>${c.monto ? '$' + c.monto : '-'}</td>
                                                            <td>${c.justificada ? '<span class="badge bg-success">Justificada</span>' : '<span class="badge bg-warning text-dark">Pendiente</span>'}</td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="4" class="text-center">No hay comisiones registradas</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Días Económicos -->
                                    <div class="tab-pane fade" id="tab-dias-economicos" role="tabpanel">
                                        ${data.dias_economicos && data.dias_economicos.length > 0 ? `
                                        <div class="alert alert-info mb-3">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <strong><i class="fas fa-calculator me-1"></i>Cálculo de Días Económicos (Art. 69 LFT):</strong>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-4">
                                                    <small class="text-muted">Periodo Actual:</small><br>
                                                    <strong>16 Julio ${new Date().getFullYear() - 1} - 15 Julio ${new Date().getFullYear()}</strong>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted">Límite por Periodo:</small><br>
                                                    <strong>9 días</strong>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted">Antigüedad Requerida:</small><br>
                                                    <strong>> 6 meses</strong>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row text-center">
                                                <div class="col-md-4">
                                                    <div class="p-2 bg-primary text-white rounded">
                                                        <div class="fs-4 fw-bold">${data.dias_economicos.filter(d => d.estatus === 'aprobado').reduce((sum, d) => sum + (parseInt(d.dias_solicitados) || 0), 0)}</div>
                                                        <small>Días Usados</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 bg-warning text-dark rounded">
                                                        <div class="fs-4 fw-bold">${data.dias_economicos.filter(d => d.estatus === 'pendiente').reduce((sum, d) => sum + (parseInt(d.dias_solicitados) || 0), 0)}</div>
                                                        <small>Días Pendientes</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 bg-success text-white rounded">
                                                        <div class="fs-4 fw-bold">${Math.max(0, 9 - data.dias_economicos.filter(d => d.estatus === 'aprobado').reduce((sum, d) => sum + (parseInt(d.dias_solicitados) || 0), 0))}</div>
                                                        <small>Días Restantes</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                <em>Modalidades: A) 3 días (espera 30 días), B) 2 días (espera 15 días), C) 1 día (espera 7 días)</em>
                                            </small>
                                        </div>
                                        ` : `
                                        <div class="alert alert-info mb-3">
                                            <strong><i class="fas fa-calculator me-1"></i>Cálculo de Días Económicos (Art. 69 LFT):</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Periodo: 16 Julio - 15 Julio (siguiente)</li>
                                                <li>Máximo: 9 días por periodo</li>
                                                <li>Antigüedad: Más de 6 meses en la dependencia</li>
                                                <li>Plaza de confianza: NO aplica</li>
                                                <li>Anticipación: 1 día antes</li>
                                                <li>Días válidos: Martes a Jueves (no lunes/viernes)</li>
                                            </ul>
                                        </div>
                                        `}
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Días</th>
                                                        <th>Estatus</th>
                                                        <th>Fecha Solicitud</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.dias_economicos && data.dias_economicos.length > 0 ? 
                                                        data.dias_economicos.map(d => `
                                                        <tr>
                                                            <td>${formatDate(d.fecha)}</td>
                                                            <td>${d.dias_solicitados || '-'}</td>
                                                            <td>${d.estatus === 'aprobado' ? '<span class="badge bg-success">Aprobado</span>' : 
                                                                d.estatus === 'rechazado' ? '<span class="badge bg-danger">Rechazado</span>' : 
                                                                '<span class="badge bg-warning">Pendiente</span>'}</td>
                                                            <td>${formatDate(d.fecha_solicitud)}</td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="4" class="text-center">No hay días económicos solicitados</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    
                                    <!-- Tab Sanciones -->
                                    <div class="tab-pane fade" id="tab-sanciones" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Tipo</th>
                                                        <th>Días</th>
                                                        <th>Motivo</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.sanciones && data.sanciones.length > 0 ? data.sanciones.map(s => `
                                                        <tr>
                                                            <td>${formatDate(s.fecha_inicio || s.fecha_sancion)}</td>
                                                            <td>${s.tipo_sancion || s.tipo || '-'}</td>
                                                            <td>${s.dias || '-'}</td>
                                                            <td>${s.motivo || '-'}</td>
                                                            <td>${(s.estatus || s.activa) === 'activa' ? '<span class="badge bg-danger">Activa</span>' : '<span class="badge bg-secondary">Inactiva</span>'}</td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="5" class="text-center">No hay sanciones registradas</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Ciclos -->
                                    <div class="tab-pane fade" id="tab-ciclos" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Vigencia</th>
                                                        <th>Horario</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.ciclos_asignados && data.ciclos_asignados.length > 0 ? data.ciclos_asignados.map(c => `
                                                        <tr>
                                                            <td>${c.nombre_ciclo || 'Ciclo #' + c.ciclo_id}</td>
                                                            <td>${formatDate(c.fecha_inicio)} - ${formatDate(c.fecha_fin)}</td>
                                                            <td>${c.bloques && c.bloques.length > 0 ? c.bloques.map(b => b.dia_semana + ' ' + b.hora_inicio + '-' + b.hora_fin).join(', ') : '-'}</td>
                                                            <td>${c.activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'}</td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="4" class="text-center">No hay ciclos asignados</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Incidencias (Justificaciones) -->
                                    <div class="tab-pane fade" id="tab-incidencias" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Tipo</th>
                                                        <th>Justificación</th>
                                                        <th>Estado</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.justificaciones && data.justificaciones.length > 0 ? 
                                                        data.justificaciones.map(j => `
                                                        <tr>
                                                            <td>${formatDate(j.fecha)}</td>
                                                            <td><span class="badge bg-secondary">${j.tipo_asistencia || '-'}</span></td>
                                                            <td><span class="badge bg-primary">${j.tipo_justificacion_nombre || 'Sin tipo'}</span></td>
                                                            <td>${(j.estado_validacion === 'aprobada' || j.estado_validacion === 'aprobado') ? '<span class="badge bg-success">Aprobada</span>' : 
                                                                (j.estado_validacion === 'rechazada' || j.estado_validacion === 'rechazado') ? '<span class="badge bg-danger">Rechazada</span>' : 
                                                                '<span class="badge bg-warning">Pendiente</span>'}</td>
                                                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificarIncidencia(${j.id}, 0, false, '${j.tipo_incidencia}', '${j.tipo_asistencia}')"><i class="fas fa-eye"></i> Ver</button></td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="5" class="text-center">No hay justificaciones registradas</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Vacaciones -->
                                    <div class="tab-pane fade" id="tab-vacaciones" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Días</th>
                                                        <th>Estado</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.vacaciones && data.vacaciones.length > 0 ? 
                                                        data.vacaciones.map(v => `
                                                        <tr>
                                                            <td>${formatDate(v.fecha_inicio)}</td>
                                                            <td>${formatDate(v.fecha_fin)}</td>
                                                            <td>${v.dias_solicitados || '-'}</td>
                                                            <td>${v.aprobado ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning">Pendiente</span>'}</td>
                                                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificar(${v.id}, 0, true, 'vacaciones')"><i class="fas fa-edit"></i> Ver</button></td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="5" class="text-center">No hay vacaciones registradas</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Licencias Médicas -->
                                    <div class="tab-pane fade" id="tab-licencias" role="tabpanel">
                                        ${data.control_licencias ? `
                                        <div class="alert alert-info mb-3">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <strong><i class="fas fa-calculator me-1"></i>Cálculo de Días de Licencia Médica (Art. 132 Fracc. XXIV LFT):</strong>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-3">
                                                    <small class="text-muted">Fecha de Ingreso:</small><br>
                                                    <strong>${formatDate(data.control_licencias.fecha_ingreso)}</strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">Antigüedad:</small><br>
                                                    <strong>${data.control_licencias.anios || 0} años ${data.control_licencias.meses || 0} meses</strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">Días con Sueldo:</small><br>
                                                    <strong>${data.control_licencias.full || 0} días</strong>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted">Días con 50% Sueldo:</small><br>
                                                    <strong>${data.control_licencias.half || 0} días</strong>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row text-center">
                                                <div class="col-md-4">
                                                    <div class="p-2 bg-primary text-white rounded">
                                                        <div class="fs-4 fw-bold">${data.control_licencias.full || 0}</div>
                                                        <small>Días Totales</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 bg-warning text-dark rounded">
                                                        <div class="fs-4 fw-bold">-${data.control_licencias.full_usados || 0}</div>
                                                        <small>Días Usados</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="p-2 bg-success text-white rounded">
                                                        <div class="fs-4 fw-bold">${data.control_licencias.full_restantes || 0}</div>
                                                        <small>Días Restantes</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Retardos -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tab-retardos">
                                                <i class="fas fa-clock me-2"></i>Retardos <span class="badge bg-danger rounded-pill ms-2">${data.estadisticas.retardos_mes || 0}</span>
                                            </button>
                                        </h2>
                                        <div id="tab-retardos" class="accordion-collapse collapse" data-bs-parent="#empleadoAccordion">
                                            <div class="accordion-body p-3">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Fecha</th>
                                                                <th>Minutos</th>
                                                                <th>Tipo</th>
                                                                <th>Estado</th>
                                                                <th>Acción</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${data.retardos && data.retardos.length > 0 ? 
                                                                data.retardos.map(r => `
                                                                <tr>
                                                        <th>Fecha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Tipo</th>
                                                        <th>Días</th>
                                                        <th>Estado</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.licencias_medicas && data.licencias_medicas.length > 0 ? 
                                                        data.licencias_medicas.map(l => `
                                                        <tr>
                                                            <td>${formatDate(l.fecha_inicio)}</td>
                                                            <td>${formatDate(l.fecha_fin)}</td>
                                                            <td>${l.tipo_sueldo || l.fundamentos || '-'}</td>
                                                            <td>${l.dias_otorgados || l.dias_solicitados || '-'}</td>
                                                            <td>${(l.estatus === 'aprobada' || l.aprobado) ? '<span class="badge bg-success">Aprobado</span>' : 
                                                                (l.estatus === 'rechazada') ? '<span class="badge bg-danger">Rechazado</span>' : 
                                                                '<span class="badge bg-warning">Pendiente</span>'}</td>
                                                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificar(${l.id}, 0, true, 'licencia_medica')"><i class="fas fa-edit"></i> Ver</button></td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="6" class="text-center">No hay licencias médicas</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Cuidados (Maternos/Paternos) -->
                                    <div class="tab-pane fade" id="tab-cuidados" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Tipo</th>
                                                        <th>Fecha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Estado</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${(data.cuidados_maternos && data.cuidados_maternos.length > 0) || (data.cuidados_paternos && data.cuidados_paternos.length > 0) ? 
                                                        [...(data.cuidados_maternos || []), ...(data.cuidados_paternos || [])].map(c => `
                                                        <tr>
                                                            <td>${c.tipo === 'maternos' ? 'Maternos' : 'Paternos'}</td>
                                                            <td>${formatDate(c.fecha_inicio)}</td>
                                                            <td>${formatDate(c.fecha_fin)}</td>
                                                            <td>${c.aprobado ? '<span class="badge bg-success">Aprobado</span>' : '<span class="badge bg-warning">Pendiente</span>'}</td>
                                                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificar(${c.id}, 0, true, 'cuidados_${c.tipo}')"><i class="fas fa-edit"></i> Ver</button></td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="5" class="text-center">No hay cuidados registrados</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Constancias de Tiempo -->
                                    <div class="tab-pane fade" id="tab-constancias" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Días</th>
                                                        <th>Estado</th>
                                                        <th>Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${data.constancias_tiempo && data.constancias_tiempo.length > 0 ? 
                                                        data.constancias_tiempo.map(c => `
                                                        <tr>
                                                            <td>${formatDate(c.fecha_inicio)}</td>
                                                            <td>${formatDate(c.fecha_fin)}</td>
                                                            <td>${c.dias_solicitados || '-'}</td>
                                                            <td>${c.estatus === 'aprobada' ? '<span class="badge bg-success">Aprobada</span>' : 
                                                                c.estatus === 'rechazada' ? '<span class="badge bg-danger">Rechazada</span>' : 
                                                                '<span class="badge bg-warning">Pendiente</span>'}</td>
                                                            <td><button class="btn btn-sm btn-outline-primary" onclick="abrirModalJustificar(${c.id}, 0, true, 'constancia_tiempo')"><i class="fas fa-edit"></i> Ver</button></td>
                                                        </tr>
                                                    `).join('') : '<tr><td colspan="5" class="text-center">No hay constancias de tiempo</td></tr>'}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tab Horarios (Cargado vía AJAX) -->
                                    <div class="tab-pane fade" id="tab-horarios" role="tabpanel">
                                        ${horariosHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <a href="${empleadosConfig.baseUrl}/empleados/${data.id}" class="btn btn-primary">Ver Detalle Completo</a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Exponer funciones globalmente para acceso desde el HTML
window.empleadosApp = {
    loadEmpleados: loadEmpleadosData,
    changePage: changePage,
    filterEmpleados: filterEmpleados,
    config: empleadosConfig,
    state: appState
};

/**
 * Configurar event listeners para todos los controles
 */
function setupEventListeners() {
    // Búsqueda con debounce
    if (domElements.searchInput) {
        let searchTimeout;
        domElements.searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterEmpleados();
            }, empleadosConfig.debounceDelay);
        });
        
        // Búsqueda en Enter
        domElements.searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterEmpleados();
            }
        });
    }
    
    // Filtro de área
    if (domElements.areaFilter) {
        domElements.areaFilter.addEventListener('change', function() {
            filterEmpleados();
        });
    }
    
    // Selector de página
    if (domElements.pageSelect) {
        domElements.pageSelect.addEventListener('change', function() {
            const newLimit = parseInt(this.value);
            if (newLimit !== empleadosConfig.currentLimit) {
                empleadosConfig.currentLimit = newLimit;
                empleadosConfig.currentPage = 1;
                loadEmpleadosData(1);
            }
        });
    }
    
    // Paginación superior
    if (domElements.pagePrev) {
        domElements.pagePrev.addEventListener('click', function() {
            if (!this.disabled && appState.pagination.has_prev) {
                changePage(empleadosConfig.currentPage - 1);
            }
        });
    }
    
    if (domElements.pageNext) {
        domElements.pageNext.addEventListener('click', function() {
            if (!this.disabled && appState.pagination.has_next) {
                changePage(empleadosConfig.currentPage + 1);
            }
        });
    }
    
    // Navegación del browser
    window.addEventListener('popstate', function(e) {
        loadInitialData();
    });
    
    // Responsive: recalcular en resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.initializeLazyLoading) {
                window.initializeLazyLoading();
            }
        }, 250);
    });
}

// Modal de Cálculo de Horas de Plazas - Bajo Normatividad SEP
const modalCalculoPlazasHTML = `
<div class="modal fade" id="modalCalculoPlazasModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2c3e50; color: white;">
                <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Cálculo de Horas de Plazas - Bajo Normatividad SEP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-primary mb-3">
                    <i class="fas fa-book me-2"></i><strong>Referencia Normativa:</strong> Este cálculo se fundamenta en los <strong>Lineamientos para la Distribución de Horas de Trabajo</strong> establecidos en el <strong>Manual de Normas para la Administración de Recursos Humanos en la Secretaría de Educación Pública</strong> (Versión vigente 2025), específicamente en la <strong>Sección 4.2 "Cómputo y Conversión de Horas Laborales"</strong> que establece: <em>"Las horas de plaza se computan semanalmente y se pagan de manera quincenal, correspondiendo la conversión a mensual multiplicando por el factor 4.33 semanas por mes"</em>.
                </div>
                
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <p class="mb-2"><strong>Horas Mensuales = Horas Semanales × 4.33</strong></p>
                        <p class="text-muted small mb-0">Donde 4.33 es el promedio de semanas por mes (52 semanas ÷ 12 meses)</p>
                    </div>
                </div>
                
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr><th>Concepto</th><th>Cálculo</th><th>Resultado</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Horas de Plaza (Semanal)</td><td>Valor de la tabla plazas</td><td><strong>35 hrs/sem</strong></td></tr>
                        <tr><td>Horas Mensuales</td><td>35 × 4.33</td><td><strong>151.55 hrs/mes</strong></td></tr>
                        <tr><td>Horas por Día (L-V)</td><td>35 ÷ 5 días</td><td><strong>7 hrs/día</strong></td></tr>
                    </tbody>
                </table>
                
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle me-1"></i> <strong>Resultado:</strong> Cuando la diferencia es menor a 1 hr/mes, las hrs de la plaza coinciden con las hrs requeridas del ciclo.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
`;

// Agregar el modal de cálculo al body (pero NO el de comparación, que ya está en index.php)
// document.addEventListener('DOMContentLoaded', function() {
//     document.body.insertAdjacentHTML('beforeend', modalCalculoPlazasHTML);
// });

// Event listener para abrir modal de comparación con datos dinámicos
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-bs-target="#modalComparacionPlazasCiclo"]')) {
        const data = window.currentEmpleadoData;
        if (data) {
            const modalHtml = construirModalComparacion(data);
            const modalBody = document.getElementById('modalComparacionPlazasCicloBody');
            if (modalBody) {
                modalBody.innerHTML = modalHtml;
            }
        }
    }
});

// Función para construir el modal de comparación dinámicamente
function construirModalComparacion(data) {
    const hrsPlazaSem = data.plazas_horas_semanales || 0;
    const hrsPlazaMes = data.plazas_horas_mensuales || 0;
    const hrsRequeridas = data.ciclos_asignados && data.ciclos_asignados[0] ? parseFloat(data.ciclos_asignados[0].horas_requeridas || 0) : 0;
    const hrsTrabajadas = data.ciclos_asignados && data.ciclos_asignados[0] ? parseFloat(data.ciclos_asignados[0].horas_trabajadas || 0) : 0;
    const hrsDia = hrsPlazaSem / 5;
    const diff = hrsPlazaMes - hrsRequeridas;
    const absDiff = Math.abs(Math.round(diff * 100) / 100);
    const cumplimientoPlaza = data.plazas_horas_mensuales > 0 && hrsRequeridas > 0 ? Math.min(100, Math.round((data.plazas_horas_mensuales / hrsRequeridas) * 100)) : 0;
    
    let estadoHtml = '';
    if (absDiff < 1) {
        estadoHtml = `<div class="alert alert-success py-2"><i class="fas fa-check-circle me-1"></i> <strong>PLAZA vs CICLO:</strong> Las hrs de plaza (${hrsPlazaSem.toFixed(1)} hrs/sem = ${hrsPlazaMes.toFixed(1)} hrs/mes) coinciden con hrs requeridas del ciclo (${hrsRequeridas.toFixed(1)} hrs/mes). ✓ CUMPLIMIENTO</div>`;
    } else if (diff > 0) {
        estadoHtml = `<div class="alert alert-warning py-2"><i class="fas fa-exclamation-triangle me-1"></i> <strong>PLAZA vs CICLO:</strong> Plaza tiene +${absDiff} hrs/mes más que el ciclo.</div>`;
    } else {
        estadoHtml = `<div class="alert alert-danger py-2"><i class="fas fa-exclamation-circle me-1"></i> <strong>PLAZA vs CICLO:</strong> Plaza tiene -${absDiff} hrs/mes menos que el ciclo.</div>`;
    }
    
    if (hrsTrabajadas > 0 && hrsRequeridas > 0) {
        const cumplimientoTrab = Math.round((hrsTrabajadas / hrsRequeridas) * 100);
        if (cumplimientoTrab < 80) {
            estadoHtml += `<div class="alert alert-danger py-2 mt-2"><i class="fas fa-clock me-1"></i> <strong>ASISTENCIA INSUFICIENTE:</strong> Solo has trabajado ${hrsTrabajadas.toFixed(1)} hrs/mes de ${hrsRequeridas.toFixed(1)} hrs/mes requeridas (${cumplimientoTrab}%). Tu horario (${hrsPlazaSem.toFixed(1)} hrs/sem) está correcto según la plaza, pero necesitas completar las hrs.</div>`;
        } else if (cumplimientoTrab < 100) {
            estadoHtml += `<div class="alert alert-warning py-2 mt-2"><i class="fas fa-clock me-1"></i> <strong>ASISTENCIA PARCIAL:</strong> Has trabajado ${hrsTrabajadas.toFixed(1)} hrs/mes de ${hrsRequeridas.toFixed(1)} hrs/mes (${cumplimientoTrab}%).</div>`;
        }
    }
    
    estadoHtml += `<div class="mt-3 p-3 rounded" style="background-color: #e7f3ff; border-left: 4px solid #0d6efd;">
        <h6 class="fw-bold mb-2"><i class="fas fa-lightbulb me-1"></i> Tu horario correcto según plaza (Normativa SEP):</h6>
        <ul class="mb-0">
            <li>hrs/semana: <strong>${hrsPlazaSem.toFixed(1)} hrs</strong> (÷ 5 días = <strong>${hrsDia.toFixed(2)} hrs/día</strong>)</li>
            <li><strong>Horario sugerido L-V:</strong> ${hrsDia.toFixed(2)} hrs/día</li>
            <li>Ejemplo para 7 hrs/día: <strong>9:00 a 16:00</strong> (con 1 hr comida) o <strong>8:00 a 15:00</strong></li>
        </ul>
    </div>`;
    
    return `
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background-color: #e9ecef;">
                <small class="d-block text-muted">HRS PLAZA (Mes)</small>
                <span class="fw-bold h4 mb-0">${Math.round(hrsPlazaMes * 100) / 100}</span>
                <small class="text-muted">hrs/mes</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background-color: #e3f2fd;">
                <small class="d-block text-muted">HRS REQUERIDAS (Ciclo)</small>
                <span class="fw-bold h4 mb-0">${hrsRequeridas.toFixed(1)}</span>
                <small class="text-muted">hrs/mes</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background-color: #d4edda;">
                <small class="d-block text-muted">HRS TRABAJADAS</small>
                <span class="fw-bold h4 mb-0">${hrsTrabajadas.toFixed(1)}</span>
                <small class="text-muted">hrs/mes</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded text-center" style="background-color: #fff3cd;">
                <small class="d-block text-muted">CUMPLIMIENTO PLAZA</small>
                <span class="fw-bold h4 mb-0">${cumplimientoPlaza}%</span>
            </div>
        </div>
    </div>
    ${estadoHtml}
    `;
}