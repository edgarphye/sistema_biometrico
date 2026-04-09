<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'helpers/Csrf.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

// Determinar acción
$action = $_GET['action'] ?? '';

if ($action === 'empleados') {
    require_once 'controllers/ValidacionJefeController.php';
    $controller = new ValidacionJefeController();
    
    // Obtener empleado_id del jefe
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, DB_OPTIONS);
    $stmt_usuario = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
    $stmt_usuario->execute([$_SESSION['user_id']]);
    $empleado_id_jefe = $stmt_usuario->fetchColumn();
    
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getEmpleadosACargo');
    $method->setAccessible(true);
    $empleados = $method->invoke($controller, $empleado_id_jefe);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'empleados' => $empleados]);
    
} elseif ($action === 'validaciones') {
    // CSRF validation desactivada temporalmente
    $csrfToken = $_POST['csrf_token'] ?? '';
    // if (empty($csrfToken)) {
    //     die(json_encode(['success' => false, 'error' => 'CSRF token requerido']));
    // }
    
    require_once 'controllers/ValidacionJefeController.php';
    $controller = new ValidacionJefeController();
    
    $filters = [
        'area' => $_POST['area'] ?? null,
        'empleado_id' => $_POST['empleado'] ?? null,
        'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
        'fecha_fin' => $_POST['fecha_fin'] ?? null,
        'tipo_incidencia' => $_POST['tipo_incidencia'] ?? null
    ];
    
    $pagina = $_POST['pagina'] ?? 1;
    $limite = $_POST['limite'] ?? 25;
    
    require_once 'models/ValidacionJefe.php';
    $model = new ValidacionJefe();
    $validaciones = $model->getPendientesPorJefe($_SESSION['user_id'], $filters);
    
    $total = count($validaciones);
    $validacionesPaginadas = array_slice($validaciones, ($pagina - 1) * $limite, $limite);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'validaciones' => $validacionesPaginadas,
        'total' => $total,
        'pagina' => $pagina,
        'limite' => $limite
    ]);
    
} elseif ($action === 'estadisticas') {
    // Endpoint para estadísticas
    require_once 'controllers/ValidacionJefeController.php';
    $controller = new ValidacionJefeController();
    
    // Simular estadísticas básicas
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, DB_OPTIONS);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM validaciones_jefe WHERE jefe_id = ? AND estado = 'pendiente'");
    $stmt->execute([$_SESSION['user_id']]);
    $total_pendientes = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM validaciones_jefe vj 
                           JOIN retardos r ON vj.incidencia_id = r.id 
                           WHERE vj.jefe_id = ? AND vj.estado = 'pendiente' AND vj.tipo_incidencia = 'retardo'");
    $stmt->execute([$_SESSION['user_id']]);
    $retardos = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM validaciones_jefe vj 
                           JOIN comisiones c ON vj.incidencia_id = c.id 
                           WHERE vj.jefe_id = ? AND vj.estado = 'pendiente' AND vj.tipo_incidencia = 'comision'");
    $stmt->execute([$_SESSION['user_id']]);
    $comisiones = $stmt->fetchColumn();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'estadisticas' => [
            'total_pendientes' => (int)$total_pendientes,
            'retardos' => (int)$retardos,
            'comisiones' => (int)$comisiones,
            'dias_economicos' => 0,
            'ausencias' => 0,
            'aprobados_hoy' => 0,
            'rechazados_hoy' => 0
        ]
    ]);
    
} elseif ($action === 'incidencias-empleados') {
    // Endpoint para incidencias de empleados seleccionados
    require_once 'controllers/ValidacionJefeController.php';
    $controller = new ValidacionJefeController();
    
    $empleados_ids = $_POST['empleados_ids'] ?? [];
    $solo_pendientes = $_POST['solo_pendientes'] ?? true;
    $filtros = $_POST;
    $pagina = $_POST['pagina'] ?? 1;
    $limite = $_POST['limite'] ?? 25;
    
    require_once 'models/ValidacionJefe.php';
    $model = new ValidacionJefe();
    
    // Filtrar por empleados seleccionados
    $all_validaciones = $model->getPendientesPorJefe($_SESSION['user_id'], []);
    
    if (!empty($empleados_ids)) {
        $validaciones_filtradas = array_filter($all_validaciones, function($v) use ($empleados_ids) {
            return in_array($v['empleado_id'], $empleados_ids);
        });
    } else {
        $validaciones_filtradas = $all_validaciones;
    }
    
    $total = count($validaciones_filtradas);
    $offset = ($pagina - 1) * $limite;
    $validaciones_paginadas = array_slice($validaciones_filtradas, $offset, $limite);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'incidencias' => $validaciones_paginadas,
        'total' => $total
    ]);
    
} elseif ($action === 'areas') {
    // Endpoint para áreas disponibles
    require_once 'controllers/ValidacionJefeController.php';
    $controller = new ValidacionJefeController();
    
    // Obtener empleado_id del jefe
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, DB_OPTIONS);
    $stmt_usuario = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ?");
    $stmt_usuario->execute([$_SESSION['user_id']]);
    $empleado_id_jefe = $stmt_usuario->fetchColumn();
    
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getAreasDisponibles');
    $method->setAccessible(true);
    $areas = $method->invoke($controller, $empleado_id_jefe);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'areas' => $areas]);
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>