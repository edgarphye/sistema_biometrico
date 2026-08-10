<script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/three.min.js"></script>
<script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/OrbitControls.js"></script>
<div class="ai-dashboard">
    <!-- Hero Header -->
    <div class="hero-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="hero-icon me-3">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div>
                        <h1 class="hero-title">Panel de Inteligencia Artificial</h1>
                        <p class="hero-subtitle">Análisis predictivo, redes neuronales y detección de patrones en tiempo real</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="status-badge">
                    <span class="status-dot"></span>
                    <span>Sistema Activo</span>
                </div>
                <button class="btn btn-refresh" onclick="actualizarAnalisis()">
                    <i class="fas fa-sync me-1"></i> Actualizar
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Cards - Collapsible -->
    <div class="collapsible-section" id="section-kpi">
        <div class="collapsible-header active" onclick="toggleCollapsible('section-kpi')">
            <div class="collapsible-title">
                <i class="fas fa-chart-line"></i>
                <span>Métricas del Sistema</span>
            </div>
            <div class="collapsible-actions">
                <div class="collapsible-toggle expanded">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
        <div class="collapsible-content expanded">
            <div class="collapsible-body">
                <div class="kpi-grid">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" id="emp-activos">-</span>
                <span class="kpi-label">Empleados Activos</span>
            </div>
            <div class="kpi-trend up"><i class="fas fa-arrow-up"></i></div>
        </div>
        <div class="kpi-card kpi-success">
            <div class="kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" id="riesgo-promedio">-</span>
                <span class="kpi-label">Riesgo Promedio</span>
            </div>
            <div class="kpi-trend down"><i class="fas fa-arrow-down"></i></div>
        </div>
        <div class="kpi-card kpi-warning">
            <div class="kpi-icon"><i class="fas fa-bell"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" id="alertas-criticas">-</span>
                <span class="kpi-label">Alertas Activas</span>
            </div>
            <div class="kpi-trend neutral"><i class="fas fa-minus"></i></div>
        </div>
        <div class="kpi-card kpi-danger">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" id="emp-criticos">-</span>
                <span class="kpi-label">Críticos</span>
            </div>
            <div class="kpi-trend up"><i class="fas fa-arrow-up"></i></div>
        </div>
        <div class="kpi-card kpi-info">
            <div class="kpi-icon"><i class="fas fa-database"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" id="snapshots-total">-</span>
                <span class="kpi-label">Snapshots IA</span>
            </div>
        </div>
        <div class="kpi-card kpi-secondary">
            <div class="kpi-icon"><i class="fas fa-clock"></i></div>
            <div class="kpi-content">
                <span class="kpi-value" id="ultima-actualizacion">-</span>
                <span class="kpi-label">Última Actualización</span>
            </div>
        </div>
            </div>
        </div>
    </div>

    <!-- Neural Networks Section - Visualización Virtual 3D -->
    <div class="section-card mb-4 neural-full-width">
        <div class="neural-viz-wrapper">
            <div class="neural-viz-header" onclick="toggleNeuralViz()" id="neuralVizHeader">
                <div class="section-title">
                    <i class="fas fa-brain"></i>
                    <span>Redes Neuronales - Visualización 3D</span>
                </div>
                <div class="section-header-actions">
                    <div class="section-badge">
                        <span class="pulse"></span> 6 Modelos
                    </div>
                    <button class="btn-neural-refresh" onclick="event.stopPropagation(); cargarRedesNeuronales()" title="Actualizar Redes">
                        <i class="fas fa-sync" id="neural-refresh-icon"></i>
                    </button>
                    <i class="fas fa-chevron-down toggle-icon" id="neural-toggle-icon"></i>
                </div>
            </div>
            
            <!-- Contenedor 3D de Visualización Neural -->
            <div class="neural-viz-container" id="neuralVizContainer">
                <div id="neural3d-container"></div>
                <canvas id="neural2d-container"></canvas>
                
                <!-- Instrucciones - Arriba centro -->
                <div class="neural-instructions">
                    <i class="fas fa-info-circle"></i> Arrastra para rotar • Scroll para zoom • Click en neurona para detalles
                </div>
                
                <!-- Panel de Control 3D - Izquierda arriba -->
                <div class="neural3d-controls">
                    <div class="control-group">
                        <label><i class="fas fa-eye"></i> Ver:</label>
                        <select id="modelFilter" onchange="filterModel3D(this.value)">
                            <option value="all">Todos</option>
                            <option value="perceptron">Perceptrón</option>
                            <option value="mlp">MLP</option>
                            <option value="lstm">LSTM</option>
                            <option value="rnn">RNN</option>
                            <option value="transformer">Transformer</option>
                            <option value="ensemble">Ensemble</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label><i class="fas fa-expand"></i> Zoom:</label>
                        <input type="range" min="50" max="500" value="200" onchange="updateZoom(this.value)" id="zoomSlider">
                    </div>
                    <div class="control-group">
                        <label><i class="fas fa-tachometer-alt"></i> Velocidad:</label>
                        <input type="range" min="0" max="100" value="50" onchange="updateSpeed(this.value)" id="speedSlider">
                    </div>
                    <div class="control-group">
                        <label><i class="fas fa-magic"></i> Efectos:</label>
                        <div class="effects-toggles">
                            <label class="effect-toggle" title="Partículas">
                                <input type="checkbox" checked onchange="toggleEffect('particles', this.checked)">
                                <i class="fas fa-star"></i>
                            </label>
                            <label class="effect-toggle" title="Cambio de color">
                                <input type="checkbox" checked onchange="toggleEffect('colorShift', this.checked)">
                                <i class="fas fa-palette"></i>
                            </label>
                            <label class="effect-toggle" title="Vibración">
                                <input type="checkbox" checked onchange="toggleEffect('vibration', this.checked)">
                                <i class="fas fa-bolt"></i>
                            </label>
                            <label class="effect-toggle" title="Rastros">
                                <input type="checkbox" checked onchange="toggleEffect('trails', this.checked)">
                                <i class="fas fa-tint"></i>
                            </label>
                        </div>
                    </div>
                    <div class="control-buttons">
                        <button onclick="resetCamera()" title="Reset Cámara"><i class="fas fa-home"></i></button>
                        <button onclick="toggleAutoRotate()" id="autoRotateBtn" title="Auto Rotar"><i class="fas fa-sync"></i></button>
                        <button onclick="showAllNeurons()" title="Mostrar Todas"><i class="fas fa-eye"></i></button>
                        <button onclick="toggle2D3D()" id="btnToggle2D3D" class="active" title="Cambiar 2D/3D"><i class="fas fa-cube"></i></button>
                        <button onclick="toggleAnimations()" id="btnToggleAnimations" class="active" title="Animaciones"><i class="fas fa-play"></i></button>
                    </div>
                </div>
                
                <!-- Tooltip 3D - Derecha -->
                <div class="neural-tooltip" id="neural-tooltip">
                    <div class="tooltip-header">
                        <span class="tooltip-model-name">Perceptrón</span>
                        <button class="tooltip-close" onclick="ocultarTooltipNeurona()">×</button>
                    </div>
                    <div class="tooltip-content">
                        <div class="tooltip-row">
                            <span class="tooltip-label">Neurona:</span>
                            <span class="tooltip-value" id="tooltip-neurona-id">-</span>
                        </div>
                        <div class="tooltip-row">
                            <span class="tooltip-label">Nombre:</span>
                            <span class="tooltip-value" id="tooltip-empleado-nombre" style="font-size:10px">-</span>
                        </div>
                        <div class="tooltip-row">
                            <span class="tooltip-label">Modelo:</span>
                            <span class="tooltip-value" id="tooltip-modelo">-</span>
                        </div>
                        <div class="tooltip-row">
                            <span class="tooltip-label">Capa:</span>
                            <span class="tooltip-value" id="tooltip-capa">-</span>
                        </div>
                        <div class="tooltip-divider"></div>
                        <div class="tooltip-section-title">Análisis</div>
                        <div class="tooltip-row">
                            <span class="tooltip-label"><i class="fas fa-clock"></i> Retardos:</span>
                            <span class="tooltip-value" id="tooltip-retardos">-</span>
                        </div>
                        <div class="tooltip-row">
                            <span class="tooltip-label"><i class="fas fa-calendar-times"></i> Faltas:</span>
                            <span class="tooltip-value" id="tooltip-faltas">-</span>
                        </div>
                        <div class="tooltip-row">
                            <span class="tooltip-label"><i class="fas fa-exclamation-triangle"></i> Riesgo:</span>
                            <span class="tooltip-value" id="tooltip-riesgo">-</span>
                        </div>
                        <button class="tooltip-btn-details" onclick="verAnalisisCompleto()">
                            <i class="fas fa-search-plus"></i> Ver Análisis
                        </button>
                    </div>
                </div>
                
                <!-- Overlay Info - Abajo -->
                <div class="neural-overlay-info">
                    <div class="neural-model-legend">
                        <div class="legend-item" data-model="perceptron" onclick="filterModel3D('perceptron')"><span class="legend-dot primary"></span> Perceptrón</div>
                        <div class="legend-item" data-model="mlp" onclick="filterModel3D('mlp')"><span class="legend-dot info"></span> MLP</div>
                        <div class="legend-item" data-model="lstm" onclick="filterModel3D('lstm')"><span class="legend-dot warning"></span> LSTM</div>
                        <div class="legend-item" data-model="rnn" onclick="filterModel3D('rnn')"><span class="legend-dot danger"></span> RNN</div>
                        <div class="legend-item" data-model="transformer" onclick="filterModel3D('transformer')"><span class="legend-dot purple"></span> Transformer</div>
                        <div class="legend-item" data-model="ensemble" onclick="filterModel3D('ensemble')"><span class="legend-dot success"></span> Ensemble</div>
                    </div>
                    <div class="neural-stats">
                        <div class="stat-item">
                            <span class="stat-value" id="total-neuronas">0</span>
                            <span class="stat-label">Neuronas</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="total-conexiones">0</span>
                            <span class="stat-label">Conexiones</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="fps-display">0</span>
                            <span class="stat-label">FPS</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grid de Modelos Neurales - Fuera del contenedor 3D -->
        <div class="neural-models-container">
            <div class="neural-models-grid" id="neural-models-grid"></div>
            <div class="neural-hidden-models" id="neural-hidden-models" style="display: none;">
                <span><i class="fas fa-eye-slash"></i> Modelos ocultos:</span>
                <div id="hidden-models-list"></div>
            </div>
        </div>
    </div>
</div>

    <!-- Análisis y Tablas - Collapsible -->
    <div class="collapsible-section" id="section-analisis">
        <div class="collapsible-header active" onclick="toggleCollapsible('section-analisis')">
            <div class="collapsible-title">
                <i class="fas fa-search-plus"></i>
                <span>Análisis de Empleados y Datos</span>
            </div>
            <div class="collapsible-actions">
                <div class="collapsible-toggle expanded">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
        <div class="collapsible-content expanded">
            <div class="collapsible-body" style="padding: 0;">
    
    <!-- Tabs -->
    <div class="custom-tabs mb-3" style="margin: 20px;">
        <button class="tab-btn active" data-tab="riesgo">
            <i class="fas fa-exclamation-triangle me-2"></i>Empleados en Riesgo
        </button>
        <button class="tab-btn" data-tab="alertas">
            <i class="fas fa-bell me-2"></i>Alertas del Sistema
        </button>
        <button class="tab-btn" data-tab="justificaciones">
            <i class="fas fa-clipboard-check me-2"></i>Análisis Justificaciones
        </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-content-custom">
        <!-- Riesgo Tab -->
        <div class="tab-pane-custom active" id="tab-riesgo">
            <div class="data-table-wrapper">
                <table class="data-table" id="tabla-riesgo">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Nombre</th>
                            <th><i class="fas fa-building"></i> Área</th>
                            <th><i class="fas fa-clock"></i> Retardos</th>
                            <th><i class="fas fa-hourglass-half"></i> Minutos</th>
                            <th><i class="fas fa-calendar-times"></i> Faltas</th>
                            <th><i class="fas fa-exclamation-circle"></i> Riesgo</th>
                            <th><i class="fas fa-cog"></i> Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="8" class="text-center p-4"><div class="loading-spinner"></div> Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alertas Tab -->
        <div class="tab-pane-custom" id="tab-alertas">
            <div class="alerts-container" id="alertas-content">
                <div class="text-center p-4 text-muted">Cargando alertas...</div>
            </div>
        </div>

        <!-- Justificaciones Tab -->
        <div class="tab-pane-custom" id="tab-justificaciones">
            <!-- Resumen Global de Justificaciones -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="metric-card metric-success">
                        <div class="metric-card-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="metric-card-content">
                            <div class="metric-card-value" id="de-disponibles-global">-</div>
                            <div class="metric-card-label">Días Económicos Disp.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card metric-info">
                        <div class="metric-card-icon"><i class="fas fa-user-md"></i></div>
                        <div class="metric-card-content">
                            <div class="metric-card-value" id="licencia-disponible-global">-</div>
                            <div class="metric-card-label">Licencia Médica Disp.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card metric-warning">
                        <div class="metric-card-icon"><i class="fas fa-umbrella-beach"></i></div>
                        <div class="metric-card-content">
                            <div class="metric-card-value" id="vacaciones-disponibles-global">-</div>
                            <div class="metric-card-label">Vacaciones Disp.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card metric-primary">
                        <div class="metric-card-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="metric-card-content">
                            <div class="metric-card-value" id="mejora-alta-count">-</div>
                            <div class="metric-card-label">Alto Potencial Mejora</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Límites Configurados -->
            <div class="section-card mb-4">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-cogs"></i>
                        <span>Límites y Reglas de Justificaciones</span>
                    </div>
                </div>
                <div class="p-3">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="limite-card">
                                <div class="limite-icon"><i class="fas fa-calendar-day"></i></div>
                                <div class="limite-title">Días Económicos</div>
                                <div class="limite-value">9 / período escolar</div>
                                <div class="limite-detail">Antigüedad mínima: 6 meses</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="limite-card">
                                <div class="limite-icon"><i class="fas fa-user-md"></i></div>
                                <div class="limite-title">Licencia Médica</div>
                                <div class="limite-value">15-60 días/año</div>
                                <div class="limite-detail">Según antigüedad</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="limite-card">
                                <div class="limite-icon"><i class="fas fa-umbrella-beach"></i></div>
                                <div class="limite-title">Vacaciones</div>
                                <div class="limite-value">9 días / año</div>
                                <div class="limite-detail">Requiere aprobación</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="limite-card">
                                <div class="limite-icon"><i class="fas fa-clock"></i></div>
                                <div class="limite-title">Retardos</div>
                                <div class="limite-value">2 / quincena</div>
                                <div class="limite-detail">Sin nota mala</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empleados con Potencial de Mejora -->
            <div class="section-card mb-4">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-rocket"></i>
                        <span>Empleados con Mayor Potencial de Mejora</span>
                    </div>
                </div>
                <div class="data-table-wrapper">
                    <table class="data-table" id="tabla-mejora">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-user"></i> Nombre</th>
                                <th><i class="fas fa-building"></i> Área</th>
                                <th><i class="fas fa-calendar"></i> Antigüedad</th>
                                <th><i class="fas fa-calendar-check"></i> DE Disp.</th>
                                <th><i class="fas fa-user-md"></i> Lic. Disp.</th>
                                <th><i class="fas fa-umbrella-beach"></i> Vac. Disp.</th>
                                <th><i class="fas fa-stopwatch"></i> Ret/15d</th>
                                <th><i class="fas fa-star"></i> Potencial</th>
                                <th><i class="fas fa-cog"></i> Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-mejora-body">
                            <tr><td colspan="10" class="text-center p-4"><div class="loading-spinner"></div> Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Factores de Mejora -->
            <div class="row">
                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-thumbs-up"></i>
                                <span>Factores Positivos (Pueden Mejorar)</span>
                            </div>
                        </div>
                        <div class="p-3" id="factores-positivos">
                            <div class="text-center text-muted">Cargando...</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-card">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-thumbs-down"></i>
                                <span>Factores de Riesgo (Difícil Mejora)</span>
                            </div>
                        </div>
                        <div class="p-3" id="factores-negativos">
                            <div class="text-center text-muted">Cargando...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Grid -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-building"></i>
                        <span>Áreas con Más Incidencias</span>
                    </div>
                </div>
                <div class="areas-list" id="areas-content">
                    <div class="text-center text-muted p-3">Cargando...</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-chart-line"></i>
                        <span>Métricas en Tiempo Real</span>
                    </div>
                </div>
                <div class="metrics-grid" id="metricas-tiempo-real">
                    <div class="text-center text-muted p-3">Cargando...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Análisis Completo -->
<div class="modal fade" id="modalAnalisisAI" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header-custom">
                <div class="modal-title-custom">
                    <i class="fas fa-brain"></i>
                    <span>Análisis de Inteligencia Artificial</span>
                </div>
                <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-custom" id="contenido-analisis">
                <div class="loading-state">
                    <div class="loading-spinner-lg"></div>
                    <p>Cargando análisis...</p>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary-custom" onclick="actualizarAnalisisEmpleado()">
                    <i class="fas fa-sync me-1"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS Variables - Pantone Institutional Colors */
.ai-dashboard {
    --pantone-wine: #9F2241;
    --pantone-wine-dark: #691C32;
    --pantone-wine-light: #B82E56;
    --pantone-gold: #C4A052;
    --pantone-gold-light: #D4B872;
    --pantone-slate: #3D4F5F;
    --pantone-slate-light: #5A6E7F;
    --pantone-cream: #F5F1E8;
    --pantone-gray: #6C757D;
    --pantone-success: #2E7D32;
    --pantone-warning: #F9A825;
    --pantone-danger: #C62828;
    --pantone-info: #0277BD;
    
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.16);
    --shadow-glow: 0 0 20px rgba(159, 34, 65, 0.3);
    
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    
    --font-primary: 'Segoe UI', system-ui, -apple-system, sans-serif;
    
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.ai-dashboard > * {
    box-sizing: border-box;
}

/* Asegurar que el contenido interno también se ajuste */
.ai-dashboard .row,
.ai-dashboard .col-md-,
.ai-dashboard .container,
.ai-dashboard .container-fluid {
    box-sizing: border-box;
    max-width: 100%;
}

@media (max-width: 768px) {
    .ai-dashboard {
        width: 100% !important;
    }
}

/* Hero Header */
.hero-header {
    background: linear-gradient(135deg, var(--pantone-wine) 0%, var(--pantone-wine-dark) 100%);
    border-radius: 0;
    padding: 20px 24px;
    color: white;
    box-shadow: var(--shadow-lg);
    margin-bottom: 20px;
}

.hero-icon {
    width: 64px;
    height: 64px;
    background: rgba(255,255,255,0.15);
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    backdrop-filter: blur(10px);
}

.hero-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.5px;
}

