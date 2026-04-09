<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO DE JEFE SIN EMPLEADOS ===\n";

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
        echo "✅ Usuario encontrado:\n";
        echo "   ID: {$usuario['id']}\n";
        echo "   Username: {$usuario['username']}\n";
        echo "   Nombre: {$usuario['nombre_completo']}\n";
        echo "   Rol: {$usuario['rol']}\n";
        echo "   Empleado_ID: {$usuario['empleado_id']}\n";
        
        // 2. Verificar si este usuario tiene empleados a cargo
        echo "\n--- Verificando empleados asignados a {$usuario['username']} ---\n";
        
        // Primero, verificar por usuario_id
        if ($usuario['empleado_id']) {
            $stmt_empleados = $pdo->prepare("SELECT id, nombre, apellido, area, jefe_directo_id FROM empleados WHERE jefe_directo_id = ?");
            $stmt_empleados->execute([$usuario['empleado_id']]);
            $empleados_por_usuario_id = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 Empleados encontrados (por usuario_id):\n";
            foreach ($empleados_por_usuario_id as $emp) {
                echo "   - ID: {$emp['id']}, Nombre: {$emp['nombre']} {$emp['apellido']}, Jefe_directo: {$emp['jefe_directo_id']}\n";
            }
            echo "   Total: " . count($empleados_por_usuario_id) . "\n";
            
            if (count($empleados_por_usuario_id) > 0) {
                echo "✅ Empleados encontrados por usuario_id\n";
            } else {
                echo "❌ No hay empleados asignados por usuario_id\n";
            }
        } else {
            echo "❌ No tiene empleado_id asociado\n";
        }
        
        // 3. Verificar si existe jefes sin empleados asignados
        echo "\n--- Verificando todos los jefes ---\n";
        
        $stmt_jefes = $pdo->query("SELECT u.id, u.username, u.empleado_id, COUNT(e.id) as empleados_count FROM usuarios u LEFT JOIN empleados e ON u.empleado_id = e.jefe_directo_id WHERE u.rol = 'jefe' GROUP BY u.id, u.username, u.empleado_id");
        $jefes = $stmt_jefes->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Todos los jefes y sus empleados:\n";
        foreach ($jefes as $jefe) {
            echo "   - {$jefe['username']} (Usuario ID: {$jefe['id']}, Empleado ID: {$jefe['empleado_id']}) → {$jefe['empleados_count']} empleados\n";
        }
        
        // 4. Verificar empleados en general
        echo "\n--- Empleados con jefe_directo_id ---\n";
        
        $stmt_con_jefe = $pdo->query("SELECT id, nombre, apellido, jefe_directo_id FROM empleados WHERE jefe_directo_id IS NOT NULL AND jefe_directo_id != 0 LIMIT 10");
        $empleados_con_jefe = $stmt_con_jefe->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Empleados con jefe_directo_id:\n";
        foreach ($empleados_con_jefe as $emp) {
            echo "   - ID: {$emp['id']}, Nombre: {$emp['nombre']} {$emp['apellido']}, Jefe: {$emp['jefe_directo_id']}\n";
        }
        
        // 5. Probar la consulta exacta que usa el controlador
        echo "\n--- Probando consulta del controlador ---\n";
        
        if ($usuario['empleado_id']) {
            $sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                           e.area, e.jerarquia 
                    FROM empleados e 
                    WHERE e.jefe_directo_id = ?
                    AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
            
            $parametros = [$usuario['empleado_id'], "%%", "%", "%"];
            
            echo "📋 SQL:\n$sql\n";
            echo "📋 Parámetros: [" . implode(', ', $parametros) . "]\n";
            
            $stmt_prueba = $pdo->prepare($sql);
            $stmt_prueba->execute($parametros);
            $resultados = $stmt_prueba->fetchAll(PDO::FETCH_ASSOC);
            
            echo "📊 Resultados de la prueba:\n";
            foreach ($resultados as $res) {
                echo "   - ID: {$res['id']}, Nombre completo: {$res['nombre_completo']}, Área: {$res['area']}\n";
            }
            echo "   Total: " . count($resultados) . "\n";
        }
        
    } else {
        echo "❌ Usuario jefe1_prueba no encontrado\n";
    }
    
    echo "\n=== CONCLUSIÓN ===\n";
    echo "✅ El problema está en la asignación de empleados al jefe\n";
    echo "🔍 Revisa la columna jefe_directo_id en la tabla empleados\n";
    echo "📊 El método esJefe() depende de getEmpleadosACargo()\n";
    echo "🎯 Si no hay empleados asignados, redirige al dashboard\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>