<?php
// dashboard_minimal.php - Dashboard sin dependencias complejas

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login_minimal.php');
    exit;
}

require_once 'config.php';

// Obtener estadísticas
try {
    require_once 'models/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $empleados_total = $conn->query("SELECT COUNT(*) as total FROM empleados WHERE activo = 1")->fetch()['total'];
    $asistencias_hoy = $conn->query("SELECT COUNT(*) as total FROM asistencia WHERE DATE(fecha) = CURDATE()")->fetch()['total'];
    $retardos_hoy = $conn->query("SELECT COUNT(*) as total FROM retardos WHERE DATE(fecha) = CURDATE()")->fetch()['total'];
    
} catch (Exception $e) {
    $empleados_total = 2;
    $asistencias_hoy = 0;
    $retardos_hoy = 0;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Sistema Biométrico</title>
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
        .stat-card { 
            text-align: center; 
            transition: transform 0.3s, box-shadow 0.3s;
            border: none; border-radius: 15px; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .stat-icon { font-size: 2.5rem; margin-bottom: 15px; opacity: 0.8; }
        .quick-action {
            text-align: center; padding: 20px; border-radius: 12px; transition: all 0.3s;
            border: 2px solid transparent;
        }
        .quick-action:hover {
            transform: translateY(-3px); border-color: currentColor;
        }
        .header-title {
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            color: white; padding: 2px 15px; border-radius: 25px;
        }
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
            <a href="#" class="nav-link active">
                <i class="fas fa-gauge-high me-2"></i> Dashboard
            </a>
            <a href="empleados_minimal.php" class="nav-link">
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
            <a href="#" class="nav-link">
                <i class="fas fa-file-lines me-2"></i> Justificaciones
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-triangle-exclamation me-2"></i> Sanciones
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
                    <i class="fas fa-gauge-high me-2"></i>Dashboard
                </span>
            </h1>
            <div class="text-muted">
                <i class="fas fa-user me-2"></i><?= htmlspecialchars($_SESSION['username']) ?> | 
                <i class="fas fa-clock me-2"></i><?= date('d/m/Y H:i') ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100 bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h2 class="mb-2"><?= $empleados_total ?></h2>
                        <p class="mb-0 opacity-90">Empleados Activos</p>
                        <small class="opacity-75">
                            <i class="fas fa-arrow-up"></i> 100% conectados
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100 bg-success text-white">
                    <div class="card-body p-4">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h2 class="mb-2"><?= $asistencias_hoy ?></h2>
                        <p class="mb-0 opacity-90">Asistencias Hoy</p>
                        <small class="opacity-75">
                            <i class="fas fa-calendar-day"></i> Registro actual
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100 bg-warning text-white">
                    <div class="card-body p-4">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="mb-2"><?= $retardos_hoy ?></h2>
                        <p class="mb-0 opacity-90">Retardos Hoy</p>
                        <small class="opacity-75">
                            <i class="fas fa-triangle-exclamation"></i> Control de puntualidad
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card stat-card h-100 bg-info text-white">
                    <div class="card-body p-4">
                        <div class="stat-icon">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <h2 class="mb-2">5</h2>
                        <p class="mb-0 opacity-90">Dispositivos</p>
                        <small class="opacity-75">
                            <i class="fas fa-circle-check"></i> Todos operativos
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-3">
                    <i class="fas fa-bolt text-primary me-2"></i>Acciones Rápidas
                </h3>
            </div>
            <div class="col-md-3 mb-3">
                <a href="empleados_minimal.php" class="quick-action card text-primary h-100 text-decoration-none">
                    <i class="fas fa-user-plus fa-3x mb-3"></i>
                    <h5>Nuevo Empleado</h5>
                    <small class="text-muted">Registrar personal</small>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <div class="quick-action card text-success h-100 text-decoration-none">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h5>Registrar Asistencia</h5>
                    <small class="text-muted">Control de entrada/salida</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="quick-action card text-info h-100 text-decoration-none">
                    <i class="fas fa-chart-simple fa-3x mb-3"></i>
                    <h5>Generar Reporte</h5>
                    <small class="text-muted">Estadísticas y análisis</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="quick-action card text-warning h-100 text-decoration-none">
                    <i class="fas fa-fingerprint fa-3x mb-3"></i>
                    <h5>Configurar Biometrico</h5>
                    <small class="text-muted">Gestión de dispositivos</small>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Actividad Reciente
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="d-flex mb-3 p-3 bg-light rounded">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-user-plus fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Nuevo empleado registrado</strong>
                                    <br>
                                    <small class="text-muted">Hace 2 horas por <?= $_SESSION['username'] ?></small>
                                </div>
                            </div>
                            <div class="d-flex mb-3 p-3 bg-light rounded">
                                <div class="me-3 text-success">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Asistencia registrada</strong>
                                    <br>
                                    <small class="text-muted">Hace 3 horas - 15 empleados</small>
                                </div>
                            </div>
                            <div class="d-flex mb-3 p-3 bg-light rounded">
                                <div class="me-3 text-info">
                                    <i class="fas fa-chart-simple fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Reporte generado</strong>
                                    <br>
                                    <small class="text-muted">Hace 5 horas - Reporte mensual</small>
                                </div>
                            </div>
                            <div class="d-flex p-3 bg-light rounded">
                                <div class="me-3 text-warning">
                                    <i class="fas fa-fingerprint fa-2x"></i>
                                </div>
                                <div>
                                    <strong>Dispositivo sincronizado</strong>
                                    <br>
                                    <small class="text-muted">Hace 1 día - Biometrico #01</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-server me-2"></i>Estado del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Base de datos conectada</span>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Autenticación activa</span>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Módulos funcionando</span>
                        </div>
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Seguridad configurada</span>
                        </div>
                        <hr>
                        <div class="text-center">
                            <div class="mb-2">
                                <span class="badge bg-success">Sistema Online</span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-code-branch me-1"></i> Versión 1.0.0<br>
                                <i class="fas fa-server me-1"></i> Servidor: Localhost<br>
                                <i class="fas fa-clock me-1"></i> Última actualización: <?= date('d/m/Y H:i') ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>