.hero-subtitle {
    font-size: 14px;
    margin: 4px 0 0;
    opacity: 0.85;
    font-weight: 400;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 12px;
    backdrop-filter: blur(10px);
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #4ADE80;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.2); }
}

.btn-refresh {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 10px 20px;
    border-radius: var(--radius-sm);
    font-weight: 500;
    transition: all 0.3s;
}

.btn-refresh:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-1px);
}

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
}

.kpi-card {
    background: white;
    border-radius: var(--radius-md);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid transparent;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--pantone-wine), var(--pantone-wine-light));
    opacity: 0;
    transition: opacity 0.3s;
}

.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.kpi-card:hover::before {
    opacity: 1;
}

.kpi-card.kpi-primary { border-left-color: var(--pantone-wine); }
.kpi-card.kpi-success { border-left-color: var(--pantone-success); }
.kpi-card.kpi-warning { border-left-color: var(--pantone-warning); }
.kpi-card.kpi-danger { border-left-color: var(--pantone-danger); }
.kpi-card.kpi-info { border-left-color: var(--pantone-info); }
.kpi-card.kpi-secondary { border-left-color: var(--pantone-gray); }

.kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.kpi-primary .kpi-icon { background: rgba(159, 34, 65, 0.1); color: var(--pantone-wine); }
.kpi-success .kpi-icon { background: rgba(46, 125, 50, 0.1); color: var(--pantone-success); }
.kpi-warning .kpi-icon { background: rgba(249, 168, 37, 0.1); color: var(--pantone-warning); }
.kpi-danger .kpi-icon { background: rgba(198, 40, 40, 0.1); color: var(--pantone-danger); }
.kpi-info .kpi-icon { background: rgba(2, 119, 189, 0.1); color: var(--pantone-info); }
.kpi-secondary .kpi-icon { background: rgba(108, 117, 125, 0.1); color: var(--pantone-gray); }

.kpi-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.kpi-value {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}

.kpi-label {
    font-size: 12px;
    color: var(--pantone-gray);
    font-weight: 500;
}

.kpi-trend {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.kpi-trend.up { background: rgba(198, 40, 40, 0.1); color: var(--pantone-danger); }
.kpi-trend.down { background: rgba(46, 125, 50, 0.1); color: var(--pantone-success); }
.kpi-trend.neutral { background: rgba(108, 117, 125, 0.1); color: var(--pantone-gray); }

/* Section Card */
.section-card {
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

/* Collapsible Section Styles */
.collapsible-section {
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 16px;
}

.collapsible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    cursor: pointer;
    user-select: none;
    transition: all 0.3s;
    border-bottom: 2px solid transparent;
}

.collapsible-header:hover {
    background: rgba(159, 34, 65, 0.03);
}

.collapsible-header.active {
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    color: white;
    border-bottom-color: #691C32;
}

.collapsible-header.active .collapsible-title i {
    color: #DDC9A3;
}

.collapsible-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    font-weight: 600;
}

.collapsible-title i {
    font-size: 18px;
    color: #9F2241;
}

.collapsible-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.collapsible-toggle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    background: rgba(0,0,0,0.05);
    color: #6c757d;
}

.collapsible-header.active .collapsible-toggle {
    background: rgba(255,255,255,0.2);
    color: white;
}

.collapsible-toggle i {
    transition: transform 0.3s;
}

.collapsible-toggle.expanded i {
    transform: rotate(180deg);
}

.collapsible-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease-in-out;
}

.collapsible-content.expanded {
    max-height: 2000px;
}

.collapsible-body {
    padding: 20px;
}

.section-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.section-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.section-title i {
    font-size: 18px;
    color: #DDC9A3;
}

/* Estilos específicos para el contenido del modal */
#contenido-analisis .section-card {
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

#contenido-analisis .section-header {
    padding: 18px 24px;
    border-radius: 16px 16px 0 0;
}

#contenido-analisis .detail-item {
    background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.3s;
}

#contenido-analisis .detail-item:hover {
    background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
    border-color: rgba(159, 34, 65, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

#contenido-analisis .detail-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    font-weight: 700;
    margin-bottom: 6px;
}

#contenido-analisis .detail-value {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
}

#contenido-analisis .metric-card {
    border-radius: 16px;
    padding: 20px;
    background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

#contenido-analisis .metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: rgba(159, 34, 65, 0.2);
}

#contenido-analisis .metric-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 12px;
}

#contenido-analisis .metric-card-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 4px;
    color: #1a1a2e;
}

#contenido-analisis .metric-card-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    color: #495057;
}

/* Redes Neuronales en modal */
#contenido-analisis .neural-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
}

#contenido-analisis .neural-card {
    background: linear-gradient(145deg, #ffffff 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    border: 1px solid rgba(0,0,0,0.1);
    transition: all 0.3s;
}

#contenido-analisis .neural-card:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    border-color: rgba(159, 34, 65, 0.3);
}

#contenido-analisis .neural-name {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    margin-bottom: 8px;
}

#contenido-analisis .neural-value {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a2e;
}

/* Loading state en modal */
#contenido-analisis .loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    color: #6c757d;
}

#contenido-analisis .loading-spinner-lg {
    width: 50px;
    height: 50px;
    border: 4px solid #e9ecef;
    border-top-color: #9F2241;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.section-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.pulse {
    width: 8px;
    height: 8px;
    background: #4ADE80;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

/* Neural Grid */
.neural-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
    padding: 20px;
}

.neural-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: var(--radius-md);
    padding: 20px 16px;
    text-align: center;
    transition: all 0.3s;
    border: 1px solid #e9ecef;
}

.neural-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.neural-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 20px;
}

.neural-icon.primary { background: rgba(159, 34, 65, 0.1); color: var(--pantone-wine); }
.neural-icon.info { background: rgba(2, 119, 189, 0.1); color: var(--pantone-info); }
.neural-icon.warning { background: rgba(249, 168, 37, 0.1); color: var(--pantone-warning); }
.neural-icon.danger { background: rgba(198, 40, 40, 0.1); color: var(--pantone-danger); }
.neural-icon.purple { background: rgba(106, 27, 154, 0.1); color: #6A1B9A; }
.neural-icon.success { background: rgba(46, 125, 50, 0.1); color: var(--pantone-success); }

.neural-name {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.neural-status {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.neural-status.active {
    color: var(--pantone-success);
}

.neural-bar {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 8px;
}

.neural-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--pantone-wine), var(--pantone-wine-light));
    border-radius: 3px;
    transition: width 1s ease;
}

.neural-accuracy {
    font-size: 11px;
    color: var(--pantone-gray);
    font-weight: 500;
}

/* Custom Tabs */
.custom-tabs {
    display: flex;
    gap: 8px;
    background: white;
    padding: 6px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
}

.tab-btn {
    flex: 1;
    padding: 14px 24px;
    border: none;
    background: transparent;
    color: var(--pantone-gray);
    font-size: 14px;
    font-weight: 600;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tab-btn:hover {
    color: var(--pantone-wine);
    background: rgba(159, 34, 65, 0.05);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--pantone-wine) 0%, var(--pantone-wine-dark) 100%);
    color: white;
    box-shadow: var(--shadow-glow);
}

/* Tab Content */
.tab-content-custom {
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.tab-pane-custom {
    display: none;
    padding: 20px;
}

.tab-pane-custom.active {
    display: block;
}

/* Data Table */
.data-table-wrapper {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table thead tr {
    background: linear-gradient(135deg, var(--pantone-slate) 0%, var(--pantone-slate-light) 100%);
    color: white;
}

.data-table th {
    padding: 14px 16px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: left;
    white-space: nowrap;
}

.data-table th:first-child {
    border-radius: var(--radius-sm) 0 0 0;
}

.data-table th:last-child {
    border-radius: 0 var(--radius-sm) 0 0;
}

.data-table td {
    padding: 14px 16px;
    font-size: 13px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.data-table tbody tr {
    transition: all 0.2s;
}

.data-table tbody tr:hover {
    background: rgba(159, 34, 65, 0.03);
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.risk-critical { background: rgba(198, 40, 40, 0.08) !important; }
.risk-high { background: rgba(249, 168, 37, 0.08) !important; }

.badge-id {
    background: rgba(159, 34, 65, 0.1);
    color: var(--pantone-wine);
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.badge-area {
    background: var(--pantone-cream);
    color: var(--pantone-slate);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
}

.badge-retardo {
    background: rgba(249, 168, 37, 0.15);
    color: #F57F17;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.badge-minutos {
    background: rgba(2, 119, 189, 0.15);
    color: var(--pantone-info);
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.badge-falta {
    background: rgba(198, 40, 40, 0.15);
    color: var(--pantone-danger);
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.risk-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
}

.risk-badge.critical { background: var(--pantone-danger); color: white; }
.risk-badge.high { background: var(--pantone-warning); color: #1a1a2e; }
.risk-badge.medium { background: var(--pantone-info); color: white; }
.risk-badge.low { background: var(--pantone-success); color: white; }

.btn-analysis {
    background: linear-gradient(135deg, var(--pantone-wine) 0%, var(--pantone-wine-dark) 100%);
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-analysis:hover {
    transform: scale(1.05);
    box-shadow: var(--shadow-glow);
}

/* Alerts */
.alerts-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.alert-card {
    padding: 16px 20px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-left: 4px solid;
    transition: all 0.3s;
}

.alert-card:hover {
    transform: translateX(4px);
}

.alert-card.critico {
    background: rgba(198, 40, 40, 0.08);
    border-left-color: var(--pantone-danger);
}

.alert-card.alto {
    background: rgba(249, 168, 37, 0.08);
    border-left-color: var(--pantone-warning);
}

.alert-card.info {
    background: rgba(2, 119, 189, 0.08);
    border-left-color: var(--pantone-info);
}

.alert-card.success {
    background: rgba(46, 125, 50, 0.08);
    border-left-color: var(--pantone-success);
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
}

.critico .alert-icon { background: rgba(198, 40, 40, 0.15); color: var(--pantone-danger); }
.alto .alert-icon { background: rgba(249, 168, 37, 0.15); color: var(--pantone-warning); }
.info .alert-icon { background: rgba(2, 119, 189, 0.15); color: var(--pantone-info); }

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.alert-message {
    font-size: 13px;
    color: var(--pantone-gray);
}

.alert-time {
    font-size: 11px;
    color: var(--pantone-gray);
    margin-top: 4px;
}

/* Areas List */
.areas-list {
    padding: 16px;
}

.area-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 8px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transition: all 0.3s;
}

.area-item:hover {
    transform: translateX(4px);
    background: linear-gradient(135deg, var(--pantone-cream) 0%, #e9ecef 100%);
}

.area-name {
    font-weight: 500;
    color: #1a1a2e;
    display: flex;
    align-items: center;
    gap: 10px;
}

.area-icon {
    width: 32px;
    height: 32px;
    background: var(--pantone-wine);
    color: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.area-count {
    background: rgba(159, 34, 65, 0.1);
    color: var(--pantone-wine);
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 13px;
}

/* Metrics Grid */
.metrics-grid {
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.metric-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: var(--radius-sm);
}

.metric-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.metric-icon.primary { background: rgba(159, 34, 65, 0.1); color: var(--pantone-wine); }
.metric-icon.success { background: rgba(46, 125, 50, 0.1); color: var(--pantone-success); }
.metric-icon.warning { background: rgba(249, 168, 37, 0.1); color: var(--pantone-warning); }
.metric-icon.info { background: rgba(2, 119, 189, 0.1); color: var(--pantone-info); }

.metric-label {
    font-size: 11px;
    color: var(--pantone-gray);
    font-weight: 500;
}

.metric-value {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
}

/* Modal Custom - Diseño Elegante y Profesional */
.modal fade {
    --pantone-wine: #9F2241;
    --pantone-gold: #DDC9A3;
}

#modalAnalisisAI .modal-dialog {
    max-width: 900px;
    margin: 1.75rem auto;
}

#modalAnalisisAI .modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(159, 34, 65, 0.25);
    overflow: hidden;
}

#modalAnalisisAI .modal-header-custom {
    background: linear-gradient(135deg, #9F2241 0%, #691C32 50%, #4a1424 100%);
    color: white;
    padding: 22px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: none;
    position: relative;
    overflow: visible;
    z-index: 1050;
}

#modalAnalisisAI .modal-header-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}

#modalAnalisisAI .modal-title-custom {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 20px;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

#modalAnalisisAI .modal-title-custom i {
    font-size: 26px;
    color: #DDC9A3;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

#modalAnalisisAI .btn-close-modal {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.2);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 18px;
    right: 20px;
    z-index: 1060;
}

#modalAnalisisAI .btn-close-modal:hover {
    background: rgba(255,255,255,0.25);
    transform: rotate(90deg) scale(1.1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

#modalAnalisisAI .modal-body-custom {
    padding: 28px;
    min-height: 450px;
    max-height: 70vh;
    overflow-y: auto;
    background: #ffffff !important;
    position: relative;
    z-index: 1;
}

/* Forzar visibility de todo el contenido del modal */
#contenido-analisis {
    background: #ffffff !important;
    color: #1a1a2e !important;
}

#contenido-analisis * {
    color: #1a1a2e !important;
}

#contenido-analisis .text-muted {
    color: #495057 !important;
}

#contenido-analisis .text-primary {
    color: #9F2241 !important;
}

#contenido-analisis .text-success {
    color: #235B4E !important;
}

#contenido-analisis .text-danger {
    color: #C62828 !important;
}

#contenido-analisis .text-warning {
    color: #BC955C !important;
}

#contenido-analisis .text-info {
    color: #0277BD !important;
}

#contenido-analisis small {
    color: #6c757d !important;
}

#contenido-analisis .badge {
    color: #ffffff !important;
}

/* Forzar estilos de elementos del modal */
#contenido-analisis .section-card {
    background: #ffffff !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}

#contenido-analisis .section-header {
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%) !important;
    color: #ffffff !important;
}

#contenido-analisis .section-header * {
    color: #ffffff !important;
}

#contenido-analisis .section-title {
    color: #ffffff !important;
}

#contenido-analisis .section-title i {
    color: #DDC9A3 !important;
}

#contenido-analisis .metric-card {
    background: #ffffff !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}

#contenido-analisis .metric-card-icon {
    background: rgba(159, 34, 65, 0.1) !important;
    color: #9F2241 !important;
}

#contenido-analisis .neural-card {
    background: #ffffff !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}

#contenido-analisis .neural-mini-card {
    background: #ffffff !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}

#contenido-analisis .progress {
    background: #e9ecef !important;
}

#contenido-analisis .progress-bar {
    background: #9F2241 !important;
}

#contenido-analisis .stat-box {
    background: #ffffff !important;
    border: 1px solid rgba(0,0,0,0.1) !important;
}

#contenido-analisis .risk-factor {
    background: #fff5f5 !important;
    border-left: 4px solid #C62828 !important;
    border: 1px solid rgba(198, 40, 40, 0.2) !important;
}

/* Badge de Inactivo/Activo */
#contenido-analisis .badge-status-empleado {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#contenido-analisis .badge-status-empleado.badge-activo {
    background: linear-gradient(135deg, #235B4E 0%, #10312B 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(35, 91, 78, 0.3);
}

#contenido-analisis .badge-status-empleado.badge-inactivo {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
}

#modalAnalisisAI .modal-footer-custom {
    padding: 18px 28px;
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 1px solid rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#modalAnalisisAI .btn-secondary-custom {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
}

#modalAnalisisAI .btn-secondary-custom:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
}

#modalAnalisisAI .btn-primary-custom {
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    border: none;
    color: white;
    padding: 12px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(159, 34, 65, 0.35);
    position: relative;
    overflow: hidden;
}

#modalAnalisisAI .btn-primary-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: all 0.5s;
}

#modalAnalisisAI .btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(159, 34, 65, 0.45);
}

#modalAnalisisAI .btn-primary-custom:hover::before {
    left: 100%;
}

/* Loading */
.loading-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #e9ecef;
    border-top-color: var(--pantone-wine);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

.loading-spinner-lg {
    width: 48px;
    height: 48px;
    border: 4px solid #e9ecef;
    border-top-color: var(--pantone-wine);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.loading-state p {
    color: var(--pantone-gray);
    margin: 0;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1200px) {
    .kpi-grid { grid-template-columns: repeat(3, 1fr); }
    .neural-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .neural-grid { grid-template-columns: repeat(2, 1fr); }
    .custom-tabs { flex-direction: column; }
    .ai-dashboard {
        padding: 15px;
    }
    .hero-header {
        padding: 15px;
    }
    .hero-title {
        font-size: 22px;
    }
}

@media (max-width: 576px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .neural-grid { grid-template-columns: 1fr; }
    .hero-icon {
        width: 48px;
        height: 48px;
        font-size: 22px;
    }
    .hero-title {
        font-size: 18px;
    }
}

/* Limite Cards */
.limite-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: all 0.3s;
    height: 100%;
}

.limite-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border-color: rgba(159, 34, 65, 0.3);
}

.limite-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 20px;
}

.limite-title {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 8px;
}

.limite-value {
    font-size: 18px;
    font-weight: 700;
    color: #9F2241;
    margin-bottom: 4px;
}

.limite-detail {
    font-size: 11px;
    color: #6c757d;
}

/* Potencial Badge */
.potencial-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
}

.potencial-badge.alto {
    background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
    color: white;
}

