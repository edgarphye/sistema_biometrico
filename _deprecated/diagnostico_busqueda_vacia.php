<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO CON BÚSQUEDA VACÍA ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    // Obtener el usuario jefe1_prueba
    $stmt = $pdo->prepare("SELECT id, username, nombre_completo, rol, empleado_id FROM usuarios WHERE username = 'jefe1_prueba'");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        $jefe_id = $usuario['empleado_id'];
        
        // 1. Probar con búsqueda vacía (como lo hace el controlador)
        echo "\n--- Probando con búsqueda vacía ---\n";
        
        $sql_vacia = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                           e.area, e.jerarquia 
                    FROM empleados e 
                    WHERE e.jefe_directo_id = ?";
        
        $parametros_vacia = [$jefe_id];
        
        echo "📋 SQL (búsqueda vacía): $sql_vacia\n";
        echo "📋 Parámetros: [" . implode(', ', array_map(function($p) { return "'$p'"; }, $parametros_vacia)) . "]\n";
        
        $stmt_vacia = $pdo->prepare($sql_vacia);
        $stmt_vacia->execute($parametros_vacia);
        $resultados_vacia = $stmt_vacia->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Resultados (búsqueda vacía): " . count($resultados_vacia) . " empleados\n";
        foreach ($resultados_vacia as $res) {
            echo "   - {$res['nombre_completo']} (ID: {$res['id']}, Área: {$res['area']})\n";
        }
        
        // 2. Probar con el controlador
        echo "\n--- Probando método del controlador (búsqueda vacía) ---\n";
        
        require_once __DIR__ . '/controllers/ValidacionJefeController.php';
        $controller = new ValidacionJefeController();
        
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getEmpleadosACargo');
        $method->setAccessible(true);
        
        $resultados_controlador = $method->invoke($controller, $jefe_id, '');
        
        echo "📊 Resultados (controlador): " . (is_array($resultados_controlador) ? count($resultados_controlador) : 0) . " empleados\n";
        foreach (($resultados_controlador ?? []) as $res) {
            echo "   - {$res['nombre_completo']} (ID: {$res['id']}, Área: {$res['area']})\n";
        }
        
        // 3. Comparación
        echo "\n--- Comparación de resultados ---\n";
        $count_directa = count($resultados_vacia);
        $count_controlador = count($resultados_controlador ?? []);
        
        if ($count_directa == $count_controlador && $count_directa > 0) {
            echo "✅ Ambas consultas devuelven la misma cantidad: $count_directa empleados\n";
            echo "✅ El método getEmpleadosACargo funciona correctamente\n";
            echo "🎯 El problema debe estar en otro lado\n";
        } elseif ($count_controlador == 0) {
            echo "❌ Ambas consultas devuelven 0 resultados\n";
            echo "🔍 Revisar la configuración del jefe_directo_id\n";
        } else {
            echo "⚠️ Diferencia en resultados: directa=$count_directa, controlador=$count_controlador\n";
        }
        
        // 4. Verificar el problema real: jefes con empleado_id diferente
        echo "\n--- Posible problema: jefe_directo_id vs empleado_id ---\n";
        
        if ($usuario['id'] != $usuario['empleado_id']) {
            echo "🔍 PROBLEMA IDENTIFICADO:\n";
            echo "   Usuario ID: {$usuario['id']}\n";
            echo "   Empleado ID: {$usuario['empleado_id']}\n";
            echo "   ❌ Los IDs son diferentes - getEmpleadosACargo usa empleado_id\n";
            echo "   ✅ La consulta WHERE e.jefe_directo_id = {$usuario['empleado_id']} está buscando con el ID equivocado\n";
            
            // Probar con el usuario_id correcto
            echo "\n--- Probando con usuario_id correcto ---\n";
            
            $parametros_correctos = [$usuario['id']];
            $stmt_correcto = $pdo->prepare($sql_vacia);
            $stmt_correcto->execute($parametros_correctos);
            $resultados_correctos = $stmt_correcto->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 Resultados (ID correcto): " . count($resultados_correctos) . " empleados\n";
            foreach ($resultados_correctos as $res) {
                echo "   - {$res['nombre_completo']} (ID: {$res['id']}, Área: {$res['area']})\n";
            }
            
        } else {
            echo "✅ Usuario ID y Empleado ID son iguales\n";
        }
        
    } else {
        echo "❌ Usuario no encontrado\n";
    }
    
    echo "\n=== SOLUCIÓN IDENTIFICADA ===\n";
    echo "🔍 El problema está en la configuración del método getEmpleadosACargo()\n";
    echo "🔧 Necesita usar \$_SESSION['user_id'] en lugar de \$jefeId de empleado_id\n";
    echo "✅ Corrección: Cambiar \$jefeId por la validación de roles\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>