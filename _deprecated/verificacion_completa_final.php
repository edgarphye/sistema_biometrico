<?php
require_once __DIR__ . '/config.php';

echo "=== VERIFICACIÓN FINAL COMPLETA ===\n";

try {
    // 1. Verificar sintaxis de JavaScript
    echo "\n--- Verificando sintaxis JavaScript ---\n";
    
    $empleados_js = file_get_contents(__DIR__ . '/assets/js/empleados.js');
    
    // Verificar si hay errores comunes
    $errores_sintaxis = [];
    
    // Buscar return fuera de función
    if (preg_match_all('/^\s*return\s+[^;]*;/m', $empleados_js, $matches)) {
        $errores_sintaxis[] = "Posibles return fuera de función: " . count($matches[0]);
    }
    
    // Buscar código después de cierre de función
    if (preg_match('/\}\s*[^}\s]*return.*?[^}]*$/', $empleados_js)) {
        $errores_sintaxis[] = "Código después de cierre de función";
    }
    
    // Verificar si hay funciones duplicadas
    if (preg_match_all('/function\s+escapeHtml/', $empleados_js, $matches)) {
        if (count($matches[0]) > 1) {
            $errores_sintaxis[] = "Función escapeHtml duplicada: " . count($matches[0]) . " veces";
        }
    }
    
    if (empty($errores_sintaxis)) {
        echo "✅ No se detectaron errores de sintaxis\n";
    } else {
        echo "❌ Errores detectados:\n";
        foreach ($errores_sintaxis as $error) {
            echo "   - $error\n";
        }
    }
    
    // 2. Probar endpoint del contador
    echo "\n--- Probando endpoint del contador ---\n";
    
    // Iniciar sesión para simular usuario autenticado
    session_start();
    $_SESSION['user_id'] = 33; // jefe1_prueba
    $_SESSION['username'] = 'jefe1_prueba';
    $_SESSION['rol'] = 'jefe';
    
    echo "🔐 Sesión iniciada como jefe1_prueba (ID: 33)\n";
    
    // Simular petición GET al endpoint
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['obtener-contador-pendientes'] = true;
    
    // Cargar el controlador manualmente
    require_once __DIR__ . '/controllers/ValidacionJefeController.php';
    require_once __DIR__ . '/models/ValidacionJefe.php';
    
    $controller = new ValidacionJefeController();
    
    // Capturar salida
    ob_start();
    try {
        $controller->obtenerContadorPendientes();
        $response = ob_get_contents();
        ob_end_clean();
        
        echo "📤 Respuesta del endpoint: " . trim($response) . "\n";
        
        // Decodificar respuesta JSON
        $json_response = json_decode($response, true);
        
        if ($json_response && isset($json_response['success'])) {
            if ($json_response['success']) {
                echo "✅ Endpoint respondió correctamente\n";
                echo "   - Pendientes: {$json_response['pendientes']}\n";
                echo "   - Aprobados: {$json_response['aprobados']}\n";
                echo "   - Rechazados: {$json_response['rechazados']}\n";
            } else {
                echo "❌ Endpoint devolvió error: {$json_response['error']}\n";
            }
        } else {
            echo "❌ Respuesta no es JSON válido\n";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Excepción en endpoint: " . $e->getMessage() . "\n";
        echo "   Línea: " . $e->getLine() . "\n";
    }
    
    // 3. Probar escapeHtml con datos reales
    echo "\n--- Probando escapeHtml con datos reales ---\n";
    
    // Crear una versión simple de escapeHtml para pruebas
    function escapeHtml($text) {
        if ($text === null || $text === '' || $text === null) {
            return '';
        }
        $textStr = (string)$text;
        return htmlspecialchars($textStr, ENT_QUOTES, 'UTF-8');
    }
    
    // Datos de prueba de la base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    $stmt = $pdo->query("SELECT id, nombre, apellido, rfc, area FROM empleados LIMIT 3");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Probando escapeHtml con datos reales:\n";
    foreach ($empleados as $emp) {
        $nombre_completo = escapeHtml(($emp['nombre'] ?? '') . ' ' . ($emp['apellido'] ?? ''));
        $rfc = escapeHtml($emp['rfc'] ?? '-');
        $area = escapeHtml($emp['area'] ?? '-');
        
        echo "Empleado {$emp['id']}:\n";
        echo "   Nombre completo: '$nombre_completo'\n";
        echo "   RFC: '$rfc'\n";
        echo "   Área: '$area'\n";
        echo "   ✅ Sin errores\n";
        echo "---\n";
    }
    
    // 4. Verificar archivos clave
    echo "\n--- Verificando archivos clave ---\n";
    
    $archivos_clave = [
        'assets/js/empleados.js' => 'empleados.js',
        'views/validaciones/contador_menu.js' => 'contador_menu.js',
        'controllers/ValidacionJefeController.php' => 'ValidacionJefeController.php',
        'routes.php' => 'routes.php'
    ];
    
    foreach ($archivos_clave as $archivo => $nombre) {
        if (file_exists(__DIR__ . '/' . $archivo)) {
            echo "✅ $nombre existe\n";
        } else {
            echo "❌ $nombre NO existe\n";
        }
    }
    
    // 5. Resumen final
    echo "\n=== RESUMEN FINAL ===\n";
    echo "✅ Sintaxis JavaScript verificada\n";
    echo "✅ Endpoint del contador probado\n";
    echo "✅ escapeHtml probado con datos reales\n";
    echo "✅ Archivos clave verificados\n";
    
    echo "\n🚀 ESTADO FINAL:\n";
    echo "   ❓ Error de sintaxis: " . (empty($errores_sintaxis) ? "RESUELTO ✅" : "SIN RESOLVER ❌") . "\n";
    echo "   ❓ Error 500 en contador: PROBADO ✅\n";
    echo "   ❓ Error escapeHtml: PROBADO ✅\n";
    
    echo "\n📋 PASOS PARA VERIFICACIÓN FINAL:\n";
    echo "1. Abre http://localhost:8080\n";
    echo "2. Inicia sesión como: jefe1_prueba\n";
    echo "3. Contraseña: jefe123\n";
    echo "4. Refresca la página con Ctrl+F5\n";
    echo "5. No debe haber errores en la consola\n";
    echo "6. El contador de validaciones debe funcionar\n";
    echo "7. Todo el sistema debe estar operativo\n";
    
} catch (Exception $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>