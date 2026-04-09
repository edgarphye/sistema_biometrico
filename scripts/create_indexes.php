<?php
/**
 * Script de ejecución de índices críticos
 * Ejecutar este script para aplicar todas las optimizaciones de base de datos
 */

require_once 'config.php';
require_once 'models/Database.php';

echo "=== INICIANDO EJECUCIÓN DE ÍNDICES CRÍTICOS ===" . PHP_EOL;
echo "Timestamp: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "Database: " . DB_NAME . PHP_EOL;
echo "========================================" . PHP_EOL;

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Array de índices a crear
    $indexes = [
        // Índice compuesto para asistencia (empleado + timestamp)
        [
            'name' => 'idx_asistencia_empleado_timestamp',
            'table' => 'asistencia',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_timestamp 
                      ON asistencia(empleado_id, timestamp DESC)'
        ],
        
        // Índice compuesto para asistencia por dispositivo + timestamp
        [
            'name' => 'idx_asistencia_dispositivo_timestamp',
            'table' => 'asistencia',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_asistencia_dispositivo_timestamp 
                      ON asistencia(dispositivo_id, timestamp DESC)'
        ],
        
        // Índice compuesto para asistencia por tipo biométrico + timestamp
        [
            'name' => 'idx_asistencia_tipo_timestamp',
            'table' => 'asistencia',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_asistencia_tipo_timestamp 
                      ON asistencia(tipo_biometria, timestamp DESC)'
        ],
        
        // Índice compuesto para empleados por área + estado activo
        [
            'name' => 'idx_empleados_area_activo',
            'table' => 'empleados',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_empleados_area_activo 
                      ON empleados(area, activo)'
        ],
        
        // Índice compuesto para retardos por empleado + fecha
        [
            'name' => 'idx_retardos_empleado_fecha',
            'table' => 'retardos',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_retardos_empleado_fecha 
                      ON retardos(empleado_id, fecha DESC)'
        ],
        
        // Índice compuesto para comisiones por empleado + fecha vencimiento
        [
            'name' => 'idx_comisiones_empleado_vencimiento',
            'table' => 'comisiones',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_comisiones_empleado_vencimiento 
                      ON comisiones(empleado_id, fecha_vencimiento DESC)'
        ],
        
        // Índice compuesto para dispositivos por sede + estado activo
        [
            'name' => 'idx_dispositivos_sede_activo',
            'table' => 'dispositivos_biometricos',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_dispositivos_sede_activo 
                      ON dispositivos_biometricos(sede, activo)'
        ],
        
        // Índice compuesto para ausencias por empleado + fecha
        [
            'name' => 'idx_ausencias_empleado_fecha',
            'table' => 'ausencias',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_ausencias_empleado_fecha 
                      ON ausencias(empleado_id, fecha DESC)'
        ],
        
        // Índice compuesto para sanciones por empleado + año
        [
            'name' => 'idx_sanciones_empleado_anio',
            'table' => 'sanciones',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_sanciones_empleado_anio 
                      ON sanciones(empleado_id, anio)'
        ],
        
        // Índice compuesto para horarios por sede + estado activo
        [
            'name' => 'idx_horarios_sede_activo',
            'table' => 'horarios_laborales',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_horarios_sede_activo 
                      ON horarios_laborales(sede, activo)'
        ],
        
        // Índice compuesto para bloques de ciclo por ciclo + día + hora
        [
            'name' => 'idx_bloques_ciclo_dia_hora',
            'table' => 'bloques_ciclo',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_bloques_ciclo_dia_hora 
                      ON bloques_ciclo(ciclo_id, dia_semana, hora_inicio)'
        ],
        
        // Índice para usuarios por rol + estado activo
        [
            'name' => 'idx_usuarios_rol_activo',
            'table' => 'usuarios',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_usuarios_rol_activo 
                      ON usuarios(rol, activo)'
        ],
        
        // Índice de fecha extraído de timestamp para asistencia
        [
            'name' => 'idx_asistencia_fecha',
            'table' => 'asistencia',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_asistencia_fecha 
                      ON asistencia((DATE(timestamp)))'
        ],
        
        // Índice compuesto para reportes mensuales por empleado y año-mes
        [
            'name' => 'idx_asistencia_empleado_ano_mes',
            'table' => 'asistencia',
            'sql' => 'CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_ano_mes 
                      ON asistencia(empleado_id, (YEAR(timestamp)), (MONTH(timestamp)))'
        ]
    ];
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($indexes as $index) {
        try {
            echo "Creando índice: {$index['name']} en tabla {$index['table']}..." . PHP_EOL;
            
            $startTime = microtime(true);
            
            $stmt = $pdo->prepare($index['sql']);
            $result = $stmt->execute();
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            if ($result) {
                echo "✅ Índice {$index['name']} creado exitosamente ({$executionTime}ms)" . PHP_EOL;
                $successCount++;
            } else {
                echo "❌ Error creando índice {$index['name']}" . PHP_EOL;
                $errorCount++;
                $errors[] = "Error en índice {$index['name']}";
            }
            
        } catch (PDOException $e) {
            echo "❌ Error ejecutando índice {$index['name']}: " . $e->getMessage() . PHP_EOL;
            $errorCount++;
            $errors[] = "Error en índice {$index['name']}: " . $e->getMessage();
        }
        
        echo PHP_EOL;
    }
    
    // Mostrar estadísticas de índices creados
    echo "=== ESTADÍSTICAS DE EJECUCIÓN ===" . PHP_EOL;
    echo "Índices creados exitosamente: {$successCount}" . PHP_EOL;
    echo "Errores: {$errorCount}" . PHP_EOL;
    
    if (!empty($errors)) {
        echo PHP_EOL . "=== ERRORES DETALLADOS ===" . PHP_EOL;
        foreach ($errors as $error) {
            echo "❌ " . $error . PHP_EOL;
        }
    }
    
    // Mostrar tamaño estimado de los índices
    try {
        $stmt = $pdo->prepare("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Table Size (MB)',
                ROUND((index_length / 1024 / 1024), 2) AS 'Index Size (MB)'
            FROM information_schema.tables 
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ");
        $stmt->execute([DB_NAME]);
        $tables = $stmt->fetchAll();
        
        echo PHP_EOL . "=== TAMAÑO DE TABLAS E ÍNDICES ===" . PHP_EOL;
        foreach ($tables as $table) {
            echo "{$table['table_name']}: {$table['Table Size (MB)']}MB total, {$table['Index Size (MB)']}MB en índices" . PHP_EOL;
        }
        
    } catch (Exception $e) {
        echo "❌ Error obteniendo tamaño de tablas: " . $e->getMessage() . PHP_EOL;
    }
    
    // Validar creación de índices específicos
    echo PHP_EOL . "=== VALIDACIÓN DE ÍNDICES CRÍTICOS ===" . PHP_EOL;
    $criticalIndexes = [
        'idx_asistencia_empleado_timestamp',
        'idx_asistencia_dispositivo_timestamp',
        'idx_empleados_area_activo',
        'idx_retardos_empleado_fecha',
        'idx_comisiones_empleado_vencimiento'
    ];
    
    foreach ($criticalIndexes as $indexName) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as exists 
            FROM information_schema.statistics 
            WHERE table_schema = ? 
            AND index_name = ?
        ");
        $stmt->execute([DB_NAME, $indexName]);
        $exists = $stmt->fetch()['exists'];
        
        echo ($exists > 0 ? "✅" : "❌") . " {$indexName}" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== EJECUCIÓN COMPLETADA ===" . PHP_EOL;
    echo "Timestamp: " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "Resultado: " . ($errorCount === 0 ? "EXITOSO" : "CON ERRORES") . PHP_EOL;
    
    if ($errorCount === 0) {
        echo "🎉 Todos los índices críticos han sido creados exitosamente!" . PHP_EOL;
        echo "📈 Se espera una mejora del 60-80% en queries principales." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
?>