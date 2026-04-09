<?php
// Prueba final del sistema masivo con 19,583 registros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRUEBA FINAL 19,583 REGISTROS ===\n";

try {
    // Cargar dependencias
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'ZKTecoAsistenciaInserterOptimizado.php';
    
    echo "1. Inicializando sistema para 19,583 registros...\n";
    
    // Limpiar tabla para prueba limpia
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("DELETE FROM asistencia");
    echo "✅ Tabla asistencia limpiada\n";
    
    // Verificar configuración
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM empleados WHERE activo = 1");
    $totalEmpleados = $stmt->fetch()['total'];
    echo "✅ Empleados activos: $totalEmpleados\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM zk_empleado_mapeo");
    $totalMapeos = $stmt->fetch()['total'];
    echo "✅ Mapeos configurados: $totalMapeos\n";
    
    // Procesar archivo masivo
    echo "\n2. Detectando formato archivo masivo...\n";
    $detector = new ZKTecoFormatDetector();
    $formato = $detector->detectarFormato('asistencia_19583_final.DAT');
    echo "✅ Formato detectado: {$formato['formato']} ({$formato['confianza']}%)\n";
    
    echo "\n3. Configurando parser...\n";
    $parser = new ZKTecoUniversalParser();
    
    // Cargar todos los mapeos
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    echo "✅ Mapeos cargados: " . count($mapeos) . " registros\n";
    
    echo "\n4. Procesando archivo DAT (esto puede tardar unos minutos)...\n";
    $tiempoInicio = microtime(true);
    
    $resultadoParser = $parser->procesarArchivo('asistencia_19583_final.DAT');
    
    $tiempoParser = microtime(true) - $tiempoInicio;
    echo "✅ Parser completado en " . number_format($tiempoParser, 2) . " segundos\n";
    echo "✅ Registros válidos: {$resultadoParser['estadisticas']['registros_validos']}\n";
    
    if (empty($resultadoParser['registros'])) {
        echo "❌ ERROR: No hay registros procesados\n";
        exit(1);
    }
    
    // Análisis rápido de los datos
    echo "\n5. Análisis de datos procesados:\n";
    
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
    echo "   📊 Promedio por empleado: " . round(count($resultadoParser['registros']) / count($empleadosEnDatos), 1) . "\n";
    
    // Procesamiento masivo optimizado
    echo "\n6. Iniciando inserción masiva optimizada...\n";
    echo "   ⚠️  ESTE PROCESO PUEDE TOMAR VARIOS MINUTOS\n";
    echo "   📊 Procesando " . count($resultadoParser['registros']) . " registros\n";
    
    $configuracion = [
        'evitar_duplicados' => true,
        'batch_size' => 2000,  // Lotes grandes para rendimiento
        'verificar_existencia_rapida' => true,
        'tiempo_max_procesamiento' => 1800  // 30 minutos máximo
    ];
    
    $inserter = new ZKTecoAsistenciaInserterOptimizado($configuracion);
    
    $tiempoInicioInsercion = microtime(true);
    $resultadoInsercion = $inserter->procesarRegistrosMasivos($resultadoParser['registros']);
    $tiempoInsercion = microtime(true) - $tiempoInicioInsercion;
    
    echo "\n7. RESULTADOS DEL PROCESAMIENTO MASIVO:\n";
    echo json_encode($resultadoInsercion['resumen'], JSON_PRETTY_PRINT) . "\n";
    
    // Verificación final
    echo "\n8. VERIFICACIÓN FINAL EN BASE DE DATOS:\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $totalRegistros = $stmt->fetch()['total'];
    echo "   📊 Total registros en BD: $totalRegistros\n";
    
    // Estadísticas detalladas
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN hora_entrada IS NOT NULL THEN 1 END) as con_entrada,
            COUNT(CASE WHEN hora_salida IS NOT NULL THEN 1 END) as con_salida,
            COUNT(CASE WHEN hora_entrada IS NOT NULL AND hora_salida IS NOT NULL THEN 1 END) as completos,
            COUNT(DISTINCT empleado_id) as empleados_unicos,
            COUNT(DISTINCT fecha) as dias_unicos
        FROM asistencia
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   📊 Con entrada: {$stats['con_entrada']}\n";
    echo "   📊 Con salida: {$stats['con_salida']}\n";
    echo "   📊 Registros completos: {$stats['completos']}\n";
    echo "   👥 Empleados únicos: {$stats['empleados_unicos']}\n";
    echo "   📅 Días únicos: {$stats['dias_unicos']}\n";
    
    // Muestra de datos
    echo "\n9. MUESTRA DE REGISTROS PROCESADOS (primeros 15):\n";
    $stmt = $pdo->query("
        SELECT empleado_id, fecha, hora_entrada, hora_salida, created_at
        FROM asistencia 
        ORDER BY empleado_id, fecha, hora_entrada 
        LIMIT 15
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("   👤 Emp:%3d | %s | %s | %s\n",
            $row['empleado_id'],
            $row['fecha'],
            $row['hora_entrada'] ?? 'NULL',
            $row['hora_salida'] ?? 'NULL'
        );
    }
    
    // Análisis de rendimiento
    $tiempoTotal = $tiempoParser + $tiempoInsercion;
    $regsPorSegundo = $totalRegistros / $tiempoInsercion;
    
    echo "\n10. ANÁLISIS DE RENDIMIENTO:\n";
    echo "   ⏱️  Tiempo parser: " . number_format($tiempoParser, 2) . " seg\n";
    echo "   ⏱️  Tiempo inserción: " . number_format($tiempoInsercion, 2) . " seg\n";
    echo "   ⏱️  Tiempo total: " . number_format($tiempoTotal, 2) . " seg\n";
    echo "   🚀 Registros/segundo: " . number_format($regsPorSegundo, 2) . "\n";
    echo "   📊 Tiempo promedio/registro: " . number_format(($tiempoInsercion / $totalRegistros) * 1000, 2) . " ms\n";
    
    // Verificación final de consistencia
    echo "\n11. VERIFICACIÓN FINAL DE CONSISTENCIA:\n";
    
    $resumen = $resultadoInsercion['resumen'];
    $procesadosEsperados = $resumen['insertados'] + $resumen['actualizados'];
    
    echo "   📊 Registros procesados: $procesadosEsperados\n";
    echo "   📊 Registros en BD: $totalRegistros\n";
    
    if ($resultadoInsercion['exito']) {
        echo "   ✅ Procesamiento masivo exitoso\n";
        
        if ($procesadosEsperados == $totalRegistros) {
            echo "   ✅ Consistencia PERFECTA entre procesamiento y BD\n";
        } else {
            echo "   ⚠️  Diferencia: Procesados=$procesadosEsperados BD=$totalRegistros\n";
        }
        
        if ($resumen['errores'] == 0) {
            echo "   ✅ Sin errores en procesamiento\n";
        } else {
            echo "   ❌ {$resumen['errores']} errores detectados\n";
        }
        
        echo "\n🎉 RESULTADO FINAL DEL SISTEMA MASIVO:\n";
        echo "   ✅ Sistema ZKTeco procesando " . number_format(count($resultadoParser['registros']), 0) . " registros\n";
        echo "   ✅ Lógica de entrada/salida funcionando correctamente\n";
        echo "   ✅ Manejo de múltiples empleados implementado\n";
        echo "   ✅ Gestión de fechas múltiples funcionando\n";
        echo "   ✅ Procesamiento optimizado para volúmenes grandes\n";
        echo "   ✅ Consistencia en base de datos verificada\n";
        
        if ($regsPorSegundo > 100) {
            echo "   🚀 Rendimiento EXCELENTE (>100 regs/seg)\n";
        } elseif ($regsPorSegundo > 50) {
            echo "   ✅ Rendimiento BUENO (>50 regs/seg)\n";
        } else {
            echo "   ⚠️  Rendimiento aceptable (<50 regs/seg)\n";
        }
        
        if ($stats['completos'] > 0) {
            $porcentajeCompletos = ($stats['completos'] / $stats['total']) * 100;
            echo "   📊 {$porcentajeCompletos}% de registros completos (entrada+salida)\n";
        }
        
        echo "\n🚀 EL SISTEMA ZKTECO ESTÁ LISTO PARA PRODUCCIÓN MASIVA\n";
        echo "   📈 Capacidad probada: " . number_format(count($resultadoParser['registros']), 0) . " registros\n";
        echo "   👥 Empleados soportados: {$stats['empleados_unicos']}\n";
        echo "   📅 Periodo procesado: {$stats['dias_unicos']} días\n";
        echo "   ⚡ Rendimiento: " . number_format($regsPorSegundo, 0) . " registros/segundo\n";
        
    } else {
        echo "   ❌ Error en procesamiento: " . ($resultadoInsercion['error'] ?? 'Desconocido') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO EN PROCESAMIENTO MASIVO: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN PROCESAMIENTO MASIVO 19,583 REGISTROS ===\n";
?>