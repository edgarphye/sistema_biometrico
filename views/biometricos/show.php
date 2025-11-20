<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispositivo <?php echo $dispositivoId; ?> - Sistema Biométrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .employee-row:hover { background-color: #f8f9fa; }
        .biometric-badge { font-size: 0.8em; }
        .log-timeline { position: relative; padding-left: 30px; }
        .log-timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
        .log-item { margin-bottom: 20px; position: relative; }
        .log-item::before { content: ''; position: absolute; left: -22px; top: 8px; width: 10px; height: 10px; border-radius: 50%; background: #007bff; }
        .log-success::before { background: #28a745; }
        .log-error::before { background: #dc3545; }
        .log-warning::before { background: #ffc107; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/biometricos"><i class="fas fa-arrow-left"></i> Dashboard Biométrico</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="fas fa-server"></i> Dispositivo <?php echo $dispositivoId; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Información del Dispositivo -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle"></i> Información del Dispositivo</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Estado Actual</h5>
                                <p><strong>ID:</strong> <?php echo $dispositivo['dispositivo_id']; ?></p>
                                <p><strong>Tipo:</strong> <?php echo $dispositivo['type']; ?></p>
                                <p><strong>Firmware:</strong> <?php echo $dispositivo['firmware_version']; ?></p>
                                <p><strong>Estado:</strong>
                                    <span class="badge bg-<?php echo $dispositivo['status'] === 'conectado' ? 'success' : 'danger'; ?>">
                                        <?php echo $dispositivo['status']; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Estadísticas del Día</h5>
                                <?php if ($estadisticasDispositivo): ?>
                                <p><strong>Entradas:</strong> <?php echo $estadisticasDispositivo['entradas']; ?></p>
                                <p><strong>Salidas:</strong> <?php echo $estadisticasDispositivo['salidas']; ?></p>
                                <p><strong>Huellas:</strong> <?php echo $estadisticasDispositivo['verificaciones_huella']; ?></p>
                                <p><strong>Caras:</strong> <?php echo $estadisticasDispositivo['verificaciones_cara']; ?></p>
                                <p><strong>Calidad Promedio:</strong> <?php echo $estadisticasDispositivo['calidad_promedio']; ?>%</p>
                                <?php else: ?>
                                <p class="text-muted">No hay estadísticas disponibles</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empleados que han usado este dispositivo -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Empleados Recientes (Últimas 24 horas)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($asistencia)): ?>
                        <p class="text-muted">No hay registros de asistencia recientes</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>RFC</th>
                                        <th>Tipo</th>
                                        <th>Biometría</th>
                                        <th>Calidad</th>
                                        <th>Hora</th>
                                        <th>Procesamiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($asistencia as $registro): ?>
                                    <tr class="employee-row">
                                        <td><?php echo htmlspecialchars($registro['nombre'] . ' ' . $registro['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($registro['rfc']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $registro['tipo'] === 'entrada' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($registro['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge biometric-badge bg-info">
                                                <i class="fas fa-<?php echo $registro['tipo_biometria'] === 'huella' ? 'fingerprint' : 'camera'; ?>"></i>
                                                <?php echo ucfirst($registro['tipo_biometria'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $registro['calidad_verificacion'] ? $registro['calidad_verificacion'] . '%' : 'N/A'; ?></td>
                                        <td><?php echo date('H:i:s', strtotime($registro['timestamp'])); ?></td>
                                        <td><?php echo $registro['tiempo_procesamiento'] ? $registro['tiempo_procesamiento'] . 's' : 'N/A'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs del Dispositivo -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Historial de Eventos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                        <p class="text-muted">No hay logs disponibles</p>
                        <?php else: ?>
                        <div class="log-timeline">
                            <?php foreach ($logs as $log): ?>
                            <div class="log-item log-<?php
                                echo $log['resultado'] === 'exitoso' ? 'success' :
                                     ($log['resultado'] === 'error' ? 'error' : 'warning');
                            ?>">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($log['mensaje']); ?></strong>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></small>
                                </div>
                                <div class="text-muted small">
                                    <span class="badge bg-secondary"><?php echo ucfirst($log['tipo_evento']); ?></span>
                                    <span class="badge bg-<?php
                                        echo $log['resultado'] === 'exitoso' ? 'success' :
                                             ($log['resultado'] === 'error' ? 'danger' : 'warning');
                                    ?>"><?php echo ucfirst($log['resultado']); ?></span>
                                    <?php if ($log['tipo_biometria']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-<?php echo $log['tipo_biometria'] === 'huella' ? 'fingerprint' : 'camera'; ?>"></i>
                                        <?php echo ucfirst($log['tipo_biometria']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($log['empleado_id']): ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-user"></i> ID: <?php echo $log['empleado_id']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $metadata = json_decode($log['metadata'], true);
                                if ($metadata && isset($metadata['error_message'])): ?>
                                <div class="text-danger small mt-1">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($metadata['error_message']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh cada 60 segundos
        setInterval(() => {
            location.reload();
        }, 60000);

        // Función para filtrar logs (puede expandirse)
        function filtrarLogs(tipo) {
            const logs = document.querySelectorAll('.log-item');
            logs.forEach(log => {
                if (tipo === 'todos' || log.classList.contains('log-' + tipo)) {
                    log.style.display = 'block';
                } else {
                    log.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
