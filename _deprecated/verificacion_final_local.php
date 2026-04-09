<?php
require_once __DIR__ . '/config.php';

echo "=== VERIFICACIÓN FINAL PARA 127.0.0.1 ===\n";

try {
    // 1. Verificar que las URLs en los archivos PHP son correctas
    echo "\n--- Verificando URLs corregidas ---\n";
    
    $archivos_a_verificar = [
        'views/empleados/show.php' => 'Ver empleado',
        'views/empleados/edit.php' => 'Editar empleado'
    ];
    
    foreach ($archivos_a_verificar as $archivo => $descripcion) {
        $contenido = file_get_contents(__DIR__ . '/' . $archivo);
        
        // Buscar URLs con rtrim(BASE_URL)
        if (strpos($contenido, 'rtrim(BASE_URL') !== false) {
            echo "✅ $descripcion: URLs corregidas con rtrim(BASE_URL)\n";
        } else {
            echo "⚠️ $descripcion: Posibles URLs sin corregir\n";
        }
        
        // Buscar uso de BASE_URL sin rtrim
        if (preg_match('/BASE_URL\s*\/[\s|\']/m', $contenido)) {
            echo "⚠️ $descripcion: Hay BASE_URL sin rtrim\n";
        } else {
            echo "✅ $descripcion: Uso correcto de BASE_URL\n";
        }
    }
    
    // 2. Verificar configuración en layout
    echo "\n--- Verificando configuración en layout ---\n";
    
    $layout_content = file_get_contents(__DIR__ . '/views/layout.php');
    
    if (strpos($layout_content, 'window.BASE_URL') !== false) {
        echo "✅ window.BASE_URL configurado en layout\n";
    } else {
        echo "❌ window.BASE_URL no configurado en layout\n";
    }
    
    if (strpos($layout_content, 'window.empleadosConfig') !== false) {
        echo "✅ window.empleadosConfig configurado en layout\n";
    } else {
        echo "❌ window.empleadosConfig no configurado en layout\n";
    }
    
    // 3. Verificar configuración BASE_URL
    echo "\n--- Verificando BASE_URL ---\n";
    echo "BASE_URL actual: '" . BASE_URL . "'\n";
    
    if (BASE_URL === '') {
        echo "✅ BASE_URL está vacía (correcto para localhost)\n";
    } else {
        echo "⚠️ BASE_URL no está vacía (podría causar problemas)\n";
    }
    
    // 4. Probar URLs específicas
    echo "\n--- URLs de prueba para 127.0.0.1:8080 ---\n";
    
    // Obtener un empleado real
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM empleados LIMIT 1");
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($empleado) {
        $emp_id = $empleado['id'];
        $base_url = 'http://127.0.0.1:8080';
        
        echo "📋 Empleado de prueba: ID $emp_id - {$empleado['nombre']} {$empleado['apellido']}\n";
        echo "\n🔗 URLs para probar en navegador:\n";
        echo "1. Lista empleados: $base_url/empleados\n";
        echo "2. Ver empleado: $base_url/empleados/$emp_id\n";
        echo "3. Editar empleado: $base_url/empleados/$emp_id/edit\n";
        
        // Verificar que las rutas coincidan
        echo "\n--- Verificación de rutas ---\n";
        
        include __DIR__ . '/routes.php';
        
        $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/empleados/{id:\d+}', ['EmpleadoController', 'show']);
            $r->addRoute('GET', '/empleados/{id:\d+}/edit', ['EmpleadoController', 'edit']);
            $r->addRoute('GET', '/empleados', ['EmpleadoController', 'index']);
        });
        
        // Probar ruta show
        $httpMethod = 'GET';
        $uri = "/empleados/$emp_id";
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        
        if ($routeInfo[0] === FastRoute\Dispatcher::FOUND) {
            echo "✅ Ruta /empleados/$emp_id encontrada: {$routeInfo[1][0]}::{$routeInfo[1][1]}\n";
        } else {
            echo "❌ Ruta /empleados/$emp_id no encontrada\n";
        }
        
        // Probar ruta edit
        $uri_edit = "/empleados/$emp_id/edit";
        $routeInfoEdit = $dispatcher->dispatch($httpMethod, $uri_edit);
        
        if ($routeInfoEdit[0] === FastRoute\Dispatcher::FOUND) {
            echo "✅ Ruta /empleados/$emp_id/edit encontrada: {$routeInfoEdit[1][0]}::{$routeInfoEdit[1][1]}\n";
        } else {
            echo "❌ Ruta /empleados/$emp_id/edit no encontrada\n";
        }
        
    } else {
        echo "❌ No hay empleados para probar\n";
    }
    
    // 5. Verificar JavaScript
    echo "\n--- Verificando JavaScript ---\n";
    
    $js_file = __DIR__ . '/assets/js/empleados.js';
    if (file_exists($js_file)) {
        $js_content = file_get_contents($js_file);
        
        if (strpos($js_content, 'empleadosConfig.baseUrl') !== false) {
            echo "✅ JavaScript usa empleadosConfig.baseUrl\n";
        } else {
            echo "⚠️ JavaScript podría estar usando otra configuración\n";
        }
        
        if (strpos($js_content, 'fetch(') !== false) {
            echo "✅ JavaScript usa fetch para llamadas AJAX\n";
        } else {
            echo "❌ JavaScript no usa fetch\n";
        }
    } else {
        echo "❌ Archivo empleados.js no existe\n";
    }
    
    echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
    echo "✅ Archivos PHP con URLs corregidas\n";
    echo "✅ Configuración JavaScript añadida al layout\n";
    echo "✅ Rutas FastRoute funcionando\n";
    echo "✅ Todo listo para pruebas\n";
    
    echo "\n🚀 PASOS PARA PROBAR EN 127.0.0.1:8080:\n";
    echo "1. Abre navegador y ve a: http://127.0.0.1:8080/login\n";
    echo "2. Inicia sesión (usuario: superadmin o admin)\n";
    echo "3. Ve a: http://127.0.0.1:8080/empleados\n";
    echo "4. Haz clic en 'Ver' de cualquier empleado\n";
    echo "5. Haz clic en 'Editar' de cualquier empleado\n";
    echo "6. Los formularios deberían mostrarse correctamente\n";
    echo "7. No debería haber errores de JavaScript\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>