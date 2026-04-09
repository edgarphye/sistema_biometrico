<?php
// huellas_standalone.php - Versión standalone del panel de huellas

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    // Simular login para demostración
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['rol'] = 'admin';
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener estadísticas de huellas
$stats = [
    'empleados_con_huellas' => 0,
    'total_huellas' => 0,
    'empleados_sin_huellas' => 0
];

try {
    // Total de empleados con huellas
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT empleado_id) as total 
        FROM huellas_empleados 
        WHERE estado = 'activo'
    ");
    $stmt->execute();
    $stats['empleados_con_huellas'] = $stmt->fetch()['total'];
    
    // Total de huellas registradas
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM huellas_empleados 
        WHERE estado = 'activo'
    ");
    $stmt->execute();
    $stats['total_huellas'] = $stmt->fetch()['total'];
    
    // Empleados sin huellas
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM empleados e 
        LEFT JOIN huellas_empleados h ON e.id = h.empleado_id AND h.estado = 'activo'
        WHERE e.activo = 1 AND h.empleado_id IS NULL
    ");
    $stmt->execute();
    $stats['empleados_sin_huellas'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    // Si hay error, mostrar valores reales calculados
    try {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT empleado_id) as total FROM huellas_empleados WHERE estado = 'activo'");
        $stmt->execute();
        $empleados_con_huellas = $stmt->fetch()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM huellas_empleados WHERE estado = 'activo'");
        $stmt->execute();
        $total_huellas = $stmt->fetch()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleados e LEFT JOIN huellas_empleados h ON e.id = h.empleado_id AND h.estado = 'activo' WHERE e.activo = 1 AND h.empleado_id IS NULL");
        $stmt->execute();
        $empleados_sin_huellas = $stmt->fetch()['total'];
        
        $stats = [
            'empleados_con_huellas' => $empleados_con_huellas,
            'total_huellas' => $total_huellas,
            'empleados_sin_huellas' => $empleados_sin_huellas
        ];
    } catch (Exception $e2) {
        // Valores por defecto si todo falla
        $stats = [
            'empleados_con_huellas' => 0,
            'total_huellas' => 0,
            'empleados_sin_huellas' => 0
        ];
    }
}

// Obtener empleados para el formulario
$empleados = [];
try {
    $stmt = $conn->prepare("
        SELECT e.id, e.nombre, e.apellido, e.rfc, e.area, e.puesto,
               COALESCE(z.zk_empleado_id, e.id) as zk_empleado_id
        FROM empleados e
        LEFT JOIN zk_empleado_mapeo z ON e.id = z.empleado_id
        WHERE e.activo = 1
        ORDER BY e.nombre, e.apellido
        LIMIT 10
    ");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Empleados de ejemplo
    $empleados = [
        ['id' => 1, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'rfc' => 'PEJJ800101', 'area' => 'TI', 'puesto' => 'Desarrollador', 'zk_empleado_id' => 1],
        ['id' => 2, 'nombre' => 'María', 'apellido' => 'Gómez', 'rfc' => 'GOMM850101', 'area' => 'RH', 'puesto' => 'Gerente', 'zk_empleado_id' => 2],
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Huellas - Sistema Biométrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #9F2241;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
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
        
        .demo-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: var(--warning-color);
            color: var(--dark-bg);
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="demo-badge">
        <i class="fas fa-flask"></i> MODO DEMOSTRACIÓN
    </div>
    
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
                        <a href="javascript:void(0)" class="btn btn-light ms-2" onclick="showDemoInfo()">
                            <i class="fas fa-info-circle"></i> Info
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
                                <span class="badge bg-warning">Sin Conectar</span><br>
                                <small class="text-muted">Configure la IP del dispositivo</small>
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
                        <button class="btn btn-primary" onclick="demoSync()">
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
                            <table class="table table-hover">
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
                                                $num_huellas = rand(0, 3); // Demo: random fingerprints
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
                                                <button class="btn btn-sm btn-primary" onclick="demoEnroll(<?= $emp['id'] ?>)">
                                                    <i class="fas fa-fingerprint"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="demoView(<?= $emp['id'] ?>)">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Demo functions
        function demoSync() {
            $('.loading-spinner').show();
            
            setTimeout(() => {
                $('.loading-spinner').hide();
                showAlert('success', 'Sincronización completada (Demo)');
                updateStats();
            }, 3000);
        }
        
        function demoEnroll(employeeId) {
            showEnrollmentStatus('capturing', 'Iniciando enrolamiento (Demo)...');
            
            setTimeout(() => {
                showEnrollmentStatus('success', 'Huella enrolada exitosamente (Demo)');
                setTimeout(() => location.reload(), 2000);
            }, 2000);
        }
        
        function demoView(employeeId) {
            showAlert('info', 'Ver detalles de huellas (Demo)');
        }
        
        function showDemoInfo() {
            showAlert('info', 'Este es un panel de demostración. Para usar el sistema completo, configure el dispositivo ZKTeco MB360 y ejecute el script de sincronización.');
        }
        
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
        
        function updateStats() {
            // Update stats with demo data
            $('.stats-number').each(function(index) {
                const currentValue = parseInt($(this).text());
                $(this).text(currentValue + Math.floor(Math.random() * 3));
            });
        }
        
        // Form submission
        $('#enrollmentForm').on('submit', function(e) {
            e.preventDefault();
            demoEnroll($('select[name="empleado_id"]').val());
        });
    </script>
</body>
</html>