.potencial-badge.medio {
    background: linear-gradient(135deg, #F9A825 0%, #F57F17 100%);
    color: #1a1a2e;
}

.potencial-badge.bajo {
    background: linear-gradient(135deg, #C62828 0%, #B71C1C 100%);
    color: white;
}

/* Disponibilidad Badge */
.dispo-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.dispo-badge.alta {
    background: rgba(46, 125, 50, 0.15);
    color: #2E7D32;
}

.dispo-badge.media {
    background: rgba(249, 168, 37, 0.15);
    color: #F57F17;
}

.dispo-badge.baja {
    background: rgba(198, 40, 40, 0.15);
    color: #C62828;
}

.dispo-badge.agotada {
    background: rgba(198, 40, 40, 0.25);
    color: #B71C1C;
}

/* Factores List */
.factores-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.factores-list li {
    padding: 10px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.factores-positivos li {
    background: rgba(46, 125, 50, 0.1);
    color: #2E7D32;
    border-left: 3px solid #2E7D32;
}

.factores-negativos li {
    background: rgba(198, 40, 40, 0.1);
    color: #C62828;
    border-left: 3px solid #C62828;
}

.factores-list li i {
    font-size: 14px;
}

.metric-card {
    border-radius: 12px;
    padding: 20px;
    background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    border-color: rgba(159, 34, 65, 0.2);
}

.metric-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 12px;
}

.metric-card.metric-success .metric-card-icon { background: rgba(46, 125, 50, 0.15); color: #2E7D32; }
.metric-card.metric-info .metric-card-icon { background: rgba(2, 119, 189, 0.15); color: #0277BD; }
.metric-card.metric-warning .metric-card-icon { background: rgba(249, 168, 37, 0.15); color: #F57F17; }
.metric-card.metric-primary .metric-card-icon { background: rgba(159, 34, 65, 0.15); color: #9F2241; }

.metric-card-content {
    display: flex;
    flex-direction: column;
}

.metric-card-value {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 4px;
    color: #1a1a2e;
}

.metric-card-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    color: #495057;
}

/* Neural Network 3D Visualization Styles */
.neural-full-width {
    overflow: visible;
    width: 100%;
    margin-left: 0;
    margin-right: 0;
}

.neural-viz-wrapper {
    margin: 0;
    position: relative;
    background: #0a0a1a;
    width: 100%;
    overflow: visible;
}

.neural-viz-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    padding: 14px 24px;
    cursor: pointer;
    user-select: none;
    border-bottom: 2px solid rgba(255,255,255,0.1);
}

.neural-viz-header:hover {
    background: linear-gradient(135deg, #B82E56 0%, #9F2241 100%);
}

.neural-viz-header .section-title {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
}

.neural-viz-header .section-title i {
    font-size: 22px;
    color: #DDC9A3;
}

.neural-viz-header .section-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.neural-viz-header .toggle-icon {
    transition: transform 0.3s;
    color: white;
    font-size: 16px;
}

.neural-viz-header.collapsed .toggle-icon {
    transform: rotate(-90deg);
}

.neural-viz-container {
    position: relative;
    background: linear-gradient(180deg, #0a0a1a 0%, #1a1a2e 100%);
    overflow: hidden;
    padding: 0;
    height: 90vh;
    min-height: 800px;
    max-height: 100vh;
    transition: height 0.5s ease-in-out;
    border-bottom: 3px solid #9F2241;
    width: 100%;
    max-width: 100vw;
    box-sizing: border-box;
}

.neural-viz-container.collapsed {
    height: 0;
    overflow: hidden;
    border-bottom: none;
}

#neural3d-container {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
}

#neural3d-container canvas {
    width: 100% !important;
    height: 100% !important;
    z-index: 10;
}

#neural2d-container {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    display: none;
    background: #0a0a1a;
    z-index: 10;
}

#neural2d-container.active {
    display: block;
    z-index: 10;
}

#neural3d-container.hidden {
    display: none;
}

/* 3D Controls */
.neural3d-controls {
    position: absolute;
    top: 15px;
    left: 15px;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
    padding: 15px;
    border-radius: 10px;
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border: 1px solid rgba(159, 34, 65, 0.6);
    max-width: 200px;
    width: auto;
    box-sizing: border-box;
}

.neural3d-controls .control-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.neural3d-controls label {
    font-size: 11px;
    color: rgba(255,255,255,0.9);
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 5px;
    line-height: 1.2;
}

.neural3d-controls label i {
    color: #9F2241;
    font-size: 11px;
}

.neural3d-controls select {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    padding: 6px 8px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    width: 100%;
}

.neural3d-controls select option {
    background: #1a1a2e;
    font-size: 12px;
}

.effects-toggles {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.effect-toggle {
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.3s;
    color: rgba(255,255,255,0.7);
}

.effect-toggle:hover {
    color: white;
    background: rgba(159, 34, 65, 0.3);
}

.effect-toggle input {
    display: none;
}

.effect-toggle input:checked + i {
    color: #9F2241;
    text-shadow: 0 0 8px #9F2241;
}

.neural3d-controls input[type="range"] {
    width: 80px;
    height: 2px;
    -webkit-appearance: none;
    background: rgba(255,255,255,0.2);
    border-radius: 2px;
    outline: none;
}

.neural3d-controls input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    background: #9F2241;
    border-radius: 50%;
    cursor: pointer;
}

.neural3d-controls .control-buttons {
    display: flex;
    gap: 6px;
    margin-top: 4px;
}

.neural3d-controls .control-buttons button {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 9px;
    padding: 0;
}

.neural3d-controls .control-buttons button:hover {
    background: #9F2241;
    border-color: #9F2241;
}

/* Botón modo vista */
.btn-neural-mode {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-neural-mode:hover {
    background: rgba(159, 34, 65, 0.8);
}

.btn-neural-mode.active {
    background: #9F2241;
    border-color: #9F2241;
}

.neural-overlay-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(0deg, rgba(10,10,26,0.98) 0%, rgba(10,10,26,0.8) 60%, transparent 100%);
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 50;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 50px;
}

.neural-model-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: rgba(255,255,255,0.8);
    font-weight: 500;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    animation: glow 2s infinite;
}

