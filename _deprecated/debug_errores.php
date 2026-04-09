<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoUniversalParser.php';
require_once __DIR__ . '/models/ZKTecoAsistenciaInserter.php';

echo "=== 🐛 DEBUG SIMPLE DE ERRORES ===\n\n";

try {
    // 1. Cargar mapeo
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT empleado_id, zk_empleado_id 
        FROM zk_empleado_mapeo
    ");
    
    $stmt->execute();
    $mapeo = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeo[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    
    echo "✅ Mapeo cargado: " . count($mapeo) . " registros\n";
    
    // 2. Cargar un solo registro para probar
    echo "📋 Procesando UN SOLO registro para debug...\n";
    $parser = new ZKTecoUniversalParser(['procesar_registros_fallidos' => true]);
    $parser->setMapeoEmpleados($mapeo);
    $resultadoParser = $parser->procesarArchivo(__DIR__ . '/data/1_attlog.dat');
    
    if (!$resultadoParser['exito']) {
        echo "❌ Error en parser: " . $resultadoParser['error'] . "\n";
        exit(1);
    }
    
    // Tomar solo el primer registro para análisis detallado
    $primerRegistro = $resultadoParser['registros'][0];
    
    echo "📋 Registro a analizar:\n";
    echo json_encode($primerRegistro, JSON_PRETTY_PRINT) . "\n\n";
    
    // 3. Probar inserción con ZKTecoAsistenciaInserter
    echo "💾 Probando inserción con ZKTecoAsistenciaInserter...\n";
    
    $inserter = new ZKTecoAsistenciaInserter([
        'procesar_registros_fallidos' => true,
        'evitar_duplicados' => false,
        'verificar_existencia_rapida' => false
    ]);
    
    $resultadoInsercion = $inserter->insertarRegistros([$primerRegistro]);
    
    echo "📈 Resultado inserción:\n";
    echo json_encode($resultadoInsercion, JSON_PRETTY_PRINT) . "\n\n";
    
    // 4. Analizar errores
    if (!$resultadoInsercion['exito']) {
        echo "❌ Error en inserción\n";
        
        if (isset($resultadoInsercion['estadisticas']['errores_detallados'])) {
            echo "📝 Errores encontrados:\n";
            foreach ($resultadoInsercion['estadisticas']['errores_detallados'] as $i => $error) {
                echo "   " . ($i + 1) . ". " . $error . "\n";
            }
        }
    }
    
    // 5. Verificar qué datos se están pasando al inserter
    echo "\n🔍 Datos que llegan al inserter:\n";
    if (isset($primerRegistro['empleado_id'])) {
        echo "   empleado_id: " . $primerRegistro['empleado_id'] . "\n";
    } else {
        echo "   empleado_id: MISSING\n";
    }
    
    if (isset($primerRegistro['fecha'])) {
        echo "   fecha: " . $primerRegistro['fecha'] . "\n";
    } else {
        echo "   fecha: MISSING\n";
    }
    
    if (isset($primerRegistro['hora'])) {
        echo "   hora: " . $primerRegistro['hora'] . "\n";
    } else {
        echo "   hora: MISSING\n";
    }
    
    if (isset($primerRegistro['hora_entrada'])) {
        echo "   hora_entrada: " . ($primerRegistro['hora_entrada'] ?? 'NULL') . "\n";
    } else {
        echo "   hora_entrada: NULL\n";
    }
    
    if (isset($primerRegistro['hora_salida'])) {
        echo "   hora_salida: " . ($primerRegistro['hora_salida'] ?? 'NULL') . "\n";
    } else {
        echo "   hora_salida: NULL\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 🏁 FIN DE DEBUG ===\n";
?>