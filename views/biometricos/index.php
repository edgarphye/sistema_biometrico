<?php
$pageTitle = 'Dashboard Biométrico';
$content = '
<style>
    :root {
        --pantone-primary: #9F2241;
        --pantone-primary-dark: #691C32;
        --pantone-secondary: #235B4E;
        --pantone-secondary-dark: #10312B;
        --pantone-accent: #DDC9A3;
        --pantone-accent-dark: #BC955C;
        --pantone-danger: #691C32;
        --pantone-gray: #98989A;
        --pantone-gray-dark: #6F7271;
    }
    
    .dashboard-biometrico {
        background-color: #f8f7f5;
        min-height: 100vh;
        padding: 2rem;
    }
    
    .dashboard-biometrico .dashboard-title {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 2rem;
        display: block;
    }
    
    .dashboard-biometrico .metric-card { 
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%); 
        color: white;
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        min-height: 140px;
    }
    .dashboard-biometrico .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(151, 34, 65, 0.25);
    }
    .dashboard-biometrico .metric-card.metric-1 { background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%); }
    .dashboard-biometrico .metric-card.metric-2 { background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%); }
    .dashboard-biometrico .metric-card.metric-3 { background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #a67c4a 100%); }
    .dashboard-biometrico .metric-card.metric-4 { background: linear-gradient(135deg, #555555 0%, #333333 100%); }
    
    .dashboard-biometrico .metric-card .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    
    .dashboard-biometrico .metric-card h3 {
        color: #ffffff !important;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        font-size: 2.8rem;
        margin-bottom: 8px;
        line-height: 1;
    }
    .dashboard-biometrico .metric-card p {
        color: rgba(255,255,255,0.95) !important;
        font-size: 0.95rem;
        font-weight: 500;
        margin: 0;
        text-align: center;
    }
    .dashboard-biometrico .metric-card i {
        color: rgba(255,255,255,0.95) !important;
        font-size: 1.1rem;
        margin-bottom: 8px;
    }
    
    .dashboard-biometrico .device-card { 
        transition: all 0.3s ease; 
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e0e0e0 !important;
    }
    .dashboard-biometrico .device-card:hover { 
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(35, 91, 78, 0.15);
    }
    .dashboard-biometrico .device-card .card-title {
        color: #1a1a1a !important;
        font-weight: 600;
    }
    .dashboard-biometrico .device-card .card-text {
        color: #555555 !important;
    }
    
    .dashboard-biometrico .log-entry { 
        border-left: 4px solid; 
        padding: 15px; 
        margin-bottom: 12px; 
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid #e8e8e8 !important;
        transition: all 0.2s ease;
    }
    .dashboard-biometrico .log-entry:hover {
        background: #f8f7f5;
        transform: translateX(3px);
    }
    .dashboard-biometrico .log-entry .log-time {
        color: #888888 !important;
    }
    .dashboard-biometrico .log-entry .log-message {
        color: #1a1a1a !important;
    }
    .dashboard-biometrico .log-entry .log-details {
        color: #666666 !important;
    }
    .dashboard-biometrico .log-success { border-left-color: var(--pantone-secondary); }
    .dashboard-biometrico .log-error { border-left-color: var(--pantone-danger); }
    .dashboard-biometrico .log-warning { border-left-color: var(--pantone-accent-dark); }
    
    .dashboard-biometrico .card {
        border-radius: 16px !important;
        overflow: hidden;
        border: none !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        background: #ffffff;
    }
    
    .dashboard-biometrico .card-header {
        border-radius: 16px 16px 0 0 !important;
        padding: 15px 20px;
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%) !important;
        color: #ffffff;
    }
    .dashboard-biometrico .card-header h5 {
        color: #ffffff !important;
    }
    .dashboard-biometrico .card-header i {
        color: #ffffff !important;
    }
    
    .dashboard-biometrico .card-body {
        background: #ffffff;
    }
    
    .dashboard-biometrico .text-muted {
        color: #888888 !important;
    }
    
    .dashboard-biometrico .btn-pantone-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .dashboard-biometrico .btn-pantone-primary:hover {
        background: linear-gradient(135deg, var(--pantone-primary-dark) 0%, #501526 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(151, 34, 65, 0.3);
    }
    
    .dashboard-biometrico .btn-pantone-secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .dashboard-biometrico .btn-pantone-secondary:hover {
        background: linear-gradient(135deg, var(--pantone-secondary-dark) 0%, #0a211d 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(35, 91, 78, 0.3);
    }
    
    .dashboard-biometrico .badge-pantone-success {
        background-color: var(--pantone-secondary);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
    }
    .dashboard-biometrico .badge-pantone-danger {
        background-color: var(--pantone-danger);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
    }
    
    .dashboard-biometrico .empty-state {
        color: #888888;
    }
    
    .dashboard-biometrico .empty-state i {
        color: #cccccc !important;
    }
    
    /* Tema Oscuro */
    body.dark-theme .dashboard-biometrico {
        background-color: #1a1a2e !important;
    }
    
    body.dark-theme .dashboard-biometrico .dashboard-title {
        background: linear-gradient(135deg, var(--pantone-accent) 0%, var(--pantone-accent-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    body.dark-theme .dashboard-biometrico .metric-card {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    }
    
    body.dark-theme .dashboard-biometrico .metric-card h3 {
        color: #ffffff !important;
    }
    
    body.dark-theme .dashboard-biometrico .metric-card p {
        color: rgba(255,255,255,0.95) !important;
    }
    
    body.dark-theme .dashboard-biometrico .metric-card i {
        color: rgba(255,255,255,0.95) !important;
    }
    
    body.dark-theme .dashboard-biometrico .device-card {
        background-color: #252538;
        border-color: #3a3a5c !important;
    }
    
    body.dark-theme .dashboard-biometrico .device-card:hover {
        background-color: #2d2d48;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }
    
    body.dark-theme .dashboard-biometrico .device-card .card-title {
        color: #e0e0e0 !important;
    }
    
    body.dark-theme .dashboard-biometrico .device-card .card-text {
        color: #8a8aaa !important;
    }
    
    body.dark-theme .dashboard-biometrico .log-entry {
        background-color: #252538;
        border-color: #3a3a5c !important;
    }
    
    body.dark-theme .dashboard-biometrico .log-entry:hover {
        background-color: #2d2d48;
    }
    
    body.dark-theme .dashboard-biometrico .log-entry .log-message {
        color: #DDC9A3 !important;
    }
    
    body.dark-theme .dashboard-biometrico .log-entry .log-details {
        color: #8a8aaa !important;
    }
    
    body.dark-theme .dashboard-biometrico .text-muted {
        color: #8a8aaa !important;
    }
    
    body.dark-theme .dashboard-biometrico .card {
        background-color: #252538;
        border-color: #3a3a5c !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    body.dark-theme .dashboard-biometrico .card-header {
        background: linear-gradient(135deg, #3a3a5c 0%, #2a2a40 100%) !important;
    }
    
    body.dark-theme .dashboard-biometrico .card-body {
        background: #252538;
    }
    
    body.dark-theme .dashboard-biometrico .empty-state i {
        color: #555555 !important;
    }
</style>

<div class="dashboard-biometrico">
    <div class="row mb-4">
        <div class="col-12">
            <span class="dashboard-title"><i class="fas fa-fingerprint me-3" style="color: #9F2241; -webkit-text-fill-color: initial;"></i>Dashboard Biométrico</span>
        </div>
    </div>

    <div class="row mb-4 g-4">
        <div class="col-md-3">
            <div class="card metric-card metric-1">
                <div class="card-body text-center py-4">
                    <i class="fas fa-server fa-2x mb-3"></i>
                    <h3>' . count($dispositivos) . '</h3>
                    <p><i class="fas fa-server me-2"></i>Dispositivos Activos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card metric-2">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3>' . array_sum(array_column($estadisticasBiometricas, 'total_verificaciones')) . '</h3>
                    <p><i class="fas fa-check-circle me-2"></i>Verificaciones Hoy</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card metric-3">
                <div class="card-body text-center py-4">
                    <i class="fas fa-chart-line fa-2x mb-3"></i>
                    <h3>' . (count($estadisticasBiometricas) > 0 ? round(array_sum(array_column($estadisticasBiometricas, 'calidad_promedio')) / count($estadisticasBiometricas), 1) : 0) . '%</h3>
                    <p><i class="fas fa-chart-line me-2"></i>Calidad Promedio</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card metric-4">
                <div class="card-body text-center py-4">
                    <i class="fas fa-clock fa-2x mb-3"></i>
                    <h3>' . (count($estadisticasBiometricas) > 0 ? round(array_sum(array_column($estadisticasBiometricas, 'tiempo_promedio_procesamiento')) / count($estadisticasBiometricas), 2) : 0) . 's</h3>
                    <p><i class="fas fa-clock me-2"></i>Tiempo Promedio</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%);">
                    <h5 class="mb-0"><i class="fas fa-server me-2"></i>Estado de Dispositivos</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        ' . (empty($dispositivos) ? '<div class="col-12 text-center py-5 empty-state"><i class="fas fa-server fa-3x mb-3"></i><p class="text-muted">No hay dispositivos registrados</p></div>' : '') . '
                        ';

foreach ($dispositivos as $dispositivo) {
    $statusClass = $dispositivo['status'] === 'conectado' ? 'badge-pantone-success' : 'badge-pantone-danger';
    $deviceId = $dispositivo['device_id'];
    $content .= '
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card device-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="fas fa-fingerprint me-2" style="color: #235B4E;"></i>Dispositivo ' . $deviceId . '
                                        <span class="badge float-end ' . $statusClass . '">
                                            ' . ucfirst($dispositivo['status']) . '
                                        </span>
                                    </h6>
                                    <p class="card-text small mb-3">
                                        <i class="fas fa-microchip me-2"></i>' . $dispositivo['type'] . ' v' . $dispositivo['firmware_version'] . '
                                    </p>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-pantone-primary btn-sm" onclick="mostrarDetalles(' . $deviceId . ', \'' . htmlspecialchars($dispositivo['type']) . '\', \'' . $dispositivo['firmware_version'] . '\', \'' . $dispositivo['status'] . '\')">
                                            <i class="fas fa-eye me-2"></i>Ver Detalles
                                        </button>
                                        <button class="btn btn-pantone-secondary btn-sm" onclick="abrirModalTest(' . $deviceId . ')">
                                            <i class="fas fa-plug me-2"></i>Test Conexión
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>';
}

$content .= '
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Actividad Reciente</h5>
                </div>
                <div class="card-body">
                    ' . (empty($logsRecientes) ? '<div class="text-center py-5 empty-state"><i class="fas fa-check-circle fa-3x mb-3" style="color: #235B4E;"></i><p class="text-muted">No hay actividad reciente</p></div>' : '
                    <div style="max-height: 400px; overflow-y: auto;">');

if (!empty($logsRecientes)) {
    foreach ($logsRecientes as $log) {
        $logClass = $log['resultado'] === 'exitoso' ? 'log-success' : ($log['resultado'] === 'error' ? 'log-error' : 'log-warning');
        $icon = $log['resultado'] === 'exitoso' ? 'fa-check-circle' : ($log['resultado'] === 'error' ? 'fa-times-circle' : 'fa-exclamation-circle');
        $iconColor = $log['resultado'] === 'exitoso' ? '#235B4E' : ($log['resultado'] === 'error' ? '#691C32' : '#BC955C');
        $content .= '
                        <div class="log-entry ' . $logClass . '">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="log-time">' . date('d/m/Y H:i', strtotime($log['timestamp'])) . '</small>
                                    <div class="fw-bold mt-1 log-message">' . htmlspecialchars($log['mensaje']) . '</div>
                                    <small class="log-details">
                                        <i class="fas fa-fingerprint me-1"></i>Dispositivo ' . $log['dispositivo_id'];
        $content .= $log['empleado_id'] ? ' | <i class="fas fa-user me-1"></i>' . htmlspecialchars($log['nombre'] . ' ' . $log['apellido']) : '';
        $content .= $log['tipo_biometria'] ? ' | <i class="fas fa-fingerprint me-1"></i>' . $log['tipo_biometria'] : '';
        $content .= '
                                    </small>
                                </div>
                                <i class="fas ' . $icon . '" style="color: ' . $iconColor . ';"></i>
                            </div>
                        </div>';
    }
}

$content .= '
                    </div>
                    ' . '
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #BC955C 0%, #a67c4a 100%);">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Resumen por Tipo</h5>
                </div>
                <div class="card-body">
                    <canvas id="biometricChart" width="100%" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #235B4E 0%, #10312B 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalles del Dispositivo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoDetalles">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status" style="color: #235B4E;"></div>
                        <p class="mt-3 text-muted">Cargando detalles...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="border-radius: 25px;" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTest" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #BC955C 0%, #a67c4a 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-plug me-2"></i>Test de Conexión</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoTest">
                    <p class="text-dark">Dispositivo: <strong id="testDeviceId" class="text-dark"></strong></p>
                    <div id="testResultado" class="mt-3"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="border-radius: 25px;" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-pantone-primary" id="btnEjecutarTest" onclick="ejecutarTest()">
                    <i class="fas fa-play me-1"></i> Ejecutar Test
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById("biometricChart").getContext("2d");
    const chart = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Huella", "Cara"],
            datasets: [{
                data: [
                    ' . (count($estadisticasBiometricas) > 0 ? array_sum(array_column($estadisticasBiometricas, 'huellas_verificadas')) : 0) . ',
                    ' . (count($estadisticasBiometricas) > 0 ? array_sum(array_column($estadisticasBiometricas, 'caras_verificadas')) : 0) . '
                ],
                backgroundColor: ["#9F2241", "#235B4E"]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom"
                }
            }
        }
    });

    let dispositivoTestActual = null;
    
    function abrirModalTest(deviceId) {
        dispositivoTestActual = deviceId;
        document.getElementById("testDeviceId").textContent = "Dispositivo " + deviceId;
        document.getElementById("testResultado").innerHTML = "<p class=\"text-muted\">Haga clic en \"Ejecutar Test\" para probar la conexión.</p>";
        document.getElementById("btnEjecutarTest").disabled = false;
        document.getElementById("btnEjecutarTest").innerHTML = "<i class=\"fas fa-play me-1\"></i> Ejecutar Test";
        var modal = new bootstrap.Modal(document.getElementById("modalTest"));
        modal.show();
    }
    
    function ejecutarTest() {
        if (!dispositivoTestActual) return;
        
        var btn = document.getElementById("btnEjecutarTest");
        btn.disabled = true;
        btn.innerHTML = "<span class=\"spinner-border spinner-border-sm me-1\"></span> Probando...";
        
        document.getElementById("testResultado").innerHTML = "<div class=\"text-center py-3\"><div class=\"spinner-border text-primary\" role=\"status\"></div></div>";
        
        fetch(BASE_URL + "/biometricos/test-dispositivo/" + dispositivoTestActual, {
            method: "POST"
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("testResultado").innerHTML = 
                    "<div class=\"alert alert-success\">" +
                    "<i class=\"fas fa-check-circle me-2\"></i><strong>¡Éxito!</strong><br>" +
                    "La conexión con el dispositivo " + dispositivoTestActual + " es exitosa." +
                    "</div>";
            } else {
                document.getElementById("testResultado").innerHTML = 
                    "<div class=\"alert alert-danger\">" +
                    "<i class=\"fas fa-times-circle me-2\"></i><strong>Error</strong><br>" +
                    "No se pudo conectar al dispositivo " + dispositivoTestActual + ": " + data.error +
                    "</div>";
            }
        })
        .catch(error => {
            document.getElementById("testResultado").innerHTML = 
                "<div class=\"alert alert-danger\">" +
                "<i class=\"fas fa-exclamation-triangle me-2\"></i><strong>Error de conexión</strong><br>" +
                "Error al intentar conectar: " + error.message +
                "</div>";
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = "<i class=\"fas fa-play me-1\"></i> Ejecutar Test";
        });
    }
    
    function mostrarDetalles(deviceId, tipo, firmware, status) {
        var statusBadge = status === "conectado" ? 
            "<span class=\"badge\" style=\"background: #235B4E; color: white;\">Conectado</span>" : 
            "<span class=\"badge\" style=\"background: #691C32; color: white;\">Desconectado</span>";
        
        var contenido = 
            "<table class=\"table table-borderless\">" +
            "<tr><td><strong class=\"text-dark\">ID del Dispositivo:</strong></td><td class=\"text-dark\">" + deviceId + "</td></tr>" +
            "<tr><td><strong class=\"text-dark\">Tipo:</strong></td><td class=\"text-dark\">" + tipo + "</td></tr>" +
            "<tr><td><strong class=\"text-dark\">Firmware:</strong></td><td class=\"text-dark\">v" + firmware + "</td></tr>" +
            "<tr><td><strong class=\"text-dark\">Estado:</strong></td><td>" + statusBadge + "</td></tr>" +
            "<tr><td><strong class=\"text-dark\">IP:</strong></td><td class=\"text-dark\">192.168.1." + deviceId + "</td></tr>" +
            "<tr><td><strong class=\"text-dark\">Puerto:</strong></td><td class=\"text-dark\">4370</td></tr>" +
            "</table>" +
            "<hr>" +
            "<div class=\"d-grid gap-2\">" +
            "<a href=\"" + BASE_URL + "/biometricos/" + deviceId + "\" class=\"btn btn-pantone-primary\">" +
            "<i class=\"fas fa-external-link-alt me-2\"></i>Ver página completa" +
            "</a>" +
            "</div>";
        
        document.getElementById("contenidoDetalles").innerHTML = contenido;
        var modal = new bootstrap.Modal(document.getElementById("modalDetalles"));
        modal.show();
    }

    setInterval(() => {
        location.reload();
    }, 30000);
</script>
';

include __DIR__ . '/../layout.php';
