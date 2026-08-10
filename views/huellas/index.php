<?php
// views/huellas/index.php - Interfaz para gestión de huellas dactilares

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /biometrico/login_final.php');
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener empleados para el formulario de enrolamiento
$stmt = $conn->prepare("
    SELECT e.id, e.nombre, e.apellido, e.rfc, e.area, e.puesto,
           COALESCE(z.zk_empleado_id, e.id) as zk_empleado_id
    FROM empleados e
    LEFT JOIN zk_empleado_mapeo z ON e.id = z.empleado_id
    WHERE e.activo = 1
    ORDER BY e.nombre, e.apellido
");
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Huellas - Sistema Biométrico</title>
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/fontawesome-all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #9F2241;
            --secondary-color: #235B4E;
            --success-color: #235B4E;
            --warning-color: #BC955C;
            --danger-color: #691C32;
            --info-color: #235B4E;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #764ba2);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #764ba2;
            border-color: #764ba2;
        }
        
        .fingerprint-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .enrollment-status {
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            display: none;
        }
        
        .status-capturing {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .status-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .status-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .employee-row {
            transition: background-color 0.2s;
        }
        
        .employee-row:hover {
            background-color: #f8f9fa;
        }
        
        .fingerprint-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
            font-size: 0.8rem;
            text-align: center;
            line-height: 20px;
            color: white;
        }
        
        .fingerprint-registered {
            background-color: var(--success-color);
        }
        
        .fingerprint-empty {
            background-color: var(--secondary-color);
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .progress-container {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h1><i class="fas fa-fingerprint"></i> Gestión de Huellas Dactilares</h1>
                        <p class="mb-0">Sistema Biométrico ZKTeco MB360</p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light text-dark">Usuario: <?= htmlspecialchars($_SESSION['username']) ?></span>
                        <a href="/logout" class="btn btn-light ms-2">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-users fingerprint-icon"></i>
                    <div class="stats-number"><?= $stats['empleados_con_huellas'] ?></div>
                    <div class="text-muted">Empleados con Huellas</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-fingerprint fingerprint-icon"></i>
                    <div class="stats-number"><?= $stats['total_huellas'] ?></div>
                    <div class="text-muted">Total de Huellas</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-user-times fingerprint-icon"></i>
                    <div class="stats-number"><?= $stats['empleados_sin_huellas'] ?></div>
                    <div class="text-muted">Empleados sin Huellas</div>
                </div>
            </div>
        </div>

        <!-- Device Status and Sync -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-desktop"></i> Estado del Dispositivo</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Modelo:</strong> ZKTeco MB360<br>
                                <strong>IP:</strong> 192.168.1.201<br>
                                <strong>Puerto:</strong> 4370
                            </div>
                            <div class="col-md-6">
                                <span class="badge bg-success">Conectado</span><br>
                                <small class="text-muted">Última sincronización: Hace 5 minutos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-sync"></i> Sincronización</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary" onclick="sincronizarHuellas()">
                            <i class="fas fa-sync-alt"></i> Sincronizar Huellas
                        </button>
                        <div class="loading-spinner mt-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Sincronizando...</span>
                            </div>
                            <p class="mt-2">Sincronizando con el dispositivo...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fingerprint Enrollment -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus"></i> Enrolar Huella</h5>
                    </div>
                    <div class="card-body">
                        <form id="enrollmentForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Empleado:</label>
                                    <select class="form-select" name="empleado_id" required>
                                        <option value="">Seleccionar empleado...</option>
                                        <?php foreach ($empleados as $emp): ?>
                                            <option value="<?= $emp['id'] ?>">
                                                <?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) ?>
                                                (<?= htmlspecialchars($emp['rfc']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dedo:</label>
                                    <select class="form-select" name="indice_dedo" required>
                                        <option value="0">Índice Derecho</option>
                                        <option value="1">Índice Izquierdo</option>
                                        <option value="2">Pulgar Derecho</option>
                                        <option value="3">Pulgar Izquierdo</option>
                                        <option value="4">Medio Derecho</option>
                                        <option value="5">Medio Izquierdo</option>
                                        <option value="6">Anular Derecho</option>
                                        <option value="7">Anular Izquierdo</option>
                                        <option value="8">Meñique Derecho</option>
                                        <option value="9">Meñique Izquierdo</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dispositivo:</label>
                                    <select class="form-select" name="dispositivo_id" required>
                                        <option value="1">ZKTeco MB360 Principal</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label><br>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-fingerprint"></i> Enrolar
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div id="enrollmentStatus" class="enrollment-status">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Procesando...</span>
                                </div>
                                <span id="statusMessage">Iniciando proceso de enrolamiento...</span>
                            </div>
                        </div>
                        
                        <div class="progress-container">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees with Fingerprints -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Empleados y Huellas Registradas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="employeesTable">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Área</th>
                                        <th>Puesto</th>
                                        <th>Huellas Registradas</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empleados as $emp): ?>
                                        <tr class="employee-row">
                                            <td>
                                                <strong><?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($emp['rfc']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($emp['area']) ?></td>
                                            <td><?= htmlspecialchars($emp['puesto']) ?></td>
                                            <td>
                                                <?php
                                                $num_huellas = $emp['num_huellas'] ?? 0;
                                                for ($i = 0; $i < 10; $i++) {
                                                    $class = ($i < $num_huellas) ? 'fingerprint-registered' : 'fingerprint-empty';
                                                    $icon = ($i < $num_huellas) ? '✓' : '';
                                                    echo "<span class='fingerprint-indicator $class'>$icon</span>";
                                                }
                                                ?>
                                                <span class="ms-2"><?= $num_huellas ?>/10</span>
                                            </td>
                                            <td>
                                                <?php if ($num_huellas > 0): ?>
                                                    <span class="badge bg-success">Con Huellas</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Sin Huellas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="enrolarEmpleado(<?= $emp['id'] ?>)">
                                                    <i class="fas fa-fingerprint"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="verHuellas(<?= $emp['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let enrollmentInterval = null;
        let currentProcessId = null;

        // Sincronizar huellas desde el dispositivo
        function sincronizarHuellas() {
            $('.loading-spinner').show();
            
            $.post('/huellas/sincronizar', {
                dispositivo_id: 1
            }, function(response) {
                $('.loading-spinner').hide();
                
                if (response.success) {
                    showAlert('success', response.message);
                    // Actualizar tabla de empleados
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', response.message);
                }
            }).fail(function() {
                $('.loading-spinner').hide();
                showAlert('danger', 'Error de comunicación con el servidor');
            });
        }

        // Enrolar huella (formulario)
        $('#enrollmentForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.post('/huellas/enrolar', formData, function(response) {
                if (response.success) {
                    currentProcessId = response.proceso_id;
                    showEnrollmentStatus('capturing', 'Proceso iniciado. Por favor, coloque el dedo en el lector...');
                    startStatusMonitoring();
                } else {
                    showEnrollmentStatus('error', response.message);
                }
            }).fail(function() {
                showEnrollmentStatus('error', 'Error de comunicación con el servidor');
            });
        });

        // Monitorear estado del enrolamiento
        function startStatusMonitoring() {
            if (enrollmentInterval) {
                clearInterval(enrollmentInterval);
            }
            
            enrollmentInterval = setInterval(function() {
                if (!currentProcessId) {
                    clearInterval(enrollmentInterval);
                    return;
                }
                
                $.get('/huellas/verificar-estado?proceso_id=' + currentProcessId, function(response) {
                    if (response.success) {
                        const status = response.data;
                        
                        if (status.estado_proceso === 'completado') {
                            showEnrollmentStatus('success', 'Huella enrolada exitosamente');
                            clearInterval(enrollmentInterval);
                            setTimeout(() => location.reload(), 2000);
                        } else if (status.estado_proceso === 'error') {
                            showEnrollmentStatus('error', status.mensaje_estado || 'Error en el enrolamiento');
                            clearInterval(enrollmentInterval);
                        } else {
                            showEnrollmentStatus('capturing', status.mensaje_estado || 'Procesando...');
                            updateProgress(status.paso_actual, status.total_pasos);
                        }
                    }
                }).fail(function() {
                    clearInterval(enrollmentInterval);
                    showEnrollmentStatus('error', 'Error al verificar estado');
                });
            }, 2000);
        }

        // Mostrar estado del enrolamiento
        function showEnrollmentStatus(type, message) {
            const statusDiv = $('#enrollmentStatus');
            const messageSpan = $('#statusMessage');
            
            statusDiv.removeClass('status-capturing status-success status-error');
            statusDiv.addClass('status-' + type);
            messageSpan.text(message);
            statusDiv.show();
            
            if (type === 'success' || type === 'error') {
                setTimeout(() => statusDiv.hide(), 5000);
            }
        }

        // Actualizar barra de progreso
        function updateProgress(current, total) {
            const percentage = Math.round((current / total) * 100);
            const progressBar = $('.progress-bar');
            
            progressBar.css('width', percentage + '%');
            progressBar.attr('aria-valuenow', percentage);
            progressBar.text(percentage + '%');
            
            $('.progress-container').show();
        }

        // Enrolar empleado específico
        function enrolarEmpleado(empleadoId) {
            // Abrir modal o redirigir a formulario de enrolamiento
            window.location.href = '#enrollmentForm';
            $('select[name="empleado_id"]').val(empleadoId);
        }

        // Ver huellas de empleado
        function verHuellas(empleadoId) {
            // Implementar modal para ver detalles de huellas
            alert('Función en desarrollo');
        }

        // Mostrar alertas
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('.container-fluid').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        // Inicializar tabla
        $(document).ready(function() {
            $('#employeesTable').DataTable({
                language: {
                    url: '<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/Spanish.json'
                },
                pageLength: 25
            });
        });
    </script>
</body>
</html>