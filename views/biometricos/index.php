<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Biométrico - Sistema de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .device-card { transition: transform 0.2s; }
        .device-card:hover { transform: translateY(-2px); }
        .status-online { color: #28a745; }
        .status-offline { color: #dc3545; }
        .status-error { color: #ffc107; }
        .metric-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .log-entry { border-left: 4px solid; padding-left: 10px; margin-bottom: 10px; }
        .log-success { border-left-color: #28a745; }
        .log-error { border-left-color: #dc3545; }
        .log-warning { border-left-color: #ffc107; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/"><i class="fas fa-fingerprint"></i> Sistema Biométrico</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/empleados">Empleados</a>
                <a class="nav-link" href="<?php echo BASE_URL; ?>/asistencia">Asistencia</a>
                <a class="nav-link active" href="<?php echo BASE_URL; ?>/biometricos">Dispositivos</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-tachometer-alt"></i> Dashboard Biométrico</h1>
            </div>
        </div>

        <!-- Métricas Generales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h3><?php echo count($dispositivos); ?></h3>
                        <p class="mb-0">Dispositivos Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h3><?php echo array_sum(array_column($estadisticasBiometricas, 'total_verificaciones')); ?></h3>
                        <p class="mb-0">Verificaciones Hoy</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h3><?php echo round(array_sum(array_column($estadisticasBiometricas, 'calidad_promedio')) / count($estadisticasBiometricas), 1); ?>%</h3>
                        <p class="mb-0">Calidad Promedio</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h3><?php echo round(array_sum(array_column($estadisticasBiometricas, 'tiempo_promedio_procesamiento')) / count($estadisticasBiometricas), 2); ?>s</h3>
                        <p class="mb-0">Tiempo Promedio</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado de Dispositivos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-server"></i> Estado de Dispositivos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($dispositivos as $dispositivo): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card device-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-fingerprint"></i> Dispositivo <?php echo $dispositivo['device_id']; ?>
                                            <span class="badge bg-<?php echo $dispositivo['status'] === 'conectado' ? 'success' : 'danger'; ?> float-end">
                                                <?php echo $dispositivo['status']; ?>
                                            </span>
                                        </h6>
                                        <p class="card-text small text-muted">
                                            <?php echo $dispositivo['type']; ?> v<?php echo $dispositivo['firmware_version']; ?>
                                        </p>
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo BASE_URL; ?>/biometricos/<?php echo $dispositivo['device_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </a>
                                            <button class="btn btn-outline-secondary btn-sm" onclick="testDispositivo(<?php echo $dispositivo['device_id']; ?>)">
                                                <i class="fas fa-plug"></i> Test Conexión
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Recientes -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Actividad Reciente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logsRecientes)): ?>
                        <p class="text-muted">No hay actividad reciente</p>
                        <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($logsRecientes as $log): ?>
                            <div class="log-entry log-<?php
                                echo $log['resultado'] === 'exitoso' ? 'success' :
                                     ($log['resultado'] === 'error' ? 'error' : 'warning');
                            ?>">
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($log['timestamp'])); ?></small>
                                <div class="fw-bold"><?php echo htmlspecialchars($log['mensaje']); ?></div>
                                <small class="text-muted">
                                    Dispositivo <?php echo $log['dispositivo_id']; ?>
                                    <?php if ($log['empleado_id']): ?>
                                        | Empleado: <?php echo htmlspecialchars($log['nombre'] . ' ' . $log['apellido']); ?>
                                    <?php endif; ?>
                                    <?php if ($log['tipo_biometria']): ?>
                                        | Tipo: <?php echo $log['tipo_biometria']; ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Resumen por Tipo</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="biometricChart" width="100%" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Gráfico de resumen biométrico
        const ctx = document.getElementById('biometricChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Huella', 'Cara'],
                datasets: [{
                    data: [
                        <?php echo array_sum(array_column($estadisticasBiometricas, 'huellas_verificadas')); ?>,
                        <?php echo array_sum(array_column($estadisticasBiometricas, 'caras_verificadas')); ?>
                    ],
                    backgroundColor: ['#007bff', '#28a745']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Función para test de dispositivo
        function testDispositivo(deviceId) {
            fetch(`<?php echo BASE_URL; ?>/biometricos/test/${deviceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Test exitoso para dispositivo ${deviceId}`);
                        location.reload();
                    } else {
                        alert(`Error en dispositivo ${deviceId}: ${data.error}`);
                    }
                })
                .catch(error => {
                    alert('Error al realizar el test: ' + error.message);
                });
        }

        // Auto-refresh cada 30 segundos
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
