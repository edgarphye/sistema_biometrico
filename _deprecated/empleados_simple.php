<?php
// empleados_simple.php - Vista de empleados simple

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: direct_login_test.php');
    exit;
}

require_once 'config.php';
require_once 'models/Empleado.php';

$baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
$baseUrl = rtrim($baseUrlPath, '/');

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - Sistema Biométrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { 
            position: fixed; top: 0; left: 0; width: 250px; height: 100vh; 
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%); 
            padding-top: 20px; color: white; 
        }
        .main-content { margin-left: 250px; padding: 20px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .nav-link { color: white; padding: 10px 20px; display: block; text-decoration: none; }
        .nav-link:hover { background: rgba(255,255,255,0.1); }
        .nav-link.active { background: rgba(255,255,255,0.2); }
        .user-info { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .employee-card { transition: transform 0.2s; }
        .employee-card:hover { transform: translateY(-5px); }
        .employee-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="text-center mb-4">
            <h4><i class="fas fa-fingerprint"></i> BioMetric</h4>
        </div>
        
        <div class="nav flex-column">
            <a href="dashboard_simple.php" class="nav-link">
                <i class="fas fa-gauge-high me-2"></i> Dashboard
            </a>
            <a href="empleados_simple.php" class="nav-link active">
                <i class="fas fa-users me-2"></i> Empleados
            </a>
            <a href="asistencia_simple.php" class="nav-link">
                <i class="fas fa-clock me-2"></i> Asistencia
            </a>
            <a href="reportes_simple.php" class="nav-link">
                <i class="fas fa-chart-simple me-2"></i> Reportes
            </a>
            <a href="biometricos_simple.php" class="nav-link">
                <i class="fas fa-fingerprint me-2"></i> Dispositivos
            </a>
        </div>
        
        <div class="user-info">
            <div class="text-center">
                <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                    <strong>' . strtoupper(substr($_SESSION['username'], 0, 1)) . '</strong>
                </div>
                <div><strong>' . htmlspecialchars($_SESSION['username']) . '</strong></div>
                <div class="small">' . htmlspecialchars($_SESSION['rol']) . '</div>
                <a href="logout_simple.php" class="btn btn-sm btn-outline-light mt-2">
                    <i class="fas fa-right-from-bracket"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users text-primary"></i> Empleados</h1>
            <div>
                <a href="' . $baseUrl . '/empleados/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Empleado
                </a>
            </div>
        </div>';

// Obtener empleados
try {
    $empleadoModel = new Empleado();
    $empleados = $empleadoModel->getAll();
    
    echo '
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary">' . count($empleados) . '</h3>
                        <p class="text-muted mb-0">Total Empleados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success">' . count(array_filter($empleados, fn($e) => $e['activo'])) . '</h3>
                        <p class="text-muted mb-0">Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info">' . count(array_unique(array_column($empleados, 'area'))) . '</h3>
                        <p class="text-muted mb-0">Áreas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning">' . count(array_filter($empleados, fn($e) => !empty($e['huella_dactilar']))) . '</h3>
                        <p class="text-muted mb-0">Con Huella</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees Grid -->
        <div class="row">';
        
        foreach ($empleados as $empleado) {
            $nombreCompleto = $empleado['nombre'] . ' ' . $empleado['apellido'];
            $area = $empleado['area'] ?? 'Sin área';
            $activo = $empleado['activo'] ? 'Activo' : 'Inactivo';
            $statusBadge = $empleado['activo'] ? 'success' : 'danger';
            
            echo '
            <div class="col-md-4 mb-4">
                <div class="card employee-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=' . urlencode($nombreCompleto) . '&background=9F2241&color=fff&size=60" 
                                 class="employee-avatar me-3" alt="' . htmlspecialchars($nombreCompleto) . '">
                            <div>
                                <h5 class="card-title mb-1">' . htmlspecialchars($nombreCompleto) . '</h5>
                                <span class="badge bg-' . $statusBadge . '">' . $activo . '</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">
                                <i class="fas fa-id-card"></i> RFC: ' . htmlspecialchars($empleado['rfc']) . '
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-building"></i> Área: ' . htmlspecialchars($area) . '
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-fingerprint"></i> Huella: ' . (!empty($empleado['huella_dactilar']) ? 'Registrada' : 'Pendiente') . '
                            </small>
                        </div>
                        
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-pen-to-square"></i> Editar
                            </button>
                            <button class="btn btn-outline-success btn-sm">
                                <i class="fas fa-fingerprint"></i> Huella
                            </button>
                        </div>
                    </div>
                </div>
            </div>';
        }
        
        echo '
        </div>';
        
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> Error al cargar empleados: ' . $e->getMessage() . '
    </div>';
}

echo '
        <!-- Modal Nuevo Empleado -->
        <div class="modal fade" id="nuevoEmpleadoModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nuevo Empleado</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">RFC</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CURP</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Área</label>
                                    <select class="form-select">
                                        <option>Recursos Humanos</option>
                                        <option>Administración</option>
                                        <option>Tecnología</option>
                                        <option>Producción</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jerarquía</label>
                                    <select class="form-select">
                                        <option>Gerente</option>
                                        <option>Supervisor</option>
                                        <option>Empleado</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Foto del Empleado</label>
                                <input type="file" class="form-control" accept="image/*">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> Guardar Empleado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?>