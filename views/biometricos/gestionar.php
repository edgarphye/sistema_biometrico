<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Dispositivo <?php echo $dispositivoId; ?> - Sistema Biométrico</title>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/colores-pantone.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/modals.css" rel="stylesheet">
    <style>
        :root {
            --pantone-primary: #9F2241;
            --pantone-primary-dark: #691C32;
            --pantone-secondary: #235B4E;
            --pantone-secondary-dark: #10312B;
            --pantone-accent: #DDC9A3;
            --pantone-accent-dark: #BC955C;
        }
        .action-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .action-card .card-icon {
            font-size: 2.5rem;
            color: var(--pantone-primary);
            opacity: 0.8;
        }
        .action-card .btn-action {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
        }
        .result-alert {
            border-radius: 12px;
            display: none;
        }
        .result-alert.show {
            display: block;
        }
        .log-timeline {
            position: relative;
            padding-left: 30px;
        }
        .log-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--pantone-secondary);
        }
        .log-item {
            margin-bottom: 15px;
            position: relative;
        }
        .log-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 8px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--pantone-secondary);
        }
        .log-success::before { background: var(--pantone-secondary); }
        .log-error::before { background: var(--pantone-primary); }
        .log-warning::before { background: var(--pantone-accent-dark); }
        .badge-pantone-primary { background-color: var(--pantone-primary); color: white; }
        .badge-pantone-secondary { background-color: var(--pantone-secondary); color: white; }
        .badge-pantone-accent { background-color: var(--pantone-accent-dark); color: white; }
        .btn-pantone-primary {
            background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
        }
        .btn-pantone-primary:hover {
            opacity: 0.9;
            color: white;
        }
        .btn-pantone-secondary {
            background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
            color: white;
            border: none;
            border-radius: 10px;
        }
        .btn-pantone-secondary:hover {
            opacity: 0.9;
            color: white;
        }
        .btn-pantone-outline {
            border: 2px solid var(--pantone-primary);
            color: var(--pantone-primary);
            border-radius: 10px;
            background: transparent;
        }
        .btn-pantone-outline:hover {
            background: var(--pantone-primary);
            color: white;
        }
        #empleadosTable th {
            background: var(--pantone-primary);
            color: white;
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        #empleadosTable td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        #empleadosTable .empleado-row:hover {
            background: #f8f7f5;
        }
        #empleadosTable code {
            color: var(--pantone-primary-dark);
            font-size: 0.85rem;
        }
        .progress-result {
            height: 6px;
            border-radius: 3px;
        }
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        .spinner-overlay.show {
            display: flex;
        }
        .modal-pantone .modal-header {
            background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }
        .modal-pantone .modal-content {
            border-radius: 16px;
            border: none;
        }
        .device-badge {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="spinner-overlay" id="spinnerOverlay">
        <div class="text-center">
            <div class="spinner-border text-pantone-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Procesando...</span>
            </div>
            <p class="mt-2 fw-bold text-pantone-primary" id="spinnerMessage">Procesando...</p>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-pantone-primary" style="background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/biometricos">
                <i class="fas fa-arrow-left me-2"></i> Dashboard Biométrico
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white">
                    <i class="fas fa-server me-1"></i> Gestionar Dispositivo #<?php echo $dispositivoId; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <!-- Resultado de operaciones -->
        <div id="resultContainer">
            <div id="resultAlert" class="alert result-alert d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle me-2" id="resultIcon"></i>
                <div id="resultMessage"></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>

        <!-- Info del dispositivo -->
        <div class="card action-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">
                            <i class="fas fa-server me-2" style="color: var(--pantone-primary);"></i>
                            Dispositivo #<?php echo $dispositivoId; ?>
                        </h5>
                        <p class="text-muted mb-0">
                            <span class="badge device-badge <?php echo ($dispositivo['status'] ?? 'desconocido') === 'conectado' ? 'badge-pantone-secondary' : 'bg-secondary'; ?> me-2">
                                <i class="fas fa-<?php echo ($dispositivo['status'] ?? 'desconocido') === 'conectado' ? 'plug' : 'exclamation-triangle'; ?> me-1"></i>
                                <?php echo ucfirst($dispositivo['status'] ?? 'Desconocido'); ?>
                            </span>
                            <span class="text-muted small">
                                <i class="fas fa-microchip me-1"></i> <?php echo $dispositivo['type'] ?? 'N/A'; ?>
                                <?php if (!empty($dispositivo['firmware_version'])): ?>
                                &middot; FW: <?php echo $dispositivo['firmware_version']; ?>
                                <?php endif; ?>
                            </span>
                        </p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/biometricos/<?php echo $dispositivoId; ?>" class="btn btn-pantone-outline btn-sm">
                        <i class="fas fa-chart-bar me-1"></i> Estadísticas
                    </a>
                </div>
            </div>
        </div>

        <!-- Estadísticas del dispositivo -->
        <?php if ($estadisticasDispositivo): ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card action-card text-center py-3">
                    <div class="card-body">
                        <i class="fas fa-clipboard-list fa-2x mb-2" style="color: var(--pantone-primary); opacity: 0.7;"></i>
                        <h4 class="fw-bold mb-1" style="color: var(--pantone-primary-dark);"><?php echo $estadisticasDispositivo['total_registros'] ?? 0; ?></h4>
                        <p class="text-muted small mb-0">Total Registros</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card text-center py-3">
                    <div class="card-body">
                        <i class="fas fa-sign-in-alt fa-2x mb-2" style="color: var(--pantone-secondary); opacity: 0.7;"></i>
                        <h4 class="fw-bold mb-1" style="color: var(--pantone-secondary-dark);"><?php echo $estadisticasDispositivo['solo_entrada'] ?? 0; ?></h4>
                        <p class="text-muted small mb-0">Solo Entrada</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card text-center py-3">
                    <div class="card-body">
                        <i class="fas fa-fingerprint fa-2x mb-2" style="color: var(--pantone-accent-dark); opacity: 0.7;"></i>
                        <h4 class="fw-bold mb-1" style="color: var(--pantone-accent-dark);"><?php echo $estadisticasDispositivo['verificaciones_huella'] ?? 0; ?></h4>
                        <p class="text-muted small mb-0">Huellas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card text-center py-3">
                    <div class="card-body">
                        <i class="fas fa-camera fa-2x mb-2" style="color: #555; opacity: 0.7;"></i>
                        <h4 class="fw-bold mb-1" style="color: #555;"><?php echo $estadisticasDispositivo['verificaciones_cara'] ?? 0; ?></h4>
                        <p class="text-muted small mb-0">Rostro</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="row g-4 mb-4">
            <!-- Actualizar Empleado -->
            <div class="col-md-4">
                <div class="card action-card">
                    <div class="card-body text-center py-4">
                        <div class="card-icon mb-3">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <h5 class="card-title">Actualizar Empleado</h5>
                        <p class="card-text text-muted small">Actualiza nombre, privilegio y contraseña de un empleado en el dispositivo sin re-enrolar su huella.</p>
                        <button class="btn btn-pantone-primary btn-action" data-bs-toggle="modal" data-bs-target="#modalActualizarEmpleado"
                            <?php echo ($dispositivo['status'] ?? '') !== 'conectado' ? 'disabled' : ''; ?>>
                            <i class="fas fa-sync-alt me-1"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Descargar Asistencias -->
            <div class="col-md-4">
                <div class="card action-card">
                    <div class="card-body text-center py-4">
                        <div class="card-icon mb-3">
                            <i class="fas fa-download"></i>
                        </div>
                        <h5 class="card-title">Descargar Asistencias</h5>
                        <p class="card-text text-muted small">Descarga todos los registros de asistencia del dispositivo e impórtalos a la base de datos.</p>
                        <button class="btn btn-pantone-primary btn-action" onclick="confirmarDescarga()"
                            <?php echo ($dispositivo['status'] ?? '') !== 'conectado' ? 'disabled' : ''; ?>>
                            <i class="fas fa-file-download me-1"></i> Descargar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Actualización Masiva -->
            <div class="col-md-4">
                <div class="card action-card">
                    <div class="card-body text-center py-4">
                        <div class="card-icon mb-3">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h5 class="card-title">Actualización Masiva</h5>
                        <p class="card-text text-muted small">Actualiza todos los empleados con huella registrada en el dispositivo de una sola vez.</p>
                        <button class="btn btn-pantone-primary btn-action" onclick="confirmarActualizacionMasiva()"
                            <?php echo ($dispositivo['status'] ?? '') !== 'conectado' ? 'disabled' : ''; ?>>
                            <i class="fas fa-users me-1"></i> Actualizar Todos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catálogo de empleados -->
        <div class="card action-card mb-4">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2" style="color: var(--pantone-primary);"></i> Catálogo de Empleados</h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" id="searchEmpleado" placeholder="Buscar empleado..." style="width: 250px; border-radius: 25px;">
                        <span class="badge bg-secondary d-flex align-items-center" id="empleadosCount"><?php echo !empty($empleados) ? count($empleados) : 0; ?> empleados</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($empleados)): ?>
                <p class="text-muted p-4 mb-0"><i class="fas fa-inbox me-1"></i> No hay empleados registrados</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="empleadosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>RFC</th>
                                <th>ZKTeco ID</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $emp): ?>
                            <tr class="empleado-row" data-id="<?php echo $emp['id']; ?>">
                                <td class="fw-bold"><?php echo $emp['id']; ?></td>
                                <td><?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($emp['rfc'] ?? '—'); ?></td>
                                <td><code><?php echo htmlspecialchars($emp['zkteo_id'] ?? '—'); ?></code></td>
                                <td class="text-center">
                                    <button class="btn btn-pantone-primary btn-sm btn-actualizar-emp"
                                        data-id="<?php echo $emp['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?>"
                                        <?php echo ($dispositivo['status'] ?? '') !== 'conectado' ? 'disabled' : ''; ?>>
                                        <i class="fas fa-sync-alt me-1"></i> Actualizar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Logs recientes -->
        <div class="card action-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-history me-2" style="color: var(--pantone-primary);"></i> Eventos Recientes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                <p class="text-muted mb-0"><i class="fas fa-inbox me-1"></i> No hay eventos registrados</p>
                <?php else: ?>
                <div class="log-timeline">
                    <?php foreach ($logs as $log): ?>
                    <div class="log-item log-<?php echo $log['resultado'] === 'exitoso' ? 'success' : ($log['resultado'] === 'error' ? 'error' : 'warning'); ?>">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($log['mensaje']); ?></strong>
                            <small class="text-muted"><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></small>
                        </div>
                        <div class="text-muted small">
                            <span class="badge bg-secondary"><?php echo ucfirst($log['tipo_evento']); ?></span>
                            <span class="badge <?php echo $log['resultado'] === 'exitoso' ? 'badge-pantone-secondary' : ($log['resultado'] === 'error' ? 'badge-pantone-primary' : 'badge-pantone-accent'); ?>">
                                <?php echo ucfirst($log['resultado']); ?>
                            </span>
                            <?php if (!empty($log['empleado_id'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-user"></i> ID: <?php echo $log['empleado_id']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php
                        $metadata = json_decode($log['metadata'] ?? '{}', true);
                        if (!empty($metadata['error_message'])): ?>
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

    <!-- Modal Actualizar Empleado -->
    <div class="modal fade modal-pantone" id="modalActualizarEmpleado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i> Actualizar Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formActualizarEmpleado" onsubmit="return actualizarEmpleado(event)">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="empleadoSelect" class="form-label">Seleccionar Empleado</label>
                            <select class="form-select" id="empleadoSelect" name="empleado_id" required>
                                <option value="">-- Seleccione un empleado --</option>
                                <?php if (!empty($empleados)): ?>
                                <?php foreach ($empleados as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?>
                                    <?php if (!empty($emp['rfc'])): ?>- <?php echo htmlspecialchars($emp['rfc']); ?><?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-1"></i>
                            Solo se actualizará el <strong>nombre</strong> del empleado en el dispositivo.
                            Huella, contraseña y privilegio existentes <strong>no se modifican</strong>.
                        </div>
                        <div id="resultadoActualizar" class="result-alert mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-pantone-primary" id="btnActualizar">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Descarga -->
    <div class="modal fade modal-pantone" id="modalConfirmarDescarga" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-download me-2"></i> Descargar Asistencias</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><i class="fas fa-info-circle me-1" style="color: var(--pantone-primary);"></i>
                        Se descargarán todos los registros de asistencia del dispositivo y se importarán a la base de datos.
                        Los registros duplicados serán omitidos automáticamente.</p>
                    <div id="resultadoDescarga" class="result-alert mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-pantone-primary" id="btnDescargar" onclick="ejecutarDescarga()">
                        <i class="fas fa-file-download me-1"></i> Iniciar Descarga
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Actualización Masiva -->
    <div class="modal fade modal-pantone" id="modalConfirmarMasiva" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users-cog me-2"></i> Actualización Masiva</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><i class="fas fa-info-circle me-1" style="color: var(--pantone-primary);"></i>
                        Se actualizarán todos los empleados con huella registrada en el dispositivo.
                        Procesando <?php echo !empty($empleados) ? count($empleados) : 0; ?> empleados...</p>
                    <div class="progress mb-3" id="progressBarContainer" style="display:none;">
                        <div class="progress-bar progress-result" id="progressBar" role="progressbar" style="width: 0%; background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-secondary) 100%);"></div>
                    </div>
                    <div id="resultadoMasiva" class="result-alert mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-pantone-primary" id="btnMasiva" onclick="ejecutarActualizacionMasiva()">
                        <i class="fas fa-play me-1"></i> Iniciar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const dispositivoId = <?php echo $dispositivoId; ?>;

        function mostrarResultado(tipo, mensaje, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.className = 'alert alert-' + tipo + ' show mt-2';
            container.innerHTML = mensaje;
        }

        function mostrarAlertaGlobal(tipo, mensaje) {
            const alert = document.getElementById('resultAlert');
            const icon = document.getElementById('resultIcon');
            const msg = document.getElementById('resultMessage');
            alert.className = 'alert alert-' + tipo + ' result-alert show d-flex align-items-center';
            icon.className = 'fas fa-' + (tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-circle' : 'info-circle') + ' me-2';
            msg.innerHTML = mensaje;
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => alert.classList.remove('show'), 8000);
        }

        function mostrarSpinner(mensaje) {
            document.getElementById('spinnerMessage').textContent = mensaje || 'Procesando...';
            document.getElementById('spinnerOverlay').classList.add('show');
        }

        function ocultarSpinner() {
            document.getElementById('spinnerOverlay').classList.remove('show');
        }

        async function actualizarEmpleado(event) {
            event.preventDefault();
            const empleadoId = document.getElementById('empleadoSelect').value;
            if (!empleadoId) {
                mostrarResultado('warning', '<i class="fas fa-exclamation-triangle me-1"></i> Seleccione un empleado', 'resultadoActualizar');
                return false;
            }

            const btn = document.getElementById('btnActualizar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';

            const formData = new URLSearchParams();
            formData.append('empleado_id', empleadoId);

            try {
                const res = await fetch(BASE_URL + '/biometricos/actualizar-empleado/' + dispositivoId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData.toString()
                });
                const data = await res.json();

                if (data.success) {
                    mostrarResultado('success', '<i class="fas fa-check-circle me-1"></i> ' + data.message, 'resultadoActualizar');
                    mostrarAlertaGlobal('success', 'Empleado actualizado correctamente en el dispositivo');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarResultado('danger', '<i class="fas fa-times-circle me-1"></i> ' + (data.error || data.message || 'Error desconocido'), 'resultadoActualizar');
                }
            } catch (err) {
                mostrarResultado('danger', '<i class="fas fa-times-circle me-1"></i> Error de conexión: ' + err.message, 'resultadoActualizar');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Actualizar';
            }
            return false;
        }

        function confirmarDescarga() {
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarDescarga'));
            document.getElementById('resultadoDescarga').className = 'result-alert';
            document.getElementById('resultadoDescarga').innerHTML = '';
            modal.show();
        }

        async function ejecutarDescarga() {
            const btn = document.getElementById('btnDescargar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Descargando...';

            document.getElementById('resultadoDescarga').className = 'result-alert alert alert-info show mt-2';
            document.getElementById('resultadoDescarga').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Descargando registros del dispositivo...';

            try {
                const res = await fetch(BASE_URL + '/biometricos/descargar-asistencias/' + dispositivoId, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.success) {
                    const r = data.resultado;
                    let html = '<div class="d-flex align-items-center mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>Descarga completada</strong></div>';
                    html += '<table class="table table-sm table-borderless mb-0">';
                    html += '<tr><td><i class="fas fa-download text-primary me-1"></i> Total registros</td><td class="text-end fw-bold">' + (r.total || 0) + '</td></tr>';
                    html += '<tr><td><i class="fas fa-check text-success me-1"></i> Importados</td><td class="text-end fw-bold text-success">' + (r.imported || 0) + '</td></tr>';
                    html += '<tr><td><i class="fas fa-copy text-warning me-1"></i> Duplicados omitidos</td><td class="text-end fw-bold text-warning">' + (r.duplicates || 0) + '</td></tr>';
                    html += '<tr><td><i class="fas fa-times text-danger me-1"></i> Errores</td><td class="text-end fw-bold text-danger">' + (r.errors || 0) + '</td></tr>';
                    html += '</table>';
                    document.getElementById('resultadoDescarga').className = 'result-alert alert alert-success show mt-2';
                    document.getElementById('resultadoDescarga').innerHTML = html;
                    mostrarAlertaGlobal('success', data.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    document.getElementById('resultadoDescarga').className = 'result-alert alert alert-danger show mt-2';
                    document.getElementById('resultadoDescarga').innerHTML = '<i class="fas fa-times-circle me-1"></i> ' + (data.error || data.message || 'Error en la descarga');
                }
            } catch (err) {
                document.getElementById('resultadoDescarga').className = 'result-alert alert alert-danger show mt-2';
                document.getElementById('resultadoDescarga').innerHTML = '<i class="fas fa-times-circle me-1"></i> Error de conexión: ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-download me-1"></i> Iniciar Descarga';
            }
        }

        function confirmarActualizacionMasiva() {
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarMasiva'));
            document.getElementById('resultadoMasiva').className = 'result-alert';
            document.getElementById('resultadoMasiva').innerHTML = '';
            document.getElementById('progressBarContainer').style.display = 'none';
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('btnMasiva').disabled = false;
            document.getElementById('btnMasiva').innerHTML = '<i class="fas fa-play me-1"></i> Iniciar';
            modal.show();
        }

        async function ejecutarActualizacionMasiva() {
            const btn = document.getElementById('btnMasiva');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';

            document.getElementById('progressBarContainer').style.display = 'block';
            document.getElementById('progressBar').style.width = '10%';
            document.getElementById('resultadoMasiva').className = 'result-alert alert alert-info show mt-2';
            document.getElementById('resultadoMasiva').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando empleados en el dispositivo...';

            try {
                const res = await fetch(BASE_URL + '/biometricos/actualizar-empleados-masivo/' + dispositivoId, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                document.getElementById('progressBar').style.width = '100%';

                if (data.success) {
                    let html = '<div class="d-flex align-items-center mb-2"><i class="fas fa-check-circle text-success me-2"></i> <strong>' + data.message + '</strong></div>';
                    html += '<table class="table table-sm table-borderless mb-0">';
                    html += '<tr><td><i class="fas fa-users text-primary me-1"></i> Total empleados</td><td class="text-end fw-bold">' + (data.total || 0) + '</td></tr>';
                    html += '<tr><td><i class="fas fa-check text-success me-1"></i> Actualizados</td><td class="text-end fw-bold text-success">' + (data.actualizados || 0) + '</td></tr>';
                    html += '<tr><td><i class="fas fa-times text-danger me-1"></i> Fallidos</td><td class="text-end fw-bold text-danger">' + (data.fallidos || 0) + '</td></tr>';
                    html += '</table>';
                    document.getElementById('resultadoMasiva').className = 'result-alert alert alert-success show mt-2';
                    document.getElementById('resultadoMasiva').innerHTML = html;
                    mostrarAlertaGlobal('success', data.message);
                    setTimeout(() => location.reload(), 2000);
                } else {
                    document.getElementById('resultadoMasiva').className = 'result-alert alert alert-danger show mt-2';
                    document.getElementById('resultadoMasiva').innerHTML = '<i class="fas fa-times-circle me-1"></i> ' + (data.error || data.message || 'Error en la actualización masiva');
                }
            } catch (err) {
                document.getElementById('resultadoMasiva').className = 'result-alert alert alert-danger show mt-2';
                document.getElementById('resultadoMasiva').innerHTML = '<i class="fas fa-times-circle me-1"></i> Error de conexión: ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play me-1"></i> Iniciar';
            }
        }

        // Búsqueda en tabla de empleados
        document.getElementById('searchEmpleado')?.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            const rows = document.querySelectorAll('#empleadosTable .empleado-row');
            let visible = 0;
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            document.getElementById('empleadosCount').textContent = visible + ' empleados';
        });

        // Botones Actualizar en tabla
        document.querySelectorAll('.btn-actualizar-emp').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;
                document.getElementById('empleadoSelect').value = id;
                const modal = new bootstrap.Modal(document.getElementById('modalActualizarEmpleado'));
                modal.show();
            });
        });
    </script>
</body>
</html>
