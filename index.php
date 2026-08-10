<?php
// index.php - Front Controller
error_log("=== index.php START ===");

// Load config immediately for DB access
require_once __DIR__ . '/config.php';
error_log("config loaded");

// Check for API request BEFORE session check
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// API handling - execute EARLY before session check
// Match both /api/ and /sistema_biometrico/api/
$apiMatch = null;
if (strpos($rawPath, '/sistema_biometrico/api/') === 0) {
    $apiMatch = '/sistema_biometrico/api/';
} elseif (strpos($rawPath, '/api/') === 0) {
    $apiMatch = '/api/';
}

if ($apiMatch !== null) {
    $apiRelPath = str_replace($apiMatch, '', $rawPath);
    $apiFile = __DIR__ . '/api/' . $apiRelPath . '.php';
    error_log("API: rawPath=$rawPath, apiRelPath=$apiRelPath, file=$apiFile");
    
    if (!empty($apiRelPath) && file_exists($apiFile)) {
        header('Content-Type: application/json; charset=utf-8');
        require_once $apiFile;
        exit;
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API not found: ' . $rawPath]);
    exit;
}

$uri = $rawPath;

// Continue with normal loading
error_log("Loading Controllers");
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/Csrf.php';
require_once __DIR__ . '/controllers/BaseController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ValidacionJefeController.php';
require_once __DIR__ . '/controllers/LogsController.php';

error_log("Controllers loaded");

// Funciones para manejo de sesiones en base de datos
function db_session_open($save_path, $session_name) {
    return true;
}

function db_session_close() {
    return true;
}

function db_session_read($id) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, DB_OPTIONS);
    $stmt = $pdo->prepare("SELECT data FROM sessions WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetchColumn();
    return $result ?: '';
}

function db_session_write($id, $data) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, DB_OPTIONS);
    $timestamp = time();
    $stmt = $pdo->prepare("REPLACE INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
    return $stmt->execute([$id, $data, $timestamp]);
}

function db_session_destroy($id) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, DB_OPTIONS);
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
    return $stmt->execute([$id]);
}

function db_session_gc($maxlifetime) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, DB_OPTIONS);
    $old = time() - $maxlifetime;
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE timestamp < ?");
    return $stmt->execute([$old]);
}

session_set_save_handler(
    'db_session_open',
    'db_session_close',
    'db_session_read',
    'db_session_write',
    'db_session_destroy',
    'db_session_gc'
);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Establecer headers de seguridad (CSP, X-Frame-Options, etc.)
require_once __DIR__ . '/helpers/SecurityHelper.php';
SecurityHelper::setSecurityHeaders();

// 2. Definición del despachador de rutas
$dispatcher = require 'routes.php';

// Obtener la URI y el método de la peticiones
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Extraer el path base para sistema biométrico
$uri = $rawPath;
$basePathUrl = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
$basePath = rtrim($basePathUrl, '/');

if (!empty($basePath) && strpos($rawPath, $basePath) === 0) {
    $uri = substr($rawPath, strlen($basePath));
    if (empty($uri)) { $uri = '/'; }
}

// Excluir archivos estáticos de la verificación de sesión
$staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot', '.map'];
$isStaticFile = false;
foreach ($staticExtensions as $ext) {
    if (str_ends_with($rawPath, $ext)) {
        $isStaticFile = true;
        break;
    }
}

// Debug logging
error_log("RawPath: $rawPath, URI: $uri, Method: $httpMethod");

// 3. Verificar autenticación (excepto para rutas públicas)
$rutasPublicas = [
    '/login', '/register', '/verify-2fa', 
    '/api/', '/api/tipos-justificacion', '/api/log-error',
    '/api/lista-empleados', '/api/obtener_registros_justificacion',
    '/api/actualizar_justificacion'
];

// Fin de la verificación de API - otras verificaciones se hacen abajo
if ($httpMethod === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $jsonInput = file_get_contents('php://input');
    $jsonData = json_decode($jsonInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
        $_POST = array_merge($_POST, $jsonData);
    }
}

