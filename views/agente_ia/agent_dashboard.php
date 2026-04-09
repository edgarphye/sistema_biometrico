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
    
    .agent-header {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 12px rgba(159, 34, 65, 0.3);
    }
    
    .agent-header h2 {
        color: #fff;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
        font-size: 1.35rem;
    }
    
    .agent-header h2 i { color: var(--pantone-dorado); }
    .agent-header p { color: rgba(255,255,255,0.85); margin: 0; font-size: 0.85rem; }
    
    .version-badge {
        background: rgba(255,255,255,0.2);
        border: 1px solid var(--pantone-dorado);
        color: var(--pantone-dorado);
        padding: 0.25rem 0.5rem;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 0.75rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        border-top: 3px solid var(--pantone-vino);
        transition: all 0.2s ease;
    }
    
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
    .stat-card.critico { border-top-color: #dc2626; }
    .stat-card.alto { border-top-color: #f97316; }
    .stat-card.acciones { border-top-color: var(--pantone-verde); }
    .stat-card.total { border-top-color: var(--pantone-gris); }
    .stat-card.justificaciones { border-top-color: var(--pantone-dorado); }
    .stat-card.modelos { border-top-color: #8b5cf6; }
    
    .stat-card .stat-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; margin-bottom: 0.4rem;
    }
    
    .stat-card.critico .stat-icon { background: #fee2e2; color: #dc2626; }
    .stat-card.alto .stat-icon { background: #ffedd5; color: #f97316; }
    .stat-card.acciones .stat-icon { background: #d1fae5; color: var(--pantone-verde); }
    .stat-card.total .stat-icon { background: #e2e8f0; color: var(--pantone-gris-oscuro); }
    .stat-card.justificaciones .stat-icon { background: #fef3c7; color: var(--pantone-dorado-oscuro); }
    .stat-card.modelos .stat-icon { background: #ede9fe; color: #8b5cf6; }
    
    .stat-card h6 { color: var(--pantone-gris-oscuro); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.02em; font-weight: 600; margin-bottom: 0.1rem; }
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
    
    .mini-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 0.5rem 0.6rem;
        margin-bottom: 0.4rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .mini-card:hover { 
        border-color: var(--pantone-vino); 
        transform: translateX(3px);
    }
    
    .mini-card .emp-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--pantone-vino-oscuro);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 140px;
    }
    
    .mini-card .emp-area { font-size: 0.7rem; color: var(--pantone-gris); }
    
    .badge-riesgo {
        display: inline-flex;
        padding: 0.2rem 0.45rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 42px;
        text-align: center;
    }
    
    .badge-riesgo.critico { background: #ede9fe; color: #7c3aed; }
    .badge-riesgo.alto { background: #fee2e2; color: #dc2626; }
    .badge-riesgo.medio { background: #fef3c7; color: #d97706; }
    .badge-riesgo.bajo { background: #d1fae5; color: #059669; }
    
    .btn-ejecutar {
        background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro));
        color: #fff; border: none;
        padding: 0.4rem 0.75rem;
        border-radius: 6px; font-weight: 600;
        font-size: 0.8rem;
    }
    
    .btn-ejecutar:hover {
        background: linear-gradient(135deg, var(--pantone-vino-oscuro), #4a1322);
        color: #fff;
    }
    
    .info-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px;
        padding: 0.6rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid var(--pantone-verde);
    }
    
    .info-card.ml { border-left-color: var(--pantone-verde); }
    .info-card.rna { border-left-color: var(--pantone-vino); }
    }
    
    .info-card.rna { border-left-color: var(--pantone-vino); }
    .info-card.ml { border-left-color: var(--pantone-verde); }
    
    .info-card .titulo {
        font-weight: 700;
        color: var(--pantone-vino-oscuro);
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    
    .info-card .subtitulo {
        font-size: 0.7rem;
        color: var(--pantone-gris-oscuro);
    }
    
    .info-card .prediccion {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--pantone-vino);
    }
    
    .confianza-badge {
        display: inline-flex;
        padding: 0.15rem 0.3rem;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    
    .confianza-badge.alta { background: #d1fae5; color: var(--pantone-verde-oscuro); }
    .confianza-badge.media { background: #fef3c7; color: #92400e; }
    .confianza-badge.baja { background: #fee2e2; color: #991b1b; }
    
    .table-mini { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .table-mini thead th {
        background: linear-gradient(135deg, var(--pantone-verde), var(--pantone-verde-oscuro));
        color: #fff;
        padding: 0.5rem;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .table-mini tbody tr:hover { background: #f8f9fa; }
    .table-mini tbody td { padding: 0.5rem; border-bottom: 1px solid #e5e7eb; }
    
    .metrica-row {
        display: flex;
        justify-content: space-between;
        padding: 0.4rem 0;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.85rem;
    }
    
    .metrica-row:last-child { border-bottom: none; }
    .metrica-row span:first-child { color: var(--pantone-gris-oscuro); }
    .metrica-row span:last-child { color: var(--pantone-vino-oscuro); font-weight: 600; }
    
    .modal-header-agent { background: linear-gradient(135deg, var(--pantone-vino), var(--pantone-vino-oscuro)); color: #fff; }
    
    .factor-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 0.35rem;
        font-size: 0.85rem;
    }
    
    .factor-row i { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 0.8rem; }
    .factor-row.alto i { background: #fee2e2; color: #dc2626; }
    .factor-row.medio i { background: #fef3c7; color: #d97706; }
    .factor-row.bajo i { background: #d1fae5; color: #059669; }
    .factor-row .desc strong { display: block; color: var(--pantone-vino-oscuro); font-size: 0.8rem; }
    .factor-row .desc small { color: var(--pantone-gris); font-size: 0.7rem; }
    
    .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; }
    .loading-spinner { width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.2); border-top-color: var(--pantone-dorado); border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    .agent-section-title {
        color: var(--pantone-vino);
        font-size: 1rem;
        font-weight: 700;
    }
</style>

<div class="agent-header d-flex justify-content-between align-items-center flex-wrap gap-1">
        <div>
            <h2><i class="fas fa-brain"></i> Agent IA Predictivo</h2>
            <p>Redes Neuronales para análisis de riesgos</p>
        </div>
        <div class="d-flex align-items-center gap-1">
            <span class="version-badge"><i class="fas fa-network-wired"></i> v3.0</span>
            <span class="version-badge" style="border-color: var(--pantone-verde); color: var(--pantone-verde);">
                <i class="fas fa-microchip"></i> 8 Modelos
            </span>
        </div>
    </div>

    <div class="row g-1 mb-1">
        <div class="col-4 col-lg-2">
            <div class="stat-card critico">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <h6>Críticos</h6>
                <h3 id="riesgo-critico">-</h3>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card alto">
                <div class="stat-icon"><i class="fas fa-fire"></i></div>
                <h6>Alto</h6>
                <h3 id="riesgo-alto">-</h3>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card acciones">
                <div class="stat-icon"><i class="fas fa-cogs"></i></div>
                <h6>Acciones</h6>
                <h3 id="acciones-ejecutadas">-</h3>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card justificaciones">
                <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
                <h6>Justif.</h6>
                <h3 id="total-justificaciones">-</h3>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card total">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <h6>Analiz.</h6>
                <h3 id="total-analizados">-</h3>
            </div>
        </div>
        <div class="col-4 col-lg-2">
            <div class="stat-card modelos">
                <div class="stat-icon"><i class="fas fa-microchip"></i></div>
                <h6>Modelos</h6>
                <h3>8</h3>
            </div>
        </div>
    </div>

    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="analisis"><i class="fas fa-brain"></i> Análisis</button>
            <button class="tab-btn" data-tab="redes"><i class="fas fa-network-wired"></i> RNA</button>
            <button class="tab-btn" data-tab="modelos"><i class="fas fa-cogs"></i> ML</button>
            <button class="tab-btn" data-tab="justificaciones"><i class="fas fa-file-signature"></i> Justif.</button>
            <button class="tab-btn" data-tab="recomendaciones"><i class="fas fa-lightbulb"></i> Recomend.</button>
            <button class="tab-btn" data-tab="metricas"><i class="fas fa-chart-bar"></i> Métricas</button>
        </div>
        
        <div class="tab-content">
            <div class="tab-pane active" id="tab-analisis">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="agent-section-title"><i class="fas fa-user-shield me-1"></i>Resultados IA</span>
                    <div class="d-flex gap-2">
                        <button class="btn-ejecutar" id="btn-auto-refresh" onclick="toggleAutoRefresh()" style="font-size: 0.75rem; padding: 4px 10px;">
                            <i class="fas fa-sync me-1"></i> Auto
                        </button>
                        <button class="btn-ejecutar" onclick="iniciarAnalisis()">
                            <i class="fas fa-play me-1"></i>Ejecutar
                        </button>
                    </div>
                </div>
                <div id="analisis-container">
                    <div class="text-center text-muted py-2">
                        <i class="fas fa-brain fa-lg mb-1" style="color: var(--pantone-gris);"></i>
                        <p class="mb-0 small">Cargando análisis...</p>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-redes">
                <span class="agent-section-title d-block mb-1"><i class="fas fa-network-wired me-1"></i>Redes Neuronales</span>
                
                <div class="info-card rna">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="titulo"><i class="fas fa-circle-nodes"></i> Perceptrón Simple</div>
                            <div class="subtitulo">Arquitectura: 4-1 | Clasificación binaria</div>
                        </div>
                        <span class="confianza-badge alta">Alta</span>
                    </div>
                </div>
                
                <div class="info-card rna">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="titulo"><i class="fas fa-layer-group"></i> MLP</div>
                            <div class="subtitulo">Arquitectura: 4-8-6-1 | Retropropagación</div>
                        </div>
                        <span class="confianza-badge alta">Alta</span>
                    </div>
                </div>
                
                <div class="info-card rna">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="titulo"><i class="fas fa-memory"></i> RNN</div>
                            <div class="subtitulo">Memoria LSTM | Secuencias temporales</div>
                        </div>
                        <span class="confianza-badge media">Media</span>
                    </div>
                </div>
                
                <div class="info-card rna">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="titulo"><i class="fas fa-bolt"></i> Transformer</div>
                            <div class="subtitulo">Self-Attention | Dependencias globales</div>
                        </div>
                        <span class="confianza-badge alta">Alta</span>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-modelos">
                <span class="agent-section-title d-block mb-1" style="color: var(--pantone-verde);"><i class="fas fa-microchip me-1"></i>Algoritmos ML</span>
                
                <div class="info-card ml">
                    <div class="titulo"><i class="fas fa-chart-line"></i> Regresión Lineal/Polinomial</div>
                    <div class="subtitulo">Predicción de tendencias con R²</div>
                </div>
                
                <div class="info-card ml">
                    <div class="titulo"><i class="fas fa-wave-square"></i> Promedio Móvil Exp.</div>
                    <div class="subtitulo">Series temporales con suavizado</div>
                </div>
                
                <div class="info-card ml">
                    <div class="titulo"><i class="fas fa-tree"></i> Bosques Aleatorios</div>
                    <div class="subtitulo">Ensemble de árboles</div>
                </div>
                
                <div class="info-card ml">
                    <div class="titulo"><i class="fas fa-calculator"></i> Naive Bayes</div>
                    <div class="subtitulo">Clasificación probabilística</div>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-justificaciones">
                <span class="agent-section-title d-block mb-1" style="color: var(--pantone-dorado-oscuro);"><i class="fas fa-file-signature me-1"></i>Justificaciones</span>
                <div class="table-responsive">
                    <table class="table-mini">
                        <thead>
                            <tr><th>Tipo</th><th>Cant</th><th>Apr</th><th>Rech</th><th>Tasa</th></tr>
                        </thead>
                        <tbody id="justificaciones-body">
                            <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-recomendaciones">
                <span class="agent-section-title d-block mb-1"><i class="fas fa-lightbulb me-1"></i>Recomendaciones</span>
                <div id="recomendaciones-container">
                    <p class="text-muted text-center">Sin recomendaciones</p>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-metricas">
                <span class="agent-section-title d-block mb-1" style="color: var(--pantone-verde);"><i class="fas fa-chart-bar me-1"></i>Métricas IA</span>
                <div class="row">
                    <div class="col-6">
                        <div class="metrica-row"><span>Alertas 24h</span><span id="metricas-alertas" style="color: var(--pantone-vino);">-</span></div>
                    </div>
                    <div class="col-6">
                        <div class="metrica-row"><span>Críticas</span><span id="metricas-criticas" style="color: #dc2626;">-</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-agent">
                <h6 class="modal-title"><i class="fas fa-brain me-1"></i>Detalle IA</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalle-ia-body" style="padding: 0.5rem;"></div>
        </div>
    </div>
</div>

<script>
let datosAnalisis = null;

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

function iniciarAnalisis() {
    mostrarLoading();
    // Always fetch fresh data from database - no cache
    fetch('/agent-ia/analizar?_=' + Date.now())
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                datosAnalisis = data.data;
                actualizarUI(data.data);
                // Mostrar última actualización
                const horaActual = new Date().toLocaleTimeString();
                console.log('Análisis actualizado:', horaActual);
            }
            ocultarLoading();
        })
        .catch(err => { 
            console.error(err); 
            ocultarLoading(); 
            alert('Error al ejecutar análisis');
        });
}

// Auto-refresh cada 60 segundos
let intervalId = null;
function toggleAutoRefresh() {
    const btn = document.getElementById('btn-auto-refresh');
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
        btn.innerHTML = '<i class="fas fa-sync me-1"></i> Auto';
        btn.classList.remove('active');
    } else {
        iniciarAnalisis(); // Ejecutar inmediatamente
        intervalId = setInterval(iniciarAnalisis, 60000); // Luego cada minuto
        btn.innerHTML = '<i class="fas fa-stop me-1"></i> Detener';
        btn.classList.add('active');
    }
}

// Iniciar automáticamente al cargar
document.addEventListener('DOMContentLoaded', function() {
    iniciarAnalisis();
});

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

function actualizarUI(data) {
    document.getElementById('riesgo-critico').textContent = data.resumen?.riesgo_critico || 0;
    document.getElementById('riesgo-alto').textContent = data.resumen?.riesgo_alto || 0;
    document.getElementById('acciones-ejecutadas').textContent = data.resumen?.acciones_ejecutadas || 0;
    document.getElementById('total-analizados').textContent = data.resumen?.total_analizados || 0;
    document.getElementById('total-justificaciones').textContent = data.analisis_justificaciones?.total_solicitudes || 0;
    
    renderAnalisis(data.analisis_detallados);
    renderRecomendaciones(data.recomendaciones_globales);
    renderJustificaciones(data.analisis_justificaciones);
    
    if (data.metricas_ia) {
        document.getElementById('metricas-alertas').textContent = data.metricas_ia.alertas_ultimas_24h || 0;
        document.getElementById('metricas-criticas').textContent = data.metricas_ia.alertas_criticas || 0;
    }
}

function renderAnalisis(analisis) {
    const container = document.getElementById('analisis-container');
    if (!analisis || analisis.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-check-circle fa-lg"></i><p>Sin riesgos detectados</p></div>';
        return;
    }
    
    // Ordenar por riesgo descendente (mayor riesgo primero)
    analisis.sort((a, b) => (b.analisis.indice_riesgo_ia || 0) - (a.analisis.indice_riesgo_ia || 0));
    
    let html = '<div class="row g-1">';
    analisis.forEach((emp, idx) => {
        const riesgo = emp.analisis.indice_riesgo_ia;
        let badgeClass = 'bajo';
        if (riesgo >= 80) badgeClass = 'critico';
        else if (riesgo >= 60) badgeClass = 'alto';
        else if (riesgo >= 40) badgeClass = 'medio';
        
        html += `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="mini-card" onclick="mostrarDetalle(${idx})">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1 me-1">
                            <div class="emp-name" title="${emp.nombre}">${emp.nombre}</div>
                            <div class="emp-area">${emp.area || 'N/A'}</div>
                        </div>
                        <span class="badge-riesgo ${badgeClass}">${riesgo}%</span>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function mostrarDetalle(idx) {
    const emp = datosAnalisis.analisis_detallados[idx];
    if (!emp) return;
    
    const analisis = emp.analisis;
    const decision = emp.decision || {};
    const metricas = emp.metricas || {};
    
    const numModelosAlto = analisis.redes_neuronales ? Object.values(analisis.redes_neuronales).filter(r => (r.prediccion || 0) >= 70 && r.prediccion !== undefined).length : 0;
    const numFactores = analisis.factores_criticos?.length || 0;
    let riesgoLabel = analisis.indice_riesgo_ia >= 80 ? 'CRÍTICO' : (analisis.indice_riesgo_ia >= 60 ? 'ALTO' : (analisis.indice_riesgo_ia >= 40 ? 'MEDIO' : 'BAJO'));
    let riesgoColor = analisis.indice_riesgo_ia >= 80 ? '#dc2626' : (analisis.indice_riesgo_ia >= 60 ? '#f59e0b' : (analisis.indice_riesgo_ia >= 40 ? '#d97706' : '#059669'));
    if (numModelosAlto > 0 || numFactores > 0) {
        riesgoLabel = analisis.indice_riesgo_ia >= 60 ? 'ALTO' : (analisis.indice_riesgo_ia >= 40 ? 'MEDIO' : 'MEDIO');
        riesgoColor = analisis.indice_riesgo_ia >= 60 ? '#f59e0b' : '#d97706';
    }
    const tendenciaLabel = analisis.tendencia === 'decreciente' ? '📉 Empeorando' : (analisis.tendencia === 'creciente' ? '📈 Mejorando' : '➡️ Estable');
    
    let html = `
        <div style="max-height: 75vh; overflow-y: auto; padding: 5px;">
            <!-- DATOS DEL EMPLEADO -->
            <div style="background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                <div style="font-size: 1.4rem; font-weight: bold;">${emp.nombre || 'Sin nombre'} ${emp.apellido || ''}</div>
                <div style="font-size: 1rem; opacity: 0.9; margin-top: 5px;">📍 ${emp.area || 'Sin área'}</div>
                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 20px; align-items: center;">
                    <span style="background: ${riesgoColor}; padding: 8px 20px; border-radius: 25px; font-size: 1.1rem; font-weight: bold;">⚠️ ${riesgoLabel}: ${analisis.indice_riesgo_ia}%</span>
                </div>
                <div style="margin-top: 10px; font-size: 1rem;">${tendenciaLabel}</div>
            </div>
            
            <!-- RESUMEN DE ASISTENCIA -->
            <div style="margin-bottom: 20px;">
                <h6 style="color: #1e3a5f; border-bottom: 2px solid #1e3a5f; padding-bottom: 5px; margin-bottom: 10px;">
                    📊 Resumen de Asistencia (Últimos 90 días)
                </h6>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <div style="background: #fef3c7; padding: 15px; border-radius: 10px; text-align: center; border: 2px solid #f59e0b;">
                        <div style="font-size: 1.8rem; font-weight: bold; color: #92400e;">${metricas.retardos || 0}</div>
                        <div style="font-size: 0.85rem; color: #666;">⏰ Retardos</div>
                    </div>
                    <div style="background: #fef3c7; padding: 15px; border-radius: 10px; text-align: center; border: 2px solid #f59e0b;">
                        <div style="font-size: 1.8rem; font-weight: bold; color: #92400e;">${metricas.minutos_atraso || 0}</div>
                        <div style="font-size: 0.85rem; color: #666;">⏳ Minutos de atraso</div>
                    </div>
                    <div style="background: #fee2e2; padding: 15px; border-radius: 10px; text-align: center; border: 2px solid #dc2626;">
                        <div style="font-size: 1.8rem; font-weight: bold; color: #dc2626;">${metricas.faltas || 0}</div>
                        <div style="font-size: 0.85rem; color: #666;">❌ Faltas</div>
                    </div>
                </div>
            </div>
            
            <!-- ANÁLISIS DE LOS MODELOS IA -->
            <div style="margin-bottom: 20px;">
                <h6 style="color: #1e3a5f; border-bottom: 2px solid #1e3a5f; padding-bottom: 5px; margin-bottom: 10px;">
                    🧠 ¿Qué dicen los modelos IA?
                </h6>
                <div style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Predicción de riesgo por cada modelo:</div>`;
    
    const modelosNombres = {
        'perceptron': '🔵 Perceptrón',
        'mlp': '🟠 MLP (Red Avanzada)',
        'lstm': '🟡 LSTM (Memoria)',
        'rnn': '🟢 RNN (Recurrente)',
        'transformer': '🟣 Transformer'
    };
    
    if (analisis.redes_neuronales) {
        Object.keys(analisis.redes_neuronales).forEach(key => {
            if (key === '_metadata') return;
            const rna = analisis.redes_neuronales[key];
            const pred = rna.prediccion || 0;
            const colorPred = pred >= 70 ? '#dc2626' : (pred >= 50 ? '#f59e0b' : '#059669');
            const nivelPred = pred >= 70 ? '🚨 ALTO' : (pred >= 50 ? '⚠️ MEDIO' : '✅ BAJO');
            html += `<div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e5e7eb;">
                <span style="font-weight: 500;">${modelosNombres[key] || key}</span>
                <span style="text-align: right;">
                    <span style="color: ${colorPred}; font-weight: bold; font-size: 1.1rem;">${pred}%</span>
                    <div style="font-size: 0.75rem; color: #666;">${nivelPred}</div>
                </span>
            </div>`;
        });
    }
    
    html += `</div>`;
    
    // FACTORES DE RIESGO - solo mostrar si hay riesgo significativo
    const tieneRiesgo = analisis.indice_riesgo_ia >= 40 || numModelosAlto > 0 || numFactores > 0;
    if (analisis.factores_criticos?.length > 0 && tieneRiesgo) {
        html += `<div style="margin-bottom: 20px;">
            <h6 style="color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 5px; margin-bottom: 10px;">
                🚨 ¿Por qué está en riesgo?
            </h6>`;
        analisis.factores_criticos.forEach(f => {
            const factorIcon = f.factor === 'mlp' ? '🤖' : (f.factor === 'transformer' ? '💡' : (f.factor === 'justificacion' ? '📝' : '⚠️'));
            const factorLabel = f.factor === 'mlp' ? 'Modelo MLP detecto problema' : (f.factor === 'transformer' ? 'Modelo Transformer detecto problema' : (f.factor === 'justificacion' ? 'Baja tasa de justificación' : f.factor));
            html += `<div style="padding: 12px; background: #fef2f2; border-left: 4px solid #dc2626; border-radius: 8px; margin-bottom: 8px;">
                <div style="font-weight: bold;">${factorIcon} ${factorLabel}</div>
                ${f.descripcion ? `<div style="color: #666; margin-top: 5px; font-size: 0.85rem;">${f.descripcion}</div>` : ''}
            </div>`;
        });
        html += `</div>`;
    }
    
    // ACCIONES RECOMENDADAS
    if (decision.decisiones?.length > 0) {
        html += `<div style="margin-bottom: 20px;">
            <h6 style="color: #059669; border-bottom: 2px solid #059669; padding-bottom: 5px; margin-bottom: 10px;">
                ✅ ¿Qué hacer?
            </h6>`;
        decision.decisiones.forEach(d => {
            const icon = d.tipo === 'monitoreo' ? '👁️' : (d.tipo === 'notificacion' ? '📢' : (d.tipo === 'intervencion' ? '干预' : '⚙️'));
            const prioridadBadge = d.prioridad === 'urgente' ? '#dc2626' : (d.prioridad === 'alta' ? '#f59e0b' : '#3b82f6');
            const accionLabel = d.tipo === 'monitoreo' ? 'Monitoreo Intensificado' : (d.tipo === 'notificacion' ? 'Notificar al empleado' : (d.tipo === 'intervencion' ? 'Intervención inmediata' : d.tipo.replace(/_/g, ' ')));
            html += `<div style="display: flex; align-items: center; padding: 12px; background: #ecfdf5; border-radius: 8px; margin-bottom: 8px; border: 1px solid #059669;">
                <span style="font-size: 1.2rem; margin-right: 10px;">${icon}</span>
                <span style="flex: 1; font-weight: 500;">${accionLabel}</span>
                <span style="background: ${prioridadBadge}; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: bold;">${d.prioridad.toUpperCase()}</span>
            </div>`;
        });
        html += `</div>`;
    }
    
    // RESUMEN FINAL - usar variable diferente para evitar redeclaración
    const resumenNumModelosAlto = analisis.redes_neuronales ? Object.values(analisis.redes_neuronales).filter(r => (r.prediccion || 0) >= 70).length : 0;
    const resumenNumModelosMedio = analisis.redes_neuronales ? Object.values(analisis.redes_neuronales).filter(r => (r.prediccion || 0) >= 50 && (r.prediccion || 0) < 70).length : 0;
    const resumenNumFactores = analisis.factores_criticos?.length || 0;
    
    let resumenColor = '#059669';
    let resumenTexto = 'Sin riesgo significativo';
    if (resumenNumModelosAlto > 0 || resumenNumFactores > 0) {
        resumenColor = '#dc2626';
        resumenTexto = `${resumenNumModelosAlto} modelo(s) alto riesgo + ${resumenNumFactores} factor(es)`;
    } else if (resumenNumModelosMedio > 0) {
        resumenColor = '#f59e0b';
        resumenTexto = `${resumenNumModelosMedio} modelo(s) con riesgo medio`;
    }
    
    html += `<div style="padding: 15px; background: ${resumenColor}; color: white; border-radius: 10px; text-align: center; font-size: 0.9rem;">
        <strong>📋 Resumen:</strong> ${resumenTexto}<br>
        <span style="opacity: 0.8; font-size: 0.8rem;">Nivel de confianza: ${analisis.confianza_global || 'media'}</span>
    </div>`;
    
    html += `</div>`;
    
    document.getElementById('detalle-ia-body').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detalleModal')).show();
}

function renderRecomendaciones(recomendaciones) {
    const container = document.getElementById('recomendaciones-container');
    if (!recomendaciones || recomendaciones.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">Sin recomendaciones</p>';
        return;
    }
    
    const prioridadColor = {
        'urgente': '#dc2626',
        'alta': '#f59e0b',
        'media': '#3b82f6',
        'baja': '#059669'
    };
    
    // Ordenar por prioridad: urgente > alta > media > baja
    const ordenPrioridad = { urgente: 0, alta: 1, media: 2, baja: 3 };
    recomendaciones.sort((a, b) => (ordenPrioridad[a.prioridad] ?? 99) - (ordenPrioridad[b.prioridad] ?? 99));
    
    let html = '';
    recomendaciones.forEach((r, idx) => {
        const color = prioridadColor[r.prioridad] || '#6b7280';
        const ids = r.empleados_ids ? r.empleados_ids.join(', ') : '';
        html += `<div class="info-card clickable-recomendacion" style="border-left: 4px solid ${color}; cursor: pointer;" onclick="mostrarEmpleadosRecomendacion(${idx})">
            <div class="d-flex justify-content-between align-items-start">
                <div class="titulo">${r.titulo}</div>
                <span class="badge" style="background: ${color}; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">${r.prioridad}</span>
            </div>
            <div class="subtitulo">${r.descripcion}</div>
            ${r.accion ? `<div style="margin-top: 8px; font-size: 0.8rem; color: var(--pantone-verde);"><i class="fas fa-arrow-right"></i> ${r.accion}</div>` : ''}
            <div class="empleados-list" id="emp-recomendacion-${idx}" style="display: none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                <strong style="font-size: 0.85rem;">Empleados:</strong>
                <div id="emp-list-${idx}"></div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function mostrarEmpleadosRecomendacion(idx) {
    const panel = document.getElementById(`emp-recomendacion-${idx}`);
    const listContainer = document.getElementById(`emp-list-${idx}`);
    const recTitulo = document.querySelectorAll('.clickable-recomendacion')[idx].querySelector('.titulo').textContent;
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        
        if (datosAnalisis && datosAnalisis.analisis_detallados) {
            const analisis = datosAnalisis.analisis_detallados;
            let empleados = [];
            
            if (recTitulo.includes('críticos')) {
                empleados = analisis.filter(e => e.analisis.indice_riesgo_ia >= 80);
            } else if (recTitulo.includes('alto')) {
                empleados = analisis.filter(e => e.analisis.indice_riesgo_ia >= 60 && e.analisis.indice_riesgo_ia < 80);
            } else if (recTitulo.includes('media') || recTitulo.includes('mejora')) {
                empleados = analisis.filter(e => e.analisis.indice_riesgo_ia >= 40 && e.analisis.indice_riesgo_ia < 60);
            } else if (recTitulo.includes('reconocer') || recTitulo.includes('desempeño')) {
                empleados = analisis.filter(e => e.analisis.indice_riesgo_ia < 40);
            } else if (recTitulo.includes('puntualidad')) {
                empleados = analisis.filter(e => (e.metricas?.retardos || 0) > 10);
            } else if (recTitulo.includes('faltas')) {
                empleados = analisis.filter(e => (e.metricas?.faltas || 0) > 3);
            }
            
            if (empleados.length > 0) {
                // Buscar el índice del primer empleado que coincida
                const primerEmpIdx = datosAnalisis.analisis_detallados.findIndex(e => 
                    e.empleado_id === empleados[0].empleado_id
                );
                
                listContainer.innerHTML = empleados.map((e, i) => {
                    const nombre = e.nombre || 'Sin nombre';
                    const apellido = e.apellido || '';
                    const area = e.area || 'Sin área';
                    const riesgo = e.analisis?.indice_riesgo_ia ?? 0;
                    const colorRiesgo = riesgo >= 80 ? '#dc2626' : (riesgo >= 60 ? '#f59e0b' : '#059669');
                    const empIdx = datosAnalisis.analisis_detallados.findIndex(emp => emp.empleado_id === e.empleado_id);
                    return `<div class="empleado-item" style="padding: 6px 0; border-bottom: 1px solid #eee; cursor: pointer;" onclick="mostrarDetalle(${empIdx})">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <div style="font-weight: 500; color: #2563eb;">${nombre} ${apellido}</div>
                                <div style="font-size: 0.75rem; color: #666;">${area}</div>
                            </div>
                            <span style="color: ${colorRiesgo}; font-weight: bold; white-space: nowrap; margin-left: 10px;">${riesgo}%</span>
                        </div>
                    </div>`;
                }).join('');
            } else {
                listContainer.innerHTML = '<span class="text-muted">Sin empleados en esta categoría</span>';
            }
        }
    } else {
        panel.style.display = 'none';
    }
}

function getRecomendacionesConEmpleados() {
    if (!datosAnalisis || !datosAnalisis.analisis_detallados) return [];
    
    const analisis = datosAnalisis.analisis_detallados;
    const criticos = analisis.filter(e => e.analisis.indice_riesgo_ia >= 80);
    const altos = analisis.filter(e => e.analisis.indice_riesgo_ia >= 60 && e.analisis.indice_riesgo_ia < 80);
    const medios = analisis.filter(e => e.analisis.indice_riesgo_ia >= 40 && e.analisis.indice_riesgo_ia < 60);
    const bajos = analisis.filter(e => e.analisis.indice_riesgo_ia < 40);
    const retraso = analisis.filter(e => (e.metricas?.retardos || 0) > 10);
    const faltas = analisis.filter(e => (e.metricas?.faltas || 0) > 3);
    
    return [
        { prioridad: 'urgente', titulo: 'Revisión de casos críticos', empleados: criticos },
        { prioridad: 'alta', titulo: 'Monitoreo de empleados en riesgo alto', empleados: altos },
        { prioridad: 'media', titulo: 'Implementar plan de mejora', empleados: medios },
        { prioridad: 'baja', titulo: 'Reconocer buen desempeño', empleados: bajos },
        { prioridad: 'alta', titulo: 'Revisar políticas de puntualidad', empleados: retraso },
        { prioridad: 'urgente', titulo: 'Atención a empleados con faltas excesivas', empleados: faltas }
    ];
}

function renderJustificaciones(analisis) {
    const container = document.getElementById('justificaciones-body');
    if (!analisis?.tipos_justificacion?.length) {
        container.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin datos</td></tr>';
        return;
    }
    
    let html = '';
    analisis.tipos_justificacion.forEach(t => {
        const tasa = t.cantidad > 0 ? Math.round((t.aprobadas / t.cantidad) * 100) : 0;
        html += `<tr>
            <td><strong>${t.tipo}</strong></td>
            <td>${t.cantidad}</td>
            <td style="color: var(--pantone-verde);">${t.aprobadas}</td>
            <td style="color: var(--pantone-vino);">${t.rechazadas}</td>
            <td><span class="badge-riesgo ${tasa >= 70 ? 'bajo' : (tasa >= 40 ? 'medio' : 'alto')}">${tasa}%</span></td>
        </tr>`;
    });
    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', iniciarAnalisis);
</script>
