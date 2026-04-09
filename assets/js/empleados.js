/**
 * JavaScript para la vista de empleados con paginación y filtros optimizados
 * Proporciona una experiencia de usuario moderna y responsiva
 */

// Configuración global
const empleadosConfig = {
    baseUrl: window.location.origin,
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
            ? `<img src="${empleado.foto_cara}" alt="${empleado.nombre}" class="rounded-circle mb-3 lazyload" data-src="${empleado.foto_cara}" style="width:100px;height:100px;object-fit:cover;">`
            : `<div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;"><i class="fas fa-user text-muted" style="font-size:2rem;"></i></div>`;
        
        const statusBadge = empleado.activo
            ? '<span class="badge bg-success" style="font-size:1rem; padding: 8px 20px;">Activo</span>'
            : '<span class="badge bg-danger" style="font-size:1rem; padding: 8px 20px;">Inactivo</span>';
        
        return `
            <div>
                <div class="card h-100 shadow-sm employee-card" data-employee-id="${empleado.id}">
                    <div class="card-body text-center p-4">
                        ${fotoHtml}
                        
                        <div class="fw-bold" style="font-size:1.3rem;">${escapeHtml((empleado.nombre || '' || "-") + ' ' + (empleado.apellido || ''))}</div>
                        <small class="text-muted d-block mb-3" style="font-size:1rem;">${escapeHtml(empleado.area || '-' || "-")}</small>
                        
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
        button.addEventListener('click', function() {
            const employeeId = this.dataset.id;
            window.location.href = `${empleadosConfig.baseUrl}/empleados/${employeeId}`;
        });
    });
    
    document.querySelectorAll('.editar-empleado').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.dataset.id;
            window.location.href = `${empleadosConfig.baseUrl}/empleados/${employeeId}?action=edit`;
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
    paginationHTML += `<li class="page-item ${!hasPrev ? 'disabled' : ''}">
            <a class="page-link pagination-link" href="#" data-page="${currentPage - 1}" ${!hasPrev ? 'tabindex="-1"' : ''}>
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>    `;
    
    // Página actual
    paginationHTML += ` <li class="page-item active">
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
        
        paginationHTML += ` <li class="page-item">
                <a class="page-link pagination-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Botón siguiente
    paginationHTML += ` <li class="page-item ${!hasNext ? 'disabled' : ''}">
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
            // Recalcular lazy loading
            if (window.initializeLazyLoading) {
                window.initializeLazyLoading();
            }
        }, 250);
    });
}