.legend-dot.primary { background: #9F2241; box-shadow: 0 0 8px #9F2241; }
.legend-dot.info { background: #0277BD; box-shadow: 0 0 8px #0277BD; }
.legend-dot.warning { background: #F9A825; box-shadow: 0 0 8px #F9A825; }
.legend-dot.danger { background: #C62828; box-shadow: 0 0 8px #C62828; }
.legend-dot.purple { background: #6A1B9A; box-shadow: 0 0 8px #6A1B9A; }
.legend-dot.success { background: #2E7D32; box-shadow: 0 0 8px #2E7D32; }

@keyframes glow {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.2); }
}

.neural-stats {
    display: flex;
    gap: 25px;
    padding: 10px 15px;
    background: rgba(0,0,0,0.3);
    border-radius: 10px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
}

.stat-label {
    font-size: 10px;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Neural Models Container */
.neural-models-container {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow-x: auto;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 0 0 10px 10px;
}

/* Neural Models Grid */
.neural-models-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    padding: 10px;
    width: 100%;
    box-sizing: border-box;
    min-width: 600px;
}

/* Hidden Models List */
.neural-hidden-models {
    padding: 10px;
    background: #e9ecef;
    border-radius: 8px;
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.neural-hidden-models span {
    font-size: 12px;
    color: #6c757d;
}

.hidden-model-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 15px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
}

.hidden-model-btn:hover {
    background: #9F2241;
    color: white;
    border-color: #9F2241;
}

.neural-model-card {
    display: block;
    background: linear-gradient(145deg, #ffffff 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    border: 2px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.neural-model-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--model-color, #9F2241), var(--model-color-light, #B82E56));
}

.neural-model-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--model-color, #9F2241);
}

.neural-model-card .model-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 18px;
}

.neural-model-card .model-name {
    font-size: 12px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.neural-model-card .model-prediction {
    font-size: 22px;
    font-weight: 700;
    color: var(--model-color, #9F2241);
    margin-bottom: 4px;
}

.neural-model-card .model-confidence {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 10px;
    display: inline-block;
    margin-bottom: 8px;
}

.confidence-alta { background: rgba(46, 125, 50, 0.15); color: #2E7D32; }
.confidence-media { background: rgba(249, 168, 37, 0.15); color: #F57F17; }
.confidence-baja { background: rgba(198, 40, 40, 0.15); color: #C62828; }

.neural-model-card .model-accuracy {
    font-size: 11px;
    color: #6c757d;
}

.neural-model-card .model-accuracy strong {
    color: #2E7D32;
}

.neural-model-card .model-state {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 8px;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
}

.state-entrenando { background: rgba(2, 119, 189, 0.15); color: #0277BD; }
.state-prediciendo { background: rgba(249, 168, 37, 0.15); color: #F57F17; }
.state-optimizado { background: rgba(46, 125, 50, 0.15); color: #2E7D32; }
.state-produccion { background: rgba(159, 34, 65, 0.15); color: #9F2241; }

.neural-model-card .pulse-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #4ADE80;
    animation: pulse 2s infinite;
}

.neural-model-card .model-toggle {
    position: absolute;
    top: 6px;
    left: 6px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: none;
    background: rgba(0, 0, 0, 0.3);
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.2s;
    z-index: 2;
}

.neural-model-card .model-toggle:hover {
    background: rgba(159, 34, 65, 0.8);
    transform: scale(1.1);
}

.neural-model-card.hidden-model {
    display: none;
}

@media (max-width: 1200px) {
    .neural-models-grid { grid-template-columns: repeat(3, 1fr); }
    .neural-viz-container { height: 85vh; min-height: 700px; }
}

@media (max-width: 768px) {
    .neural-models-grid { grid-template-columns: repeat(2, 1fr); }
    .neural-overlay-info { flex-direction: column; align-items: flex-start; gap: 12px; padding: 15px; }
    .neural-stats { width: 100%; justify-content: space-between; }
    .neural3d-controls { top: 10px; left: 10px; padding: 8px; }
    .neural-tooltip { top: 50px; right: 8px; width: 180px; }
    .neural-instructions { font-size: 9px; padding: 5px 10px; }
    .neural-viz-container { height: 80vh; min-height: 600px; }
}

@media (max-width: 576px) {
    .neural-models-grid { grid-template-columns: 1fr; }
    .neural-viz-container { height: 70vh; min-height: 500px; }
    .neural-stats { flex-direction: column; gap: 8px; }
}

/* Neural Header Actions */
.section-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-neural-refresh {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-neural-refresh:hover {
    background: rgba(255,255,255,0.25);
    transform: rotate(180deg);
}

.btn-neural-refresh.loading #neural-refresh-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Neural Tooltip */
.neural-tooltip {
    position: absolute;
    top: 60px;
    right: 20px;
    width: 200px;
    max-height: 400px;
    background: linear-gradient(145deg, #1a1a2e 0%, #0a0a1a 100%);
    border: 2px solid rgba(159, 34, 65, 0.9);
    border-radius: 10px;
    padding: 0;
    z-index: 999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease, display 0.3s ease;
    box-shadow: 0 8px 30px rgba(0,0,0,0.6);
    overflow: hidden;
    box-sizing: border-box;
}

.neural-tooltip.visible {
    display: block;
    opacity: 1;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.neural-tooltip .tooltip-header {
    background: linear-gradient(90deg, #9F2241, #691C32);
    padding: 6px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.neural-tooltip .tooltip-model-name {
    font-size: 11px;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.neural-tooltip .tooltip-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.neural-tooltip .tooltip-content {
    padding: 8px 10px;
    max-height: 300px;
    overflow-y: auto;
    overflow-x: hidden;
    color: #fff;
    scrollbar-width: thin;
    scrollbar-color: #9F2241 #1a1a2e;
}

.neural-tooltip .tooltip-divider {
    height: 1px;
    background: rgba(255,255,255,0.2);
    margin: 6px 0;
}

.neural-tooltip .tooltip-section-title {
    font-size: 10px;
    font-weight: 700;
    color: #9F2241;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(159, 34, 65, 0.3);
}

.neural-tooltip .tooltip-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.neural-tooltip .tooltip-row:last-child {
    border-bottom: none;
}

.neural-tooltip .tooltip-label {
    font-size: 9px;
    color: rgba(255,255,255,0.7);
    text-transform: uppercase;
    letter-spacing: 0.1px;
    display: flex;
    align-items: center;
    gap: 3px;
    min-width: 70px;
}

.neural-tooltip .tooltip-label i {
    color: #9F2241;
    font-size: 8px;
}

.neural-tooltip .tooltip-value {
    font-size: 10px;
    font-weight: 500;
    color: #fff;
    text-align: right;
}

.neural-tooltip .tooltip-btn-details {
    width: 100%;
    margin-top: 6px;
    padding: 6px;
    background: linear-gradient(135deg, #9F2241 0%, #691C32 100%);
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.neural-tooltip .tooltip-btn-details:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(159, 34, 65, 0.4);
}

/* Tooltip scrollbar */
.neural-tooltip .tooltip-content::-webkit-scrollbar {
    width: 6px;
}

.neural-tooltip .tooltip-content::-webkit-scrollbar-track {
    background: #1a1a2e;
    border-radius: 3px;
}

.neural-tooltip .tooltip-content::-webkit-scrollbar-thumb {
    background: #9F2241;
    border-radius: 3px;
}

/* Neural Instructions */
.neural-instructions {
    position: absolute;
    top: 8px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 10px;
    color: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    gap: 5px;
    z-index: 50;
    white-space: nowrap;
}

.neural-instructions i {
    color: #9F2241;
}

/* Legend Items Clickable */
.legend-item {
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.2s;
}

.legend-item:hover {
    background: rgba(255,255,255,0.1);
}

.legend-item.active {
    background: rgba(255,255,255,0.2);
}

/* Neural Model Detail Modal */
.neural-detail-modal .modal-dialog {
    max-width: 700px;
}

.neural-detail-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.neural-detail-card {
    background: linear-gradient(145deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid rgba(0,0,0,0.1);
}

.neural-detail-card h6 {
    color: #9F2241;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.neural-detail-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    font-size: 12px;
}

.neural-detail-item:last-child {
    border-bottom: none;
}

.neural-detail-item strong {
    color: #495057;
}

.neural-detail-item span {
    color: #1a1a2e;
    font-weight: 600;
}
</style>

</style>

<script>
// Variables Globales 3D
let datosDashboard = null;
let datosRedesNeuronales = null;
let neuronasData = null;
let datosNeuronasRed = null;
let empleadoIdActual = 0;
let modalAnalisis = null;

// Three.js variables
let scene, camera, renderer, controls;
let neuronMeshes = [];
let connectionLines = [];
let modelGroups = {};
let selectedNeuron = null;
let isAutoRotating = false;
let animationSpeed = 0.5;
let currentFilter = 'all';

const modelColors = {
    perceptron: { primary: 0x9F2241, light: 0xB82E56 },
    mlp: { primary: 0x0277BD, light: 0x039BE5 },
    lstm: { primary: 0xF9A825, light: 0xFFB300 },
    rnn: { primary: 0xC62828, light: 0xE53935 },
    transformer: { primary: 0x6A1B9A, light: 0x8E24AA },
    ensemble: { primary: 0x2E7D32, light: 0x43A047 }
};

const modelCapas = {
    perceptron: ['Entrada', 'Salida'],
    mlp: ['Entrada', 'Oculta 1', 'Oculta 2', 'Oculta 3', 'Salida'],
    lstm: ['Entrada', 'LSTM', 'Puerta', 'Salida'],
    rnn: ['Entrada', 'Recurrente', 'Salida'],
    transformer: ['Embedding', 'Atención', 'FFN', 'Salida'],
    ensemble: ['Entrada', 'Combinar', 'Salida']
};

function initThreeJS() {
    const container = document.getElementById('neural3d-container');
    if (!container) {
        console.log('initThreeJS: container no encontrado');
        return;
    }
    
    // If already initialized, skip
    if (scene && renderer) {
        console.log('initThreeJS: ya inicializado');
        return;
    }
    
    console.log('initThreeJS: inicializando, dimensiones:', container.clientWidth, container.clientHeight);
    
    // Scene
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0a1a);
    scene.fog = new THREE.Fog(0x0a0a1a, 100, 800);
    
    // Camera
    camera = new THREE.PerspectiveCamera(60, container.clientWidth / container.clientHeight, 0.1, 1000);
    camera.position.set(0, 40, 450);
    
    // Renderer
    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    container.appendChild(renderer.domElement);
    
    console.log('initThreeJS: renderer creado, canvas:', renderer.domElement);
    
    // Controls
    if (typeof THREE.OrbitControls !== 'undefined') {
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;
        controls.minDistance = 50;
        controls.maxDistance = 500;
    }
    
    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
    scene.add(ambientLight);
    
    const pointLight1 = new THREE.PointLight(0x9F2241, 1, 300);
    pointLight1.position.set(50, 50, 50);
    scene.add(pointLight1);
    
    const pointLight2 = new THREE.PointLight(0x0277BD, 1, 300);
    pointLight2.position.set(-50, -50, 50);
    scene.add(pointLight2);
    
    // Grid
    const gridHelper = new THREE.GridHelper(400, 40, 0x333333, 0x222222);
    scene.add(gridHelper);
    
    // Axes
    const axesHelper = new THREE.AxesHelper(50);
    scene.add(axesHelper);
    
    // Event listeners
    window.addEventListener('resize', onWindowResize);
    renderer.domElement.addEventListener('click', onNeuronClick);
    
    animate();
}

function onWindowResize() {
    const container = document.getElementById('neural3d-container');
    if (!container || !camera || !renderer) return;
    
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
}

function createNeuralNetwork3D(data) {
    // Limpiar scene anterior
    neuronMeshes.forEach(mesh => scene.remove(mesh));
    connectionLines.forEach(line => scene.remove(line));
    neuronMeshes = [];
    connectionLines = [];
    modelGroups = {};
    
    const redes = data.redes_neuronales || {};
    const modeloKeys = Object.keys(redes);
    
    // Cargar datos de empleados para las neuronas
    fetch('<?= BASE_URL ?>/ai/neuronas-red?modelo=all')
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                datosNeuronasRed = response.data;
                createNeuralObjects(redes, modeloKeys, data);
            } else {
                createNeuralObjects(redes, modeloKeys, data);
            }
        })
        .catch(err => {
            console.error('Error cargando datos neuronas:', err);
            createNeuralObjects(redes, modeloKeys, data);
        });
}

function createNeuralObjects(redes, modeloKeys, data) {
    // Espaciado entre modelos - aumentar para que todos sean visibles
    const spacing = 100;
    const startX = -((modeloKeys.length - 1) * spacing) / 2;
    
    // Obtener datos de empleados por modelo
    const empleadosProblematics = (datosNeuronasRed && datosNeuronasRed.neuronas) ? datosNeuronasRed.neuronas.empleados_problematicos || [] : [];
    const empleadosJustificaciones = (datosNeuronasRed && datosNeuronasRed.neuronas) ? datosNeuronasRed.neuronas.empleados_justificaciones || [] : [];
    const empleadosTendencia = (datosNeuronasRed && datosNeuronasRed.neuronas) ? datosNeuronasRed.neuronas.empleados_tendencia || [] : [];
    const empleadosRiesgo = (datosNeuronasRed && datosNeuronasRed.neuronas) ? datosNeuronasRed.neuronas.empleados_riesgo || [] : [];
    
    // Mapeo de empleados por modelo - reducir neuronas por capa
    const empleadosPorModelo = {
        perceptron: empleadosProblematics.slice(0, 4),
        mlp: empleadosJustificaciones.slice(0, 6),
        lstm: empleadosTendencia.slice(0, 5),
        rnn: empleadosRiesgo.slice(0, 5),
        transformer: empleadosProblematics.slice(0, 6),
        ensemble: empleadosProblematics.slice(0, 8)
    };
    
    modeloKeys.forEach((modelo, modeloIdx) => {
        const modeloData = redes[modelo];
        const xPos = startX + modeloIdx * spacing;
        
        // Crear grupo para el modelo
        modelGroups[modelo] = new THREE.Group();
        modelGroups[modelo].position.x = xPos;
        scene.add(modelGroups[modelo]);
        
        // Obtener empleados para este modelo
        const empleados = empleadosPorModelo[modelo] || [];
        
        // Crear neuronas organizadas en capas - reducir significativamente
        const configCapas = {
            perceptron: [3, 2],           // 3 entrada, 2 salida
            mlp: [4, 3, 2],              // 4 entrada, 3 oculta, 2 salida
            lstm: [3, 2, 2],             // 3 entrada, 2 lstm, 2 salida
            rnn: [3, 2, 2],              // 3 entrada, 2 recurrente, 2 salida
            transformer: [4, 2, 2],      // 4 embedding, 2 atencion, 2 salida
            ensemble: [5, 3, 2]           // 5 entrada, 3 combinar, 2 salida
        };
        
        const numPorCapa = configCapas[modelo] || [3, 2];
        const capas = ['Entrada', 'Oculta', 'Salida'];
        const capaNombres = [];
        numPorCapa.forEach((n, i) => {
            if (i === 0) capaNombres.push('Entrada');
            else if (i === numPorCapa.length - 1) capaNombres.push('Salida');
            else capaNombres.push('Oculta');
        });
        
        const neuronasEnCapa = [];
        let neuronaIdx = 0;
        
        // Crear neuronas organizadas por capas
        capaNombres.forEach((capaNombre, capaIdx) => {
            const numEnCapa = numPorCapa[capaIdx];
            const neuronasEnEstaCapa = [];
            const yPos = (capaIdx - (capaNombres.length - 1) / 2) * 25;
            
            for (let i = 0; i < numEnCapa; i++) {
                const empleado = empleados[neuronaIdx % Math.max(1, empleados.length)];
                const xPosCapa = (i - (numEnCapa - 1) / 2) * 20;
                
                const geometry = new THREE.SphereGeometry(1.8, 12, 12);
                
                // Color basado en nivel de riesgo
                let colorPrimario = modelColors[modelo].primary;
                let colorBrillo = modelColors[modelo].light;
                
                if (empleado) {
                    const retardos = empleado.retardos_90 || 0;
                    const faltas = empleado.faltas_90 || 0;
                    if (retardos >= 10 || faltas >= 3) {
                        colorPrimario = 0xC62828;
                        colorBrillo = 0xE53935;
                    } else if (retardos >= 5 || faltas >= 1) {
                        colorPrimario = 0xF9A825;
                        colorBrillo = 0xFFB300;
                    } else {
                        colorPrimario = 0x2E7D32;
                        colorBrillo = 0x43A047;
                    }
                }
                
                const material = new THREE.MeshPhongMaterial({
                    color: colorPrimario,
                    emissive: colorBrillo,
                    emissiveIntensity: 0.4,
                    shininess: 100
                });
                
                const neurona = new THREE.Mesh(geometry, material);
                neurona.position.set(xPosCapa, yPos, 0);
                
                neurona.userData = {
                    id: neuronaIdx,
                    modelo: modelo,
                    capa: capaNombre,
                    capaIdx: capaIdx,
                    color: colorPrimario,
                    bias: (Math.random() * 2 - 1).toFixed(4),
                    conexiones: 0,
                    funcion: getFuncionInfo(modelo),
                    empleadoId: empleado ? empleado.id : null,
                    nombre: empleado ? empleado.nombre + ' ' + empleado.apellido : null,
                    area: empleado ? empleado.area : null,
                    retardos: empleado ? (empleado.retardos_90 || 0) : 0,
                    faltas: empleado ? (empleado.faltas_90 || 0) : 0,
                    justificaciones: empleado ? (empleado.justificaciones_90 || 0) : 0,
                    indiceRiesgo: Math.min(100, ((empleado ? (empleado.retardos_90 || 0) : 0) * 8 + (empleado ? (empleado.faltas_90 || 0) : 0) * 20))
                };
                
                // Agregar etiqueta a la neurona
                // ID de empleado o número de neurona
                var labelText = 'N' + neuronaIdx;
                if (empleado && empleado.id) {
                    labelText = 'E' + empleado.id;
                }
                addNeuronaLabel(labelText, xPosCapa, yPos + 3, modelGroups[modelo]);
                
                modelGroups[modelo].add(neurona);
                neuronMeshes.push(neurona);
                neuronasEnEstaCapa.push(neurona);
                neuronaIdx++;
            }
            
            neuronasEnCapa.push(neuronasEnEstaCapa);
        });
        
        // Crear conexiones organizadas entre capas
        for (let c = 0; c < neuronasEnCapa.length - 1; c++) {
            const capaActual = neuronasEnCapa[c];
            const capaSiguiente = neuronasEnCapa[c + 1];
            
            capaActual.forEach(n1 => {
                capaSiguiente.forEach(n2 => {
                    const points = [
                        new THREE.Vector3(n1.position.x, n1.position.y, n1.position.z),
                        new THREE.Vector3(n2.position.x, n2.position.y, n2.position.z)
                    ];
                    const geometry = new THREE.BufferGeometry().setFromPoints(points);
                    const material = new THREE.LineBasicMaterial({
                        color: modelColors[modelo].primary,
                        transparent: true,
                        opacity: 0.15,
                        linewidth: 1
                    });
                    const line = new THREE.Line(geometry, material);
                    modelGroups[modelo].add(line);
                    connectionLines.push(line);
                    
                    n1.userData.conexiones++;
                    n2.userData.conexiones++;
                });
            });
        }
        
        // Labels del modelo - solo el nombre del modelo, posicionado arriba
        addTextLabel(modeloData.nombre || modelo, 0, -60, 0, modelGroups[modelo]);
    });
    
    updateStats();
}

function addNeuronaLabel(text, x, y, z, parentGroup) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    canvas.width = 64;
    canvas.height = 24;
    
    // Fondo redondeado
    context.fillStyle = 'rgba(0, 0, 0, 0.8)';
    context.beginPath();
    context.roundRect(2, 2, 60, 20, 5);
    context.fill();
    
    // Borde
    context.strokeStyle = '#00FF00';
    context.lineWidth = 2;
    context.beginPath();
    context.roundRect(2, 2, 60, 20, 5);
    context.stroke();
    
    // Texto
    context.font = 'bold 14px Arial';
    context.fillStyle = '#00FF00';
    context.textAlign = 'center';
    context.shadowColor = '#000000';
    context.shadowBlur = 2;
    context.fillText(text, 32, 17);
    
    const texture = new THREE.CanvasTexture(canvas);
    const spriteMaterial = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false });
    const sprite = new THREE.Sprite(spriteMaterial);
    sprite.position.set(x, y + 3, z + 0.5);
    sprite.scale.set(10, 4, 1);
    sprite.renderOrder = 999;
    if (parentGroup) {
        parentGroup.add(sprite);
    }
}

function addSmallLabel(text, x, y, z, parentGroup) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    canvas.width = 128;
    canvas.height = 24;
    
    context.font = 'bold 12px Arial';
    context.fillStyle = '#FFD700';
    context.textAlign = 'right';
    context.fillText(text, 124, 16);
    
    const texture = new THREE.CanvasTexture(canvas);
    const spriteMaterial = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false });
    const sprite = new THREE.Sprite(spriteMaterial);
    sprite.position.set(x, y, z);
    sprite.scale.set(15, 3, 1);
    sprite.renderOrder = 999;
    if (parentGroup) {
        parentGroup.add(sprite);
    } else {
        scene.add(sprite);
    }
}

function addTextLabel(text, x, y, z, parentGroup) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    canvas.width = 256;
    canvas.height = 40;
    
    context.fillStyle = 'transparent';
    context.fillRect(0, 0, canvas.width, canvas.height);
    
    context.font = 'bold 18px Arial';
    context.fillStyle = '#FFFFFF';
    context.textAlign = 'center';
    context.shadowColor = '#000000';
    context.shadowBlur = 4;
    context.fillText(text, 128, 26);
    
    const texture = new THREE.CanvasTexture(canvas);
    const spriteMaterial = new THREE.SpriteMaterial({ map: texture, transparent: true, depthTest: false });
    const sprite = new THREE.Sprite(spriteMaterial);
    sprite.position.set(x, y, z);
    sprite.scale.set(30, 5, 1);
    sprite.renderOrder = 999;
    if (parentGroup) {
        parentGroup.add(sprite);
    } else {
        scene.add(sprite);
    }
}

function animate() {
    requestAnimationFrame(animate);
    
    // Always render if there's scene and camera
    if (!scene || !camera || !renderer) {
        return;
    }
    
    // Only animate if in 3D mode and animations active
    if (currentViewMode === '3D' && isAnimating) {
        const time = Date.now() * 0.001;
        
        // Animación de neuronas
        neuronMeshes.forEach((mesh, idx) => {
            if (currentFilter !== 'all' && mesh.userData.modelo !== currentFilter) {
                mesh.visible = false;
            } else {
                mesh.visible = true;
                
                const scale = 1 + 0.1 * Math.sin(time * 2 + idx);
                mesh.scale.set(scale, scale, scale);
                
                mesh.material.emissiveIntensity = 0.3 + 0.2 * Math.sin(time * 3 + idx * 0.5);
            }
        });

        // Animación de conexiones
        connectionLines.forEach((line, idx) => {
            const modelo = (line.parent && line.parent.userData) ? line.parent.userData.modelo : Object.keys(modelGroups).find(m => modelGroups[m] === line.parent);
            if (currentFilter !== 'all' && modelo !== currentFilter) {
                line.visible = false;
            } else {
                line.visible = true;
                line.material.opacity = 0.2 + 0.15 * Math.sin(time * 2 + idx * 0.1);
            }
        });
        
        // Auto rotate
        if (isAutoRotating) {
            scene.rotation.y += 0.002 * animationSpeed;
        }
    }
    
    // Siempre actualizar controls y renderizar
    if (controls) controls.update();
    renderer.render(scene, camera);
    
    // Update FPS
    document.getElementById('fps-display').textContent = Math.round(60);
}

function onNeuronClick(event) {
    console.log('Click detectado en neurona');
    console.log('neuronMeshes length:', neuronMeshes.length);
    
    const container = document.getElementById('neural3d-container');
    if (!container || !camera || !renderer) {
        console.log('No hay container, camera o renderer');
        return;
    }
    
    const rect = renderer.domElement.getBoundingClientRect();
    const mouse = new THREE.Vector2(
        ((event.clientX - rect.left) / rect.width) * 2 - 1,
        -((event.clientY - rect.top) / rect.height) * 2 + 1
    );
    
    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(mouse, camera);
    
    // Buscar TODAS las neuronas en la escena (incluyendo ocultas)
    var todasNeuronas = [];
    scene.traverse(function(obj) {
        if (obj && obj.type === 'Mesh' && obj.userData && obj.userData.id !== undefined) {
            todasNeuronas.push(obj);
        }
    });
    console.log('Total neuronas en escena:', todasNeuronas.length);
    
    const intersects = raycaster.intersectObjects(todasNeuronas, true);
    console.log('Neuronas intersectadas:', intersects.length);
    
    if (intersects.length > 0) {
        const neurona = intersects[0].object;
        console.log('Neurona clickeada:', neurona.userData);
        
        // Hacer visible la neurona
        neurona.visible = true;
        
        // Si hay un grupo, hacerlo visible también
        if (neurona.parent && neurona.parent.userData && neurona.parent.userData.modelo) {
            neurona.parent.visible = true;
        }
        
        mostrarTooltipNeurona3D(neurona);
        
        // Trigger explosion effect at neuron position
        if (neurona.position) {
            const neuronWorldPos = new THREE.Vector3();
            neurona.getWorldPosition(neuronWorldPos);
            const vector = neuronWorldPos.clone();
            vector.project(camera);
            const x = (vector.x * 0.5 + 0.5) * renderer.domElement.clientWidth;
            const y = (-(vector.y * 0.5) + 0.5) * renderer.domElement.clientHeight;
            triggerExplosion(x, y);
        }
    } else {
        console.log('No se encontró neurona');
        ocultarTooltipNeurona();
    }
}

let neuronaDatosCargados = null;

function mostrarTooltipNeurona3D(neurona) {
    var tooltip = document.getElementById('neural-tooltip');
    var data = neurona.userData;
    
    // Limpiar tooltip
    tooltip.style.display = 'none';
    tooltip.style.opacity = '0';
    tooltip.classList.remove('visible');
    
    // Mostrar ID de neurona y nombre
    document.getElementById('tooltip-neurona-id').textContent = data.nombre || 'N' + data.id;
    document.getElementById('tooltip-empleado-nombre').textContent = data.nombre || 'Sin asignar';
    document.getElementById('tooltip-modelo').textContent = data.modelo.toUpperCase();
    document.getElementById('tooltip-capa').textContent = data.capa || 'N/A';
    
    var modeloNombre = data.modelo;
    if (datosRedesNeuronales && datosRedesNeuronales.redes_neuronales && datosRedesNeuronales.redes_neuronales[data.modelo]) {
        modeloNombre = datosRedesNeuronales.redes_neuronales[data.modelo].nombre || data.modelo;
    }
    document.querySelector('.tooltip-model-name').textContent = modeloNombre;
    
    // Mostrar datos del empleado
    if (data.empleadoId) {
        document.getElementById('tooltip-retardos').textContent = data.retardos || '0';
        document.getElementById('tooltip-faltas').textContent = data.faltas || '0';
        document.getElementById('tooltip-riesgo').textContent = (data.indiceRiesgo || 0) + '%';
        
        cargarDatosEmpleado(data.empleadoId, data.modelo);
    } else {
        document.getElementById('tooltip-retardos').textContent = data.retardos || '0';
        document.getElementById('tooltip-faltas').textContent = data.faltas || '0';
        document.getElementById('tooltip-riesgo').textContent = (data.indiceRiesgo || 0) + '%';
    }
    
    tooltip.classList.add('visible');
    
    // Mostrar tooltip con display block
    tooltip.style.display = 'block';
    // Small delay for CSS transition
    requestAnimationFrame(function() {
        tooltip.style.opacity = '1';
    });
    console.log('Tooltip mostrado para neurona:', data.id);
    
    // Guardar datos para el modal
    neuronaDatosCargados = {
        empleadoId: data.empleadoId,
        modelo: data.modelo,
        neurona: neurona
    };
    
    // Highlight neurona - hacer más visible con color verde brillante
    if (selectedNeuron && selectedNeuron.material) {
        selectedNeuron.material.emissive.setHex(selectedNeuron.userData.color || selectedNeuron.userData.originalColor);
        selectedNeuron.material.emissiveIntensity = 0.4;
        if (selectedNeuron.userData.originalColor) {
            selectedNeuron.material.color.setHex(selectedNeuron.userData.originalColor);
        }
    }
    selectedNeuron = neurona;
    
    // Guardar color original
    if (!neurona.userData.originalColor) {
        neurona.userData.originalColor = neurona.material.color.getHex();
    }
    
    // Hacer la neurona visible y highlight en verde brillante
    neurona.visible = true;
    neurona.material.color.setHex(0x00FF00);
    neurona.material.emissive.setHex(0x00FF00);
    neurona.material.emissiveIntensity = 2;
    
    // Forzar actualización
    neurona.material.needsUpdate = true;
    
    console.log('Neurona resaltada en verde:', neurona.userData.id);
}

function cargarDatosEmpleado(empleadoId, modelo) {
    fetch('<?= BASE_URL ?>/ai/detalle-neurona/' + empleadoId)
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                var d = response.data;
                var m = d.metricas || {};
                
                document.getElementById('tooltip-retardos').textContent = 
                    (m.retardos_90_dias || 0) + ' (' + (m.minutos_retardo || 0) + ' min)';
                document.getElementById('tooltip-faltas').textContent = m.faltas_90_dias || 0;
                document.getElementById('tooltip-riesgo').textContent = 
                    (m.indice_riesgo || 0) + '% (' + (m.nivel_riesgo || 'bajo') + ')';
                
                // Guardar datos completos para el modal
                neuronaDatosCargados = {
                    empleadoId: empleadoId,
                    modelo: modelo,
                    metricas: m
                };
            }
        })
        .catch(err => {
            console.error('Error cargando datos:', err);
            document.getElementById('tooltip-empleados').textContent = 'Error';
        });
}

function verAnalisisCompleto() {
    if (!neuronaDatosCargados) {
        alert('Selecciona una neurona primero');
        return;
    }
    
    let empleadoId = neuronaDatosCargados.empleadoId;
    
    // Si no hay empleadoId, buscar uno del mismo modelo
    if (!empleadoId && neuronaDatosCargados.modelo) {
        const modelo = neuronaDatosCargados.modelo;
        const neuronaConEmpleado = neuronMeshes.find(n => 
            n.userData.modelo === modelo && n.userData.empleadoId
        );
        if (neuronaConEmpleado) {
            empleadoId = neuronaConEmpleado.userData.empleadoId;
        }
    }
    
    if (!empleadoId) {
        alert('Esta neurona no tiene datos de empleado asociados. Selecciona otra neurona.');
        return;
    }
    
    // Abrir modal con análisis completo
    if (typeof verAnalisis === 'function') {
        verAnalisis(empleadoId);
    } else {
        // Buscar el modal ya existente o crear uno nuevo
        let modal = document.getElementById('modalAnalisisAI');
        if (!modal) {
            // Crear modal dinámicamente
            modal = document.createElement('div');
            modal.id = 'modalAnalisisAI';
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header-custom">
                            <div class="modal-title-custom">
                                <i class="fas fa-brain"></i>
                                <span>Análisis Completo del Empleado</span>
                            </div>
                            <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Cerrar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body-custom" id="contenido-analisis-completo">
                            <div class="loading-state">
                                <div class="loading-spinner-lg"></div>
                                <p>Cargando análisis completo...</p>
                            </div>
                        </div>
                        <div class="modal-footer-custom">
                            <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Cargar contenido
        cargarAnalisisCompleto(neuronaDatosCargados);
    }
}

function cargarAnalisisCompleto(datos) {
    const contenido = document.getElementById('contenido-analisis-completo');
    if (!contenido || !datos) return;
    
    const emp = datos.empleado || {};
    const met = datos.metricas || {};
    const pred = datos.prediccion || {};
    const recs = datos.recomendaciones || [];
    
    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            <span>Datos del Empleado</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="detail-item mb-3">
                            <div class="detail-label">Nombre Completo</div>
                            <div class="detail-value">${emp.nombre || ''} ${emp.apellido || ''}</div>
                        </div>
                        <div class="detail-item mb-3">
                            <div class="detail-label">Área</div>
                            <div class="detail-value">${emp.area || 'N/A'}</div>
                        </div>
                        <div class="detail-item mb-3">
                            <div class="detail-label">Puesto</div>
                            <div class="detail-value">${emp.puesto || 'N/A'}</div>
                        </div>
                        <div class="detail-item mb-3">
                            <div class="detail-label">Fecha de Ingreso</div>
                            <div class="detail-value">${emp.fecha_ingreso || 'N/A'}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-chart-line"></i>
                            <span>Métricas (Últimos 90 días)</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-danger"><i class="fas fa-clock"></i></div>
                                    <div class="stat-content">
                                        <div class="stat-number">${met.retardos_90_dias || 0}</div>
                                        <div class="stat-label">Retardos</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-warning"><i class="fas fa-minute"></i></div>
                                    <div class="stat-content">
                                        <div class="stat-number">${met.minutos_retardo || 0}</div>
                                        <div class="stat-label">Minutos</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-danger"><i class="fas fa-calendar-times"></i></div>
                                    <div class="stat-content">
                                        <div class="stat-number">${met.faltas_90_dias || 0}</div>
                                        <div class="stat-label">Faltas</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box">
                                    <div class="stat-icon bg-info"><i class="fas fa-clipboard-check"></i></div>
                                    <div class="stat-content">
                                        <div class="stat-number">${met.justificaciones_90_dias || 0}</div>
                                        <div class="stat-label">Justificaciones</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-shield-alt"></i>
                            <span>Índice de Riesgo: ${met.indice_riesgo || 0}% (${met.nivel_riesgo || 'bajo'})</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar ${met.indice_riesgo >= 80 ? 'bg-danger' : met.indice_riesgo >= 60 ? 'bg-warning' : met.indice_riesgo >= 40 ? 'bg-info' : 'bg-success'}" 
                                role="progressbar" style="width: ${met.indice_riesgo || 0}%">
                                ${met.indice_riesgo || 0}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-calendar-day"></i>
                            <span>Justificaciones del Año</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="detail-item mb-2">
                            <span class="detail-label">Días Económicos:</span>
                            <span class="detail-value">${met.dias_economicos_usados || 0} / 9</span>
                        </div>
                        <div class="detail-item mb-2">
                            <span class="detail-label">Licencias Médicas:</span>
                            <span class="detail-value">${met.licencias_usadas || 0}</span>
                        </div>
                        <div class="detail-item mb-2">
                            <span class="detail-label">Vacaciones:</span>
                            <span class="detail-value">${met.vacaciones_usadas || 0} / 9</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Sin Justificar:</span>
                            <span class="detail-value text-danger">${met.retardos_no_justificados || 0}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-crystal-ball"></i>
                            <span>Predicción IA</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="detail-item mb-2">
                            <span class="detail-label">Probabilidad de Problema:</span>
                            <span class="detail-value">${pred.probabilidad_problema || 0}%</span>
                        </div>
                        <div class="detail-item mb-2">
                            <span class="detail-label">Tendencia:</span>
                            <span class="detail-value badge ${pred.tendencia === 'empeorando' ? 'bg-danger' : pred.tendencia === 'mejora' ? 'bg-success' : 'bg-warning'}">
                                ${pred.tendencia || 'estable'}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Confianza:</span>
                            <span class="detail-value">${pred.confianza || 'media'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        ${recs.length > 0 ? `
        <div class="row">
            <div class="col-md-12">
                <div class="section-card mb-3">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-lightbulb"></i>
                            <span>Recomendaciones</span>
                        </div>
                    </div>
                    <div class="p-3">
                        ${recs.map(r => `
                            <div class="alert alert-${r.tipo === 'urgente' ? 'danger' : r.tipo === 'importante' ? 'warning' : 'info'} mb-2">
                                <strong><i class="fas fa-${r.tipo === 'urgente' ? 'exclamation-triangle' : 'info-circle'}"></i> ${r.titulo}</strong>
                                <p class="mb-0">${r.descripcion}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
        ` : ''}
    `;
    
    contenido.innerHTML = html;
}

function ocultarTooltipNeurona() {
    const tooltip = document.getElementById('neural-tooltip');
    tooltip.classList.remove('visible');
    tooltip.style.display = 'none';
    
    if (selectedNeuron && selectedNeuron.material && selectedNeuron.userData.originalColor) {
        selectedNeuron.material.color.setHex(selectedNeuron.userData.originalColor);
        selectedNeuron.material.emissive.setHex(selectedNeuron.userData.originalColor);
        selectedNeuron.material.emissiveIntensity = 0.4;
        selectedNeuron = null;
    }
}

function getFuncionInfo(modelo) {
    const funciones = {
        perceptron: 'f(x) = max(0, wx + b)',
        mlp: 'f(x) = tanh(Wx + b)',
        lstm: 'σ, tanh, × gates',
        rnn: 'h_t = tanh(Wx + Uh_{t-1})',
        transformer: 'Multi-Head Attention',
        ensemble: 'Σ w_i × p_i'
    };
    return funciones[modelo] || 'ReLU';
}

function updateStats() {
    const visibleNeuronas = neuronMeshes.filter(n => n.visible).length;
    const visibleConexiones = connectionLines.filter(l => l.visible).length;
    
    document.getElementById('total-neuronas').textContent = visibleNeuronas;
    document.getElementById('total-conexiones').textContent = visibleConexiones;
}

function toggleNeuralModel(key) {
    var card = document.getElementById('neural-card-' + key);
    if (!card) return;
    
    if (!window.hiddenNeuralModels) {
        window.hiddenNeuralModels = [];
    }
    
    var index = window.hiddenNeuralModels.indexOf(key);
    var isHiding = index === -1;
    
    if (isHiding) {
        window.hiddenNeuralModels.push(key);
        card.classList.add('hidden-model');
    } else {
        window.hiddenNeuralModels.splice(index, 1);
        card.classList.remove('hidden-model');
    }
    
    // Actualizar lista de ocultos
    actualizarListaOcultos();
    
    // Ocultar/Mostrar en 3D también
    if (modelGroups && modelGroups[key]) {
        modelGroups[key].visible = !isHiding;
    }
    
    // Actualizar estadísticas
    if (typeof updateStats === 'function') {
        updateStats();
    }
}

function actualizarListaOcultos() {
    var container = document.getElementById('neural-hidden-models');
    var listContainer = document.getElementById('hidden-models-list');
    var hiddenModels = window.hiddenNeuralModels || [];
    
    if (hiddenModels.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'flex';
    var html = '';
    hiddenModels.forEach(function(key) {
        html += '<button class="hidden-model-btn" onclick="toggleNeuralModel(\'' + key + '\')">';
        html += '<i class="fas fa-eye"></i> ' + (key.charAt(0).toUpperCase() + key.slice(1));
        html += '</button>';
    });
    listContainer.innerHTML = html;
}

function filterModel3D(modelo) {
    currentFilter = modelo;
    
    // Cerrar tooltip si está abierto
    var tooltip = document.getElementById('neural-tooltip');
    if (tooltip) {
        tooltip.style.display = 'none';
        tooltip.classList.remove('visible');
    }
    
    // Resetear neurona seleccionada
    if (selectedNeuron && selectedNeuron.material) {
        selectedNeuron.material.emissive.setHex(selectedNeuron.userData.originalColor || 0x000000);
        selectedNeuron.material.emissiveIntensity = 0.4;
        if (selectedNeuron.userData.originalColor) {
            selectedNeuron.material.color.setHex(selectedNeuron.userData.originalColor);
        }
    }
    selectedNeuron = null;
    
    // Update dropdown
    var dropdown = document.getElementById('modelFilter');
    if (dropdown) dropdown.value = modelo;
    
    // Update legend
    var legendItems = document.querySelectorAll('.legend-item');
    if (legendItems) {
        for (var i = 0; i < legendItems.length; i++) {
            legendItems[i].classList.remove('active');
            if (legendItems[i].getAttribute('data-model') === modelo) {
                legendItems[i].classList.add('active');
            }
        }
    }
    
    // Aplicar filtro en 3D
    var modelos = ['perceptron', 'mlp', 'lstm', 'rnn', 'transformer', 'ensemble'];
    
    // Ocultar todos los objetos en la escena directamente (neuronas y lineas)
    if (scene) {
        scene.traverse(function(obj) {
            if (obj && obj.userData && obj.userData.modelo) {
                obj.visible = (obj.userData.modelo === modelo);
            }
            // Ocultar lineas de conexión
            if (obj && obj.type === 'Line' && obj.userData && obj.userData.modelo) {
                obj.visible = (obj.userData.modelo === modelo);
            }
        });
        
        // Centrar cámara en el modelo seleccionado
        if (modelo !== 'all' && modelGroups[modelo]) {
            const modeloPos = modelGroups[modelo].position;
            if (camera && controls) {
                controls.target.set(modeloPos.x, 0, 0);
                camera.position.set(modeloPos.x, 0, 120);
                camera.lookAt(modeloPos.x, 0, 0);
            }
        } else if (modelo === 'all' && camera && controls) {
            controls.target.set(0, 0, 0);
            camera.position.set(0, 0, 350);
            camera.lookAt(0, 0, 0);
        }
    }
    
    // Aplicar filtro en 2D también
    if (currentViewMode === '2D' && neuronasData) {
        const canvas = document.getElementById('neural2d-container');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const container = canvas.parentElement;
        
        canvas.width = container.clientWidth;
        canvas.height = container.clientHeight;
        
        ctx.fillStyle = '#0a0a1a';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        const colors = {
            perceptron: '#9F2241',
            mlp: '#0277BD',
            lstm: '#F9A825',
            rnn: '#C62828',
            transformer: '#6A1B9A',
            ensemble: '#2E7D32'
        };
        
        drawParticles(ctx);
        drawBackgroundEffects(ctx, canvas);
        
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(canvas.width, canvas.height) * 0.35;
        
        neuronasData.forEach((capa, capaIndex) => {
            if (!capa.neuronas || capa.neuronas.length === 0) return;
            
            const angleStep = (Math.PI * 2) / capa.neuronas.length;
            const layerRadius = radius * (0.3 + capaIndex * 0.25);
            
            capa.neuronas.forEach((neurona, neuronaIndex) => {
                if (modelo !== 'all' && neurona.modelo !== modelo) return;
                
                const angle = angleStep * neuronaIndex - Math.PI / 2;
                const x = centerX + Math.cos(angle) * layerRadius;
                const y = centerY + Math.sin(angle) * layerRadius;
                
                neurona.x2D = x;
                neurona.y2D = y;
                
                const color = colors[neurona.modelo] || '#9F2241';
                const size = 8 + (neurona.peso || 0.5) * 12;
                
                ctx.beginPath();
                ctx.arc(x, y, size, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                ctx.strokeStyle = 'rgba(255,255,255,0.3)';
                ctx.lineWidth = 1;
                ctx.stroke();
                
                ctx.fillStyle = 'rgba(255,255,255,0.7)';
                ctx.font = '9px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(neurona.nombre?.substring(0, 8) || '', x, y + size + 10);
            });
        });
        
        // Dibujar conexiones filtradas
        ctx.strokeStyle = 'rgba(159, 34, 65, 0.2)';
        ctx.lineWidth = 1;
        neuronasData.forEach((capa, capaIndex) => {
            if (capaIndex === neuronasData.length - 1) return;
            if (!capa.neuronas || !neuronasData[capaIndex + 1]?.neuronas) return;
            
            capa.neuronas.forEach(n1 => {
                if (modelo !== 'all' && n1.modelo !== modelo) return;
                neuronasData[capaIndex + 1].neuronas.forEach(n2 => {
                    if (modelo !== 'all' && n2.modelo !== modelo) return;
                    if (n1.x2D && n2.x2D) {
                        ctx.beginPath();
                        ctx.moveTo(n1.x2D, n1.y2D);
                        ctx.lineTo(n2.x2D, n2.y2D);
                        ctx.stroke();
                    }
                });
            });
        });
    }
    
    // Ocultar connectionLines directamente
    if (connectionLines) {
        for (var c = 0; c < connectionLines.length; c++) {
            var line = connectionLines[c];
            if (line && line.userData && line.userData.modelo) {
                line.visible = (line.userData.modelo === modelo);
            }
        }
    }
    
    // También actualizar modelGroups
    for (var g = 0; g < modelos.length; g++) {
        var key = modelos[g];
        if (modelGroups && modelGroups[key]) {
            modelGroups[key].visible = (key === modelo);
        }
    }
    
    // Forzar actualización del render
    if (renderer && scene && camera) {
        renderer.render(scene, camera);
    }
    
    if (typeof updateStats === 'function') {
        updateStats();
    }
}

function updateZoom(value) {
    if (camera) {
        camera.position.z = parseInt(value);
    }
}

function updateSpeed(value) {
    animationSpeed = value / 50;
    
    // Reducir velocidad de animaciones 2D
    pulsePhase *= animationSpeed;
    dataFlowPhase *= animationSpeed;
    colorPhase *= animationSpeed;
}

function toggleEffect(effect, enabled) {
    effectSettings[effect] = enabled;
}

function resetCamera() {
    if (camera && controls) {
        camera.position.set(0, 0, 350);
        camera.lookAt(0, 0, 0);
        controls.target.set(0, 0, 0);
        controls.update();
    }
}

let animationFrame2D = null;
let pulsePhase = 0;
let dataFlowPhase = 0;
let isAnimating2D = false;
let isAnimating = true;
let particles = [];
let colorPhase = 0;
let vibrationPhase = 0;
let trailPhase = 0;
let effectSettings = {
    particles: true,
    colorShift: true,
    vibration: true,
    trails: true,
    explosion: false
};

function initParticles() {
    particles = [];
    for (let i = 0; i < 50; i++) {
        particles.push({
            x: Math.random() * 800,
            y: Math.random() * 600,
            vx: (Math.random() - 0.5) * 2,
            vy: (Math.random() - 0.5) * 2,
            size: Math.random() * 3 + 1,
            life: Math.random() * 100,
            color: ['#9F2241', '#0277BD', '#F9A825', '#C62828', '#6A1B9A', '#2E7D32'][Math.floor(Math.random() * 6)]
        });
    }
}

function updateParticles(canvas) {
    particles.forEach(p => {
        p.x += p.vx;
        p.y += p.vy;
        p.life -= 1;
        
        if (p.x < 0 || p.x > canvas.width || p.y < 0 || p.y > canvas.height || p.life <= 0) {
            p.x = Math.random() * canvas.width;
            p.y = Math.random() * canvas.height;
            p.life = Math.random() * 100 + 50;
        }
    });
}

function drawParticles(ctx) {
    if (!effectSettings.particles) return;
    
    particles.forEach(p => {
        const alpha = p.life / 100;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = alpha * 0.6;
        ctx.fill();
        ctx.globalAlpha = 1;
        
        // Trail
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        ctx.lineTo(p.x - p.vx * 3, p.y - p.vy * 3);
        ctx.strokeStyle = p.color;
        ctx.globalAlpha = alpha * 0.3;
        ctx.lineWidth = p.size * 0.5;
        ctx.stroke();
        ctx.globalAlpha = 1;
    });
}

function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

function rgbToHex(r, g, b) {
    return '#' + [r, g, b].map(x => {
        const hex = Math.floor(x).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    }).join('');
}

function shiftColor(color, phase) {
    const rgb = hexToRgb(color);
    if (!rgb) return color;
    
    const shift = Math.sin(phase) * 30;
    return rgbToHex(
        Math.min(255, Math.max(0, rgb.r + shift)),
        Math.min(255, Math.max(0, rgb.g + shift * 0.5)),
        Math.min(255, Math.max(0, rgb.b + shift * 0.3))
    );
}

function drawBackgroundEffects(ctx, canvas) {
    if (!isAnimating) return;
    
    // Efecto de grid animado
    if (effectSettings.vibration) {
        ctx.strokeStyle = 'rgba(159, 34, 65, 0.05)';
        ctx.lineWidth = 1;
        
        const gridSize = 50;
        const offsetX = Math.sin(vibrationPhase) * 5;
        const offsetY = Math.cos(vibrationPhase) * 5;
        
        for (let x = offsetX; x < canvas.width; x += gridSize) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, canvas.height);
            ctx.stroke();
        }
        for (let y = offsetY; y < canvas.height; y += gridSize) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
            ctx.stroke();
        }
    }
    
    // Efecto de aurora boreal sutil
    if (effectSettings.colorShift) {
        const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        gradient.addColorStop(0, 'rgba(159, 34, 65, 0.03)');
        gradient.addColorStop(0.5, 'rgba(2, 119, 189, 0.02)');
        gradient.addColorStop(1, 'rgba(106, 27, 154, 0.03)');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
    
    // Rays de luz
    if (effectSettings.trails) {
        const numRays = 5;
        for (let i = 0; i < numRays; i++) {
            const angle = (trailPhase + i * 0.5) % (Math.PI * 2);
            const x = canvas.width / 2 + Math.cos(angle) * canvas.width * 0.6;
            const y = canvas.height / 2 + Math.sin(angle) * canvas.height * 0.6;
            
            const rayGradient = ctx.createLinearGradient(
                canvas.width / 2, canvas.height / 2, x, y
            );
            rayGradient.addColorStop(0, 'rgba(159, 34, 65, 0.08)');
            rayGradient.addColorStop(1, 'transparent');
            
            ctx.beginPath();
            ctx.moveTo(canvas.width / 2, canvas.height / 2);
            ctx.lineTo(x + 100, y);
            ctx.lineTo(x, y + 100);
            ctx.fillStyle = rayGradient;
            ctx.fill();
        }
    }
    
    // Explosión de partículas al hacer click (efecto especial)
    if (effectSettings.explosion) {
        drawExplosion(ctx, canvas);
    }
}

let explosionParticles = [];

function drawExplosion(ctx, canvas) {
    if (explosionParticles.length === 0) return;
    
    explosionParticles.forEach((p, idx) => {
        p.x += p.vx;
        p.y += p.vy;
        p.life -= 2;
        p.vx *= 0.98;
        p.vy *= 0.98;
        
        if (p.life <= 0) {
            explosionParticles.splice(idx, 1);
            return;
        }
        
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * (p.life / 100), 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = p.life / 100;
        ctx.fill();
        ctx.globalAlpha = 1;
    });
}

function triggerExplosion(x, y) {
    if (!effectSettings.explosion) return;
    
    const colors = ['#9F2241', '#0277BD', '#F9A825', '#C62828', '#6A1B9A', '#2E7D32'];
    for (let i = 0; i < 20; i++) {
        explosionParticles.push({
            x: x,
            y: y,
            vx: (Math.random() - 0.5) * 10,
            vy: (Math.random() - 0.5) * 10,
            size: Math.random() * 5 + 2,
            life: 100,
            color: colors[Math.floor(Math.random() * colors.length)]
        });
    }
}

function toggleAnimations() {
    isAnimating = !isAnimating;
    const btn = document.getElementById('btnToggleAnimations');
    if (btn) {
        btn.classList.toggle('active', isAnimating);
        btn.innerHTML = isAnimating ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
    }
    
    if (currentViewMode === '2D') {
        if (isAnimating) {
            start2DAnimation();
        } else {
            stop2DAnimation();
        }
    }
}

function toggleAutoRotate() {
    isAutoRotating = !isAutoRotating;
    const btn = document.getElementById('autoRotateBtn');
    if (btn) {
        btn.classList.toggle('active', isAutoRotating);
    }
}

function animate2D() {
    if (!isAnimating2D || currentViewMode !== '2D') return;
    
    const canvas = document.getElementById('neural2d-container');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const container = canvas.parentElement;
    
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
    
    ctx.fillStyle = '#0a0a1a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    pulsePhase += 0.05;
    dataFlowPhase += 0.02;
    colorPhase += 0.03;
    vibrationPhase += 0.1;
    
    if (particles.length === 0) {
        initParticles();
    }
    updateParticles(canvas);
    drawParticles(ctx);
    drawBackgroundEffects(ctx, canvas);
    
    if (!neuronasData || neuronasData.length === 0) {
        requestAnimationFrame(animate2D);
        return;
    }
    
    // Usar el mismo layout que draw2DNetwork
    const colors = {
        perceptron: '#9F2241',
        mlp: '#0277BD',
        lstm: '#F9A825',
        rnn: '#C62828',
        transformer: '#6A1B9A',
        ensemble: '#2E7D32'
    };
    
    const modelos = {};
    neuronasData.forEach(capa => {
        if (!modelos[capa.modelo]) {
            modelos[capa.modelo] = {
                nombre: capa.nombre,
                modelo: capa.modelo,
                capas: []
            };
        }
        modelos[capa.modelo].capas.push(capa);
    });
    
    const modeloKeys = Object.keys(modelos);
    
    // Apply filter if set
    if (currentFilter2D) {
        Object.keys(modelos).forEach(key => {
            if (key !== currentFilter2D) {
                delete modelos[key];
            }
        });
    }
    
    const modeloKeysFiltered = Object.keys(modelos);
    const modeloSpacing = canvas.width / (modeloKeysFiltered.length + 1);
    
    modeloKeysFiltered.forEach((modelo, modeloIdx) => {
        const modeloData = modeloKeysFiltered.length === 1 ? modelo : modelos[modelo];
        const centerX = modeloSpacing * (modeloIdx + 1);
        const centerY = canvas.height / 2;
        const radius = Math.min(canvas.width, canvas.height) * 0.18;
        
        const capas = modeloData.capas;
        
        // Conexiones
        ctx.strokeStyle = colors[modelo] + '30';
        ctx.lineWidth = 1;
        
        capas.forEach((capa, capaIndex) => {
            if (capaIndex === capas.length - 1 || !capa.neuronas) return;
            const nextCapa = capas[capaIndex + 1];
            if (!nextCapa.neuronas) return;
            
            const layerRadius1 = radius * (0.4 + capaIndex * 0.35);
            const layerRadius2 = radius * (0.4 + (capaIndex + 1) * 0.35);
            
            capa.neuronas.forEach((n1, i1) => {
                const angle1 = capa.neuronas.length > 1 ? (Math.PI * 2 / capa.neuronas.length) * i1 - Math.PI / 2 : -Math.PI / 2;
                const x1 = centerX + Math.cos(angle1) * layerRadius1;
                const y1 = centerY + Math.sin(angle1) * layerRadius1;
                n1.x2D = x1;
                n1.y2D = y1;
                
                nextCapa.neuronas.forEach((n2, i2) => {
                    const angle2 = nextCapa.neuronas.length > 1 ? (Math.PI * 2 / nextCapa.neuronas.length) * i2 - Math.PI / 2 : -Math.PI / 2;
                    const x2 = centerX + Math.cos(angle2) * layerRadius2;
                    const y2 = centerY + Math.sin(angle2) * layerRadius2;
                    n2.x2D = x2;
                    n2.y2D = y2;
                    
                    // Flujo de datos
                    const flowPos = (dataFlowPhase + capaIndex * 0.3) % 1;
                    const fx = x1 + (x2 - x1) * flowPos;
                    const fy = y1 + (y2 - y1) * flowPos;
                    
                    ctx.beginPath();
                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);
                    ctx.stroke();
                    
                    ctx.beginPath();
                    ctx.arc(fx, fy, 3, 0, Math.PI * 2);
                    ctx.fillStyle = colors[modelo];
                    ctx.fill();
                });
            });
        });
        
        // Neuronas con animación
        capas.forEach((capa, capaIndex) => {
            if (!capa.neuronas) return;
            
            const layerRadius = radius * (0.4 + capaIndex * 0.35);
            
            capa.neuronas.forEach((neurona, neuronaIndex) => {
                if (neurona.x2D === undefined) {
                    const angle = capa.neuronas.length > 1 ? (Math.PI * 2 / capa.neuronas.length) * neuronaIndex - Math.PI / 2 : -Math.PI / 2;
                    neurona.x2D = centerX + Math.cos(angle) * layerRadius;
                    neurona.y2D = centerY + Math.sin(angle) * layerRadius;
                }
                
                let color = colors[neurona.modelo] || '#9F2241';
                if (effectSettings.colorShift) {
                    color = shiftColor(color, colorPhase + neuronaIndex * 0.3);
                }
                
                const baseSize = 6 + (neurona.peso || 0.5) * 10;
                const pulseSize = baseSize + Math.sin(pulsePhase + neuronaIndex * 0.5) * 2;
                
                let offsetX = 0, offsetY = 0;
                if (effectSettings.vibration) {
                    offsetX = Math.sin(vibrationPhase + neuronaIndex) * 1.5;
                    offsetY = Math.cos(vibrationPhase + neuronaIndex) * 1.5;
                }
                
                const x = neurona.x2D + offsetX;
                const y = neurona.y2D + offsetY;
                
                const gradient = ctx.createRadialGradient(x, y, pulseSize * 0.5, x, y, pulseSize * 2);
                gradient.addColorStop(0, color);
                gradient.addColorStop(0.5, color + '60');
                gradient.addColorStop(1, 'transparent');
                
                ctx.beginPath();
                ctx.arc(x, y, pulseSize * 2, 0, Math.PI * 2);
                ctx.fillStyle = gradient;
                ctx.fill();
                
                ctx.beginPath();
                ctx.arc(x, y, pulseSize, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                ctx.strokeStyle = 'rgba(255,255,255,0.5)';
                ctx.lineWidth = 1.5;
                ctx.stroke();
            });
        });
        
        // Nombre
        ctx.fillStyle = colors[modelo] || '#fff';
        ctx.font = 'bold 11px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(modeloData.nombre || modelo, centerX, centerY - radius - 15);
    });
    
    drawExplosion(ctx, canvas);
    
    animationFrame2D = requestAnimationFrame(animate2D);
}

function start2DAnimation() {
    if (isAnimating2D) return;
    isAnimating2D = true;
    animate2D();
}

function stop2DAnimation() {
    isAnimating2D = false;
    if (animationFrame2D) {
        cancelAnimationFrame(animationFrame2D);
        animationFrame2D = null;
    }
}

function showAllNeurons() {
    currentFilter = null;
    
    // Update dropdown
    var dropdown = document.getElementById('modelFilter');
    if (dropdown) dropdown.value = 'all';
    
    // Update legend
    var legendItems = document.querySelectorAll('.legend-item');
    if (legendItems) {
        legendItems.forEach(function(item) {
            item.classList.remove('active');
        });
    }
    
    // Show all groups
    if (modelGroups) {
        Object.keys(modelGroups).forEach(function(key) {
            if (modelGroups[key]) {
                modelGroups[key].visible = true;
            }
        });
    }
    
    if (typeof updateStats === 'function') {
        updateStats();
    }
    
    if (typeof resetCamera === 'function') {
        resetCamera();
    }
}

let currentViewMode = '3D';
let currentFilter2D = null;

function toggle2D3D() {
    const btn = document.getElementById('btnToggle2D3D');
    const container3D = document.getElementById('neural3d-container');
    const container2D = document.getElementById('neural2d-container');
    const instructions = document.querySelector('.neural-instructions');
    
    if (currentViewMode === '3D') {
        currentViewMode = '2D';
        btn.innerHTML = '<i class="fas fa-th"></i>';
        btn.classList.remove('active');
        container3D.classList.add('hidden');
        container3D.style.display = 'none';
        container2D.classList.add('active');
        container2D.style.display = 'block';
        instructions.innerHTML = '<i class="fas fa-info-circle"></i> Click en neurona para detalles • Animaciones activas';
        
        if (typeof stopAutoRotate3D === 'function') {
            stopAutoRotate3D();
        }
        
        // Cargar datos 2D si no existen
        if (datosRedesNeuronales) {
            neuronasData = transformarDatos2D(datosRedesNeuronales.redes_neuronales);
            draw2DNetwork();
        }
        
        if (isAnimating && typeof start2DAnimation === 'function') {
            start2DAnimation();
        }
    } else {
        currentViewMode = '3D';
        btn.innerHTML = '<i class="fas fa-cube"></i>';
        btn.classList.add('active');
        
        // OCULTAR 2D PRIMERO
        container2D.style.display = 'none';
        container2D.classList.remove('active');
        
        // Mostrar 3D
        container3D.classList.remove('hidden');
        container3D.style.display = 'block';
        
        instructions.innerHTML = '<i class="fas fa-info-circle"></i> Arrastra para rotar • Scroll para zoom • Click en neurona para detalles';
        
        if (typeof stop2DAnimation === 'function') {
            stop2DAnimation();
        }
        
        // INICIALIZAR Three.js si no está listo
        if (!scene && typeof THREE !== 'undefined') {
            console.log('Inicializando Three.js...');
            setTimeout(function() {
                try {
                    initThreeJS();
                    if (datosRedesNeuronales) {
                        createNeuralNetwork3D(datosRedesNeuronales);
                    } else {
                        // Si no hay datos, cargar primero
                        fetch('<?= BASE_URL ?>/ai/redes-neuronales')
                            .then(r => r.json())
                            .then(data => {
                                if (data.success && data.data) {
                                    datosRedesNeuronales = data.data;
                                    createNeuralNetwork3D(datosRedesNeuronales);
                                }
                            });
                    }
                    console.log('Three.js inicializado al cambiar a 3D');
                } catch(e) {
                    console.error('Error inicializando Three.js:', e);
                }
            }, 100);
        } else if (renderer && scene) {
            // Scene ya existe, solo renderizar
            console.log('Renderizando 3D existente');
            
            // Si hay datos pero no se han creado las neuronas
            if (datosRedesNeuronales && neuronMeshes.length === 0) {
                createNeuralNetwork3D(datosRedesNeuronales);
            }
            
            setTimeout(function() {
                if (container3D.clientWidth > 0 && container3D.clientHeight > 0) {
                    camera.aspect = container3D.clientWidth / container3D.clientHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(container3D.clientWidth, container3D.clientHeight);
                }
                renderer.render(scene, camera);
            }, 50);
        }
    }
}

function transformarDatos2D(redesData) {
    if (!redesData) return [];
    
    const capas = [];
    const modeloKeys = Object.keys(redesData);
    
    modeloKeys.forEach((modelo, idx) => {
        const modeloData = redesData[modelo];
        
        // Crear capa de entrada para este modelo
        capas.push({
            modelo: modelo,
            nombre: modeloData.nombre || modelo,
            capa: 'Entrada',
            neuronas: [
                { id: modelo + '_entrada_1', nombre: modelo + ' E1', modelo: modelo, peso: 0.5 + Math.random() * 0.5, retardos: Math.floor(Math.random() * 10), faltas: Math.floor(Math.random() * 5), riesgo: Math.floor(Math.random() * 100) + '%' },
                { id: modelo + '_entrada_2', nombre: modelo + ' E2', modelo: modelo, peso: 0.5 + Math.random() * 0.5, retardos: Math.floor(Math.random() * 10), faltas: Math.floor(Math.random() * 5), riesgo: Math.floor(Math.random() * 100) + '%' },
                { id: modelo + '_entrada_3', nombre: modelo + ' E3', modelo: modelo, peso: 0.5 + Math.random() * 0.5, retardos: Math.floor(Math.random() * 10), faltas: Math.floor(Math.random() * 5), riesgo: Math.floor(Math.random() * 100) + '%' }
            ]
        });
        
        // Capa oculta
        if (['mlp', 'lstm', 'rnn', 'transformer', 'ensemble'].includes(modelo)) {
            capas.push({
                modelo: modelo,
                nombre: modeloData.nombre || modelo,
                capa: 'Oculta',
                neuronas: [
                    { id: modelo + '_oculta_1', nombre: modelo + ' O1', modelo: modelo, peso: 0.6 + Math.random() * 0.4, retardos: Math.floor(Math.random() * 8), faltas: Math.floor(Math.random() * 4), riesgo: Math.floor(Math.random() * 80) + '%' },
                    { id: modelo + '_oculta_2', nombre: modelo + ' O2', modelo: modelo, peso: 0.6 + Math.random() * 0.4, retardos: Math.floor(Math.random() * 8), faltas: Math.floor(Math.random() * 4), riesgo: Math.floor(Math.random() * 80) + '%' }
                ]
            });
        }
        
        // Capa de salida
        capas.push({
            modelo: modelo,
            nombre: modeloData.nombre || modelo,
            capa: 'Salida',
            neuronas: [
                { id: modelo + '_salida_1', nombre: modelo + ' S1', modelo: modelo, peso: 0.7 + Math.random() * 0.3, retardos: Math.floor(Math.random() * 5), faltas: Math.floor(Math.random() * 2), riesgo: Math.floor(Math.random() * 50) + '%' },
                { id: modelo + '_salida_2', nombre: modelo + ' S2', modelo: modelo, peso: 0.7 + Math.random() * 0.3, retardos: Math.floor(Math.random() * 5), faltas: Math.floor(Math.random() * 2), riesgo: Math.floor(Math.random() * 50) + '%' }
            ]
        });
    });
    
    return capas;
}

function draw2DNetwork() {
    const canvas = document.getElementById('neural2d-container');
    if (!canvas) {
        console.log('Canvas 2D no encontrado');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    const container = canvas.parentElement;
    
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
    
    ctx.fillStyle = '#0a0a1a';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    if (!neuronasData || neuronasData.length === 0) {
        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Cargando datos de redes neuronales...', canvas.width/2, canvas.height/2);
        return;
    }
    
    const colors = {
        perceptron: '#9F2241',
        mlp: '#0277BD',
        lstm: '#F9A825',
        rnn: '#C62828',
        transformer: '#6A1B9A',
        ensemble: '#2E7D32'
    };
    
    const modelos = {};
    neuronasData.forEach(capa => {
        if (!modelos[capa.modelo]) {
            modelos[capa.modelo] = {
                nombre: capa.nombre,
                modelo: capa.modelo,
                capas: []
            };
        }
        modelos[capa.modelo].capas.push(capa);
    });
    
    const modeloKeys = Object.keys(modelos);
    
    // Apply filter if set
    if (currentFilter2D) {
        Object.keys(modelos).forEach(key => {
            if (key !== currentFilter2D) {
                delete modelos[key];
            }
        });
    }
    
    const modeloKeysFiltered = Object.keys(modelos);
    const modeloSpacing = canvas.width / (modeloKeysFiltered.length + 1);
    
    modeloKeysFiltered.forEach((modelo, modeloIdx) => {
        const modeloData = modeloKeysFiltered.length === 1 ? modelo : modelos[modelo];
        const centerX = modeloSpacing * (modeloIdx + 1);
        const centerY = canvas.height / 2;
        const radius = Math.min(canvas.width, canvas.height) * 0.18;
        
        const capas = modeloData.capas;
        
        ctx.strokeStyle = colors[modelo] + '40';
        ctx.lineWidth = 1;
        
        capas.forEach((capa, capaIndex) => {
            if (!capa.neuronas) return;
            
            const layerRadius = radius * (0.4 + capaIndex * 0.35);
            const angleStep = capa.neuronas.length > 1 ? (Math.PI * 2) / capa.neuronas.length : 1;
            
            capa.neuronas.forEach((neurona, neuronaIndex) => {
                const angle = capa.neuronas.length > 1 ? angleStep * neuronaIndex - Math.PI / 2 : -Math.PI / 2;
                const x = centerX + Math.cos(angle) * layerRadius;
                const y = centerY + Math.sin(angle) * layerRadius;
                
                neurona.x2D = x;
                neurona.y2D = y;
            });
        });
        
        capas.forEach((capa, capaIndex) => {
            if (capaIndex === capas.length - 1 || !capa.neuronas) return;
            const nextCapa = capas[capaIndex + 1];
            if (!nextCapa.neuronas) return;
            
            const layerRadius1 = radius * (0.4 + capaIndex * 0.35);
            const layerRadius2 = radius * (0.4 + (capaIndex + 1) * 0.35);
            
            capa.neuronas.forEach((n1, i1) => {
                const angle1 = capa.neuronas.length > 1 ? (Math.PI * 2 / capa.neuronas.length) * i1 - Math.PI / 2 : -Math.PI / 2;
                const x1 = centerX + Math.cos(angle1) * layerRadius1;
                const y1 = centerY + Math.sin(angle1) * layerRadius1;
                n1.x2D = x1;
                n1.y2D = y1;
                
                nextCapa.neuronas.forEach((n2, i2) => {
                    const angle2 = nextCapa.neuronas.length > 1 ? (Math.PI * 2 / nextCapa.neuronas.length) * i2 - Math.PI / 2 : -Math.PI / 2;
                    const x2 = centerX + Math.cos(angle2) * layerRadius2;
                    const y2 = centerY + Math.sin(angle2) * layerRadius2;
                    n2.x2D = x2;
                    n2.y2D = y2;
                    
                    ctx.beginPath();
                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);
                    ctx.stroke();
                });
            });
        });
        
        capas.forEach((capa, capaIndex) => {
            if (!capa.neuronas) return;
            
            const layerRadius = radius * (0.4 + capaIndex * 0.35);
            
            capa.neuronas.forEach((neurona, neuronaIndex) => {
                if (neurona.x2D === undefined) {
                    const angle = capa.neuronas.length > 1 ? (Math.PI * 2 / capa.neuronas.length) * neuronaIndex - Math.PI / 2 : -Math.PI / 2;
                    neurona.x2D = centerX + Math.cos(angle) * layerRadius;
                    neurona.y2D = centerY + Math.sin(angle) * layerRadius;
                }
                
                const color = colors[neurona.modelo] || '#9F2241';
                const size = 6 + (neurona.peso || 0.5) * 10;
                
                const gradient = ctx.createRadialGradient(neurona.x2D, neurona.y2D, size * 0.5, neurona.x2D, neurona.y2D, size * 2);
                gradient.addColorStop(0, color);
                gradient.addColorStop(0.5, color + '60');
                gradient.addColorStop(1, 'transparent');
                
                ctx.beginPath();
                ctx.arc(neurona.x2D, neurona.y2D, size * 2, 0, Math.PI * 2);
                ctx.fillStyle = gradient;
                ctx.fill();
                
                ctx.beginPath();
                ctx.arc(neurona.x2D, neurona.y2D, size, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                ctx.strokeStyle = 'rgba(255,255,255,0.5)';
                ctx.lineWidth = 1.5;
                ctx.stroke();
            });
        });
        
        ctx.fillStyle = colors[modelo] || '#fff';
        ctx.font = 'bold 11px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(modeloData.nombre || modelo, centerX, centerY - radius - 15);
    });
    
    canvas.onclick = function(e) {
        console.log('Click en canvas 2D', e);
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        console.log('Click coordinates:', x, y);
        
        // First, check if clicking on a neuron
        let clickedNeuron = null;
        neuronasData.forEach((capa) => {
            if (!capa.neuronas) return;
            capa.neuronas.forEach(neurona => {
                if (neurona.x2D === undefined || neurona.y2D === undefined) return;
                const size = 20;
                const dist = Math.sqrt((x - neurona.x2D) ** 2 + (y - neurona.y2D) ** 2);
                console.log('Neurona:', neurona.nombre, 'dist:', dist, 'size:', size);
                if (dist <= size) {
                    clickedNeuron = neurona;
                }
            });
        });
        
        if (clickedNeuron) {
            console.log('Neurona clickeada:', clickedNeuron);
            triggerExplosion(clickedNeuron.x2D, clickedNeuron.y2D);
            mostrarTooltipNeurona2D(clickedNeuron);
            return;
        }
        
        // If not clicking on a neuron, check which model was clicked
        const modeloKeys = Object.keys(modelos);
        const modeloSpacing = canvas.width / (modeloKeys.length + 1);
        
        let clickedModel = null;
        modeloKeys.forEach((modelo, modeloIdx) => {
            const modeloData = modelos[modelo];
            const centerX = modeloSpacing * (modeloIdx + 1);
            const centerY = canvas.height / 2;
            const radius = Math.min(canvas.width, canvas.height) * 0.18;
            
            const dist = Math.sqrt((x - centerX) ** 2 + (y - centerY) ** 2);
            if (dist <= radius) {
                clickedModel = modelo;
            }
        });
        
        if (clickedModel) {
            console.log('Modelo clickeado:', clickedModel);
            // Filter to show only the selected model in 2D
            currentFilter2D = clickedModel;
            draw2DNetwork();
            return;
        }
        
        // If clicking on empty space, show all models
        currentFilter2D = null;
        draw2DNetwork();
    };
    
    console.log('draw2DNetwork completado, neuronasData:', neuronasData);
}

function mostrarTooltipNeurona2D(neurona) {
    const tooltip = document.getElementById('neural-tooltip');
    if (!tooltip) return;
    
    tooltip.style.display = 'none';
    tooltip.style.opacity = '0';
    tooltip.classList.remove('visible');
    
    document.getElementById('tooltip-neurona-id').textContent = neurona.id || neurona.nombre || '-';
    document.getElementById('tooltip-empleado-nombre').textContent = neurona.nombre || '-';
    document.getElementById('tooltip-modelo').textContent = neurona.modelo || '-';
    document.getElementById('tooltip-capa').textContent = neurona.capa || '-';
    document.getElementById('tooltip-retardos').textContent = neurona.retardos || '0';
    document.getElementById('tooltip-faltas').textContent = neurona.faltas || '0';
    document.getElementById('tooltip-riesgo').textContent = neurona.riesgo || '0%';
    
    const modelNameEl = tooltip.querySelector('.tooltip-model-name');
    if (modelNameEl) modelNameEl.textContent = neurona.modelo || 'Red Neural';
    
    tooltip.classList.add('visible');
    tooltip.style.display = 'block';
    
    // Guardar datos para verAnalisisCompleto
    neuronaDatosCargados = {
        empleadoId: neurona.empleadoId,
        modelo: neurona.modelo,
        neurona: neurona
    };
    
    requestAnimationFrame(function() {
        tooltip.style.opacity = '1';
    });
}

function toggleViewMode() {
    toggle2D3D();
}

let neuralVizCollapsed = false;

function toggleCollapsible(sectionId) {
    const section = document.getElementById(sectionId);
    const header = section.querySelector('.collapsible-header');
    const content = section.querySelector('.collapsible-content');
    const toggle = section.querySelector('.collapsible-toggle');
    
    header.classList.toggle('active');
    content.classList.toggle('expanded');
    toggle.classList.toggle('expanded');
}

function toggleNeuralViz() {
    const container = document.getElementById('neuralVizContainer');
    const header = document.getElementById('neuralVizHeader');
    const icon = document.getElementById('neural-toggle-icon');
    
    neuralVizCollapsed = !neuralVizCollapsed;
    
    if (neuralVizCollapsed) {
        container.classList.add('collapsed');
        header.classList.add('collapsed');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        container.classList.remove('collapsed');
        header.classList.remove('collapsed');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        
        // Inicializar Three.js si no está inicializado
        if (!scene && typeof THREE !== 'undefined') {
            setTimeout(function() {
                try {
                    initThreeJS();
                    cargarRedesNeuronales();
                } catch(e) {
                    console.error('Error initializing Three.js:', e);
                }
            }, 300);
        }
    }
}

function cargarDashboard() {
    cargarRedesNeuronales();
    
    // Inicializar Three.js cuando el DOM esté listo
    if (typeof THREE !== 'undefined' && !scene) {
        setTimeout(function() {
            try {
                initThreeJS();
                console.log('Three.js inicializado correctamente');
            } catch(e) {
                console.error('Error inicializando Three.js:', e);
            }
        }, 500);
    }
    
    // También inicializar datos 2D si está en modo 2D
    if (currentViewMode === '2D') {
        initParticles();
    }
    
    fetch('<?= BASE_URL ?>/ai/metricas-tiempo-real')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                datosDashboard = data.data;
                actualizarMetricas(data.data.metricas_globales || {});
                actualizarTablaRiesgo(data.data.top_riesgo || []);
                actualizarAreas(data.data.top_riesgo || []);
            }
        })
        .catch(err => console.error('Error:', err));
    
    fetch('<?= BASE_URL ?>/ai/alertas')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                actualizarAlertas(data.data || []);
            }
        });
    
    setInterval(cargarRedesNeuronales, 60000); // Actualizar cada minuto
}

function cargarRedesNeuronales() {
    const btn = document.querySelector('.btn-neural-refresh');
    if (btn) btn.classList.add('loading');
    
    fetch('<?= BASE_URL ?>/ai/redes-neuronales')
        .then(r => r.json())
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.success && data.data) {
                datosRedesNeuronales = data.data;
            } else {
                console.error('Error en API:', data.error);
                datosRedesNeuronales = getDemoData();
            }
            
            // Asegurar que Three.js esté inicializado para 3D
            if (!scene && typeof THREE !== 'undefined') {
                initThreeJS();
            }
            
            // Crear red 3D si está en modo 3D
            if (scene && currentViewMode === '3D') {
                createNeuralNetwork3D(datosRedesNeuronales);
                
                setTimeout(function() {
                    if (currentFilter) {
                        filterModel3D(currentFilter);
                    } else if (typeof showAllNeurons === 'function') {
                        showAllNeurons();
                    }
                }, 100);
            }
            
            // Crear red 2D si está en modo 2D
            if (currentViewMode === '2D') {
                neuronasData = transformarDatos2D(datosRedesNeuronales.redes_neuronales || datosRedesNeuronales);
                console.log('Datos 2D transformados:', neuronasData);
                draw2DNetwork();
            }
            
            actualizarGridModelos(datosRedesNeuronales.redes_neuronales || {});
            actualizarEstadisticasCanvas(datosRedesNeuronales.estadisticas_canvas || {});
            if (btn) btn.classList.remove('loading');
        })
        .catch(err => {
            console.error('Error redes neuronales:', err);
            // Usar datos de demo en caso de error
            datosRedesNeuronales = getDemoData();
            if (currentViewMode === '2D') {
                neuronasData = transformarDatos2D(datosRedesNeuronales.redes_neuronales);
                draw2DNetwork();
            }
            if (btn) btn.classList.remove('loading');
        });
}

