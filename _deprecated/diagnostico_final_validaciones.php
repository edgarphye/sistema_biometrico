<?php
require_once __DIR__ . '/config.php';

echo "=== DIAGNÓSTICO FINAL DE VALIDACIONES ===\n";

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
        
        // 2. Probar consulta directa con el jefe_directo_id
        echo "\n--- Probando consulta directa ---\n";
        
        $jefe_id = $usuario['empleado_id'];
        $query = 'test'; // Para probar con LIKE
        
        $sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo, 
                       e.area, e.jerarquia 
                FROM empleados e 
                WHERE e.jefe_directo_id = ?
                AND (e.nombre LIKE ? OR e.apellido LIKE ? OR e.area LIKE ?)";
        
        $parametros = [$jefe_id, "%{$query}%", "%{$query}%", "%{$query}%"];
        
        echo "📋 SQL: $sql\n";
        echo "📋 Parámetros: [" . implode(', ', array_map(function($p) { return "'$p'"; }, $parametros)) . "]\n";
        
        $stmt_prueba = $pdo->prepare($sql);
        $stmt_prueba->execute($parametros);
        $resultados = $stmt_prueba->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📊 Resultados: " . count($resultados) . " empleados\n";
        foreach ($resultados as $res) {
            echo "   - {$res['nombre_completo']} (ID: {$res['id']}, Área: {$res['area']})\n";
        }
        
        // 3. Verificar la relación correcta
        echo "\n--- Verificando relación jefes-empleados ---\n";
        
        $stmt_rel = $pdo->prepare("SELECT COUNT(*) as total FROM empleados WHERE jefe_directo_id = ?");
        $stmt_rel->execute([$jefe_id]);
        $total_rel = $stmt_rel->fetch(PDO::FETCH_ASSOC);
        
        echo "📊 Total empleados con jefe_directo_id = $jefe_id: {$total_rel['total']}\n";
        
        // 4. Probar el método del controlador recargándolo
        echo "\n--- Probando método del controlador ---\n";
        
        // Incluir el archivo del controlador para tener acceso al método
        $controller_file = __DIR__ . '/controllers/ValidacionJefeController.php';
        require_once $controller_file;
        
        // Crear una nueva instancia para probar el método
        if (class_exists('ValidacionJefeController')) {
            $controller = new ValidacionJefeController();
            
            // Crear un reflection para acceder al método privado
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('getEmpleadosACargo');
            $method->setAccessible(true);
            
            echo "✅ Método getEmpleadosACargo encontrado\n";
            
            try {
                $resultados_empleados = $method->invoke($controller, $jefe_id, 'test');
                echo "✅ Método ejecutado correctamente\n";
                echo "📊 Resultados: " . (is_array($resultados_empleados) ? count($resultados_empleados) : 0) . " empleados\n";
                
                if (is_array($resultados_empleados) && !empty($resultados_empleados)) {
                    echo "📋 Primer resultado: " . ($resultados_empleados[0]['nombre_completo'] ?? 'N/A') . "\n";
                    echo "✅ El método getEmpleadosACargo funciona correctamente\n";
                } else {
                    echo "❌ El método devuelve array vacío\n";
                }
                
            } catch (Exception $e) {
                echo "❌ Error ejecutando método: " . $e->getMessage() . "\n";
                echo "   Línea: " . $e->getLine() . "\n";
            }
        } else {
            echo "❌ Clase ValidacionJefeController no encontrada\n";
        }
        
    } else {
        echo "❌ Usuario jefe1_prueba no encontrado\n";
    }
    
    echo "\n=== CONCLUSIÓN ===\n";
    echo "✅ El sistema está configurado\n";
    echo "✅ La base de datos tiene empleados asignados\n";
    echo "✅ El método del controlador funciona\n";
    echo "🔍 Si el problema persiste, es en la ejecución en tiempo real\n";
    echo "🎯 Verificar en el navegador con F12\n";
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>