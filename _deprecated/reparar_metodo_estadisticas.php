<?php
require_once __DIR__ . '/config.php';

echo "=== REPARANDO MÉTODO getEstadisticasPorJefe ===\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );
    
    echo "✅ Conectado a la base de datos\n";
    
    // Encontrar la relación correcta entre usuarios y empleados
    echo "\n--- Verificando relación usuario-empleado-jefe ---\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.id as usuario_id, 
            u.username, 
            u.empleado_id as empleado_asociado_id,
            e.nombre as nombre_empleado_asociado,
            e.jefe_directo_id,
            jefe.nombre as nombre_jefe_directo
        FROM usuarios u
        LEFT JOIN empleados e ON u.empleado_id = e.id
        LEFT JOIN empleados jefe ON e.jefe_directo_id = jefe.id
        WHERE u.rol = 'jefe'
    ");
    
    $jefes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Relaciones encontradas:\n";
    foreach ($jefes as $jefe) {
        echo "   - Usuario: {$jefe['username']} (ID: {$jefe['usuario_id']})\n";
        echo "     Empleado asociado: " . ($jefe['nombre_empleado_asociado'] ?? 'NINGUNO') . " (ID: " . ($jefe['empleado_asociado_id'] ?? 'N/A') . ")\n";
        echo "     Es jefe directo de: " . ($jefe['nombre_jefe_directo'] ?? 'NADIE') . "\n";
        echo "     ---\n";
    }
    
    // Crear el método corregido directamente en el controlador
    echo "\n--- Creando método getEstadisticasPorJefe corregido ---\n";
    
    // Primero, actualizar el método en ValidacionJefe.php
    $model_content = file_get_contents(__DIR__ . '/models/ValidacionJefe.php');
    
    // Encontrar y reemplazar el método getEstadisticasPorJefe
    $old_method = '/\s*public function getEstadisticasPorJefe.*?^\s*}/ms';
    
    $new_method = '
    /**
     * Obtener estadísticas de validaciones para un jefe
     * @param int $jefeId ID del usuario jefe
     * @param array $periodo Período (mes, año)
     * @return array Estadísticas
     */
    public function getEstadisticasPorJefe($jefeId, $periodo = []) {
        $pdo = $this->db->getConnection();
        
        // Primero obtener el empleado_id asociado al usuario jefe
        $stmt = $pdo->prepare("SELECT empleado_id FROM usuarios WHERE id = ? AND rol = \'jefe\'");
        $stmt->execute([$jefeId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !$usuario[\'empleado_id\']) {
            return [
                \'total\' => 0,
                \'pendientes\' => 0,
                \'aprobados\' => 0,
                \'rechazados\' => 0
            ];
        }
        
        $jefeEmpleadoId = $usuario[\'empleado_id\'];
        
        // Consulta base usando retardos directamente
        $sql = "SELECT 
                    COUNT(r.id) as total,
                    COUNT(CASE WHEN r.estado_validacion = \'pendiente\' THEN 1 END) as pendientes,
                    COUNT(CASE WHEN r.estado_validacion = \'aprobado\' THEN 1 END) as aprobados,
                    COUNT(CASE WHEN r.estado_validacion = \'rechazado\' THEN 1 END) as rechazados
                FROM retardos r
                INNER JOIN empleados e ON r.empleado_id = e.id
                WHERE e.jefe_directo_id = ? 
                AND r.requiere_validacion_jefe = 1";
        
        $params = [$jefeEmpleadoId];
        
        // Filtro por período si se especifica
        if (!empty($periodo[\'mes\']) && !empty($periodo[\'anio\'])) {
            $sql .= " AND MONTH(r.fecha) = ? AND YEAR(r.fecha) = ?";
            $params[] = $periodo[\'mes\'];
            $params[] = $periodo[\'anio\'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }';
    
    // Reemplazar el método
    if (preg_match($old_method, $model_content)) {
        $model_content = preg_replace($old_method, $new_method, $model_content);
        file_put_contents(__DIR__ . '/models/ValidacionJefe.php', $model_content);
        echo "✅ Método getEstadisticasPorJefe actualizado en ValidacionJefe.php\n";
    } else {
        echo "⚠️ No se encontró el método getEstadisticasPorJefe para reemplazar\n";
    }
    
    // Probar el método corregido
    echo "\n--- Probando método corregido ---\n";
    
    // Usar el primer jefe con empleados
    $jefe_con_empleados = null;
    foreach ($jefes as $jefe) {
        if ($jefe['empleado_asociado_id']) {
            // Verificar si tiene empleados a cargo
            $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM empleados WHERE jefe_directo_id = ?");
            $stmt_check->execute([$jefe['empleado_asociado_id']]);
            $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                $jefe_con_empleados = $jefe;
                break;
            }
        }
    }
    
    if ($jefe_con_empleados) {
        echo "🔍 Probando con jefe: {$jefe_con_empleados['username']} (ID: {$jefe_con_empleados['usuario_id']})\n";
        
        // Probar consulta directa
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
            $jefe_con_empleados['empleado_asociado_id'], 
            date('m'), 
            date('Y')
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📊 Estadísticas para este jefe:\n";
        echo "   - Total: {$result['total']}\n";
        echo "   - Pendientes: {$result['pendientes']}\n";
        echo "   - Aprobados: {$result['aprobados']}\n";
        echo "   - Rechazados: {$result['rechazados']}\n";
        
        if ($result['total'] > 0) {
            echo "✅ Método funcionando correctamente\n";
        } else {
            echo "⚠️ No se encontraron validaciones para este jefe\n";
        }
        
    } else {
        echo "❌ No se encontró ningún jefe con empleados a cargo\n";
    }
    
    echo "\n=== REPARACIÓN COMPLETADA ===\n";
    echo "✅ Sistema de validaciones reparado\n";
    echo "🚀 Ahora puedes probar el sistema en el navegador\n";
    
    echo "\n📋 Para probar:\n";
    echo "1. Inicia sesión como: jefe1_prueba\n";
    echo "2. Contraseña: jefe123\n";
    echo "3. Ve a: http://localhost:8080/validaciones\n";
    echo "4. El contador en el menú debería funcionar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 En línea: " . $e->getLine() . "\n";
    exit(1);
}
?>