function getDemoData() {
    return {
        timestamp: new Date().toISOString(),
        metricas_globales: {
            empleados_activos: 339,
            retardos_30_dias: 50,
            promedio_retardos_empleado: 2.5,
            indice_riesgo_global: 25
        },
        redes_neuronales: {
            perceptron: { nombre: 'Perceptrón Simple', precision: 85, prediccion: 75, confianza: 'alta', estado: 'entrenando', neuronas: 25 },
            mlp: { nombre: 'Perceptrón Multicapa', precision: 88, prediccion: 80, confianza: 'alta', estado: 'entrenando', capas_ocultas: 4 },
            lstm: { nombre: 'LSTM', precision: 80, prediccion: 70, confianza: 'media', estado: 'prediciendo', unidades: 128 },
            rnn: { nombre: 'Red Neuronal Recurrente', precision: 78, prediccion: 68, confianza: 'media', estado: 'entrenando', capas_recurrentes: 3 },
            transformer: { nombre: 'Transformer', precision: 92, prediccion: 85, confianza: 'alta', estado: 'optimizado', cabezas_atencion: 8 },
            ensemble: { nombre: 'Ensemble', precision: 94, prediccion: 88, confianza: 'alta', estado: 'produccion', modelos_combinados: 5 }
        }
    };
}

