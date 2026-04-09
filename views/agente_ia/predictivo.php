<style>
    :root {
        --pantone-vino: #9F2241;
        --pantone-vino-oscuro: #691C32;
        --pantone-verde: #235B4E;
        --pantone-verde-oscuro: #10312B;
        --pantone-dorado: #DDC9A3;
        --pantone-dorado-oscuro: #BC955C;
        --pantone-gris: #98989A;
        --pantone-gris-oscuro: #6F7271;
    }
    
    .predictivo-header {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 12px rgba(159, 34, 65, 0.3);
    }
    
    .predictivo-header h2 {
        color: #fff;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
        font-size: 1.35rem;
    }
    
    .predictivo-header h2 i { color: var(--pantone-dorado); }
    .predictivo-header p { color: rgba(255,255,255,0.85); margin: 0; font-size: 0.85rem; }
    
    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 0.75rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        border-top: 3px solid var(--pantone-vino);
        transition: all 0.2s ease;
    }
    
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
    .stat-card.bajo { border-top-color: var(--pantone-verde); }
    .stat-card.medio { border-top-color: #3b82f6; }
    .stat-card.medio-alto { border-top-color: #f59e0b; }
    .stat-card.alto { border-top-color: #ef4444; }
    .stat-card.critico { border-top-color: #7c3aed; }
    .stat-card.total { border-top-color: var(--pantone-gris); }
    
    .stat-card .stat-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; margin-bottom: 0.4rem;
    }
    
    .stat-card.bajo .stat-icon { background: #d1fae5; color: var(--pantone-verde); }
    .stat-card.medio .stat-icon { background: #dbeafe; color: #3b82f6; }
    .stat-card.medio-alto .stat-icon { background: #fef3c7; color: #d97706; }
    .stat-card.alto .stat-icon { background: #fee2e2; color: #ef4444; }
    .stat-card.critico .stat-icon { background: #ede9fe; color: #7c3aed; }
    .stat-card.total .stat-icon { background: #e2e8f0; color: var(--pantone-gris-oscuro); }
    
    .stat-card h6 { color: var(--pantone-gris-oscuro); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.03em; font-weight: 600; margin-bottom: 0.1rem; }
    .stat-card h3 { font-size: 1.4rem; font-weight: 800; color: var(--pantone-vino-oscuro); line-height: 1; }
    .stat-card small { color: var(--pantone-gris); font-size: 0.65rem; }
    
    .tabs-container { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
    
    .tabs-header {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        padding: 0; display: flex; flex-wrap: wrap;
    }
    
    .tab-btn {
        background: transparent; border: none;
        color: rgba(255,255,255,0.8);
        padding: 0.6rem 0.85rem;
        font-weight: 600; font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        border-bottom: 3px solid transparent;
        display: flex; align-items: center; gap: 0.35rem;
    }
    
    .tab-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .tab-btn.active { background: rgba(255,255,255,0.15); color: var(--pantone-dorado); border-bottom-color: var(--pantone-dorado); }
    .tab-content { padding: 0.75rem; }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }
    
    .modelo-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px;
        padding: 0.6rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid var(--pantone-verde);
        transition: all 0.2s ease;
    }
    
    .modelo-card:hover { transform: translateX(3px); }
    .modelo-card.bajo { border-left-color: var(--pantone-verde); }
    .modelo-card.medio { border-left-color: #3b82f6; }
    .modelo-card.medio-alto { border-left-color: #f59e0b; }
    .modelo-card.alto { border-left-color: #ef4444; }
    .modelo-card.critico { border-left-color: #7c3aed; }
    .modelo-card.total { border-left-color: var(--pantone-gris); }
    
    .modelo-card h6 {
        font-weight: 700;
        color: var(--pantone-vino-oscuro);
        font-size: 0.85rem;
    }
    
    .modelo-card small { color: var(--pantone-gris-oscuro); font-size: 0.7rem; }
    
    .confianza-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.35rem;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    
    .confianza-badge.alta { background: #d1fae5; color: var(--pantone-verde-oscuro); }
    .confianza-badge.media { background: #fef3c7; color: #92400e; }
    .confianza-badge.baja { background: #fee2e2; color: #991b1b; }
    
    .empleado-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 0.5rem 0.6rem;
        margin-bottom: 0.4rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .empleado-card:hover { 
        border-color: var(--pantone-vino); 
        box-shadow: 0 2px 6px rgba(159, 34, 65, 0.12);
        transform: translateX(3px);
    }
    
    .empleado-card .emp-nombre {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--pantone-vino-oscuro);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
    }
    
    .empleado-card .emp-area {
        font-size: 0.7rem;
        color: var(--pantone-gris);
    }
    
    .badge-nivel {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.4rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        min-width: 45px;
        text-align: center;
    }
    
    .badge-nivel.critico { background: #ede9fe; color: #7c3aed; }
    .badge-nivel.alto { background: #fee2e2; color: #dc2626; }
    .badge-nivel.medio-alto { background: #fef3c7; color: #d97706; }
    .badge-nivel.medio { background: #dbeafe; color: #2563eb; }
    .badge-nivel.bajo { background: #d1fae5; color: #059669; }
    
    .btn-accion {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        color: #fff; border: none;
        padding: 0.3rem 0.55rem;
        border-radius: 5px; font-weight: 600;
        font-size: 0.7rem;
        transition: all 0.2s ease;
    }
    
    .btn-accion:hover {
        background: linear-gradient(135deg, var(--pantone-vino-oscuro), #4a1322);
        color: #fff;
    }
    
    .btn-refresh {
        background: rgba(255,255,255,0.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        padding: 0.3rem 0.5rem;
        border-radius: 5px;
        font-size: 0.7rem;
    }
    
    .btn-refresh:hover { background: rgba(255,255,255,0.25); color: #fff; }
    
    .table-empleados { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .table-empleados thead th {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        color: #fff;
        padding: 0.5rem;
        font-weight: 600;
        font-size: 0.75rem;
        text-align: left;
    }
    .table-empleados tbody tr:hover { background: #f8f9fa; }
    .table-empleados tbody td { padding: 0.5rem; border-bottom: 1px solid #e5e7eb; }
    
    .metrica-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.85rem;
    }
    
    .metrica-item:last-child { border-bottom: none; }
    .metrica-item span:first-child { color: var(--pantone-gris-oscuro); }
    .metrica-item span:last-child { color: var(--pantone-vino-oscuro); font-weight: 600; }
    
    .tendencia-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .tendencia-indicator.creciente { background: #fee2e2; color: #dc2626; }
    .tendencia-indicator.decreciente { background: #d1fae5; color: #059669; }
    .tendencia-indicator.estable { background: #e2e8f0; color: #64748b; }
    
    .empty-state { text-align: center; padding: 1.5rem; color: var(--pantone-gris); }
    .empty-state i { font-size: 2rem; margin-bottom: 0.75rem; }
    
    .config-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px;
        padding: 0.75rem;
    }
    
    .config-section .form-control {
        border-radius: 6px;
        border: 2px solid #e2e8f0;
        padding: 0.45rem 0.65rem;
        font-size: 0.8rem;
    }
    
    .config-section .form-control:focus {
        border-color: var(--pantone-vino);
        box-shadow: 0 0 0 3px rgba(159, 34, 65, 0.1);
    }
    
    .config-section label {
        color: var(--pantone-gris-oscuro);
        font-weight: 500;
        font-size: 0.75rem;
        margin-bottom: 0.3rem;
    }
    
    .btn-guardar {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        color: #fff; border: none;
        padding: 0.45rem 1rem;
        border-radius: 6px; font-weight: 600;
        font-size: 0.8rem;
    }
    
    .btn-guardar:hover {
        background: linear-gradient(135deg, var(--pantone-vino-oscuro), #4a1322);
        color: #fff;
    }
    
    .modal-header-pred { background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro)); color: #fff; }
    
    .factor-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.45rem;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 0.35rem;
        font-size: 0.85rem;
    }
    
    .factor-item i {
        width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 6px;
        font-size: 0.8rem;
    }
    
    .factor-item.alto i { background: #fee2e2; color: #dc2626; }
    .factor-item.medio i { background: #fef3c7; color: #d97706; }
    .factor-item.bajo i { background: #d1fae5; color: #059669; }
    .factor-item .desc strong { display: block; color: var(--pantone-vino-oscuro); font-size: 0.8rem; }
    .factor-item .desc small { color: var(--pantone-gris); font-size: 0.7rem; }
    
    .riesgo-gauge { position: relative; width: 100px; height: 100px; margin: 0 auto 0.75rem; }
    .riesgo-gauge svg { transform: rotate(-90deg); }
    .riesgo-gauge circle { fill: none; stroke-width: 10; stroke-linecap: round; }
    .riesgo-gauge .bg { stroke: #e2e8f0; }
    .riesgo-gauge .value { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
    .riesgo-gauge .value strong { font-size: 1.5rem; font-weight: 700; display: block; }
    .riesgo-gauge .value span { color: var(--pantone-gris); font-size: 0.65rem; }
    
    .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; }
    .loading-spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.2); border-top-color: var(--pantone-dorado); border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    .section-title {
        color: var(--pantone-vino);
        font-size: 0.95rem;
        font-weight: 700;
    }
</style>

<div class="predictivo-header d-flex justify-content-between align-items-center flex-wrap gap-1">
        <div>
            <h2><i class="fas fa-brain"></i> Análisis Predictivo</h2>
            <p>Predicción de riesgos con Inteligencia Artificial</p>
        </div>
    </div>

    <div class="row g-1 mb-1">
        <div class="col-4 col-lg-2">
            <div class="stat-card bajo">
                <div class="stat-icon"><i class="fas fa-shield-check"></i></div>
                <h6>Bajo</h6>
                <h3 id="riesgo-bajo">-</h3>
                <small>0-30%</small>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card medio">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <h6>Medio</h6>
                <h3 id="riesgo-medio">-</h3>
                <small>31-50%</small>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card medio-alto">
                <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                <h6>Med-Alto</h6>
                <h3 id="riesgo-medio-alto">-</h3>
                <small>51-65%</small>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card alto">
                <div class="stat-icon"><i class="fas fa-fire"></i></div>
                <h6>Alto</h6>
                <h3 id="riesgo-alto">-</h3>
                <small>66-80%</small>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card critico">
                <div class="stat-icon"><i class="fas fa-skull-crossbones"></i></div>
                <h6>Crítico</h6>
                <h3 id="riesgo-critico">-</h3>
                <small>81-100%</small>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <h6>Total</h6>
                <h3 id="total-empleados">-</h3>
                <small>Analiz.</small>
            </div>
        </div>
    </div>

    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="alertas"><i class="fas fa-bell"></i> Alertas</button>
            <button class="tab-btn" data-tab="empleados"><i class="fas fa-users"></i> Empleados</button>
            <button class="tab-btn" data-tab="recomendaciones"><i class="fas fa-lightbulb"></i> Recomendaciones</button>
            <button class="tab-btn" data-tab="tendencias"><i class="fas fa-chart-bar"></i> Tendencias</button>
            <button class="tab-btn" data-tab="metricas"><i class="fas fa-metrics"></i> Métricas</button>
            <button class="tab-btn" data-tab="config"><i class="fas fa-cog"></i> Config</button>
        </div>
        
        <div class="tab-content">
            <div class="tab-pane active" id="Tab-alertas">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="section-title"><i class="fas fa-bell me-1"></i>Alertas</span>
                    <button class="btn-refresh" onclick="actualizarAlertas()"><i class="fas fa-sync-alt"></i></button>
                </div>
                <div id="alertas-container">
                    <div class="empty-state"><i class="fas fa-inbox"></i><p>Cargando...</p></div>
                </div>
            </div>
            
            <div class="tab-pane" id="Tab-empleados">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="section-title"><i class="fas fa-user-shield me-1"></i>Empleados</span>
                    <button class="btn-refresh" onclick="actualizarEmpleados()"><i class="fas fa-sync-alt"></i></button>
                </div>
                <div id="empleados-riesgo-container">
                    <div class="empty-state"><i class="fas fa-users"></i><p>Cargando...</p></div>
                </div>
            </div>
            
            <div class="tab-pane" id="Tab-recomendaciones">
                <span class="section-title d-block mb-1"><i class="fas fa-lightbulb me-1"></i>Recomendaciones</span>
                <div id="recomendaciones-container">
                    <div class="empty-state"><i class="fas fa-brain"></i><p>Cargando...</p></div>
                </div>
            </div>
            
            <div class="tab-pane" id="Tab-tendencias">
                <span class="section-title d-block mb-1" style="color: var(--pantone-verde);"><i class="fas fa-chart-line me-1"></i>Tendencias</span>
                <div id="patrones-container">
                    <div class="empty-state"><i class="fas fa-chart-line"></i><p>Cargando...</p></div>
                </div>
            </div>
            
            <div class="tab-pane" id="Tab-metricas">
                <span class="section-title d-block mb-1" style="color: var(--pantone-verde);"><i class="fas fa-chart-bar me-1"></i>Métricas</span>
                <div id="metricas-container">
                    <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>
                </div>
            </div>
            
            <div class="tab-pane" id="Tab-config">
                <span class="section-title d-block mb-1"><i class="fas fa-cog me-1"></i>Configuración</span>
                <form id="config-analisis-form">
                    <div class="config-section">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label>Días análisis histórico</label>
                                <input type="number" class="form-control" name="dias_analisis_historico" value="180">
                            </div>
                            <div class="col-md-6">
                                <label>Días de pronóstico</label>
                                <input type="number" class="form-control" name="pronostico_dias" value="30">
                            </div>
                            <div class="col-md-6">
                                <label>Umbral alerta temprana</label>
                                <input type="number" step="0.05" class="form-control" name="umbral_alerta_precoz" value="0.35">
                            </div>
                            <div class="col-md-6">
                                <label>Umbral alerta crítica</label>
                                <input type="number" step="0.05" class="form-control" name="umbral_alerta_critica" value="0.70">
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="submit" class="btn-guardar"><i class="fas fa-save me-1"></i>Guardar</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleEmpleadoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-pred">
                <h6 class="modal-title"><i class="fas fa-user-circle me-1"></i>Detalle Empleado</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalle-empleado-body" style="padding: 0.5rem;"></div>
        </div>
    </div>
</div>

<script>
let datosDashboard = null;

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('Tab-' + this.dataset.tab).classList.add('active');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    cargarDashboard();
    cargarConfiguracion();
});

function cargarConfiguracion() {
    fetch('/analisis-predictivo/config?clave=*', { credentials: 'same-origin' })
        .then(r => {
            if (!r.ok) throw new Error('Error HTTP: ' + r.status);
            return r.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const config = data.data;
                const form = document.getElementById('config-analisis-form');
                if (form) {
                    Object.keys(config).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.value = config[key];
                        }
                    });
                }
            }
        })
        .catch(err => console.error('Error cargando config:', err));
}

function cargarDashboard() {
    mostrarLoading();
    fetch('/analisis-predictivo/dashboard')
        .then(r => { if (!r.ok) throw new Error('HTTP: ' + r.status); return r.json(); })
        .then(data => {
            if (data.success) {
                datosDashboard = data.data;
                actualizarUI(data.data);
            }
            ocultarLoading();
        })
        .catch(err => { console.error(err); ocultarLoading(); });
}

function mostrarLoading() {
    let loader = document.getElementById('loading-overlay');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'loading-overlay';
        loader.className = 'loading-overlay';
        loader.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(loader);
    }
    loader.style.display = 'flex';
}

function ocultarLoading() {
    const loader = document.getElementById('loading-overlay');
    if (loader) loader.style.display = 'none';
}

function actualizarUI(dashboard) {
    const riesgos = dashboard.resumen_riesgos?.distribucion || {};
    document.getElementById('riesgo-bajo').textContent = riesgos.bajo || riesgos.bajos || 0;
    document.getElementById('riesgo-medio').textContent = riesgos.medio || riesgos.medios || 0;
    document.getElementById('riesgo-medio-alto').textContent = riesgos.medio_alto || 0;
    document.getElementById('riesgo-alto').textContent = riesgos.alto || riesgos.altos || 0;
    document.getElementById('riesgo-critico').textContent = riesgos.critico || riesgos.criticos || 0;
    document.getElementById('total-empleados').textContent = dashboard.resumen_riesgos?.total_empleados_analizados || 0;
    
    renderAlertas(dashboard.alertas);
    renderEmpleadosRiesgo(dashboard.empleados_criticos);
    renderRecomendaciones(dashboard.recomendaciones?.recomendaciones || dashboard.recomendaciones || []);
    renderPatrones(dashboard.tendencias);
    renderMetricas(dashboard.metricas_clave);
    console.log('Dashboard completo:', dashboard);
    console.log('Recomendaciones:', dashboard.recomendaciones);
}

function renderAlertas(alertas) {
    const container = document.getElementById('alertas-container');
    console.log('Alertas debug:', alertas);
    if (!alertas) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="color: var(--pantone-verde);"></i><p>Sin alertas</p></div>';
        return;
    }
    let html = '';
    
    // Mostrar resumen
    const criticas = alertas.criticas || 0;
    const advertencias = alertas.advertencias || 0;
    const precauciones = alertas.precauciones || 0;
    
    console.log('Criticas:', criticas, 'Advertencias:', advertencias, 'Precauciones:', precauciones);
    
    if (criticas > 0) html += `<div class="modelo-card critico mb-2"><h6><i class="fas fa-exclamation-triangle me-1"></i>${criticas} alertas críticas</h6><small>Requieren atención inmediata</small></div>`;
    if (advertencias > 0) html += `<div class="modelo-card medio-alto mb-2"><h6><i class="fas fa-exclamation-circle me-1"></i>${advertencias} advertencias</h6><small>Requieren seguimiento</small></div>`;
    if (precauciones > 0) html += `<div class="modelo-card medio mb-2"><h6><i class="fas fa-info-circle me-1"></i>${precauciones} precauciones</h6><small>Monitoreo recomendado</small></div>`;
    
    // Mostrar lista de alertas individuales
    if (alertas.items && alertas.items.length > 0) {
        html += '<div class="mt-2" style="max-height: 300px; overflow-y: auto;">';
        alertas.items.slice(0, 10).forEach(a => {
            const nivelClass = a.nivel === 'urgente' ? 'critico' : (a.nivel === 'alta' ? 'alto' : (a.nivel === 'media' || a.nivel === 'baja' || a.nivel === 'informativa' ? 'medio' : 'medio'));
            const nivelIcon = a.nivel === 'urgente' ? 'fa-exclamation-triangle' : (a.nivel === 'alta' ? 'fa-exclamation-circle' : 'fa-info-circle');
            html += `<div class="modelo-card ${nivelClass} mb-1" style="padding: 8px;">
                <div class="d-flex align-items-center">
                    <i class="fas ${nivelIcon} me-2"></i>
                    <div>
                        <strong>${a.nombre || ''} ${a.apellido || ''}</strong>
                        <small class="d-block">${a.mensaje || a.tipo || 'Alerta'}</small>
                    </div>
                </div>
            </div>`;
        });
        html += '</div>';
    }
    
    container.innerHTML = html || '<p>Sin alertas</p>';
}

function renderEmpleadosRiesgo(empleados) {
    const container = document.getElementById('empleados-riesgo-container');
    if (!empleados || empleados.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-shield-alt" style="color: var(--pantone-verde);"></i><p>Sin empleados en riesgo</p></div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table-empleados"><thead><tr><th>Empleado</th><th>Área</th><th>Riesgo</th><th>Nivel</th><th>Acciones</th></tr></thead><tbody>';
    empleados.forEach(e => {
        const nivelClass = e.nivel === 'critico' ? 'critico' : (e.nivel === 'alto' ? 'alto' : (e.nivel === 'medio_alto' ? 'medio-alto' : 'medio'));
        html += `<tr>
            <td><strong>${e.nombre}</strong></td>
            <td>${e.area || 'N/A'}</td>
            <td><strong style="color: ${e.riesgo >= 60 ? '#dc2626' : '#235B4E'}">${e.riesgo}%</strong></td>
            <td><span class="badge-nivel ${nivelClass}">${e.nivel.toUpperCase()}</span></td>
            <td><button class="btn-accion" onclick="verDetalle(${e.empleado_id})"><i class="fas fa-eye"></i></button></td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function renderRecomendaciones(recomendaciones) {
    const container = document.getElementById('recomendaciones-container');
    if (!recomendaciones || recomendaciones.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="color: var(--pantone-verde);"></i><p>Sin recomendaciones</p></div>';
        return;
    }
    let html = '';
    recomendaciones.slice(0, 8).forEach(r => {
        const prioridadClass = r.prioridad === 'urgente' ? 'critico' : (r.prioridad === 'alta' ? 'alto' : 'medio');
        const nombreEmpleado = r.nombre ? `${r.nombre} ${r.apellido || ''}` : (r.empleado || r.categoria || 'General');
        html += `<div class="modelo-card ${prioridadClass}">
            <h6><span class="badge-nivel ${prioridadClass} me-1" style="font-size: 0.6rem;">${(r.prioridad || 'media').toUpperCase()}</span>${nombreEmpleado}</h6>
            <small>${r.descripcion || r.mensaje || ''}</small>
        </div>`;
    });
    container.innerHTML = html;
}

function renderPatrones(tendencias) {
    const container = document.getElementById('patrones-container');
    if (!tendencias || tendencias.tendencia === undefined) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><p>Sin datos</p></div>';
        return;
    }
    const tendenciaClass = tendencias.tendencia === 'creciente' ? 'creciente' : (tendencias.tendencia === 'decreciente' ? 'decreciente' : 'estable');
    const tendenciaIcon = tendencias.tendencia === 'creciente' ? 'fa-arrow-up' : (tendencias.tendencia === 'decreciente' ? 'fa-arrow-down' : 'fa-equals');
    container.innerHTML = `
        <div class="text-center mb-3">
            <span class="tendencia-indicator ${tendenciaClass}">
                <i class="fas ${tendenciaIcon}"></i> ${tendencias.tendencia.toUpperCase()}
            </span>
        </div>
        <div class="text-center">
            <h3 style="color: var(--pantone-vino);">${(tendencias.cambio_porcentual > 0 ? '+' : '') + tendencias.cambio_porcentual}%</h3>
            <small class="text-muted">Cambio porcentual</small>
        </div>`;
}

function renderMetricas(metricas) {
    const container = document.getElementById('metricas-container');
    if (!metricas) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
        return;
    }
    container.innerHTML = `
        <div class="metrica-item"><span>Empleados c/incidencias</span><span>${metricas.empleados_con_incidencias}</span></div>
        <div class="metrica-item"><span>Total incidencias mes</span><span>${metricas.total_incidencias_mes}</span></div>
        <div class="metrica-item"><span>Promedio retardo (min)</span><span>${metricas.promedio_minutos_retardo}</span></div>
        <div class="metrica-item"><span>Tasa incidencia</span><span>${metricas.tasa_incidencia_porcentual}%</span></div>
        <div class="metrica-item"><span>Empleados activos</span><span>${metricas.total_empleados_activos}</span></div>`;
}

function verDetalle(empleado_id) {
    fetch('/analisis-predictivo/empleado?empleado_id=' + empleado_id)
        .then(r => { if (!r.ok) throw new Error('HTTP: ' + r.status); return r.json(); })
        .then(data => {
            if (data.success) mostrarDetalleEmpleado(data.data);
        })
        .catch(console.error);
}

function mostrarDetalleEmpleado(datos) {
    const riesgoColor = datos.indice_riesgo >= 60 ? '#dc2626' : (datos.indice_riesgo >= 40 ? '#d97706' : '#10b981');
    const riesgoStroke = datos.indice_riesgo >= 60 ? '#dc2626' : (datos.indice_riesgo >= 40 ? '#f59e0b' : '#10b981');
    const circumference = 2 * Math.PI * 40;
    const offset = circumference - (datos.indice_riesgo / 100) * circumference;
    const metricas = datos.metricas || {};
    const tiposJustificacion = metricas.tipos_justificacion || {};
    
    let html = `
        <div class="text-center mb-3">
            <h4 class="mb-1">${datos.nombre}</h4>
            <small class="text-muted">${datos.area || 'N/A'} - ${datos.puesto || 'N/A'}</small>
        </div>
        
        <div class="riesgo-gauge">
            <svg width="100" height="100">
                <circle class="bg" cx="50" cy="50" r="40"/>
                <circle class="bg" cx="50" cy="50" r="40" stroke="${riesgoStroke}" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"/>
            </svg>
            <div class="value">
                <strong style="color: ${riesgoColor}">${datos.indice_riesgo}%</strong>
                <span>Riesgo</span>
            </div>
        </div>
        
        <div class="text-center mb-3">
            <span class="badge-nivel ${datos.nivel_riesgo === 'critico' ? 'critico' : (datos.nivel_riesgo === 'alto' ? 'alto' : (datos.nivel_riesgo === 'medio_alto' ? 'medio-alto' : 'medio'))}">
                ${(datos.nivel_riesgo || 'medio').toUpperCase()}
            </span>
            ${datos.esAptoPuntualidad ? '<span class="badge-nivel" style="background: #10b981; color: white; margin-left: 5px;">⭐ Aptos para Empleado del Mes</span>' : ''}
        </div>
        
        <hr>
        
        <h6><i class="fas fa-chart-line me-2"></i>Métricas (Últimos 90 días)</h6>
        <div class="row text-center mb-3">
            <div class="col-4"><h5 class="mb-0" style="color: #f59e0b;">${metricas.retardos || 0}</h5><small class="text-muted">Retardos</small></div>
            <div class="col-4"><h5 class="mb-0" style="color: #dc2626;">${metricas.faltas || 0}</h5><small class="text-muted">Faltas</small></div>
            <div class="col-4"><h5 class="mb-0" style="color: #7c3aed;">${metricas.sanciones || 0}</h5><small class="text-muted">Sanciones</small></div>
        </div>
        
        <h6><i class="fas fa-calendar-check me-2"></i>Permisos (Año actual)</h6>
        <div class="row text-center mb-3">
            <div class="col-3"><h5 class="mb-0" style="color: #3b82f6;">${metricas.dias_economicos || 0}</h5><small class="text-muted">Días Econ.</small></div>
            <div class="col-3"><h5 class="mb-0" style="color: #10b981;">${metricas.licencias || 0}</h5><small class="text-muted">Licencias</small></div>
            <div class="col-3"><h5 class="mb-0" style="color: #f59e0b;">${metricas.vacaciones || 0}</h5><small class="text-muted">Vacaciones</small></div>
            <div class="col-3"><h5 class="mb-0" style="color: #6366f1;">${metricas.comisiones || 0}</h5><small class="text-muted">Comisiones</small></div>
        </div>`;
    
    const tiposKeys = Object.keys(tiposJustificacion);
    if (tiposKeys.length > 0) {
        html += `<h6><i class="fas fa-file-signature me-2"></i>Tipos de Justificación</h6>
        <div class="row text-center mb-3">`;
        tiposKeys.forEach((tipo, idx) => {
            const color = ['#ec4899', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#6366f1', '#14b8a6'][idx % 7];
            const cantidad = tiposJustificacion[tipo];
            const tieneDatos = cantidad > 0;
            html += `<div class="col-4 col-md-3 mb-2">
                <div style="background: ${tieneDatos ? color + '20' : '#f3f4f6'}; padding: 8px; border-radius: 8px; border: 1px solid ${tieneDatos ? color + '40' : '#e5e7eb'};">
                    <h6 class="mb-0" style="color: ${tieneDatos ? color : '#9ca3af'};">${cantidad}</h6>
                    <small class="text-muted" style="font-size: 0.65rem;">${tipo}</small>
                </div>
            </div>`;
        });
        html += `</div>`;
    }
    
    html += `<hr>
        
        <h6><i class="fas fa-exclamation-triangle me-2"></i>Factores de Riesgo</h6>`;
    
    if (datos.factores_riesgo && datos.factores_riesgo.length > 0) {
        datos.factores_riesgo.forEach(f => {
            html += `<div class="factor-item ${f.impacto}">
                <i class="fas fa-exclamation-circle"></i>
                <div class="desc">
                    <strong>${f.factor}</strong>
                    <small>${f.descripcion}</small>
                </div>
            </div>`;
        });
    } else {
        html += '<p class="text-success"><i class="fas fa-check-circle me-1"></i>Sin factores de riesgo</p>';
    }
    
    const pronostico = datos.pronostico || { incidencias_estimadas: 0, dias_pronostico: 0, probabilidad: 0 };
    
    html += `<hr><h6><i class="fas fa-calendar-alt me-2"></i>Pronóstico</h6>
        <div class="row text-center">
            <div class="col-4"><h5 class="mb-0">${pronostico.incidencias_estimadas}</h5><small class="text-muted">Incidencias</small></div>
            <div class="col-4"><h5 class="mb-0">${pronostico.dias_pronostico}</h5><small class="text-muted">Días</small></div>
            <div class="col-4"><h5 class="mb-0">${pronostico.probabilidad}%</h5><small class="text-muted">Probabilidad</small></div>
        </div>`;
    
    document.getElementById('detalle-empleado-body').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detalleEmpleadoModal')).show();
}

function actualizarAlertas() {
    fetch('/analisis-predictivo/dashboard', { credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error('HTTP: ' + r.status); return r.json(); })
        .then(data => {
            if (data.success) renderAlertas(data.data.alertas);
        })
        .catch(err => console.error('Error:', err));
}

function actualizarEmpleados() {
    fetch('/analisis-predictivo/dashboard', { credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error('HTTP: ' + r.status); return r.json(); })
        .then(data => {
            if (data.success) renderEmpleadosRiesgo(data.data.empleados_criticos);
        })
        .catch(err => console.error('Error:', err));
}

function actualizarTodo() {
    mostrarLoading();
    fetch('/analisis-predictivo/dashboard', { credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error('HTTP: ' + r.status); return r.json(); })
        .then(data => {
            if (data.success) {
                datosDashboard = data.data;
                actualizarUI(data.data);
            }
            ocultarLoading();
        })
        .catch(err => { console.error('Error:', err); ocultarLoading(); });
}

// Auto-refresh cada 60 segundos
let intervalId = setInterval(actualizarTodo, 60000);

document.getElementById('config-analisis-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    // Agregar token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.getElementById('csrf_token')?.value || '';
    formData.append('csrf_token', csrfToken);
    
    fetch('/analisis-predictivo/config', { 
        method: 'POST', 
        body: formData,
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
        .then(r => { if (!r.ok) throw new Error('HTTP: ' + r.status); return r.json(); })
        .then(data => {
            if (data.success) {
                alert(data.message || 'Configuración guardada');
            } else {
                alert('Error: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar configuración');
        });
});
</script>
