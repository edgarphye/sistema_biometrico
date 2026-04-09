<?php
require_once __DIR__ . '/config.php';

echo "=== REPARANDO ACCESO A APIS DE VALIDACIONES ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Conectado a la base de datos\n";
    
    // 1. Verificar que la vista de validación completa existe y funciona
    echo "\n--- Verificando vista de validación completa ---\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'vista_validacion_completa'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Vista 'vista_validacion_completa' no existe. Creándola...\n";
        
        $sql = "
        CREATE VIEW vista_validacion_completa AS
        SELECT 
            r.id,
            r.empleado_id,
            r.fecha as fecha_incidencia,
            r.minutos_retardo,
            r.tipo_registro,
            r.categoria_principal,
            r.requiere_validacion_jefe,
            r.estado_validacion as estado_validacion_jefe,
            r.evidencia_adjunta,
            r.motivo_detalle,
            r.hora_entrada,
            r.hora_salida,
            r.fecha_asistencia,
            r.dia_semana,
            r.fecha_creacion as fecha_solicitud,
            r.fecha_aprobacion as fecha_validacion,
            CASE 
                WHEN r.minutos_retardo <= 5 THEN 1
                WHEN r.minutos_retardo <= 15 THEN 2
                WHEN r.minutos_retardo <= 30 THEN 3
                ELSE 4
            END as prioridad_atencion,
            CASE 
                WHEN r.categoria_principal = 'retardo' THEN 'retardo'
                WHEN r.categoria_principal = 'ausencia' THEN 'ausencia'
                ELSE 'otro'
            END as tipo_incidencia,
            r.empleado_id as incidencia_id
        FROM retardos r
        WHERE r.requiere_validacion_jefe = 1 OR r.estado_validacion IN ('pendiente', 'aprobado', 'rechazado', 'requiere_info')
        ";
        
        $pdo->exec($sql);
        echo "✅ Vista 'vista_validacion_completa' creada\n";
    } else {
        echo "✅ Vista 'vista_validacion_completa' ya existe\n";
    }
    
    // 2. Verificar que el empleado actual tiene empleados a cargo
    echo "\n--- Verificando estructura de jefes ---\n";
    
    // Verificar cuántos usuarios de tipo jefe existen
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.nombre_completo, u.empleado_id, 
               COUNT(e.id) as empleados_a_cargo
        FROM usuarios u
        LEFT JOIN empleados e ON u.empleado_id = e.jefe_directo_id
        WHERE u.rol = 'jefe'
        GROUP BY u.id, u.username, u.nombre_completo, u.empleado_id
    ");
    
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Jefes encontrados:\n";
    foreach ($jefes as $jefe) {
        echo "   - {$jefe['username']} ({$jefe['nombre_completo']}): {$jefe['empleados_a_cargo']} empleados a cargo\n";
        
        if ($jefe['empleados_a_cargo'] == 0) {
            echo "     ⚠️ Este jefe no tiene empleados asignados\n";
        }
    }
    
    // 3. Verificar incidencias pendientes
    echo "\n--- Verificando incidencias pendientes ---\n";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_pendientes,
            COUNT(CASE WHEN r.estado_validacion = 'pendiente' THEN 1 END) as pendientes,
            COUNT(CASE WHEN r.estado_validacion = 'aprobado' THEN 1 END) as aprobados,
            COUNT(CASE WHEN r.estado_validacion = 'rechazado' THEN 1 END) as rechazados
        FROM retardos r
        WHERE r.requiere_validacion_jefe = 1
    ");
    
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Estadísticas de validaciones:\n";
    echo "   - Total incidencias que requieren validación: {$estadisticas['total_pendientes']}\n";
    echo "   - Pendientes: {$estadisticas['pendientes']}\n";
    echo "   - Aprobados: {$estadisticas['aprobados']}\n";
    echo "   - Rechazados: {$estadisticas['rechazados']}\n";
    
    // 4. Probar la consulta específica que usa el controlador
    echo "\n--- Probando consulta principal del controlador ---\n";
    
    if (!empty($jefes)) {
        // Encontrar un jefe con empleados a cargo
        $jefe_prueba = null;
        foreach ($jefes as $jefe) {
            if ($jefe['empleados_a_cargo'] > 0) {
                $jefe_prueba = $jefe;
                break;
            }
        }
        
        if (!$jefe_prueba) {
            echo "❌ No se encontró ningún jefe con empleados asignados\n";
            exit(1);
        }
        
        $jefe_id = $jefe_prueba['id'];
        
        echo "🔍 Probando con jefe: {$jefe_prueba['username']} (ID: $jefe_id)\n";
        
        // Esta es la consulta que usa el método getEstadisticasPorJefe (corregida)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(v.incidencia_id) as total,
                COUNT(CASE WHEN v.estado_validacion_jefe = 'pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN v.estado_validacion_jefe = 'aprobado' THEN 1 END) as aprobados,
                COUNT(CASE WHEN v.estado_validacion_jefe = 'rechazado' THEN 1 END) as rechazados,
                COUNT(CASE WHEN v.estado_validacion_jefe = 'requiere_info' THEN 1 END) as requiere_info
            FROM vista_validacion_completa v
            WHERE v.jefe_directo_id = ?
            AND MONTH(v.fecha_incidencia) = ?
            AND YEAR(v.fecha_incidencia) = ?
        ");
        
        $stmt->execute([$jefe_id, date('m'), date('Y')]);
        $estadisticas_jefe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📊 Estadísticas para este jefe:\n";
        echo "   - Total: {$estadisticas_jefe['total']}\n";
        echo "   - Pendientes: {$estadisticas_jefe['pendientes']}\n";
        echo "   - Aprobados: {$estadisticas_jefe['aprobados']}\n";
        echo "   - Rechazados: {$estadisticas_jefe['rechazados']}\n";
    }
    
    // 5. Crear función helper para simular sesión
    echo "\n--- Creando helper para pruebas ---\n";
    
    // Encontrar un jefe con empleados a cargo
    $jefe_con_empleados = null;
    foreach ($jefes as $jefe) {
        if ($jefe['empleados_a_cargo'] > 0) {
            $jefe_con_empleados = $jefe;
            break;
        }
    }
    
    $test_jefe_username = $jefe_con_empleados['username'] ?? 'jefe1_prueba';
    $test_jefe_id = $jefe_con_empleados['id'] ?? 29;
    
    echo "🔑 Para probar el sistema manualmente:\n";
    echo "   1. Inicia sesión como: $test_jefe_username\n";
    echo "   2. Contraseña: jefe123\n";
    echo "   3. Ve a: http://localhost:8080/validaciones\n";
    echo "   4. Deberías ver el panel de validaciones funcionando\n";
    
    echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
    echo "✅ Sistema verificado y listo para usar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 En línea: " . $e->getLine() . "\n";
    exit(1);
}
?>