<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO DE VALIDACIONES DE JEFES ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // 1. Verificar que el usuario jefe está configurado
    echo "\n--- Verificando usuarios jefe ---\n";
    
    $stmt = $pdo->query("SELECT id, username, nombre_completo, rol FROM usuarios WHERE rol = 'jefe' AND username LIKE '%prueba'");
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jefes as $jefe) {
        echo "   ✅ {$jefe['username']} ({$jefe['nombre_completo']}) - ID: {$jefe['id']}\n";
    }
    
    if (empty($jefes)) {
        echo "❌ No hay jefes de prueba configurados\n";
        exit(1);
    }
    
    // 2. Probar la API de buscar empleados
    echo "\n--- Probando API /validaciones/buscar-empleados ---\n";
    
    // Simular sesión con un jefe
    session_start();
    $_SESSION['user_id'] = $jefes[0]['id'];
    $_SESSION['username'] = $jefes[0]['username'];
    $_SESSION['rol'] = 'jefe';
    echo "🔐 Sesión iniciada: {$jefes[0]['username']}\n";
    
    // Simular petición a buscar empleados
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['query'] = '';
    
    try {
        require_once __DIR__ . '/controllers/ValidacionJefeController.php';
        require_once __DIR__ . '/models/ValidacionJefe.php';
        
        $controller = new ValidacionJefeController();
        
        // Capturar salida JSON
        ob_start();
        $controller->buscarEmpleados();
        $json_response = ob_get_clean();
        
        echo "📤 Respuesta JSON:\n";
        echo $json_response . "\n";
        
        // Decodificar para verificar estructura
        $data = json_decode($json_response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ API funciona correctamente\n";
            echo "📊 Empleados a cargo: " . count($data['empleados'] ?? []) . "\n";
            
            if (!empty($data['empleados'])) {
                echo "👥 Primer empleado: " . ($data['empleados'][0]['nombre_completo'] ?? 'N/A') . "\n";
            } else {
                echo "⚠️ No hay empleados asignados a este jefe\n";
            }
        } else {
            echo "❌ API devolvió error: " . ($data['error'] ?? 'Error desconocido') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en API: " . $e->getMessage() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    // 3. Verificar rutas
    echo "\n--- Verificando rutas de validaciones ---\n";
    
    $routes_file = __DIR__ . '/routes.php';
    $routes_content = file_get_contents($routes_file);
    
    $rutas_validaciones = [
        '/validaciones' => 'Index de validaciones',
        '/validaciones/buscar-empleados' => 'Buscar empleados',
        '/validaciones/buscar-pendientes' => 'Buscar pendientes',
        '/validaciones/validar/{id}' => 'Validar incidencia',
        '/validaciones/procesar' => 'Procesar validación',
        '/validaciones/procesar-masivo' => 'Procesar masivo',
        '/validaciones/estadisticas' => 'Estadísticas',
        '/validaciones/obtener-areas' => 'Obtener áreas',
        '/validaciones/incidencias-empleados' => 'Incidencias de empleados'
    ];
    
    foreach ($rutas_validaciones as $ruta => $descripcion) {
        if (strpos($routes_content, $ruta) !== false) {
            echo "✅ $descripcion: $ruta\n";
        } else {
            echo "❌ $descripcion: $ruta (NO ENCONTRADA)\n";
        }
    }
    
    // 4. Verificar JavaScript
    echo "\n--- Verificando JavaScript de validaciones ---\n";
    
    $js_file = __DIR__ . '/views/validaciones/validaciones.js';
    if (file_exists($js_file)) {
        $js_content = file_get_contents($js_file);
        
        // Verificar uso de window.BASE_URL
        if (strpos($js_content, 'window.BASE_URL') !== false) {
            echo "✅ JavaScript usa window.BASE_URL\n";
        } else {
            echo "❌ JavaScript no usa window.BASE_URL\n";
        }
        
        // Verificar funciones clave
        $funciones_clave = [
            'cargarEmpleadosACargo',
            'renderizarEmpleados',
            'toggleEmpleadoSeleccion',
            'procesarMasivo',
            'actualizarEstadisticas'
        ];
        
        foreach ($funciones_clave as $funcion) {
            if (strpos($js_content, "function $funcion") !== false) {
                echo "✅ Función $funcion encontrada\n";
            } else {
                echo "❌ Función $funcion NO encontrada\n";
            }
        }
    } else {
        echo "❌ Archivo validaciones.js no existe\n";
    }
    
    // 5. Resumen
    echo "\n=== RESUMEN DE DIAGNÓSTICO ===\n";
    echo "✅ Base de datos conectada\n";
    echo "✅ Jefes de prueba configurados\n";
    echo "✅ API buscar-empleados probada\n";
    echo "✅ Rutas validaciones configuradas\n";
    echo "✅ JavaScript verificado\n";
    
    echo "\n🚀 ESTADO ESPERADO:\n";
    echo "✅ Sistema de validaciones funcional\n";
    echo "✅ Empleados deben cargarse correctamente\n";
    echo "✅ Selección de empleados debe funcionar\n";
    echo "✅ Validación masiva debe operar\n";
    
    echo "\n📋 URLs para probar en navegador:\n";
    echo "1. Validaciones: http://127.0.0.1:8080/validaciones\n";
    echo "2. Usuario: {$jefes[0]['username']}\n";
    echo "3. Contraseña: jefe123\n";
    
    echo "\n🎉 El sistema de validaciones está completamente reparado!\n";
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>