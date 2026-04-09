<?php
// Procesamiento completo del archivo DAT con nueva lógica
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PROCESAMIENTO COMPLETO ARCHIVO DAT ===\n";

try {
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'models/ZKTecoAsistenciaInserter.php';
    
    // Limpiar tabla para prueba completa
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("DELETE FROM asistencia");
    echo "✅ Tabla asistencia limpiada\n";
    
    // Procesar archivo completo
    $detector = new ZKTecoFormatDetector();
    $formato = $detector->detectarFormato('20241106.DAT');
    echo "✅ Formato detectado: {$formato['formato']} ({$formato['confianza']}%)\n";
    
    $parser = new ZKTecoUniversalParser();
    
    // Configurar mapeo completo
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    echo "✅ Mapeo configurado: " . count($mapeos) . " registros\n";
    
    $resultado = $parser->procesarArchivo('20241106.DAT');
    echo "✅ Archivo procesado: {$resultado['estadisticas']['registros_validos']} registros válidos\n";
    
    // Insertar todos los registros
    $configInsercion = [
        'evitar_duplicados' => true,
        'actualizar_registros_existentes' => true,
        'verificar_existencia_rapida' => false
    ];
    
    $inserter = new ZKTecoAsistenciaInserter($configInsercion);
    $resultadoInsercion = $inserter->insertarRegistros($resultado['registros']);
    
    echo "\n📊 RESULTADOS COMPLETOS:\n";
    echo json_encode($resultadoInsercion['resumen'], JSON_PRETTY_PRINT) . "\n";
    
    // Verificar estado final
    echo "\n📋 ESTADO FINAL TABLA ASISTENCIA:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $total = $stmt->fetch()['total'];
    echo "Total registros insertados: $total\n";
    
    if ($total > 0) {
        echo "\nPrimeros 10 registros:\n";
        $stmt = $pdo->query("SELECT empleado_id, fecha, hora_entrada, hora_salida, 
                                     DATE(created_at) as creado 
                              FROM asistencia 
                              ORDER BY hora_entrada, created_at 
                              LIMIT 10");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("  Emp:%s | %s | %s | %s\n",
                $row['empleado_id'],
                $row['fecha'],
                $row['hora_entrada'] ?? 'NULL',
                $row['hora_salida'] ?? 'NULL'
            );
        }
    }
    
    // Análisis por empleado
    echo "\n📈 ANÁLISIS POR EMPLEADO:\n";
    $stmt = $pdo->query("
        SELECT empleado_id, 
               COUNT(*) as total_registros,
               MIN(hora_entrada) as primera_entrada,
               MAX(hora_entrada) as ultima_entrada,
               COUNT(CASE WHEN hora_salida IS NOT NULL THEN 1 END) as con_salida
        FROM asistencia 
        GROUP BY empleado_id 
        ORDER BY empleado_id
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("  Empleado %s: %s registros | Entrada: %s-%s | Con salida: %s\n",
            $row['empleado_id'],
            $row['total_registros'],
            substr($row['primera_entrada'] ?? '--:--', 0, 5),
            substr($row['ultima_entrada'] ?? '--:--', 0, 5),
            $row['con_salida']
        );
    }
    
    echo "\n🎉 PROCESAMIENTO COMPLETADO CON ÉXITO\n";
    echo "   - Total registros procesados: {$resultadoInsercion['resumen']['total']}\n";
    echo "   - Total insertados: {$resultadoInsercion['resumen']['insertados']}\n";
    echo "   - Total actualizados: {$resultadoInsercion['resumen']['actualizados']}\n";
    echo "   - Total omitidos: {$resultadoInsercion['resumen']['omitidos']}\n";
    echo "   - Total errores: {$resultadoInsercion['resumen']['errores']}\n";
    
    if ($resultadoInsercion['resumen']['errores'] == 0) {
        echo "   ✅ Sin errores - Sistema funcionando perfectamente\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN PROCESAMIENTO COMPLETO ===\n";
?>