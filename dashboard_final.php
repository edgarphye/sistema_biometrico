<?php
// dashboard_final.php - Dashboard sin dependencias externas

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login_final.php');
    exit;
}

require_once 'config.php';

// Obtener estadísticas con manejo de errores
$empleados_total = 0;
$asistencias_hoy = 0;
$retardos_hoy = 0;
$empleados_activos = 0;

try {
    require_once 'models/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener estadísticas
    $empleados_total = $conn->query("SELECT COUNT(*) as total FROM empleados WHERE activo = 1")->fetch()['total'];
    $empleados_activos = $empleados_total;
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM asistencia WHERE DATE(fecha) = CURDATE()");
    $asistencias_hoy = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM retardos WHERE DATE(fecha) = CURDATE()");
    $retardos_hoy = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    // Usar datos de demostración si hay error
    $empleados_total = 2;
    $empleados_activos = 2;
    $asistencias_hoy = 0;
    $retardos_hoy = 0;
    $error_message = $e->getMessage();
}

// Variables de sesión seguras
$session_username = htmlspecialchars($_SESSION['username'] ?? 'Usuario');
$session_rol = htmlspecialchars($_SESSION['rol'] ?? 'Usuario');
$session_first_letter = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self' data:;">
    <title>Dashboard - Sistema Biométrico</title>
    <style>
        :root {
            --pantone-blue: #003366;
            --pantone-blue-light: #004B8D;
            --pantone-gold: #B8860B;
            --pantone-gold-light: #DAA520;
            --pantone-slate: #4A5568;
            --pantone-slate-light: #718096;
            --pantone-gray: #E2E8F0;
            --pantone-gray-dark: #CBD5E0;
            --pantone-white: #FAFBFC;
            --pantone-success: #2E7D32;
            --pantone-danger: #C62828;
            --pantone-warning: #F57C00;
            --bg-light: #F7FAFC;
            --bg-dark: #1A202C;
            --sidebar-dark: #2D3748;
            --card-dark: #2D3748;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--pantone-slate);
            background-color: var(--bg-light);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%);
            padding: 25px 20px;
            color: white;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 51, 102, 0.15);
            transition: background 0.3s ease;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            pointer-events: none;
        }

        .sidebar h4 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 1px;
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.85);
            padding: 14px 18px;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: var(--pantone-gold);
            border-radius: 10px;
            transition: width 0.3s ease;
            z-index: -1;
        }

        .nav-link:hover {
            color: white;
            transform: translateX(5px);
        }

        .nav-link:hover::before {
            width: 100%;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }

        .nav-link .nav-icon {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .user-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            padding: 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, var(--pantone-gold) 0%, var(--pantone-gold-light) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            font-size: 1.6rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(184, 134, 11, 0.4);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 51, 102, 0.08);
            padding: 0;
            overflow: hidden;
            margin-bottom: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--pantone-gray);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 51, 102, 0.15);
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -12px;
            margin-left: -12px;
        }

        .col-4, .col-3, .col-8, .col-12 {
            padding: 0 12px;
            margin-bottom: 24px;
        }

        .col-3 { flex: 0 0 25%; max-width: 25%; }
        .col-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-12 { flex: 0 0 100%; max-width: 100%; }

        .stat-card {
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--pantone-blue) 0%, var(--pantone-gold) 100%);
        }

        .stat-card .card-body {
            padding: 2.2rem 1.5rem;
        }

        .stat-card:nth-child(1)::before { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
        .stat-card:nth-child(2)::before { background: linear-gradient(90deg, var(--pantone-success) 0%, #4CAF50 100%); }
        .stat-card:nth-child(3)::before { background: linear-gradient(90deg, var(--pantone-warning) 0%, #FF9800 100%); }
        .stat-card:nth-child(4)::before { background: linear-gradient(90deg, #9C27B0 0%, #E91E63 100%); }

        .stat-icon {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            display: inline-block;
            padding: 15px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(0, 51, 102, 0.1) 0%, rgba(184, 134, 11, 0.1) 100%);
        }

        .stat-number {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--pantone-blue);
            font-family: 'Roboto', sans-serif;
        }

        .stat-label {
            font-size: 0.95rem;
            color: var(--pantone-slate);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .stat-info {
            font-size: 0.8rem;
            color: var(--pantone-slate-light);
            background: var(--bg-light);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 51, 102, 0.25);
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .welcome-title {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            position: relative;
        }

        .welcome-subtitle {
            opacity: 0.95;
            font-size: 1.1rem;
            position: relative;
        }

        .section-title {
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
            color: var(--pantone-blue);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, var(--pantone-gold) 0%, var(--pantone-gold-light) 100%);
            border-radius: 2px;
        }

        .quick-action {
            text-align: center;
            padding: 2rem 1.5rem;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: block;
            background: white;
            border: 2px solid var(--pantone-gray);
            position: relative;
            overflow: hidden;
        }

        .quick-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--pantone-blue) 0%, var(--pantone-gold) 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 51, 102, 0.15);
            border-color: var(--pantone-blue);
        }

        .quick-action:hover::before {
            transform: scaleX(1);
        }

        .quick-action-icon {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            display: inline-block;
            padding: 15px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--bg-light) 0%, var(--pantone-gray) 100%);
        }

        .quick-action h5 {
            color: var(--pantone-blue);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .quick-action small {
            color: var(--pantone-slate-light);
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 1.2rem;
            background: var(--bg-light);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border-left: 3px solid var(--pantone-blue);
        }

        .timeline-item:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.1);
            transform: translateX(5px);
        }

        .timeline-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            min-width: 2rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--pantone-blue) 0%, var(--pantone-blue-light) 100%);
            border-radius: 10px;
            color: white;
        }

        .timeline-content h6 {
            margin-bottom: 0.25rem;
            color: var(--pantone-blue);
            font-weight: 600;
        }

        .timeline-content small {
            color: var(--pantone-slate-light);
        }

        .status-item {
            display: flex;
            align-items: center;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--pantone-gray);
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--pantone-success);
            margin-right: 0.85rem;
            position: relative;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2); }
            50% { box-shadow: 0 0 0 6px rgba(46, 125, 50, 0.1); }
        }

        .status-icon::before {
            content: '✓';
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.65rem;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            width: 100%;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-warning {
            background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%);
            border: 1px solid #FFE082;
            color: #F57C00;
        }

        .alert-icon {
            font-size: 1.5rem;
        }

        .theme-toggle {
            position: fixed;
            top: 25px;
            right: 25px;
            background: white;
            border: 2px solid var(--pantone-blue);
            border-radius: 50%;
            width: 52px;
            height: 52px;
            cursor: pointer;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.15);
        }

        .theme-toggle:hover {
            background: var(--pantone-blue);
            color: white;
            transform: scale(1.1);
        }

        body.dark-theme {
            background-color: var(--bg-dark);
            color: #E2E8F0;
        }

        body.dark-theme .card {
            background-color: var(--card-dark);
            color: #E2E8F0;
            border-color: #4A5568;
        }

        body.dark-theme .card-header {
            background: linear-gradient(135deg, #1A365D 0%, #2C5282 100%);
        }

        body.dark-theme .sidebar {
            background: linear-gradient(180deg, #1A365D 0%, #2C5282 100%);
        }

        body.dark-theme .welcome-section {
            background: linear-gradient(135deg, #1A365D 0%, #2C5282 100%);
        }

        body.dark-theme .timeline-item {
            background: #2D3748;
            color: #E2E8F0;
            border-left-color: var(--pantone-gold);
        }

        body.dark-theme .timeline-item:hover {
            background: #374151;
        }

        body.dark-theme .timeline-content h6 {
            color: #E2E8F0;
        }

        body.dark-theme .stat-number {
            color: #E2E8F0;
        }

        body.dark-theme .stat-label {
            color: #A0AEC0;
        }

        body.dark-theme .quick-action {
            background: var(--card-dark);
            border-color: #4A5568;
        }

        body.dark-theme .quick-action:hover {
            border-color: var(--pantone-gold);
        }

        body.dark-theme .quick-action h5 {
            color: #E2E8F0;
        }

        body.dark-theme .section-title {
            color: #E2E8F0;
        }

        body.dark-theme .stat-info {
            background: #4A5568;
            color: #A0AEC0;
        }

        body.dark-theme .status-item {
            color: #E2E8F0;
            border-bottom-color: #4A5568;
        }

        .mobile-menu-toggle {
            position: fixed;
            top: 25px;
            left: 25px;
            background: var(--pantone-blue);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            cursor: pointer;
            z-index: 2000;
            display: none;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.2);
        }

        .online-badge {
            background: linear-gradient(135deg, var(--pantone-success) 0%, #4CAF50 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
        }

        .version-info {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        body.dark-theme .version-info {
            background: #374151;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle { display: block; }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .col-3, .col-4, .col-8 { flex: 0 0 100%; max-width: 100%; }
            .theme-toggle { top: 15px; right: 15px; }
            .mobile-menu-toggle { top: 15px; left: 15px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <h4>👆 BioMetric</h4>
        
        <div>
            <a href="dashboard_final.php" class="nav-link active">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="empleados_final.php" class="nav-link">
                <span class="nav-icon">👥</span> Empleados
            </a>
            <a href="asistencia_final.php" class="nav-link">
                <span class="nav-icon">⏰</span> Asistencia
            </a>
            <a href="reportes_final.php" class="nav-link">
                <span class="nav-icon">📈</span> Reportes
            </a>
            <a href="dispositivos_final.php" class="nav-link">
                <span class="nav-icon">👆</span> Dispositivos
            </a>
            <a href="horarios_final.php" class="nav-link">
                <span class="nav-icon">📅</span> Horarios
            </a>
            <a href="justificaciones_final.php" class="nav-link">
                <span class="nav-icon">📄</span> Justificaciones
            </a>
            <a href="sanciones_final.php" class="nav-link">
                <span class="nav-icon">⚠️</span> Sanciones
            </a>
        </div>
        
        <div class="user-info">
            <div class="user-avatar"><?= $session_first_letter ?></div>
            <div><strong><?= $session_username ?></strong></div>
            <div style="font-size: 0.9rem; opacity: 0.8;"><?= $session_rol ?></div>
            <a href="logout_final.php" class="btn btn-logout">
                🚪 Cerrar Sesión
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="themeToggle" title="Cambiar Tema">
        🌙
    </button>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" title="Menú">
        ☰
    </button>
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <main class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-8">
                    <h1 class="welcome-title">
                        👋 ¡Bienvenido, <?= $session_username ?>!
                    </h1>
                    <p class="welcome-subtitle">
                        Panel de control del Sistema Biométrico | <?= date('d/m/Y H:i') ?>
                    </p>
                </div>
                <div class="col-4" style="text-align: right;">
                    <div class="online-badge">
                        🛡️ <?= $session_rol ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if(isset($error_message)): ?>
            <div class="alert alert-warning">
                <span class="alert-icon">ℹ️</span>
                <div>
                    <strong>Información:</strong> Usando datos de demostración. 
                    Error de conexión: <?= htmlspecialchars($error_message) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?= $empleados_total ?></div>
                        <div class="stat-label">Empleados Activos</div>
                        <div class="stat-info">📈 100% conectados</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?= $asistencias_hoy ?></div>
                        <div class="stat-label">Asistencias Hoy</div>
                        <div class="stat-info">📅 Registro actual</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">⏰</div>
                        <div class="stat-number"><?= $retardos_hoy ?></div>
                        <div class="stat-label">Retardos Hoy</div>
                        <div class="stat-info">⚠️ Control de puntualidad</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">👆</div>
                        <div class="stat-number">5</div>
                        <div class="stat-label">Dispositivos</div>
                        <div class="stat-info">✅ Todos operativos</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <h3 class="section-title">⚡ Acciones Rápidas</h3>
            </div>
            <div class="col-3">
                <a href="empleados_final.php" class="card quick-action">
                    <div class="quick-action-icon">👤</div>
                    <h5>Nuevo Empleado</h5>
                    <small>Registrar personal</small>
                </a>
            </div>
            <div class="col-3">
                <a href="asistencia_final.php" class="card quick-action">
                    <div class="quick-action-icon">⏰</div>
                    <h5>Registrar Asistencia</h5>
                    <small>Control de entrada/salida</small>
                </a>
            </div>
            <div class="col-3">
                <a href="reportes_final.php" class="card quick-action">
                    <div class="quick-action-icon">📈</div>
                    <h5>Generar Reporte</h5>
                    <small>Estadísticas y análisis</small>
                </a>
            </div>
            <div class="col-3">
                <a href="dispositivos_final.php" class="card quick-action">
                    <div class="quick-action-icon">👆</div>
                    <h5>Configurar Biometrico</h5>
                    <small>Gestión de dispositivos</small>
                </a>
            </div>
        </div>

        <!-- System Status & Activity -->
        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        📚 Actividad Reciente
                    </div>
                    <div class="card-body">
                        <div class="timeline-item">
                            <div class="timeline-icon">👤</div>
                            <div class="timeline-content">
                                <h6>Nuevo empleado registrado</h6>
                                <small>Hace 2 horas por <?= $session_username ?></small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-icon">⏰</div>
                            <div class="timeline-content">
                                <h6>Asistencia registrada</h6>
                                <small>Hace 3 horas - 15 empleados</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-icon">📈</div>
                            <div class="timeline-content">
                                <h6>Reporte generado</h6>
                                <small>Hace 5 horas - Reporte mensual</small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-icon">👆</div>
                            <div class="timeline-content">
                                <h6>Dispositivo sincronizado</h6>
                                <small>Hace 1 día - Biometrico #01</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        🖥️ Estado del Sistema
                    </div>
                    <div class="card-body">
                        <div class="status-item">
                            <div class="status-icon"></div>
                            <span>Base de datos conectada</span>
                        </div>
                        <div class="status-item">
                            <div class="status-icon"></div>
                            <span>Autenticación activa</span>
                        </div>
                        <div class="status-item">
                            <div class="status-icon"></div>
                            <span>Módulos funcionando</span>
                        </div>
                        <div class="status-item">
                            <div class="status-icon"></div>
                            <span>Seguridad configurada</span>
                        </div>
                        <hr style="margin: 1.5rem 0; border: 1px solid var(--pantone-gray);">
                        <div style="text-align: center;">
                            <div class="online-badge" style="margin-bottom: 1rem;">
                                ✅ Sistema Online
                            </div>
                            <div class="version-info">
                                <small style="color: var(--pantone-slate-light);">
                                    <div>🔖 Versión 1.0.0</div>
                                    <div>🖥️ Servidor: Localhost</div>
                                    <div>🕐 Última actualización: <?= date('d/m/Y H:i') ?></div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        function setTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
                themeToggle.textContent = '☀️';
            } else {
                document.body.classList.remove('dark-theme');
                themeToggle.textContent = '🌙';
            }
        }
        
        // Set initial theme
        setTheme(currentTheme);
        
        // Theme toggle functionality
        themeToggle.addEventListener('click', function() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            localStorage.setItem('theme', newTheme);
            setTheme(newTheme);
        });
        
        // Mobile Menu Toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            this.style.display = 'none';
        });
        
        // Close sidebar when clicking on a link (mobile)
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.style.display = 'none';
                }
            });
        });
        
        // Quick Actions functionality
        const quickActionCards = document.querySelectorAll('.quick-action');
        quickActionCards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                
                if (href && href !== '#') {
                    window.location.href = href;
                } else {
                    // Show alert for non-functional actions
                    const actionName = this.querySelector('h5').textContent;
                    showNotification(`${actionName} - Función en desarrollo`, 'info');
                }
            });
        });
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-icon">
                        ${type === 'info' ? 'ℹ️' : type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}
                    </span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'info' ? '#3498db' : type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Add notification styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .notification-icon {
                margin-right: 10px;
            }
            
            .notification-message {
                flex: 1;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                margin-left: 10px;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            
            body.dark-theme .notification {
                background: linear-gradient(135deg, #2D3748 0%, #1A202C 100%) !important;
                border: 1px solid #4A5568;
            }
            
            body.dark-theme .notification-info {
                border-left: 4px solid #667eea;
            }
            
            body.dark-theme .notification-success {
                border-left: 4px solid #48BB78;
            }
            
            body.dark-theme .notification-error {
                border-left: 4px solid #F56565;
            }
        `;
        document.head.appendChild(style);
        
        // Add mobile menu styles
        const mobileStyle = document.createElement('style');
        mobileStyle.textContent = `
            @media (max-width: 768px) {
                .sidebar-overlay {
                    display: none;
                }
            }
        `;
        document.head.appendChild(mobileStyle);
        
        // Update active nav link based on current page
        const currentPage = window.location.pathname.split('/').pop();
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Real-time clock update
        function updateClock() {
            const clockElements = document.querySelectorAll('[data-clock]');
            clockElements.forEach(element => {
                element.textContent = new Date().toLocaleTimeString();
            });
        }
        
        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock();
        
        // Dynamic welcome message based on time of day
        function updateWelcomeMessage() {
            const hour = new Date().getHours();
            const welcomeTitle = document.querySelector('.welcome-title');
            const timeGreeting = hour < 12 ? '¡Buenos días' : hour < 18 ? '¡Buenas tardes' : '¡Buenas noches';
            
            if (welcomeTitle) {
                welcomeTitle.innerHTML = `👋 ${timeGreeting}, ${session_username}!`;
            }
        }
        
        updateWelcomeMessage();
        setInterval(updateWelcomeMessage, 60000); // Update every minute
        
        // Stat card animations
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + T for theme toggle
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                themeToggle.click();
            }
            
            // Alt + M for mobile menu toggle
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                mobileMenuToggle.click();
            }
            
            // Escape to close mobile menu
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                sidebarOverlay.style.display = 'none';
            }
        });
        
        // Sidebar hover effects
        sidebar.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768 && !sidebar.classList.contains('show')) {
                this.style.transform = 'translateX(0)';
            }
        });
        
        sidebar.addEventListener('mouseleave', function() {
            if (window.innerWidth > 768 && !sidebar.classList.contains('show')) {
                this.style.transform = '';
            }
        });
        
        // Performance monitoring
        console.log('Dashboard loaded successfully');
        console.log('Page load time:', performance.now() + 'ms');
    </script>
</body>
</html>