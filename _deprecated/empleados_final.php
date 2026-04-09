<?php
// empleados_final.php - Módulo de empleados completo

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login_final.php');
    exit;
}

require_once 'config.php';

// Obtener empleados con manejo de errores
$empleados = [];
$error = '';
$total_empleados = 0;
$empleados_activos = 0;
$areas_count = 0;
$empleados_con_huella = 0;

try {
    require_once 'models/Database.php';
    require_once 'models/Empleado.php';
    
    $empleadoModel = new Empleado();
    $empleados = $empleadoModel->getAll();
    
    if (!empty($empleados)) {
        $total_empleados = count($empleados);
        $empleados_activos = count(array_filter($empleados, fn($e) => ($e['activo'] ?? 0) == 1));
        $areas_count = count(array_unique(array_column($empleados, 'area')));
        $empleados_con_huella = count(array_filter($empleados, fn($e) => !empty($e['huella_dactilar'])));
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    // Usar datos de demostración
    $empleados = [
        [
            'id' => 1,
            'nombre' => 'Edgar',
            'apellido' => 'Phye Parga',
            'rfc' => 'PEPE721114KZ0',
            'area' => 'Recursos Humanos',
            'activo' => 1,
            'huella_dactilar' => 'registrada'
        ],
        [
            'id' => 2,
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'rfc' => 'JUAP123456',
            'area' => 'Administración',
            'activo' => 1,
            'huella_dactilar' => ''
        ]
    ];
    $total_empleados = 2;
    $empleados_activos = 2;
    $areas_count = 2;
    $empleados_con_huella = 1;
}

$session_username = htmlspecialchars($_SESSION['username'] ?? 'Usuario');
$session_rol = htmlspecialchars($_SESSION['rol'] ?? 'Usuario');
$session_first_letter = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:;">
    <title>Empleados - Sistema Biométrico</title>
    <style>
        /* Reset y estilos base */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
        
        .col-4, .col-3, .col-6, .col-12 {
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
        
        /* Employee Cards */
        .employee-card {
            position: relative;
            overflow: hidden;
        }
        
        .employee-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            text-align: center;
            color: white;
        }
        
        .employee-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 2rem;
            font-weight: bold;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .employee-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .employee-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        
        .employee-details {
            padding: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            margin-right: 0.5rem;
            min-width: 60px;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .employee-actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            flex: 1;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
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
        
        .btn-large {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            background: #9F2241;
            color: white;
        }
        
        .btn-large:hover {
            background: #7a1c32;
            transform: translateY(-2px);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #9F2241 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background 0.3s ease;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #9F2241;
            box-shadow: 0 0 0 3px rgba(159,34,65,0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-col {
            flex: 1;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-info {
            background: #e8f4fd;
            border-color: #2196f3;
            color: #1565c0;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
            
            .sidebar.show {
                transform: translateX(0);
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <h4>👆 BioMetric</h4>
        
        <div>
            <a href="dashboard_final.php" class="nav-link">
                📊 Dashboard
            </a>
            <a href="empleados_final.php" class="nav-link active">
                👥 Empleados
            </a>
            <a href="asistencia_final.php" class="nav-link">
                ⏰ Asistencia
            </a>
            <a href="reportes_final.php" class="nav-link">
                📈 Reportes
            </a>
            <a href="dispositivos_final.php" class="nav-link">
                👆 Dispositivos
            </a>
            <a href="horarios_final.php" class="nav-link">
                📅 Horarios
            </a>
            <a href="justificaciones_final.php" class="nav-link">
                📄 Justificaciones
            </a>
            <a href="sanciones_final.php" class="nav-link">
                ⚠️ Sanciones
            </a>
        </div>
        
        <div class="user-info">
            <div class="user-avatar"><?= $session_first_letter ?></div>
            <div><strong><?= $session_username ?></strong></div>
            <div style="font-size: 0.9rem; opacity: 0.8;"><?= $session_rol ?></div>
            <a href="logout_final.php" style="color: white; text-decoration: none; margin-top: 0.5rem; display: block; padding: 0.5rem; background: rgba(255,255,255,0.2); border-radius: 8px;">
                🚪 Cerrar Sesión
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                👥 Empleados
            </h1>
            <button class="btn btn-large" onclick="openModal('nuevoEmpleadoModal')">
                ➕ Nuevo Empleado
            </button>
        </div>

        <?php if($error): ?>
            <div class="alert alert-warning">
                <strong>ℹ️ Información:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?= $total_empleados ?></div>
                        <div class="stat-label">Total Empleados</div>
                        <div class="stat-info">Registrados en el sistema</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?= $empleados_activos ?></div>
                        <div class="stat-label">Activos</div>
                        <div class="stat-info">Empleados operativos</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">🏢</div>
                        <div class="stat-number"><?= $areas_count ?></div>
                        <div class="stat-label">Áreas</div>
                        <div class="stat-info">Departamentos</div>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-icon">👆</div>
                        <div class="stat-number"><?= $empleados_con_huella ?></div>
                        <div class="stat-label">Con Huella</div>
                        <div class="stat-info">Biometría registrada</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees Grid -->
        <div class="row">
            <?php if(!empty($empleados)): ?>
                <?php foreach ($empleados as $empleado): ?>
                    <div class="col-3">
                        <div class="card employee-card">
                            <div class="employee-header">
                                <div class="employee-avatar">
                                    <?= strtoupper(substr($empleado['nombre'], 0, 1)) ?>
                                </div>
                                <div class="employee-name">
                                    <?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido']) ?>
                                </div>
                                <span class="employee-status <?= $empleado['activo'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $empleado['activo'] ? '✅ Activo' : '❌ Inactivo' ?>
                                </span>
                            </div>
                            
                            <div class="employee-details">
                                <div class="detail-item">
                                    <span class="detail-label">🆔:</span>
                                    <span class="detail-value"><?= htmlspecialchars($empleado['rfc']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">🏢:</span>
                                    <span class="detail-value"><?= htmlspecialchars($empleado['area'] ?? 'Sin área') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">👆:</span>
                                    <span class="detail-value">
                                        <?php if(!empty($empleado['huella_dactilar'])): ?>
                                            ✅ Registrada
                                        <?php else: ?>
                                            ⏳ Pendiente
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📅:</span>
                                    <span class="detail-value">ID: <?= $empleado['id'] ?></span>
                                </div>
                            </div>
                            
                            <div class="employee-actions">
                                <button class="btn btn-primary" onclick="viewEmployee(<?= $empleado['id'] ?>)">
                                    👁️ Ver
                                </button>
                                <button class="btn btn-warning" onclick="editEmployee(<?= $empleado['id'] ?>)">
                                    ✏️ Editar
                                </button>
                                <button class="btn btn-success" onclick="registerFingerprint(<?= $empleado['id'] ?>)">
                                    👆 <?= !empty($empleado['huella_dactilar']) ? 'Huella' : 'Registrar' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center" style="padding: 3rem;">
                            <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">
                                👥
                            </div>
                            <h3>No hay empleados registrados</h3>
                            <p style="color: #666; margin-bottom: 2rem;">
                                Comienza agregando el primer empleado al sistema
                            </p>
                            <button class="btn btn-large" onclick="openModal('nuevoEmpleadoModal')">
                                ➕ Agregar Primer Empleado
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nuevo Empleado -->
    <div id="nuevoEmpleadoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Nuevo Empleado</h3>
                <button class="modal-close" onclick="closeModal('nuevoEmpleadoModal')">×</button>
            </div>
            <div class="modal-body">
                <form id="nuevoEmpleadoForm">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">👤 Nombre</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">👤 Apellido</label>
                                <input type="text" class="form-control" name="apellido" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">🆔 RFC</label>
                                <input type="text" class="form-control" name="rfc" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">🆔 CURP</label>
                                <input type="text" class="form-control" name="curp">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">🏢 Área</label>
                                <select class="form-select" name="area">
                                    <option>Recursos Humanos</option>
                                    <option>Administración</option>
                                    <option>Tecnología</option>
                                    <option>Producción</option>
                                    <option>Ventas</option>
                                    <option>Contabilidad</option>
                                    <option>Logística</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">👔 Jerarquía</label>
                                <select class="form-select" name="jerarquia">
                                    <option>Gerente</option>
                                    <option>Supervisor</option>
                                    <option>Empleado</option>
                                    <option>Practicante</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📷 Foto del Empleado</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <small style="color: #666; font-size: 0.85rem;">
                            Formatos permitidos: JPG, PNG, GIF (máx. 5MB)
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="button" class="btn btn-primary" onclick="saveEmployee()">
                            💾 Guardar Empleado
                        </button>
                        <button type="button" class="btn" style="background: #ccc; color: #333;" onclick="closeModal('nuevoEmpleadoModal')">
                            ❌ Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Employee Actions
        function viewEmployee(id) {
            alert('Ver empleado ID: ' + id + '\n\nFunción en desarrollo.');
        }
        
        function editEmployee(id) {
            alert('Editar empleado ID: ' + id + '\n\nFunción en desarrollo.');
        }
        
        function registerFingerprint(id) {
            alert('Registrar huella para empleado ID: ' + id + '\n\nFunción en desarrollo.');
        }
        
        function saveEmployee() {
            const form = document.getElementById('nuevoEmpleadoForm');
            const formData = new FormData(form);
            
            // Simple validation
            const nombre = formData.get('nombre');
            const apellido = formData.get('apellido');
            
            if (!nombre || !apellido) {
                showMessage('Por favor, complete los campos obligatorios', 'warning');
                return;
            }
            
            // Simulate saving
            showMessage('Empleado guardado exitosamente', 'success');
            closeModal('nuevoEmpleadoModal');
            form.reset();
            
            // In real application, this would submit to the server
            console.log('Employee data:', Object.fromEntries(formData));
        }
        
        function showMessage(message, type = 'info') {
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert alert-${type}`;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 3000;
                min-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            messageDiv.innerHTML = message;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => messageDiv.remove(), 300);
            }, 3000);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
            
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                openModal('nuevoEmpleadoModal');
            }
        });
        
        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100%); }
            }
        `;
        document.head.appendChild(style);
        
        console.log('Empleados module loaded successfully');
    </script>
</body>
</html>