<?php
// empleados_minimal.php - Vista de empleados sin dependencias

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login_minimal.php');
    exit;
}

require_once 'config.php';

// Obtener empleados
try {
    require_once 'models/Database.php';
    require_once 'models/Empleado.php';
    $empleadoModel = new Empleado();
    $empleados = $empleadoModel->getAll();
} catch (Exception $e) {
    $empleados = [];
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Empleados - Sistema Biométrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .sidebar { 
            position: fixed; top: 0; left: 0; width: 250px; height: 100vh; 
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%); 
            padding: 20px; color: white; z-index: 1000;
        }
        .main-content { margin-left: 250px; padding: 20px; min-height: 100vh; }
        .nav-link { 
            color: white; padding: 12px 20px; display: block; 
            text-decoration: none; border-radius: 8px; margin-bottom: 5px;
            transition: all 0.3s;
        }
        .nav-link:hover { background: rgba(255,255,255,0.15); }
        .nav-link.active { background: rgba(255,255,255,0.25); }
        .user-info { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .employee-card { 
            transition: all 0.3s; border: none; border-radius: 15px;
            overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .employee-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 20px rgba(0,0,0,0.15);
        }
        .employee-avatar { 
            width: 80px; height: 80px; border-radius: 50%; 
            object-fit: cover; border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .stat-card { 
            text-align: center; border: none; border-radius: 15px;
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .header-title {
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            color: white; padding: 2px 15px; border-radius: 25px;
        }
        .badge-status { padding: 8px 12px; border-radius: 20px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="text-center mb-4">
            <h4><i class="fas fa-fingerprint"></i> BioMetric</h4>
            <small class="opacity-75">Sistema de Control</small>
        </div>
        
        <div class="nav flex-column">
            <a href="dashboard_minimal.php" class="nav-link">
                <i class="fas fa-gauge-high me-2"></i> Dashboard
            </a>
            <a href="#" class="nav-link active">
                <i class="fas fa-users me-2"></i> Empleados
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-clock me-2"></i> Asistencia
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-chart-simple me-2"></i> Reportes
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-fingerprint me-2"></i> Dispositivos
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-calendar-days me-2"></i> Horarios
            </a>
        </div>
        
        <div class="user-info">
            <div class="text-center">
                <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                     style="width: 50px; height: 50px; font-size: 1.5rem;">
                    <strong><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></strong>
                </div>
                <div><strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
                <div class="small opacity-75"><?= htmlspecialchars($_SESSION['rol']) ?></div>
                <a href="logout_minimal.php" class="btn btn-sm btn-outline-light mt-3 w-100">
                    <i class="fas fa-right-from-bracket"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <span class="header-title">
                    <i class="fas fa-users me-2"></i>Empleados
                </span>
            </h1>
            <a href="<?php echo BASE_URL; ?>/empleados/create" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Nuevo Empleado
            </a>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-triangle-exclamation me-2"></i>
                Error al cargar empleados: <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                        <h3 class="mb-2"><?= count($empleados) ?></h3>
                        <p class="mb-0">Total Empleados</p>
                        <small class="opacity-75">Registrados en el sistema</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-user-check fa-2x opacity-75"></i>
                        </div>
                        <h3 class="mb-2"><?= count(array_filter($empleados, fn($e) => $e['activo'])) ?></h3>
                        <p class="mb-0">Activos</p>
                        <small class="opacity-75">Empleados operativos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-building fa-2x opacity-75"></i>
                        </div>
                        <h3 class="mb-2"><?= count(array_unique(array_column($empleados, 'area'))) ?></h3>
                        <p class="mb-0">Áreas</p>
                        <small class="opacity-75">Departamentos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-fingerprint fa-2x opacity-75"></i>
                        </div>
                        <h3 class="mb-2"><?= count(array_filter($empleados, fn($e) => !empty($e['huella_dactilar']))) ?></h3>
                        <p class="mb-0">Con Huella</p>
                        <small class="opacity-75">Biometría registrada</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees Grid -->
        <div class="row">
            <?php foreach ($empleados as $empleado): ?>
                <?php 
                    $nombreCompleto = $empleado['nombre'] . ' ' . $empleado['apellido'];
                    $area = $empleado['area'] ?? 'Sin área';
                    $activo = $empleado['activo'];
                    $statusClass = $activo ? 'success' : 'danger';
                    $statusIcon = $activo ? 'circle-check' : 'circle-xmark';
                    $statusText = $activo ? 'Activo' : 'Inactivo';
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card employee-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($nombreCompleto) ?>&background=9F2241&color=fff&size=80" 
                                     class="employee-avatar me-3" alt="<?= htmlspecialchars($nombreCompleto) ?>">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($nombreCompleto) ?></h5>
                                    <span class="badge bg-<?= $statusClass ?> badge-status">
                                        <i class="fas fa-<?= $statusIcon ?> me-1"></i><?= $statusText ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-id-card me-2"></i>RFC
                                    </small><br>
                                    <strong><?= htmlspecialchars($empleado['rfc']) ?></strong>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-building me-2"></i>Área
                                    </small><br>
                                    <strong><?= htmlspecialchars($area) ?></strong>
                                </div>
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-fingerprint me-2"></i>Biometría
                                    </small><br>
                                    <span class="badge <?= !empty($empleado['huella_dactilar']) ? 'bg-success' : 'bg-warning' ?>">
                                        <?= !empty($empleado['huella_dactilar']) ? 'Registrada' : 'Pendiente' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver
                                </button>
                                <button class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-pen-to-square me-1"></i>Editar
                                </button>
                                <button class="btn <?= !empty($empleado['huella_dactilar']) ? 'btn-outline-success' : 'btn-outline-info' ?> btn-sm">
                                    <i class="fas fa-fingerprint me-1"></i>
                                    <?= !empty($empleado['huella_dactilar']) ? 'Huella' : 'Registrar' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($empleados)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h4>No hay empleados registrados</h4>
                            <p class="text-muted">Comienza agregando el primer empleado al sistema.</p>
                            <a href="<?php echo BASE_URL; ?>/empleados/create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Agregar Primer Empleado
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal Nuevo Empleado -->
        <div class="modal fade" id="nuevoEmpleadoModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Nuevo Empleado
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-2"></i>Nombre
                                    </label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-2"></i>Apellido
                                    </label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-id-card me-2"></i>RFC
                                    </label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-id-card me-2"></i>CURP
                                    </label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-building me-2"></i>Área
                                    </label>
                                    <select class="form-select">
                                        <option>Recursos Humanos</option>
                                        <option>Administración</option>
                                        <option>Tecnología</option>
                                        <option>Producción</option>
                                        <option>Ventas</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-tie me-2"></i>Jerarquía
                                    </label>
                                    <select class="form-select">
                                        <option>Gerente</option>
                                        <option>Supervisor</option>
                                        <option>Empleado</option>
                                        <option>Practicante</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-camera me-2"></i>Foto del Empleado
                                </label>
                                <input type="file" class="form-control" accept="image/*">
                                <small class="text-muted">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-xmark me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary">
                            <i class="fas fa-floppy-disk me-2"></i>Guardar Empleado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>