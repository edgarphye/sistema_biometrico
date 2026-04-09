<?php
// Depuración detallada de la lógica de entrada/salida
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEPURACIÓN LÓGICA ENTRADA/SALIDA ===\n";

try {
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'models/ZKTecoAsistenciaInserter.php';
    
    // Limpiar tabla de asistencia para prueba limpia
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("DELETE FROM asistencia");
    echo "✅ Tabla asistencia limpiada\n";
    
    // Procesar archivo completo
    $detector = new ZKTecoFormatDetector();
    $formato = $detector->detectarFormato('20241106.DAT');
    
    $parser = new ZKTecoUniversalParser();
    
    // Configurar mapeo
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    
    $resultado = $parser->procesarArchivo('20241106.DAT');
    echo "✅ Archivo procesado: {$resultado['estadisticas']['registros_validos']} registros\n";
    
    // Analizar primeros registros con sus acciones
    echo "\n📋 ANÁLISIS DE REGISTROS POR ACCIÓN:\n";
    $contadorAcciones = ['entrada' => 0, 'salida' => 0];
    $registrosPorEmpleado = [];
    
    foreach (array_slice($resultado['registros'], 0, 10) as $i => $registro) {
        $accion = $registro['accion'] == 0 ? 'ENTRADA' : 'SALIDA';
        $contadorAcciones[$registro['accion'] == 0 ? 'entrada' : 'salida']++;
        
        if (!isset($registrosPorEmpleado[$registro['empleado_id']])) {
            $registrosPorEmpleado[$registro['empleado_id']] = [];
        }
        $registrosPorEmpleado[$registro['empleado_id']][] = [
            'index' => $i,
            'accion' => $accion,
            'hora' => $registro['hora']
        ];
        
        echo sprintf("  [%d] ZK:%s → Emp:%s | %s | %s\n", 
            $i, 
            $registro['zk_empleado_id'], 
            $registro['empleado_id'], 
            $accion, 
            $registro['hora']
        );
    }
    
    echo "\n📊 RESUMEN POR ACCIONES:\n";
    echo "  - Entradas (acción 0): {$contadorAcciones['entrada']}\n";
    echo "  - Salidas (acción 1): {$contadorAcciones['salida']}\n";
    
    echo "\n📈 REGISTROS POR EMPLEADO:\n";
    foreach ($registrosPorEmpleado as $empId => $regs) {
        echo "  Empleado $empId:\n";
        foreach ($regs as $reg) {
            echo "    [{$reg['index']}] {$reg['accion']} - {$reg['hora']}\n";
        }
    }
    
    // Probar inserción con configuración explícita
    echo "\n🔧 CONFIGURACIÓN INSERCIÓN:\n";
    $configInsercion = [
        'evitar_duplicados' => true,
        'actualizar_registros_existentes' => true,
        'verificar_existencia_rapida' => false  // Desactivar caché para verificación exacta
    ];
    
    $inserter = new ZKTecoAsistenciaInserter($configInsercion);
    echo "  ✅ evitar_duplicados: " . ($configInsercion['evitar_duplicados'] ? 'SÍ' : 'NO') . "\n";
    echo "  ✅ actualizar_registros_existentes: " . ($configInsercion['actualizar_registros_existentes'] ? 'SÍ' : 'NO') . "\n";
    
    // Insertar solo los primeros 5 registros para análisis
    $registrosPrueba = array_slice($resultado['registros'], 0, 5);
    echo "\n📤 INSERCIÓN DE PRIMEROS 5 REGISTROS:\n";
    
    $resultadoInsercion = $inserter->insertarRegistros($registrosPrueba);
    
    echo "📊 RESULTADOS INSERCIÓN:\n";
    echo json_encode($resultadoInsercion, JSON_PRETTY_PRINT) . "\n";
    
    // Verificar estado final de la tabla
    echo "\n📋 ESTADO FINAL TABLA ASISTENCIA:\n";
    $stmt = $pdo->query("SELECT empleado_id, fecha, hora_entrada, hora_salida, created_at FROM asistencia ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("  Emp:%s | %s | Entrada:%s | Salida:%s | Creado:%s\n",
            $row['empleado_id'],
            $row['fecha'],
            $row['hora_entrada'] ?? 'NULL',
            $row['hora_salida'] ?? 'NULL',
            $row['created_at']
        );
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEPURACIÓN ===\n";
?>