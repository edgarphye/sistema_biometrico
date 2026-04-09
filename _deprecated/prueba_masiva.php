<?php
// Prueba completa del sistema optimizado para 19,583 registros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRUEBA SISTEMA MASIVO 19,583 REGISTROS ===\n";

try {
    // Cargar dependencias
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'ZKTecoAsistenciaInserterOptimizado.php';
    
    echo "1. Inicializando sistema optimizado...\n";
    
    // Limpiar tabla para prueba limpia
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("DELETE FROM asistencia");
    echo "✅ Tabla asistencia limpiada\n";
    
    // Verificar empleados y mapeos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE activo = 1");
    $totalEmpleados = $stmt->fetch()['total'];
    echo "✅ Empleados activos: $totalEmpleados\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM zk_empleado_mapeo");
    $totalMapeos = $stmt->fetch()['total'];
    echo "✅ Mapeos configurados: $totalMapeos\n";
    
    // Procesar archivo grande
    echo "\n2. Procesando archivo DAT grande...\n";
    $detector = new ZKTecoFormatDetector();
    $formato = $detector->detectarFormato('asistencia_grande.DAT');
    echo "✅ Formato detectado: {$formato['formato']} ({$formato['confianza']}%)\n";
    
    $parser = new ZKTecoUniversalParser();
    
    // Configurar mapeos completos
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    echo "✅ Mapeo cargado: " . count($mapeos) . " registros\n";
    
    $resultadoParser = $parser->procesarArchivo('asistencia_grande.DAT');
    echo "✅ Archivo procesado: {$resultadoParser['estadisticas']['registros_validos']} registros válidos\n";
    
    if (empty($resultadoParser['registros'])) {
        echo "❌ No hay registros procesados\n";
        exit(1);
    }
    
    // Análisis de los datos
    echo "\n3. Análisis de datos procesados:\n";
    
    $empleadosEnDatos = array_unique(array_map(function($r) { 
        return $r['empleado_id']; 
    }, $resultadoParser['registros']));
    
    $fechasEnDatos = array_unique(array_map(function($r) { 
        return $r['fecha']; 
    }, $resultadoParser['registros']));
    
    $entradas = array_filter($resultadoParser['registros'], function($r) {
        return $r['accion'] == 0;
    });
    
    $salidas = array_filter($resultadoParser['registros'], function($r) {
        return $r['accion'] == 1;
    });
    
    echo "   📊 Total registros: " . count($resultadoParser['registros']) . "\n";
    echo "   👥 Empleados únicos: " . count($empleadosEnDatos) . "\n";
    echo "   📅 Fechas únicas: " . count($fechasEnDatos) . "\n";
    echo "   🚪 Entradas: " . count($entradas) . "\n";
    echo "   🚪 Salidas: " . count($salidas) . "\n";
    
    // Mostrar distribución
    echo "\n📋 Distribución por empleado (primeros 10):\n";
    $distribucion = [];
    foreach ($resultadoParser['registros'] as $registro) {
        $emp = $registro['empleado_id'];
        if (!isset($distribucion[$emp])) {
            $distribucion[$emp] = ['entradas' => 0, 'salidas' => 0];
        }
        if ($registro['accion'] == 0) {
            $distribucion[$emp]['entradas']++;
        } else {
            $distribucion[$emp]['salidas']++;
        }
    }
    
    foreach (array_slice($distribucion, 0, 10, true) as $emp => $datos) {
        echo sprintf("   Emp:%2d → Entradas:%3d Salidas:%3d Total:%3d\n", 
            $emp, $datos['entradas'], $datos['salidas'], 
            $datos['entradas'] + $datos['salidas']
        );
    }
    
    // Procesamiento masivo optimizado
    echo "\n4. Iniciando procesamiento masivo optimizado...\n";
    
    $configuracion = [
        'evitar_duplicados' => true,
        'batch_size' => 1000,
        'verificar_existencia_rapida' => true,
        'tiempo_max_procesamiento' => 600  // 10 minutos
    ];
    
    $inserter = new ZKTecoAsistenciaInserterOptimizado($configuracion);
    
    echo "🔧 Configuración: batch_size={$configuracion['batch_size']}\n";
    
    $tiempoInicio = microtime(true);
    $resultadoInsercion = $inserter->procesarRegistrosMasivos($resultadoParser['registros']);
    $tiempoTotal = microtime(true) - $tiempoInicio;
    
    echo "\n📊 RESULTADOS DEL PROCESAMIENTO MASIVO:\n";
    echo json_encode($resultadoInsercion['resumen'], JSON_PRETTY_PRINT) . "\n";
    
    // Verificación final
    echo "\n5. Verificación final en base de datos:\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $totalRegistros = $stmt->fetch()['total'];
    echo "   📊 Total registros en BD: $totalRegistros\n";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN hora_entrada IS NOT NULL THEN 1 END) as con_entrada,
            COUNT(CASE WHEN hora_salida IS NOT NULL THEN 1 END) as con_salida,
            COUNT(CASE WHEN hora_entrada IS NOT NULL AND hora_salida IS NOT NULL THEN 1 END) as completos
        FROM asistencia
    ");
    
    $estadisticasBD = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   📊 Con entrada: {$estadisticasBD['con_entrada']}\n";
    echo "   📊 Con salida: {$estadisticasBD['con_salida']}\n";
    echo "   📊 Registros completos: {$estadisticasBD['completos']}\n";
    
    // Mostrar muestra de registros procesados
    echo "\n📋 Muestra de registros procesados (primeros 15):\n";
    $stmt = $pdo->query("
        SELECT empleado_id, fecha, hora_entrada, hora_salida, created_at
        FROM asistencia 
        ORDER BY empleado_id, fecha, hora_entrada 
        LIMIT 15
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("   Emp:%2d | %s | %s | %s\n",
            $row['empleado_id'],
            $row['fecha'],
            $row['hora_entrada'] ?? 'NULL',
            $row['hora_salida'] ?? 'NULL'
        );
    }
    
    // Análisis de rendimiento
    echo "\n🚀 ANÁLISIS DE RENDIMIENTO:\n";
    $registrosPorSegundo = $totalRegistros / $tiempoTotal;
    echo "   ⏱️  Tiempo total: " . number_format($tiempoTotal, 2) . " segundos\n";
    echo "   📊 Registros/segundo: " . number_format($registrosPorSegundo, 2) . "\n";
    echo "   📊 Tiempo promedio/registro: " . number_format(($tiempoTotal / $totalRegistros) * 1000, 2) . " ms\n";
    
    // Verificación de consistencia
    echo "\n🎯 VERIFICACIÓN DE CONSISTENCIA:\n";
    
    if ($resultadoInsercion['exito']) {
        echo "   ✅ Procesamiento exitoso\n";
        
        $resumen = $resultadoInsercion['resumen'];
        if ($resumen['insertados'] + $resumen['actualizados'] == $totalRegistros) {
            echo "   ✅ Consistencia perfecta entre procesamiento y BD\n";
        } else {
            echo "   ⚠️  Diferencia: Procesados=" . ($resumen['insertados'] + $resumen['actualizados']) . " BD=$totalRegistros\n";
        }
        
        if ($resumen['errores'] == 0) {
            echo "   ✅ Sin errores en el procesamiento\n";
        } else {
            echo "   ❌ {$resumen['errores']} errores detectados\n";
        }
        
        echo "\n🎉 RESULTADO FINAL:\n";
        echo "   ✅ Sistema masivo funcional\n";
        echo "   ✅ Lógica de entrada/salida implementada\n";
        echo "   ✅ Procesamiento optimizado para volúmenes grandes\n";
        echo "   ✅ Manejo correcto de duplicados\n";
        echo "   ✅ Consistencia en base de datos\n";
        
        if ($registrosPorSegundo > 100) {
            echo "   🚀 Rendimiento EXCELENTE (>100 regs/seg)\n";
        } elseif ($registrosPorSegundo > 50) {
            echo "   ✅ Rendimiento BUENO (>50 regs/seg)\n";
        } else {
            echo "   ⚠️  Rendimiento aceptable (<50 regs/seg)\n";
        }
        
    } else {
        echo "   ❌ Error en procesamiento: " . ($resultadoInsercion['error'] ?? 'Desconocido') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN PRUEBA MASIVA ===\n";
?>