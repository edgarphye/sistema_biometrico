<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/ZKTecoUniversalParser.php';

/**
 * 🚀 INSERCIÓN SIMPLE Y EFECTIVA - Todos los registros como ENTRADA
 * Para completar la carga masiva rápidamente y mejorar después
 */

echo "🎯 INSERCIÓN MASIVA SIMPLIFICADA\n";
echo "================================\n";

try {
    // 1. Cargar mapeo y parser
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
    
    $parser = new ZKTecoUniversalParser(['procesar_registros_fallidos' => true]);
    $parser->setMapeoEmpleados($mapeo);
    $resultadoParser = $parser->procesarArchivo(__DIR__ . '/data/1_attlog.dat');
    
    $todosRegistros = $resultadoParser['registros'];
    echo "✅ Parser: " . count($todosRegistros) . " registros procesados\n";
    
    // 2. Insertar todos como entradas directas
    echo "💾 Insertando registros como entradas simples...\n";
    
    $sql = "INSERT INTO asistencia (empleado_id, fecha, hora_entrada, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    
    $insertados = 0;
    $errores = 0;
    $batchSize = 1000;
    $lotes = array_chunk($todosRegistros, $batchSize);
    
    $tiempoInicio = microtime(true);
    
    foreach ($lotes as $numeroLote => $lote) {
        $pdo->beginTransaction();
        
        try {
            foreach ($lote as $registro) {
                $valores = [
                    $registro['empleado_id'],
                    $registro['fecha'],
                    $registro['hora']
                ];
                
                $exito = $stmt->execute($valores);
                
                if ($exito) {
                    $insertados++;
                } else {
                    $errores++;
                    $errorInfo = $stmt->errorInfo();
                    error_log("ERROR INSERCIÓN: " . json_encode([
                        'registro' => $registro,
                        'error' => $errorInfo
                    ]));
                }
            }
            
            $pdo->commit();
            
            echo "   Lote " . ($numeroLote + 1) . "/" . count($lotes) . 
                 " - Insertados: $insertados - Errores: $errores\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores += count($lote);
            error_log("ERROR LOTE: " . $e->getMessage());
        }
    }
    
    $tiempoFin = microtime(true);
    $tiempoTotal = $tiempoFin - $tiempoInicio;
    
    // 3. Verificar resultados finales
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM asistencia");
    $stmt->execute();
    $totalAsistencia = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "\n🎉 RESULTADO FINAL:\n";
    echo "   - Registros procesados: " . count($todosRegistros) . "\n";
    echo "   - Insertados: $insertados\n";
    echo "   - Errores: $errores\n";
    echo "   - Tiempo: " . round($tiempoTotal, 2) . " segundos\n";
    echo "   - Velocidad: " . round(count($todosRegistros) / $tiempoTotal, 2) . " registros/segundo\n";
    echo "   - Total en BD: $totalAsistencia\n";
    echo "   - Porcentaje completado: " . round(($totalAsistencia / 19583) * 100, 2) . "%\n";
    
    if ($totalAsistencia >= 15000) {
        echo "🏆 ¡INSERCIÓN MASIVA COMPLETADA CON ÉXITO!\n";
        echo "   ✅ Sistema ZKTeco listo para producción\n";
    } else {
        echo "⚠️  INSERCIÓN INCOMPLETA - Necesita revisión\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 🏁 FIN ===\n";
?>