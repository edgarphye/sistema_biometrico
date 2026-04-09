<?php
// dashboard_simple.php - Dashboard sin routing complejo

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: login_direct.php');
    exit;
}

require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/Empleado.php';
require_once 'models/Asistencia.php';

// Obtener estadísticas
$empleadoModel = new Empleado();
$asistenciaModel = new Asistencia();

$empleados = $empleadoModel->getAll();
$totalEmpleados = count($empleados);

// Estadísticas del día
$hoy = date('Y-m-d');
$asistenciasHoy = []; // Temporal - método getByDate no existe

$session_username = htmlspecialchars($_SESSION['username'] ?? 'Usuario');
$session_rol = htmlspecialchars($_SESSION['rol'] ?? 'Usuario');
$session_first_letter = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self';">
    <title>Dashboard - Sistema Biométrico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            padding: 20px;
            color: white;
            z-index: 1000;
        }
        
        .sidebar h4 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }
        
        .nav-link {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.25);
        }
        
        .user-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            padding: 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: white;
            color: #9F2241;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        /* Grid System */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }
        
        .col-3, .col-4, .col-6, .col-12 {
            padding: 0 10px;
            margin-bottom: 20px;
        }
        
        .col-3 {
            flex: 0 0 25%;
            max-width: 25%;
        }
        
        .col-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
        
        .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        
        .col-12 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        /* Statistics Cards */
        .stat-card {
            text-align: center;
        }
        
        .stat-card .card-body {
            padding: 2rem 1.5rem;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #9F2241;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stat-info {
            font-size: 0.8rem;
            color: #999;
        }
        
        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin: 0;
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
        }
        
        /* Welcome Message */
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .welcome-card .card-body {
            padding: 2rem;
        }
        
        .welcome-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .col-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .col-3, .col-4, .col-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <h4>👆 BioMetric</h4>
        
        <div>
            <a href="dashboard_simple.php" class="nav-link active">
                📊 Dashboard
            </a>
            <a href="empleados_final.php" class="nav-link">
                👥 Empleados
            </a>
            <a href="asistencia_final.php" class="nav-link">
                ⏰ Asistencia
            </a>
            <a href="#" class="nav-link">
                📈 Reportes
            </a>
            <a href="#" class="nav-link">
                👆 Dispositivos
            </a>
            <a href="#" class="nav-link">
                📅 Horarios
            </a>
            <a href="#" class="nav-link">
                📄 Justificaciones
            </a>
            <a href="#" class="nav-link">
                ⚠️ Sanciones
            </a>
        </div>
        
        <div class="user-info">
            <div class="user-avatar"><?= $session_first_letter ?></div>
            <div><strong><?= $session_username ?></strong></div>
            <div style="font-size: 0.9rem; opacity: 0.8;"><?= $session_rol ?></div>
            <a href="logout_simple.php" style="color: white; text-decoration: none; margin-top: 0.5rem; display: block; padding: 0.5rem; background: rgba(255,255,255,0.2); border-radius: 8px;">
                🚪 Cerrar Sesión
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                📊 Dashboard
            </h1>
        </div>

        <!-- Welcome Message -->
        <div class="card welcome-card">
            <div class="card-body">
                <h2 class="welcome-title">¡Bienvenido, <?= $session_username ?>!</h2>
                <p class="welcome-subtitle">Sistema de Control Biométrico - Panel Principal</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?= $totalEmpleados ?></div>
                        <div class="stat-label">Total Empleados</div>
                        <div class="stat-info">Registrados en el sistema</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?= count($asistenciasHoy) ?></div>
                        <div class="stat-label">Asistencias Hoy</div>
                        <div class="stat-info">Registradas el día de hoy</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">🏢</div>
                        <div class="stat-number">5</div>
                        <div class="stat-label">Áreas</div>
                        <div class="stat-info">Departamentos activos</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">👆</div>
                        <div class="stat-number">12</div>
                        <div class="stat-label">Con Huella</div>
                        <div class="stat-info">Biometría registrada</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        Acciones Rápidas
                    </div>
                    <div class="card-body">
                        <p style="margin-bottom: 1rem;">Seleccione una opción del menú lateral para comenzar:</p>
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 0.5rem;">👥 <strong>Empleados:</strong> Gestión completa de personal</li>
                            <li style="margin-bottom: 0.5rem;">⏰ <strong>Asistencia:</strong> Control de entrada y salida</li>
                            <li style="margin-bottom: 0.5rem;">📈 <strong>Reportes:</strong> Informes y estadísticas</li>
                            <li style="margin-bottom: 0.5rem;">👆 <strong>Dispositivos:</strong> Configuración biométrica</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>