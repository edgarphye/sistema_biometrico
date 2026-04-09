<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'helpers/Csrf.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'error' => 'No autorizado'], 401);
}

// CSRF validation para POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!Csrf::validate($csrfToken)) {
        jsonResponse(['success' => false, 'error' => 'CSRF token inválido'], 403);
    }
}

// Route handling via query parameter
$action = $_GET['action'] ?? '';
if (empty($action)) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = rtrim(BASE_URL, '/');
    if (!empty($basePath) && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
    // Si después de quitar el subdirectorio no queda nada, es la raíz.
    if (empty($path)) { $path = '/'; }
} else {
    switch ($action) {
        case 'empleados':
            $path = '/empleados-ajax';
            break;
        case 'validaciones':
            $path = '/validaciones-ajax';
            break;
        default:
            $path = '/unknown';
            break;
    }
}

function jsonResponse($data, $status = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

switch ($path) {
    case '/empleados-ajax':
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
        jsonResponse(['success' => true, 'empleados' => $empleados]);
        
    case '/validaciones-ajax':
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
        
        jsonResponse([
            'success' => true,
            'validaciones' => $validacionesPaginadas,
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite
        ]);
        
    default:
        jsonResponse(['success' => false, 'error' => 'Endpoint no encontrado'], 404);
}
?>