<?php
// Procesar uno por uno para encontrar exactamente cuáles fallan
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PROCESAMIENTO INDIVIDUAL DETALLADO ===\n";

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
    
    // Procesar uno por uno
    $configInsercion = [
        'evitar_duplicados' => true,
        'actualizar_registros_existentes' => true,
        'verificar_existencia_rapida' => false
    ];
    
    $inserter = new ZKTecoAsistenciaInserter($configInsercion);
    
    $insertadosCount = 0;
    $fallidos = [];
    
    foreach ($resultado['registros'] as $i => $registro) {
        echo sprintf("[%2d] Intentando insertar: ZK:%d → Emp:%d | %s | %s",
            $i,
            $registro['zk_empleado_id'],
            $registro['empleado_id'],
            $registro['accion'] == 0 ? 'ENTRADA' : 'SALIDA',
            $registro['hora']
        );
        
        // Intentar insertar registro individual
        try {
            $resultadoIndividual = $inserter->insertarRegistros([$registro]);
            
            if ($resultadoIndividual['exito']) {
                if ($resultadoIndividual['resumen']['insertados'] > 0) {
                    echo " ✅ INSERTADO\n";
                    $insertadosCount++;
                } elseif ($resultadoIndividual['resumen']['omitidos'] > 0) {
                    echo " ❌ OMITIDO (duplicado)\n";
                    $fallidos[] = [
                        'index' => $i,
                        'registro' => $registro,
                        'motivo' => 'omitido_duplicado'
                    ];
                } elseif ($resultadoIndividual['resumen']['errores'] > 0) {
                    echo " ❌ ERROR\n";
                    $fallidos[] = [
                        'index' => $i,
                        'registro' => $registro,
                        'motivo' => 'error_insercion'
                    ];
                } else {
                    echo " ❌ SIN RESULTADO CLARO\n";
                    $fallidos[] = [
                        'index' => $i,
                        'registro' => $registro,
                        'motivo' => 'resultado_desconocido'
                    ];
                }
            } else {
                echo " ❌ FALLO GENERAL\n";
                $fallidos[] = [
                    'index' => $i,
                    'registro' => $registro,
                    'motivo' => 'fallo_general'
                ];
            }
            
        } catch (Exception $e) {
            echo " ❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
            $fallidos[] = [
                'index' => $i,
                'registro' => $registro,
                'motivo' => 'excepcion',
                'error' => $e->getMessage()
            ];
        }
    }
    
    echo "\n📊 RESUMEN FINAL:\n";
    echo "Total registros procesados: " . count($resultado['registros']) . "\n";
    echo "Total insertados: $insertadosCount\n";
    echo "Total fallidos: " . count($fallidos) . "\n";
    
    if (!empty($fallidos)) {
        echo "\n❌ DETALLE DE REGISTROS FALLIDOS:\n";
        foreach ($fallidos as $fallido) {
            echo sprintf("[%2d] ZK:%d → Emp:%d | %s | %s | Motivo: %s\n",
                $fallido['index'],
                $fallido['registro']['zk_empleado_id'],
                $fallido['registro']['empleado_id'],
                $fallido['registro']['accion'] == 0 ? 'ENTRADA' : 'SALIDA',
                $fallido['registro']['hora'],
                $fallido['motivo']
            );
            
            if (isset($fallido['error'])) {
                echo "     Error: " . $fallido['error'] . "\n";
            }
        }
    }
    
    // Verificar estado final
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $totalEnBD = $stmt->fetch()['total'];
    echo "\n📋 Registros en BD: $totalEnBD\n";
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN PROCESAMIENTO INDIVIDUAL ===\n";
?>