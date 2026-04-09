<?php
// Investigar por qué se omiten 7 registros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== INVESTIGACIÓN REGISTROS OMITIDOS ===\n";

try {
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'models/ZKTecoAsistenciaInserter.php';
    
    // Limpiar tabla
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("DELETE FROM asistencia");
    
    // Procesar archivo
    $parser = new ZKTecoUniversalParser();
    
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    
    $resultado = $parser->procesarArchivo('20241106.DAT');
    
    echo "📋 TODOS LOS REGISTROS DEL ARCHIVO:\n";
    foreach ($resultado['registros'] as $i => $registro) {
        echo sprintf("[%2d] ZK:%2d → Emp:%2d | %s | %s\n",
            $i,
            $registro['zk_empleado_id'],
            $registro['empleado_id'],
            $registro['accion'] == 0 ? 'ENTRADA' : 'SALIDA',
            $registro['hora']
        );
    }
    
    echo "\n📤 PROCESANDO REGISTRO POR REGISTRO:\n";
    
    $configInsercion = [
        'evitar_duplicados' => true,
        'actualizar_registros_existentes' => true,
        'verificar_existencia_rapida' => false
    ];
    
    $inserter = new ZKTecoAsistenciaInserter($configInsercion);
    
    foreach ($resultado['registros'] as $i => $registro) {
        echo sprintf("[%2d] Procesando: ZK:%d → Emp:%d | %s | %s ",
            $i,
            $registro['zk_empleado_id'],
            $registro['empleado_id'],
            $registro['accion'] == 0 ? 'ENTRADA' : 'SALIDA',
            $registro['hora']
        );
        
        // Verificar duplicados exactos antes de insertar
        $campoHora = $registro['accion'] == 0 ? 'hora_entrada' : 'hora_salida';
        $sql = "SELECT id FROM asistencia WHERE empleado_id = ? AND fecha = ? AND {$campoHora} = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$registro['empleado_id'], $registro['fecha'], $registro['hora']]);
        $duplicadoExacto = $stmt->fetch();
        
        if ($duplicadoExacto) {
            echo "❌ DUPLICADO EXACTO - omitido\n";
        } else {
            echo "✅ ÚNICO - se insertará\n";
        }
    }
    
    // Insertar todos y ver resultados
    $resultadoInsercion = $inserter->insertarRegistros($resultado['registros']);
    
    echo "\n📊 RESULTADOS FINALES:\n";
    echo json_encode($resultadoInsercion['resumen'], JSON_PRETTY_PRINT) . "\n";
    
    // Mostrar registros insertados
    echo "\n📋 REGISTROS FINALMENTE INSERTADOS:\n";
    $stmt = $pdo->query("SELECT empleado_id, fecha, hora_entrada, hora_salida, created_at 
                         FROM asistencia ORDER BY created_at");
    
    $insertados = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $insertados++;
        echo sprintf("[%2d] Emp:%d | %s | %s | %s\n",
            $insertados,
            $row['empleado_id'],
            $row['fecha'],
            $row['hora_entrada'] ?? 'NULL',
            $row['hora_salida'] ?? 'NULL'
        );
    }
    
    echo "\n🔍 ANÁLISIS FINAL:\n";
    echo "Total registros archivo: " . count($resultado['registros']) . "\n";
    echo "Total insertados: $insertados\n";
    echo "Diferencia: " . (count($resultado['registros']) - $insertados) . " omitidos\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN INVESTIGACIÓN ===\n";
?>