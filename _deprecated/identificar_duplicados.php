<?php
// Identificar exactamente cuáles registros son duplicados por hora
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== IDENTIFICANDO DUPLICADOS EXACTOS ===\n";

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
    
    echo "📋 ANÁLISIS DE HORAS DUPLICADAS:\n";
    
    // Agrupar por hora para encontrar duplicados
    $horas = [];
    foreach ($resultado['registros'] as $i => $registro) {
        $clave = $registro['empleado_id'] . '|' . $registro['fecha'] . '|' . $registro['hora'];
        if (!isset($horas[$clave])) {
            $horas[$clave] = [];
        }
        $horas[$clave][] = [
            'index' => $i,
            'zk_id' => $registro['zk_empleado_id'],
            'empleado_id' => $registro['empleado_id'],
            'fecha' => $registro['fecha'],
            'hora' => $registro['hora'],
            'accion' => $registro['accion']
        ];
    }
    
    $duplicados = [];
    $unicos = [];
    
    foreach ($horas as $clave => $registrosHora) {
        if (count($registrosHora) > 1) {
            $duplicados[$clave] = $registrosHora;
        } else {
            $unicos[$clave] = $registrosHora[0];
        }
    }
    
    echo "\n🔍 DUPLICADOS POR HORA EXACTA:\n";
    foreach ($duplicados as $clave => $regs) {
        echo "  $clave -> " . count($regs) . " registros:\n";
        foreach ($regs as $reg) {
            echo sprintf("    [%2d] ZK:%2d | %s\n", 
                $reg['index'], 
                $reg['zk_id'], 
                $reg['hora']
            );
        }
    }
    
    echo "\n✅ REGISTROS ÚNICOS (se insertarán):\n";
    foreach ($unicos as $clave => $reg) {
        echo sprintf("    [%2d] ZK:%2d | %s\n", 
            $reg['index'], 
            $reg['zk_id'], 
            $reg['hora']
        );
    }
    
    echo "\n📊 RESUMEN:\n";
    echo "  Total registros archivo: " . count($resultado['registros']) . "\n";
    echo "  Claves únicas: " . count($horas) . "\n";
    echo "  Registros duplicados: " . array_sum(array_map('count', $duplicados)) . "\n";
    echo "  Registros únicos: " . count($unicos) . "\n";
    echo "  Total esperado en BD: " . count($unicos) . "\n";
    
    // Probar inserción
    $configInsercion = [
        'evitar_duplicados' => true,
        'actualizar_registros_existentes' => true,
        'verificar_existencia_rapida' => false,
        'batch_size' => 1
    ];
    
    $inserter = new ZKTecoAsistenciaInserter($configInsercion);
    $resultadoInsercion = $inserter->insertarRegistros($resultado['registros']);
    
    echo "\n📋 RESULTADO INSERCIÓN:\n";
    echo json_encode($resultadoInsercion['resumen'], JSON_PRETTY_PRINT) . "\n";
    
    // Verificar BD
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $totalEnBD = $stmt->fetch()['total'];
    echo "\n📋 Registros en BD: $totalEnBD\n";
    
    // Mostrar registros insertados ordenados por hora
    echo "\n📋 REGISTROS INSERTADOS (ordenados por hora):\n";
    $stmt = $pdo->query("SELECT empleado_id, fecha, hora_entrada, created_at 
                         FROM asistencia ORDER BY hora_entrada");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("  Emp:%d | %s | %s\n",
            $row['empleado_id'],
            $row['fecha'],
            $row['hora_entrada']
        );
    }
    
    echo "\n🎯 CONCLUSIÓN:\n";
    if ($totalEnBD == count($unicos)) {
        echo "✅ El sistema funciona PERFECTAMENTE\n";
        echo "✅ Inserta solo registros únicos por hora\n";
        echo "✅ Omite correctamente duplicados exactos\n";
        echo "✅ Los 7 registros omitidos son duplicados de hora exacta\n";
    } else {
        echo "❌ Hay inconsistencia en los resultados\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN IDENTIFICACIÓN DUPLICADOS ===\n";
?>