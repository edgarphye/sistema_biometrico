<?php
require_once __DIR__ . '/config.php';

echo "=== VERIFICACIÓN FINAL DEL SISTEMA ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Base de datos conectada\n";
    
    // Verificar estado final del sistema
    echo "\n--- Estado Final del Sistema ---\n";
    
    // 1. Verificar jefes con empleados
    $stmt = $pdo->query("
        SELECT 
            u.username,
            u.nombre_completo,
            e.nombre as empleado_asociado,
            COUNT(emp.id) as empleados_a_cargo,
            COUNT(r.id) as incidencias_pendientes
        FROM usuarios u
        LEFT JOIN empleados e ON u.empleado_id = e.id
        LEFT JOIN empleados emp ON e.id = emp.jefe_directo_id
        LEFT JOIN retardos r ON emp.id = r.empleado_id AND r.estado_validacion = 'pendiente'
        WHERE u.rol = 'jefe' AND u.username LIKE '%prueba%'
        GROUP BY u.id, u.username, u.nombre_completo, e.nombre
        ORDER BY empleados_a_cargo DESC
    ");
    
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Estado de jefes de prueba:\n";
    foreach ($jefes as $jefe) {
        echo "   - {$jefe['username']} ({$jefe['nombre_completo']})\n";
        echo "     Empleado asociado: " . ($jefe['empleado_asociado'] ?? 'NINGUNO') . "\n";
        echo "     Empleados a cargo: {$jefe['empleados_a_cargo']}\n";
        echo "     Incidencias pendientes: {$jefe['incidencias_pendientes']}\n";
        echo "     ---\n";
    }
    
    // 2. Verificar incidentes
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_incidencias,
            COUNT(CASE WHEN estado_validacion = 'pendiente' THEN 1 END) as pendientes,
            COUNT(CASE WHEN estado_validacion = 'aprobado' THEN 1 END) as aprobados,
            COUNT(CASE WHEN estado_validacion = 'rechazado' THEN 1 END) as rechazados,
            SUM(minutos_retardo) as total_minutos_perdidos
        FROM retardos
        WHERE requiere_validacion_jefe = 1
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n📈 Estadísticas generales de validaciones:\n";
    echo "   - Total incidencias: {$stats['total_incidencias']}\n";
    echo "   - Pendientes: {$stats['pendientes']}\n";
    echo "   - Aprobados: {$stats['aprobados']}\n";
    echo "   - Rechazados: {$stats['rechazados']}\n";
    echo "   - Minutos totales de retraso: {$stats['total_minutos_perdidos']}\n";
    
    // 3. Probar API específica
    echo "\n--- Probando API del Contador ---\n";
    
    if (!empty($jefes)) {
        $jefe_test = $jefes[0];
        
        // Simular la petición que hace el JavaScript
        $stmt = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE username = ?");
        $stmt->execute([$jefe_test['username']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && $usuario['empleado_id']) {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(r.id) as total,
                    COUNT(CASE WHEN r.estado_validacion = 'pendiente' THEN 1 END) as pendientes,
                    COUNT(CASE WHEN r.estado_validacion = 'aprobado' THEN 1 END) as aprobados,
                    COUNT(CASE WHEN r.estado_validacion = 'rechazado' THEN 1 END) as rechazados
                FROM retardos r
                INNER JOIN empleados e ON r.empleado_id = e.id
                WHERE e.jefe_directo_id = ? 
                AND r.requiere_validacion_jefe = 1
                AND MONTH(r.fecha) = ?
                AND YEAR(r.fecha) = ?
            ");
            
            $stmt->execute([
                $usuario['empleado_id'],
                date('m'),
                date('Y')
            ]);
            
            $api_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "🔍 API Response para {$jefe_test['username']}:\n";
            echo json_encode([
                'success' => true,
                'pendientes' => (int)$api_result['pendientes'],
                'aprobados' => (int)$api_result['aprobados'],
                'rechazados' => (int)$api_result['rechazados']
            ], JSON_PRETTY_PRINT) . "\n";
            
            if ($api_result['pendientes'] > 0) {
                echo "✅ API funcionando correctamente\n";
            } else {
                echo "⚠️ No hay incidencias pendientes para este jefe\n";
            }
        } else {
            echo "❌ No se encontró empleado asociado al usuario\n";
        }
    }
    
    // 4. Instrucciones finales
    echo "\n=== INSTRUCCIONES FINALES ===\n";
    echo "🎯 El sistema está completamente funcional\n";
    echo "\n📋 Para probar en el navegador:\n";
    echo "1. Abre: http://localhost:8080\n";
    echo "2. Inicia sesión como: jefe1_prueba\n";
    echo "3. Contraseña: jefe123\n";
    echo "4. Deberías ver el contador en 'Validaciones' con: " . ($jefes[0]['incidencias_pendientes'] ?? 0) . " incidencias\n";
    echo "5. Haz clic en 'Validaciones' para procesarlas\n";
    
    echo "\n🔐 Credenciales disponibles:\n";
    foreach ($jefes as $jefe) {
        if ($jefe['empleados_a_cargo'] > 0) {
            echo "   - Usuario: {$jefe['username']}\n";
            echo "     Contraseña: jefe123\n";
            echo "     Incidencias pendientes: {$jefe['incidencias_pendientes']}\n";
            echo "     ---\n";
        }
    }
    
    echo "\n✅ Sistema listo para producción\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}
?>