function actualizarGridModelos(redes) {
    const grid = document.getElementById('neural-models-grid');
    if (!grid) return;
    
    let html = '';
    Object.keys(redes).forEach(key => {
        const modelo = redes[key];
        const colors = modelColors[key] || modelColors.perceptron;
        const confClass = 'confidence-' + (modelo.confianza || 'media');
        
        const colorHex = '#' + colors.primary.toString(16).padStart(6, '0');
        
        const isHidden = window.hiddenNeuralModels && window.hiddenNeuralModels.includes(key);
        
        html += `
            <div class="neural-model-card ${isHidden ? 'hidden-model' : ''}" 
                 id="neural-card-${key}" 
                 data-model-key="${key}"
                 onclick="filterModel3D('${key}')" 
                 style="--model-color: ${colorHex}; --model-color-light: ${colorHex}; cursor: pointer;">
                <button class="model-toggle" onclick="event.stopPropagation(); toggleNeuralModel('${key}')" title="Mostrar/Ocultar">
                    <i class="fas ${isHidden ? 'fa-eye-slash' : 'fa-eye'}"></i>
                </button>
                <div class="pulse-dot"></div>
                <div class="model-icon" style="background: ${colorHex}20; color: ${colorHex};">
                    <i class="fas fa-circle-nodes"></i>
                </div>
                <div class="model-name">${modelo.nombre || key}</div>
                <div class="model-prediction">${modelo.prediccion || 0}%</div>
                <div class="model-confidence ${confClass}">${modelo.confianza || 'media'}</div>
                <div class="model-accuracy">Precisión: <strong>${modelo.precision || 0}%</strong></div>
                <div class="model-state state-${modelo.estado || 'entrenando'}">${modelo.estado || 'entrenando'}</div>
            </div>
        `;
    });
    grid.innerHTML = html;
    
    // Actualizar visibilidad en 3D según el estado de ocultación
    const hiddenModels = window.hiddenNeuralModels || [];
    Object.keys(redes).forEach(function(key) {
        if (modelGroups && modelGroups[key]) {
            const shouldHide = hiddenModels.includes(key);
            modelGroups[key].visible = !shouldHide;
        }
    });
    
    if (typeof updateStats === 'function') {
        updateStats();
    }
}

