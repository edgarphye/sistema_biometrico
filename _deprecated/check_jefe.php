<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNOSTICO DE JEFE SIN EMPLEADOS ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // 1. Verificar al usuario jefe1_prueba
    echo "\n--- Verificando usuario jefe1_prueba ---\n";
    
    $stmt = $pdo->prepare("SELECT id, username, nombre_completo, rol, empleado_id FROM usuarios WHERE username = 'jefe1_prueba'");
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo "✅ Usuario encontrado: ID {$usuario['id']}, Empleado_ID: {$usuario['empleado_id']}\n";
        
        // 2. Verificar si este usuario tiene empleados a cargo
        echo "\n--- Verificando empleados asignados ---\n";
        
        if ($usuario['empleado_id']) {
            $stmt_empleados = $pdo->prepare("SELECT id, nombre, apellido, area, jefe_directo_id FROM empleados WHERE jefe_directo_id = ?");
            $stmt_empleados->execute([$usuario['empleado_id']]);
            $empleados_por_usuario_id = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 Empleados para jefe_directo_id {$usuario['empleado_id']}: " . count($empleados_por_usuario_id) . "\n";
            
            if (count($empleados_por_usuario_id) > 0) {
                echo "✅ Hay empleados asignados - el jefe debería poder acceder\n";
                
                // 3. Probar la consulta exacta del controlador
                echo "\n--- Probando consulta del controlador ---\n";
                
                $sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                               e.area, e.jerarquia 
                        FROM empleados e 
                        WHERE e.jefe_directo_id = ?
                        AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
                
                $parametros = [$usuario['empleado_id'], "%%", "%"];
                
                $stmt_prueba = $pdo->prepare($sql);
                $stmt_prueba->execute($parametros);
                $resultados = $stmt_prueba->fetchAll(PDO::FETCH_ASSOC);
                
                echo "📊 Resultados: " . count($resultados) . " empleados encontrados\n";
                foreach ($resultados as $res) {
                    echo "   - {$res['nombre_completo']} (ID: {$res['id']}, Área: {$res['area']})\n";
                }
            } else {
                echo "❌ No hay empleados asignados - PROBLEMA ENCONTRADO\n";
            }
        } else {
            echo "❌ No tiene empleado_id asociado\n";
        }
        
    } else {
        echo "❌ Usuario jefe1_prueba no encontrado\n";
    }
    
    echo "\n=== CONCLUSIÓN ===\n";
    echo "🔍 El método esJefe() depende de encontrar empleados por jefe_directo_id\n";
    echo "📊 Si getEmpleadosACargo() devuelve vacío, redirige al dashboard\n";
    echo "🎯 Verificar que la columna jefe_directo_id esté correctamente asignada\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>