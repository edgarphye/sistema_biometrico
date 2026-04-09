<?php
require_once __DIR__ . '/config.php';

echo "=== VERIFICACIÓN PARA ACCESO DESDE 127.0.0.1:8080 ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // 1. Verificar configuración BASE_URL para localhost/127.0.0.1
    echo "\n--- Verificando BASE_URL ---\n";
    echo "BASE_URL actual: '" . BASE_URL . "'\n";
    
    // Detectar si estamos accediendo desde 127.0.0.1
    $server_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    echo "HTTP_HOST detectado: '$server_host'\n";
    
    if (strpos($server_host, '127.0.0.1') !== false) {
        echo "✅ Acceso detectado desde 127.0.0.1\n";
        $base_url_correcta = 'http://127.0.0.1:8080';
    } else {
        echo "ℹ️ Acceso desde localhost\n";
        $base_url_correcta = 'http://localhost:8080';
    }
    
    echo "URL base correcta esperada: '$base_url_correcta'\n";
    
    // 2. Verificar problemas comunes con BASE_URL
    echo "\n--- Posibles problemas con BASE_URL ---\n";
    
    if (BASE_URL !== '') {
        echo "⚠️ BASE_URL no está vacía (puede causar problemas con rutas relativas)\n";
        echo "   Valor actual: '" . BASE_URL . "'\n";
        echo "   Se recomienda dejar BASE_URL vacía para localhost/127.0.0.1\n";
    } else {
        echo "✅ BASE_URL está vacía (correcto para localhost)\n";
    }
    
    // 3. Verificar URLs generadas en las vistas
    echo "\n--- Verificando URLs en vistas ---\n";
    
    // Revisar el archivo show.php para URLs absolutas
    $show_file = __DIR__ . '/views/empleados/show.php';
    if (file_exists($show_file)) {
        $show_content = file_get_contents($show_file);
        
        if (strpos($show_content, 'BASE_URL . \'/empleados\'') !== false) {
            echo "✅ URLs en show.php parecen correctas\n";
        } else {
            echo "⚠️ Posible problema con URLs en show.php\n";
        }
        
        // Buscar URLs hardcoded o incorrectas
        if (preg_match('/href=[\'"](?!http|https|\s*\/)/', $show_content, $matches)) {
            echo "⚠️ Se encontraron URLs relativas que pueden causar problemas:\n";
            foreach ($matches as $match) {
                echo "   - $match\n";
            }
        }
    }
    
    // Revisar el archivo edit.php para URLs
    $edit_file = __DIR__ . '/views/empleados/edit.php';
    if (file_exists($edit_file)) {
        $edit_content = file_get_contents($edit_file);
        
        if (strpos($edit_content, 'BASE_URL . \'/empleados\'') !== false) {
            echo "✅ URLs en edit.php parecen correctas\n";
        } else {
            echo "⚠️ Posible problema con URLs en edit.php\n";
        }
    }
    
    // 4. Verificar JavaScript y rutas absolutas
    echo "\n--- Verificando JavaScript y enlaces ---\n";
    
    // Revisar empleados.js para manejo de BASE_URL
    $empleados_js = __DIR__ . '/assets/js/empleados.js';
    if (file_exists($empleados_js)) {
        $js_content = file_get_contents($empleados_js);
        
        // Buscar uso de BASE_URL en JavaScript
        if (strpos($js_content, 'BASE_URL') !== false) {
            echo "ℹ️ JavaScript usa BASE_URL\n";
            echo "   Verificar que BASE_URL esté disponible en JavaScript\n";
        }
        
        // Buscar fetch o requests
        if (strpos($js_content, 'fetch(') !== false) {
            echo "✅ JavaScript usa fetch para llamadas AJAX\n";
        }
    }
    
    // 5. Probar URLs específicas
    echo "\n--- Probando URLs específicas para 127.0.0.1:8080 ---\n";
    
    // Obtener un empleado real para pruebas
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM empleados LIMIT 1");
    $empleado_test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($empleado_test) {
        $emp_id = $empleado_test['id'];
        echo "📋 Empleado para pruebas: ID $emp_id - {$empleado_test['nombre']} {$empleado_test['apellido']}\n";
        
        echo "\n🔗 URLs que deberían funcionar en 127.0.0.1:8080:\n";
        echo "   1. Lista empleados: http://127.0.0.1:8080/empleados\n";
        echo "   2. Ver empleado: http://127.0.0.1:8080/empleados/$emp_id\n";
        echo "   3. Editar empleado: http://127.0.0.1:8080/empleados/$emp_id/edit\n";
        
        // Simular las rutas
        echo "\n--- Simulando rutas con FastRoute ---\n";
        
        $routes_file = __DIR__ . '/routes.php';
        if (file_exists($routes_file)) {
            include $routes_file;
            
            $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
                // Rutas de empleados
                $r->addRoute('GET', '/empleados/{id:\d+}', ['EmpleadoController', 'show']);
                $r->addRoute('GET', '/empleados/{id:\d+}/edit', ['EmpleadoController', 'edit']);
                $r->addRoute('GET', '/empleados', ['EmpleadoController', 'index']);
            });
            
            // Probar rutas específicas
            $httpMethod = 'GET';
            $uri = "/empleados/$emp_id";
            
            $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
            
            switch ($routeInfo[0]) {
                case FastRoute\Dispatcher::FOUND:
                    echo "✅ Ruta /empleados/$emp_id encontrada\n";
                    echo "   Controller: {$routeInfo[1][0]} - Método: {$routeInfo[1][1]}\n";
                    break;
                case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    echo "❌ Método no permitido para /empleados/$emp_id\n";
                    break;
                case FastRoute\Dispatcher::NOT_FOUND:
                    echo "❌ Ruta /empleados/$emp_id no encontrada\n";
                    break;
            }
            
            // Probar ruta de edición
            $uri_edit = "/empleados/$emp_id/edit";
            $routeInfoEdit = $dispatcher->dispatch($httpMethod, $uri_edit);
            
            switch ($routeInfoEdit[0]) {
                case FastRoute\Dispatcher::FOUND:
                    echo "✅ Ruta /empleados/$emp_id/edit encontrada\n";
                    echo "   Controller: {$routeInfoEdit[1][0]} - Método: {$routeInfoEdit[1][1]}\n";
                    break;
                case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    echo "❌ Método no permitido para /empleados/$emp_id/edit\n";
                    break;
                case FastRoute\Dispatcher::NOT_FOUND:
                    echo "❌ Ruta /empleados/$emp_id/edit no encontrada\n";
                    break;
            }
        }
    } else {
        echo "⚠️ No hay empleados para probar\n";
    }
    
    // 6. Verificar configuración de Apache/Nginx
    echo "\n--- Configuración del Servidor Web ---\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'No definido') . "\n";
    echo "SERVER_PORT: " . ($_SERVER['SERVER_PORT'] ?? 'No definido') . "\n";
    echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'No definido') . "\n";
    echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'No definido') . "\n";
    echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'No definido') . "\n";
    
    // 7. Recomendaciones específicas
    echo "\n=== RECOMENDACIONES ESPECÍFICAS ===\n";
    
    if ($server_host === '127.0.0.1') {
        echo "🔧 Para acceso desde 127.0.0.1:8080:\n";
        echo "1. Verifica que BASE_URL esté vacía en config.php\n";
        echo "2. Inicia sesión en: http://127.0.0.1:8080/login\n";
        echo "3. Usa las URLs completas: http://127.0.0.1:8080/empleados\n";
        echo "4. Los botones deberían generar URLs absolutas\n";
    }
    
    echo "\n🔍 Diagnóstico completado\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>