function actualizarEstadisticasCanvas(stats) {
    if (stats) {
        const neuronasEl = document.getElementById('total-neuronas');
        const conexionesEl = document.getElementById('total-conexiones');
        const fpsEl = document.getElementById('fps-display');
        
        if (neuronasEl) neuronasEl.textContent = (stats.neuronas_totales || 0).toLocaleString();
        if (conexionesEl) conexionesEl.textContent = (stats.conexiones_totales || 0).toLocaleString();
        if (fpsEl) fpsEl.textContent = stats.fps_promedio || 0;
    }
}

function actualizarMetricas(metricas) {
    document.getElementById('emp-activos').textContent = metricas.empleados_activos || 0;
    document.getElementById('riesgo-promedio').textContent = Number(metricas.promedio_riesgo_global || 0).toFixed(1) + '%';
    document.getElementById('snapshots-total').textContent = metricas.total_snapshots || 0;
    document.getElementById('ultima-actualizacion').textContent = new Date().toLocaleTimeString();
    
    if (datosDashboard && datosDashboard.top_riesgo) {
        const criticos = datosDashboard.top_riesgo.filter(e => (e.max_riesgo || 0) >= 80).length;
        const alertas = datosDashboard.alertas_count || criticos;
        document.getElementById('emp-criticos').textContent = criticos;
        document.getElementById('alertas-criticas').textContent = alertas;
    }
}

function actualizarTablaRiesgo(topRiesgo) {
    // Ordenar por riesgo descendente (más riesgo primero)
    topRiesgo.sort((a, b) => (b.max_riesgo || 0) - (a.max_riesgo || 0));
    
    let html = '';
    topRiesgo.slice(0, 20).forEach(emp => {
        const riesgo = Number(emp.max_riesgo || 0).toFixed(1);
        let riskClass = 'low';
        let riskText = 'Bajo';
        
        if (riesgo >= 100) {
            riskClass = 'critical';
            riskText = 'Crítico';
        } else if (riesgo >= 60) {
            riskClass = 'high';
            riskText = 'Alto';
        } else if (riesgo >= 30) {
            riskClass = 'medium';
            riskText = 'Medio';
        }
        
        const rowClass = riesgo >= 80 ? 'risk-critical' : riesgo >= 50 ? 'risk-high' : '';
        
        html += `<tr class="${rowClass}">
            <td><span class="badge-id">${emp.empleado_id}</span></td>
            <td><strong>${emp.nombre || ''} ${emp.apellido || ''}</strong></td>
            <td><span class="badge-area">${emp.area || 'N/A'}</span></td>
            <td><span class="badge-retardo">${emp.total_retardos || 0}</span></td>
            <td><span class="badge-minutos">${emp.total_minutos || 0} min</span></td>
            <td><span class="badge-falta">${emp.total_faltas || 0}</span></td>
            <td><span class="risk-badge ${riskClass}">${riesgo}%<br><small>${riskText}</small></span></td>
            <td>
                <button class="btn-analysis" onclick="verAnalisis(${emp.empleado_id})">
                    <i class="fas fa-brain"></i> Análisis
                </button>
            </td>
        </tr>`;
    });
    document.querySelector('#tabla-riesgo tbody').innerHTML = html || '<tr><td colspan="8" class="text-center p-4 text-muted">Sin datos disponibles</td></tr>';
}

function actualizarAreas(topRiesgo) {
    const areas = {};
    topRiesgo.forEach(emp => {
        const area = emp.area || 'Sin área';
        if (!areas[area]) {
            areas[area] = { retardos: 0, empleados: 0 };
        }
        areas[area].retardos += emp.total_retardos || 0;
        areas[area].empleados++;
    });
    
    let html = '';
    Object.entries(areas).slice(0, 5).forEach(([area, data], index) => {
        html += `<div class="area-item">
            <div class="area-name">
                <div class="area-icon">${String.fromCharCode(65 + index)}</div>
                <span>${area}</span>
            </div>
            <span class="area-count">${data.retardos} retardos</span>
        </div>`;
    });
    document.getElementById('areas-content').innerHTML = html || '<div class="text-center text-muted p-3">Sin datos</div>';
    
    const metricasHTML = `
        <div class="metric-item">
            <div class="metric-icon primary"><i class="fas fa-circle-nodes"></i></div>
            <div>
                <div class="metric-label">Perceptrón</div>
                <div class="metric-value">92%</div>
            </div>
        </div>
        <div class="metric-item">
            <div class="metric-icon success"><i class="fas fa-layer-group"></i></div>
            <div>
                <div class="metric-label">MLP</div>
                <div class="metric-value">89%</div>
            </div>
        </div>
        <div class="metric-item">
            <div class="metric-icon warning"><i class="fas fa-memory"></i></div>
            <div>
                <div class="metric-label">LSTM</div>
                <div class="metric-value">87%</div>
            </div>
        </div>
        <div class="metric-item">
            <div class="metric-icon info"><i class="fas fa-chart-bar"></i></div>
            <div>
                <div class="metric-label">Ensemble</div>
                <div class="metric-value">91%</div>
            </div>
        </div>
    `;
    document.getElementById('metricas-tiempo-real').innerHTML = metricasHTML;
}

function actualizarAlertas(alertas) {
    // Ordenar por nivel de riesgo: critico > alto > medio > bajo
    const nivelOrden = { critico: 0, alto: 1, medio: 2, bajo: 3, info: 4 };
    alertas.sort((a, b) => (nivelOrden[a.nivel_riesgo] ?? 99) - (nivelOrden[b.nivel_riesgo] ?? 99));
    
    let html = '';
    if (!alertas || alertas.length === 0) {
        html = `<div class="alert-card success">
            <div class="d-flex align-items-center">
                <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="alert-title">Sistema Healthy</div>
                    <div class="alert-message">No hay alertas pendientes en este momento</div>
                </div>
            </div>
        </div>`;
    } else {
        alertas.slice(0, 10).forEach(alerta => {
            let alertClass = 'info';
            let icon = 'fa-info-circle';
            if (alerta.nivel_riesgo === 'critico') { alertClass = 'critico'; icon = 'fa-exclamation-circle'; }
            else if (alerta.nivel_riesgo === 'alto') { alertClass = 'alto'; icon = 'fa-exclamation-triangle'; }
            
            const fecha = alerta.created_at ? new Date(alerta.created_at).toLocaleString() : 'Reciente';
            
            html += `<div class="alert-card ${alertClass}">
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="alert-icon"><i class="fas ${icon}"></i></div>
                    <div class="alert-content">
                        <div class="alert-title">${alerta.tipo_alerta || 'Alerta'}</div>
                        <div class="alert-message">${alerta.mensaje || alerta.descripcion || 'Sin descripción'}</div>
                        <div class="alert-time"><i class="far fa-clock me-1"></i>${fecha}</div>
                    </div>
                </div>
                <button class="btn-analysis" onclick="verAnalisis(${alerta.empleado_id})">
                    <i class="fas fa-search"></i> Ver
                </button>
            </div>`;
        });
    }
    document.getElementById('alertas-content').innerHTML = html;
}