// Verificar estado de sesión (usando AuthController o fallback directo)
if (class_exists('AuthController') && method_exists('AuthController', 'isLoggedIn')) {
    $isLoggedIn = AuthController::isLoggedIn();
} else {
    $isLoggedIn = isset($_SESSION['user_id']);
}

// Rutas API que deben devolver JSON en lugar de redirect cuando no hay sesión
$jsonOnlyRoutes = [
    '/ai/analizar', '/ai/metricas', '/ai/alertas', '/ai/reportes',
    '/logs/leer', '/logs/analizar-ai', '/logs/aplicar-fix',
    '/menu-config/guardar', '/menu-config/getConfig',
    '/usuarios/guardar-menu'
];

// Verificar si la ruta requiere autenticación (excluir archivos estáticos)
if (!$isStaticFile && !in_array($uri, $rutasPublicas) && !$isLoggedIn) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $isJsonRoute = false;
    foreach ($jsonOnlyRoutes as $route) {
        if (strpos($uri, $route) === 0) {
            $isJsonRoute = true;
            break;
        }
    }
    if ($isAjax || $isJsonRoute || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Sesión expirada', 'redirect' => '/login']);
        exit;
    }
    header('Location: ' . $basePath . '/login');
    exit;
}

// Verificar si es una ruta API que necesita servirse directamente desde archivo
if (strpos($uri, '/api/') === 0) {
    $apiFile = __DIR__ . str_replace('/sistema_biometrico', '', $uri);
    if (file_exists($apiFile)) {
        require_once $apiFile;
        exit;
    }
}

// 4. Despachar la ruta
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 5. Manejar el resultado del despachador
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 'Página no encontrada';
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo 'Método no permitido. Métodos permitidos: ' . implode(', ', $allowedMethods);
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        
        // Si es una Closure (ruta API), ejecutarla directamente
        if ($handler instanceof Closure) {
            $handler();
            exit;
        }
        
        $controllerName = $handler[0];
        $methodName = $handler[1];

// Validación CSRF para endpoints AJAX
        $isExcluded = str_contains($uri, '/login') || str_contains($uri, '/register') || str_contains($uri, '/validaciones/') || str_contains($uri, '/biometricos/') || str_contains($uri, '/catalogos/') || str_contains($uri, '/usuarios/') || str_contains($uri, '/justificaciones/') || str_contains($uri, '/logs/') || str_contains($uri, '/menu-config/') || str_contains($uri, '/permisos-menu') || str_contains($uri, '/api/');
        
        if ($httpMethod === 'POST' && !$isExcluded) {
            $csrfToken = $_POST['csrf_token'] ?? $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Csrf::validate($csrfToken)) {
                http_response_code(403);
                echo 'CSRF token inválido o ausente.';
                exit;
            }
        }
    
        //}
        // Cargar y ejecutar el controlador
        $controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';
        if (file_exists($controllerFile)) {
            try {
                require_once $controllerFile;
                
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    
                    if (method_exists($controller, $methodName)) {
                        // Llamar al método del controlador con los parámetros de la ruta
                        call_user_func_array([$controller, $methodName], array_values($vars));
                    } else {
                        throw new Exception("El método '$methodName' no existe en el controlador '$controllerName'.");
                    }
                } else {
                    throw new Exception("La clase '$controllerName' no fue encontrada.");
                }
            } catch (Throwable $e) {
                // Capturar cualquier error en la ejecución del controlador para que no falle fatalmente
                http_response_code(500);
                error_log("Error en ruta $uri: " . $e->getMessage());
                
                // Detectar si es una petición AJAX o espera JSON
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                $wantsJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
                $isApiRoute = strpos($uri, '/configuracion/') !== false || strpos($uri, '/api/') !== false;

                if ($isAjax || $wantsJson || $isApiRoute) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                } else {
                    // Mostrar detalles del error para depuración (HTML)
                    echo "<h1>Error Interno (500)</h1>";
                    echo "<p>Ocurrió un error al procesar su solicitud.</p>";
                    echo "<hr>";
                    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><strong>Ubicación:</strong> " . htmlspecialchars($e->getFile()) . " en línea " . $e->getLine() . "</p>";
                    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                }
            }

        } else {
            http_response_code(500);
            echo "Error: No se encontró el archivo del controlador '$controllerName'.";
        }
       break;

}