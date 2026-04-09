<?php
require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/ZKTecoUniversalParser.php';

echo "=== DIAGNÓSTICO DE PROCESAMIENTO ZKTECO ===\n";

try {
    $parser = new ZKTecoUniversalParser();
    
    // Cargar mapeo
    $db = Database::getInstance();
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
    
    $parser->setMapeoEmpleados($mapeo);
    echo "Mapeo cargado: " . count($mapeo) . " registros\n";
    
    // Procesar las primeras 10 líneas para diagnóstico
    $handle = fopen('data/1_attlog.dat', 'r');
    $lineaNumero = 0;
    $procesadas = 0;
    $omitidas = 0;
    
    echo "\nProcesando primeras 10 líneas:\n";
    echo str_repeat("-", 80) . "\n";
    
    while (($linea = fgets($handle)) !== false && $procesadas + $omitidas < 10) {
        $lineaNumero++;
        $linea = trim($linea);
        
        if (empty($linea)) continue;
        
        echo "Línea $lineaNumero: $linea\n";
        
        // Extraer campos
        $campos = explode("\t", $linea);
        $zkEmpleadoId = (int)($campos[0] ?? 0);
        $datetime = $campos[1] ?? '';
        
        echo "  ZK ID: $zkEmpleadoId\n";
        echo "  DateTime: $datetime\n";
        echo "  Mapeado a: " . ($mapeo[$zkEmpleadoId] ?? 'NULL') . "\n";
        
        // Verificar si existe mapeo
        if (!isset($mapeo[$zkEmpleadoId])) {
            echo "  ❌ OMITIDO: Sin mapeo ZK-ID $zkEmpleadoId\n";
            $omitidas++;
            continue;
        }
        
        // Validar formato datetime
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime)) {
            echo "  ❌ OMITIDO: Formato datetime inválido\n";
            $omitidas++;
            continue;
        }
        
        echo "  ✅ VÁLIDO\n";
        $procesadas++;
        
        // Verificar qué día de la semana es
        $diaSemana = date('w', strtotime($datetime));
        $nombreDia = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$diaSemana];
        echo "  Día: $nombreDia (ID: $diaSemana)\n";
        
        // Verificar resultado
        $resultado = (int)($campos[4] ?? 0);
        echo "  Resultado: $resultado\n";
        
        echo str_repeat("-", 40) . "\n";
    }
    
    fclose($handle);
    
    echo "\nRESUMEN:\n";
    echo "Líneas procesadas: " . ($procesadas + $omitidas) . "\n";
    echo "Válidas: $procesadas\n";
    echo "Omitidas: $omitidas\n";
    
    // Procesar archivo completo
    echo "\nProcesando archivo completo...\n";
    $resultado = $parser->procesarArchivo('data/1_attlog.dat');
    
    if ($resultado['exito']) {
        echo "✅ Archivo procesado exitosamente\n";
        echo "Registros válidos: " . $resultado['estadisticas']['registros_validos'] . "\n";
        echo "Registros omitidos: " . $resultado['estadisticas']['registros_omitidos'] . "\n";
        echo "Total errores: " . count($resultado['errores']) . "\n";
        
        if (count($resultado['registros']) > 0) {
            echo "\nPrimeros 5 registros procesados:\n";
            foreach (array_slice($resultado['registros'], 0, 5) as $reg) {
                echo "- ZK{$reg['zk_empleado_id']} -> Emp{$reg['empleado_id']} {$reg['fecha']} {$reg['hora']} {$reg['accion']}\n";
            }
        }
    } else {
        echo "❌ Error procesando archivo: " . $resultado['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>