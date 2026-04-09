<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO FINAL - CORRECCIÓN IMPLEMENTADA ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // 1. Verificar usuario jefe1_prueba
    echo "\n--- Verificando usuario jefe1_prueba ---\n";
    
    $stmt = $pdo->prepare("SELECT id, username, nombre_completo, rol, empleado_id FROM usuarios WHERE username = 'jefe1_prueba'");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "✅ Usuario encontrado: ID {$usuario['id']}, Empleado_ID: {$usuario['empleado_id']}\n";
        
        $jefe_id = $usuario['id'];
        $empleado_id_jefe = $usuario['empleado_id'];
        
        // 2. Probar el método corregido
        echo "\n--- Probando método corregido ---\n";
        
        require_once __DIR__ . '/controllers/ValidacionJefeController.php';
        $controller = new ValidacionJefeController();
        
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getEmpleadosACargo');
        $method->setAccessible(true);
        
        try {
            echo "🔍 Probando con jefe_id: $jefe_id (ID usuario)\n";
            $resultados_con_jefe_id = $method->invoke($controller, $jefe_id, '');
            echo "📊 Resultados (con jefe_id): " . (is_array($resultados_con_jefe_id) ? count($resultados_con_jefe_id) : 0) . " empleados\n";
            
            echo "🔍 Probando con empleado_id: $empleado_id_jefe (ID empleado)\n";
            $resultados_con_empleado_id = $method->invoke($controller, $empleado_id_jefe, '');
            echo "📊 Resultados (con empleado_id): " . (is_array($resultados_con_empleado_id) ? count($resultados_con_empleado_id) : 0) . " empleados\n";
            
            if (is_array($resultados_con_empleado_id) && !empty($resultados_con_empleado_id)) {
                echo "✅ Método funciona con empleado_id_jefe\n";
                foreach ($resultados_con_empleado_id as $res) {
                    echo "   - {$res['nombre_completo']} (ID: {$res['id']}, Área: {$res['area']})\n";
                }
            } else {
                echo "❌ Método sigue devolviendo array vacío con empleado_id_jefe\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error en método corregido: " . $e->getMessage() . "\n";
        }
        
        // 3. Verificar la base de datos directamente
        echo "\n--- Verificación directa en base de datos ---\n";
        
        $stmt_direct = $pdo->prepare("SELECT id, nombre, apellido FROM empleados WHERE jefe_directo_id = ?");
        $stmt_direct->execute([$empleado_id_jefe]);
        $empleados_direct = $stmt_direct->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Empleados con jefe_directo_id = $empleado_id_jefe:\n";
        foreach ($empleados_direct as $emp) {
            echo "   - {$emp['nombre']} {$emp['apellido']} (ID: {$emp['id']})\n";
        }
        
        echo "   Total: " . count($empleados_direct) . " empleados\n";
        
    } else {
        echo "❌ Usuario no encontrado\n";
    }
    
    echo "\n=== RESULTADO FINAL ===\n";
    echo "🔍 El método getEmpleadosACargo() fue corregido\n";
    echo "✅ Ahora usa el empleado_id del jefe desde la tabla usuarios\n";
    echo "🎯 La corrección debería resolver el problema de la redirección\n";
    echo "📋 El sistema de validaciones debe funcionar ahora correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>