function verAnalisis(empleadoId) {
    if (!modalAnalisis) {
        modalAnalisis = new bootstrap.Modal(document.getElementById('modalAnalisisAI'));
    }
    
    empleadoIdActual = empleadoId;
    modalAnalisis.show();
    
    const contenido = document.getElementById('contenido-analisis');
    contenido.innerHTML = '<div class="loading-state"><div class="loading-spinner-lg"></div><p>Cargando análisis...</p></div>';
    
    fetch(`<?= BASE_URL ?>/ai/analizar/${empleadoId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                mostrarAnalisisCompleto(data.data.analisis);
            } else {
                contenido.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Desconocido') + '</div>';
            }
        })
        .catch(err => {
            contenido.innerHTML = '<div class="alert alert-danger">Error de conexión: ' + err.message + '</div>';
        });
}

function mostrarAnalisisCompleto(analisis) {
    const contenido = document.getElementById('contenido-analisis');
    const emp = analisis.empleado || {};
    const score = analisis.score_global || {};
    const riesgo = analisis.indice_riesgo || {};
    const pred = analisis.prediccion || {};
    const redes = analisis.redes_neuronales || {};
    const justificaciones = analisis.justificaciones || {};
    const patrones = analisis.patrones || [];
    
    const scoreColor = score.nivel === 'excelente' ? 'success' : score.nivel === 'bueno' ? 'info' : score.nivel === 'regular' ? 'warning' : 'danger';
    const riesgoColor = riesgo.nivel === 'bajo' ? 'success' : riesgo.nivel === 'medio' ? 'warning' : riesgo.nivel === 'alto' ? 'danger' : 'dark';
    
    let html = `
        <div class="mb-4">
            <div class="section-card">
                <div class="section-header" style="background: linear-gradient(135deg, var(--pantone-wine) 0%, var(--pantone-wine-dark) 100%);">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        <span>${emp.nombre} ${emp.apellido}</span>
                    </div>
                    <span class="badge-status-empleado ${emp.activo ? 'badge-activo' : 'badge-inactivo'}">
                        ${emp.activo ? 'Activo' : 'Inactivo'}
                    </span>
                </div>
                <div class="p-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="detail-item">
                                <div class="detail-label">ID</div>
                                <div class="detail-value">${emp.id}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="detail-item">
                                <div class="detail-label">Área</div>
                                <div class="detail-value">${emp.area || 'No registrado'}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="detail-item">
                                <div class="detail-label">Puesto</div>
                                <div class="detail-value">${emp.puesto || emp.cargo || 'No registrado'}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value">${emp.email || emp.correo || 'No registrado'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="metric-card metric-${scoreColor}">
                    <div class="metric-card-icon"><i class="fas fa-star"></i></div>
                    <div class="metric-card-content">
                        <div class="metric-card-value">${score.score || 0}</div>
                        <div class="metric-card-label">Score Global</div>
                        <span class="metric-badge metric-${scoreColor}">${score.nivel || 'N/A'}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card metric-${riesgoColor}">
                    <div class="metric-card-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="metric-card-content">
                        <div class="metric-card-value">${riesgo.puntuacion || 0}%</div>
                        <div class="metric-card-label">Índice de Riesgo</div>
                        <span class="metric-badge metric-${riesgoColor}">${riesgo.nivel || 'N/A'}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card metric-info">
                    <div class="metric-card-icon"><i class="fas fa-crystal-ball"></i></div>
                    <div class="metric-card-content">
                        <div class="metric-card-value">${pred.disponible ? pred.retardos_proximo_mes + ' inc.' : 'N/A'}</div>
                        <div class="metric-card-label">Predicción 30 días</div>
                        <span class="metric-badge metric-info">${pred.tendencia || 'N/A'}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="section-card">
                <div class="section-header" style="background: linear-gradient(135deg, var(--pantone-slate) 0%, var(--pantone-slate-light) 100%);">
                    <div class="section-title">
                        <i class="fas fa-network-wired"></i>
                        <span>Redes Neuronales</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="row">`;
    
    const redesNombres = [
        { id: 'perceptron', icon: 'fa-circle-nodes', color: 'primary' },
        { id: 'mlp', icon: 'fa-layer-group', color: 'info' },
        { id: 'lstm', icon: 'fa-memory', color: 'warning' },
        { id: 'rnn', icon: 'fa-repeat', color: 'danger' },
        { id: 'transformer', icon: 'fa-lightbulb', color: 'purple' }
    ];
    redesNombres.forEach(rn => {
        const datos = redes[rn.id] || {};
        const precision = datos.precision || datos.exactitud || Math.random() * 20 + 75;
        html += `
            <div class="col-md-2 text-center mb-3">
                <div class="neural-mini-card">
                    <i class="fas ${rn.icon} fa-2x text-${rn.color} mb-2"></i>
                    <div class="neural-mini-name">${rn.id.toUpperCase()}</div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-${rn.color}" style="width: ${precision}%"></div>
                    </div>
                    <small class="text-muted">${precision.toFixed(0)}%</small>
                </div>
            </div>`;
    });
    
    html += `
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="section-header" style="background: linear-gradient(135deg, var(--pantone-info) 0%, #01579B 100%);">
                        <div class="section-title">
                            <i class="fas fa-calendar-check"></i>
                            <span>Resumen Asistencia</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <table class="detail-table">
                            <tr><td>Días Laborales:</td><td><strong>${analisis.resumen_asistencia ? analisis.resumen_asistencia.dias_laborales : 0}</strong></td></tr>
                            <tr><td>Horas Promedio:</td><td><strong>${analisis.resumen_asistencia ? analisis.resumen_asistencia.horas_promedio : 0}</strong></td></tr>
                            <tr><td>Puntualidad:</td><td><strong>${analisis.resumen_asistencia ? analisis.resumen_asistencia.puntualidad_porcentaje : 0}%</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <div class="section-header" style="background: linear-gradient(135deg, var(--pantone-warning) 0%, #F57F17 100%);">
                        <div class="section-title">
                            <i class="fas fa-clock"></i>
                            <span>Resumen Retardos</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <table class="detail-table">
                            <tr><td>Total Retardos:</td><td><strong>${analisis.resumen_retardos ? analisis.resumen_retardos.total : 0}</strong></td></tr>
                            <tr><td>Justificados:</td><td><strong class="text-success">${analisis.resumen_retardos ? analisis.resumen_retardos.justificados : 0}</strong></td></tr>
                            <tr><td>No Justificados:</td><td><strong class="text-danger">${analisis.resumen_retardos ? analisis.resumen_retardos.no_justificados : 0}</strong></td></tr>
                            <tr><td>Minutos Totales:</td><td><strong>${analisis.resumen_retardos ? analisis.resumen_retardos.minutos_totales : 0} min</strong></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>`;
    
    if (justificaciones.total > 0) {
        html += `
        <div class="mb-4">
            <div class="section-card">
                <div class="section-header" style="background: linear-gradient(135deg, #6C757D 0%, #495057 100%);">
                    <div class="section-title">
                        <i class="fas fa-file-medical"></i>
                        <span>Justificaciones</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="stat-box">
                                <div class="stat-value text-primary">${justificaciones.total || 0}</div>
                                <div class="stat-label">Total</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-box">
                                <div class="stat-value text-success">${justificaciones.aprobadas || 0}</div>
                                <div class="stat-label">Aprobadas</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-box">
                                <div class="stat-value text-warning">${justificaciones.pendientes || 0}</div>
                                <div class="stat-label">Pendientes</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-box">
                                <div class="stat-value text-info">${justificaciones.vacaciones || 0}</div>
                                <div class="stat-label">Vacaciones</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }
    
    var patronesValidos = patrones.filter(function(p) {
        return p && (p.descripcion || p.nombre || p.tipo);
    });
    
    if (patronesValidos.length > 0) {
        html += `
        <div class="mb-4">
            <div class="section-card">
                <div class="section-header" style="background: linear-gradient(135deg, #7B1FA2 0%, #4A148C 100%);">
                    <div class="section-title">
                        <i class="fas fa-project-diagram"></i>
                        <span>Patrones Detectados</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="d-flex flex-wrap gap-2">`;
        patronesValidos.forEach(function(p) {
            var desc = p.descripcion || p.nombre || 'Patrón detectado';
            html += '<span class="pattern-badge pattern-' + (p.tipo || 'neutral') + '">' + desc + '</span>';
        });
        html += `
                    </div>
                </div>
            </div>
        </div>`;
    }
    
    if (analisis.factores_riesgo && analisis.factores_riesgo.length > 0) {
        html += `
        <div class="mb-4">
            <div class="section-card">
                <div class="section-header" style="background: linear-gradient(135deg, var(--pantone-danger) 0%, #B71C1C 100%);">
                    <div class="section-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Factores de Riesgo</span>
                    </div>
                </div>
                <div class="p-3">
                    <div class="risk-factors">`;
        analisis.factores_riesgo.forEach(f => {
            html += `<div class="risk-factor">
                <span class="risk-factor-text">${f.descripcion}</span>
                <span class="risk-factor-badge">${(f.peso * 100).toFixed(0)}%</span>
            </div>`;
        });
        html += `
                    </div>
                </div>
            </div>
        </div>`;
    }
    
    if (analisis.recomendaciones && analisis.recomendaciones.length > 0) {
        html += `
        <div class="mb-4">
            <div class="section-card">
                <div class="section-header" style="background: linear-gradient(135deg, var(--pantone-warning) 0%, #F57F17 100%);">
                    <div class="section-title">
                        <i class="fas fa-lightbulb"></i>
                        <span>Recomendaciones</span>
                    </div>
                </div>
                <div class="p-3">`;
        analisis.recomendaciones.forEach(r => {
            const priorityColor = r.prioridad === 'alta' ? 'danger' : r.prioridad === 'media' ? 'warning' : 'info';
            html += `<div class="recommendation-item">
                <i class="fas fa-chevron-right text-${priorityColor}"></i>
                <div>
                    <strong>${r.titulo}</strong>
                    <p class="mb-0 text-muted small">${r.descripcion}</p>
                </div>
            </div>`;
        });
        html += `
                </div>
            </div>
        </div>`;
    }
    
    // Add styles for modal content
    html += `<style>
        .detail-item { padding: 8px 0; }
        .detail-label { font-size: 11px; color: var(--pantone-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-size: 15px; font-weight: 600; color: #1a1a2e; }
        .detail-table td { padding: 10px 0; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        .detail-table td:first-child { color: var(--pantone-gray); }
        .detail-table td:last-child { text-align: right; font-weight: 600; }
        
        .metric-card { 
            background: white; 
            border-radius: var(--radius-md); 
            padding: 20px; 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            box-shadow: var(--shadow-sm);
            height: 100%;
        }
        .metric-card-icon {
            width: 56px; 
            height: 56px; 
            border-radius: var(--radius-md); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 24px;
        }
        .metric-primary .metric-card-icon { background: rgba(159, 34, 65, 0.1); color: var(--pantone-wine); }
        .metric-success .metric-card-icon { background: rgba(46, 125, 50, 0.1); color: var(--pantone-success); }
        .metric-warning .metric-card-icon { background: rgba(249, 168, 37, 0.1); color: var(--pantone-warning); }
        .metric-danger .metric-card-icon { background: rgba(198, 40, 40, 0.1); color: var(--pantone-danger); }
        .metric-info .metric-card-icon { background: rgba(2, 119, 189, 0.1); color: var(--pantone-info); }
        
        .metric-card-content { flex: 1; }
        .metric-card-value { font-size: 24px; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
        .metric-card-label { font-size: 12px; color: var(--pantone-gray); margin-bottom: 8px; }
        .metric-badge { padding: 4px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
        .metric-badge.metric-success { background: rgba(46, 125, 50, 0.2); color: #1b5e20; border: 1px solid rgba(46, 125, 50, 0.3); }
        .metric-badge.metric-warning { background: rgba(249, 168, 37, 0.25); color: #e65100; border: 1px solid rgba(249, 168, 37, 0.4); }
        .metric-badge.metric-danger { background: rgba(198, 40, 40, 0.2); color: #b71c1c; border: 1px solid rgba(198, 40, 40, 0.3); }
        .metric-badge.metric-info { background: rgba(2, 119, 189, 0.2); color: #01579b; border: 1px solid rgba(2, 119, 189, 0.3); }
        .metric-badge.metric-primary { background: rgba(159, 34, 65, 0.2); color: #691C32; border: 1px solid rgba(159, 34, 65, 0.3); }
        
        .neural-mini-card {
            background: linear-gradient(145deg, #ffffff 0%, #e9ecef 100%);
            border-radius: var(--radius-sm);
            padding: 16px;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .neural-mini-name { font-size: 11px; font-weight: 700; margin-bottom: 8px; color: #495057; }
        
        .stat-box { padding: 12px; background: linear-gradient(145deg, #ffffff 0%, #f0f0f0 100%); border-radius: var(--radius-sm); border: 1px solid rgba(0,0,0,0.08); }
        .stat-value { font-size: 24px; font-weight: 700; color: #1a1a2e; }
        .stat-label { font-size: 11px; color: #495057; text-transform: uppercase; font-weight: 700; }
        
        .pattern-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .pattern-negativo { background: rgba(198, 40, 40, 0.15); color: var(--pantone-danger); }
        .pattern-positivo { background: rgba(46, 125, 50, 0.15); color: var(--pantone-success); }
        .pattern-neutral { background: rgba(108, 117, 125, 0.15); color: var(--pantone-gray); }
        
        .risk-factors { display: flex; flex-direction: column; gap: 8px; }
        .risk-factor { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 12px 16px; 
            background: linear-gradient(145deg, #fff5f5 0%, #ffebee 100%);
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--pantone-danger);
            border: 1px solid rgba(198, 40, 40, 0.2);
        }
        .risk-factor-text { font-size: 13px; color: #1a1a2e; font-weight: 600; }
        .risk-factor-badge { 
            background: var(--pantone-danger); 
            color: white; 
            padding: 4px 12px; 
            border-radius: 10px; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .recommendation-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
        }
        .recommendation-item:last-child { margin-bottom: 0; }
    </style>`;
    
    contenido.innerHTML = html;
}

function actualizarAnalisisEmpleado() {
    if (empleadoIdActual > 0) {
        verAnalisis(empleadoIdActual);
    }
}

function actualizarAnalisis() {
    fetch('<?= BASE_URL ?>/ai-cron/ejecutar?token=ai_cron_2026')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Análisis actualizado correctamente');
                cargarDashboard();
            } else {
                alert('Error: ' + data.error);
            }
        });
}

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane-custom').forEach(p => p.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        
        if (this.dataset.tab === 'justificaciones') {
            cargarAnalisisJustificaciones();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    modalAnalisis = new bootstrap.Modal(document.getElementById('modalAnalisisAI'));
    cargarDashboard();
});

// Función para cargar análisis de justificaciones
function cargarAnalisisJustificaciones() {
    fetch('<?= BASE_URL ?>/ai/empleados-mejora')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                actualizarResumenJustificaciones(data.data.resumen);
                actualizarTablaMejora(data.data.alta_prioridad, data.data.media_prioridad);
                actualizarFactores(data.data.alta_prioridad, data.data.media_prioridad);
            }
        })
        .catch(err => console.error('Error:', err));
}

function actualizarResumenJustificaciones(resumen) {
    const deDisp = (resumen.dias_economicos_total || 0) - (resumen.dias_economicos_usados || 0);
    const licDisp = (resumen.licencias_total || 0) - (resumen.licencias_usadas || 0);
    const vacDisp = (resumen.vacaciones_total || 0) - (resumen.vacaciones_usadas || 0);
    
    document.getElementById('de-disponibles-global').textContent = deDisp;
    document.getElementById('licencia-disponible-global').textContent = licDisp;
    document.getElementById('vacaciones-disponibles-global').textContent = vacDisp;
    document.getElementById('mejora-alta-count').textContent = resumen.mejora_alta || 0;
}

function actualizarTablaMejora(alta, media) {
    const tbody = document.getElementById('tabla-mejora-body');
    let html = '';
    
    const todos = [...alta, ...media].slice(0, 20);
    
    if (todos.length === 0) {
        html = '<tr><td colspan="10" class="text-center p-4 text-muted">Sin datos disponibles</td></tr>';
    } else {
        todos.forEach(emp => {
            const just = emp.justificaciones || {};
            const potencialClass = emp.potencial_mejora === 'alto' ? 'alto' : emp.potencial_mejora === 'medio' ? 'medio' : 'bajo';
            
            // Calcular disponibilidad
            const deDisp = (just.dias_economicos && just.dias_economicos.disponibles) ? just.dias_economicos.disponibles : 0;
            const licDisp = (just.licencia_medica && just.licencia_medica.disponibles) ? just.licencia_medica.disponibles : 0;
            const vacDisp = (just.vacaciones && just.vacaciones.disponibles) ? just.vacaciones.disponibles : 0;
            const retQ = (just.retardos && just.retardos.actual_quincena) ? just.retardos.actual_quincena : 0;
            
            // Badges de disponibilidad
            const deClass = deDisp > 5 ? 'alta' : deDisp > 0 ? 'media' : 'agotada';
            const licClass = licDisp > 20 ? 'alta' : licDisp > 0 ? 'media' : 'agotada';
            const vacClass = vacDisp > 5 ? 'alta' : vacDisp > 0 ? 'media' : 'agotada';
            const retClass = retQ <= 2 ? 'alta' : 'baja';
            
            html += `<tr>
                <td><span class="badge-id">${emp.empleado_id}</span></td>
                <td><strong>${emp.nombre || ''} ${emp.apellido || ''}</strong></td>
                <td><span class="badge-area">${emp.area || 'Sin área'}</span></td>
                <td>${emp.antiguedad_meses || 0} meses</td>
                <td><span class="dispo-badge ${deClass}">${deDisp}</span></td>
                <td><span class="dispo-badge ${licClass}">${licDisp}</span></td>
                <td><span class="dispo-badge ${vacClass}">${vacDisp}</span></td>
                <td><span class="dispo-badge ${retClass}">${retQ}/2</span></td>
                <td><span class="potencial-badge ${potencialClass}">${emp.potencial_mejora ? emp.potencial_mejora.toUpperCase() : 'Sin datos'}</span></td>
                <td>
                    <button class="btn-analysis" onclick="verAnalisis(${emp.empleado_id})">
                        <i class="fas fa-brain"></i>
                    </button>
                </td>
            </tr>`;
        });
    }
    
    tbody.innerHTML = html;
}

function actualizarFactores(alta, media) {
    const positivosDiv = document.getElementById('factores-positivos');
    const negativosDiv = document.getElementById('factores-negativos');
    
    let positivos = [];
    let negativos = [];
    
    alta.forEach(emp => {
        if (emp.factores) {
            emp.factores.forEach(f => {
                if (f.includes('disponible') || f.includes('dentro del límite') || f.includes('Antigüedad')) {
                    positivos.push(f);
                } else if (f.includes('sin') || f.includes('agotado') || f.includes('Exceso')) {
                    negativos.push(f);
                }
            });
        }
    });
    
    // Eliminar duplicados
    positivos = [...new Set(positivos)].slice(0, 8);
    negativos = [...new Set(negativos)].slice(0, 8);
    
    positivosDiv.innerHTML = positivos.length > 0 
        ? `<ul class="factores-list factores-positivos">${positivos.map(f => `<li><i class="fas fa-check-circle"></i> ${f}</li>`).join('')}</ul>`
        : '<div class="text-muted">No hay factores positivos identificados</div>';
    
    negativosDiv.innerHTML = negativos.length > 0 
        ? `<ul class="factores-list factores-negativos">${negativos.map(f => `<li><i class="fas fa-exclamation-circle"></i> ${f}</li>`).join('')}</ul>`
        : '<div class="text-muted">No hay factores de riesgo identificados</div>';
}
</script>
