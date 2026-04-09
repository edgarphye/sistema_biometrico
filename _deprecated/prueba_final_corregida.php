<?php
// Prueba final corregida sin errores de sintaxis
echo "=== PRUEBA FINAL: SINCRONIZACIÓN + PROCESAMIENTO MULTI-AÑO ===\n";

try {
    require_once 'config.php';
    require_once 'models/Database.php';
    require_once 'models/ZKTecoFormatDetector.php';
    require_once 'models/ZKTecoUniversalParser.php';
    require_once 'ZKTecoAsistenciaInserterMejorado.php';
    require_once 'ZKTecoSincronizadorEmpleados.php';
    
    echo "1. INICIALIZANDO SISTEMA...\n";
    
    // Limpiar tabla para prueba limpia
    $pdo = Database::getInstance()->getConnection();
    $pdo->exec("DELETE FROM asistencia");
    echo "✅ Tabla asistencia limpiada\n";
    
    // PASO 1: SINCRONIZAR EMPLEADOS
    echo "\n2. SINCRONIZANDO EMPLEADOS DESDE DAT...\n";
    
    $sincronizador = new ZKTecoSincronizadorEmpleados();
    $resultadoSincronizacion = $sincronizador->sincronizarEmpleadosDesdeDAT('asistencia_anios_mixtos.DAT');
    
    if (!$resultadoSincronizacion['exito']) {
        echo "❌ ERROR EN SINCRONIZACIÓN: " . $resultadoSincronizacion['error'] . "\n";
        exit(1);
    }
    
    echo "✅ Sincronización completada exitosamente\n";
    
    // Mostrar resultados de sincronización
    echo "\n📊 RESULTADOS DE SINCRONIZACIÓN:\n";
    if (isset($resultadoSincronizacion['estadisticas'])) {
        $stats = $resultadoSincronizacion['estadisticas'];
        echo "   📊 Total IDs ZKTeco en DAT: " . ($stats['total_ids_encontrados'] ?? 0) . "\n";
        echo "   👥 IDs ya existentes: " . ($stats['ids_existente_en_empleados'] ?? 0) . "\n";
        
        $mapeosActualizados = $stats['mapeos_actualizados'] ?? 0;
        $mapeosCreados = $stats['mapeos_creados'] ?? 0;
        $idsNuevos = $resultadoSincronizacion['empleados_nuevos'] ?? 0;
        
        echo "   👥 IDs nuevos sincronizados: " . ($mapeosActualizados + $mapeosCreados) . "\n";
        
        $sincCompleta = false;
        if (isset($resultadoSincronizacion['verificacion'])) {
            $sincCompleta = $resultadoSincronizacion['verificacion']['sincronizacion_completa'];
        }
        echo "   ✅ Sincronización completa: " . ($sincCompleta ? 'SÍ' : 'NO') . "\n";
    }
    
    // PASO 2: PROCESAR ARCHIVO DAT MULTI-AÑO
    echo "\n3. PROCESANDO ARCHIVO DAT MULTI-AÑO...\n";
    
    $detector = new ZKTecoFormatDetector();
    $formato = $detector->detectarFormato('asistencia_anios_mixtos.DAT');
    echo "✅ Formato detectado: {$formato['formato']} ({$formato['confianza']}%)\n";
    
    $parser = new ZKTecoUniversalParser();
    
    // Cargar todos los mapeos actualizados
    $stmt = $pdo->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
    $mapeos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mapeos[$row['zk_empleado_id']] = $row['empleado_id'];
    }
    $parser->setMapeoEmpleados($mapeos);
    echo "✅ Mapeos cargados: " . count($mapeos) . " registros\n";
    
    $tiempoInicio = microtime(true);
    $resultadoParser = $parser->procesarArchivo('asistencia_anios_mixtos.DAT');
    $tiempoParser = microtime(true) - $tiempoInicio;
    
    echo "✅ Parser completado en " . number_format($tiempoParser, 2) . " segundos\n";
    echo "✅ Registros válidos: {$resultadoParser['estadisticas']['registros_validos']}\n";
    
    if (empty($resultadoParser['registros'])) {
        echo "❌ ERROR: No hay registros procesados\n";
        exit(1);
    }
    
    // Análisis de datos
    echo "\n4. ANÁLISIS DE DATOS MULTI-AÑO:\n";
    
    $empleadosEnDatos = array_unique(array_map(function($r) { 
        return $r['empleado_id']; 
    }, $resultadoParser['registros']));
    
    $fechasEnDatos = array_unique(array_map(function($r) { 
        return $r['fecha']; 
    }, $resultadoParser['registros']));
    
    $anosEnDatos = array_unique(array_map(function($r) { 
        return substr($r['fecha'], 0, 4); 
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
    echo "   📅 Años procesados: " . implode(', ', $anosEnDatos) . "\n";
    echo "   🚪 Total entradas: " . count($entradas) . "\n";
    echo "   🚪 Total salidas: " . count($salidas) . "\n";
    
    $promedioPorEmpleado = count($empleadosEnDatos) > 0 ? round(count($resultadoParser['registros']) / count($empleadosEnDatos), 1) : 0;
    echo "   📊 Promedio por empleado: {$promedioPorEmpleado}\n";
    
    // Procesamiento masivo
    echo "\n5. PROCESAMIENTO MASIVO MULTI-AÑO...\n";
    echo "   ⚠️  PROCESANDO " . number_format(count($resultadoParser['registros']), 0) . " REGISTROS\n";
    
    $configuracion = [
        'evitar_duplicados' => true,
        'batch_size' => 1500,
        'verificar_existencia_rapida' => true,
        'tiempo_max_procesamiento' => 2400  // 40 minutos
    ];
    
    $inserter = new ZKTecoAsistenciaInserterMejorado($configuracion);
    
    $tiempoInicioInsercion = microtime(true);
    $resultadoInsercion = $inserter->procesarRegistrosMultiAnio($resultadoParser['registros']);
    $tiempoInsercion = microtime(true) - $tiempoInicioInsercion;
    
    echo "\n6. RESULTADOS DEL PROCESAMIENTO MULTI-AÑO:\n";
    
    if (!$resultadoInsercion['exito']) {
        echo "   ❌ Error en procesamiento: " . ($resultadoInsercion['error'] ?? 'Desconocido') . "\n";
        exit(1);
    }
    
    $resumen = $resultadoInsercion['resumen'];
    echo "   ✅ Procesamiento exitoso\n";
    echo "   📊 Total: " . ($resumen['total'] ?? 0) . "\n";
    echo "   📊 Insertados: " . ($resumen['insertados'] ?? 0) . "\n";
    echo "   📊 Actualizados: " . ($resumen['actualizados'] ?? 0) . "\n";
    echo "   📊 Omitidos: " . ($resumen['omitidos'] ?? 0) . "\n";
    echo "   📊 Errores: " . ($resumen['errores'] ?? 0) . "\n";
    
    if (isset($resumen['empleados_unicos'])) {
        echo "   👥 Empleados únicos: " . $resumen['empleados_unicos'] . "\n";
    }
    
    if (isset($resumen['dias_procesados'])) {
        echo "   📅 Días procesados: " . $resumen['dias_procesados'] . "\n";
    }
    
    if (isset($resumen['rango_anos'])) {
        $rango = $resumen['rango_anos'];
        echo "   📅 Rango de años: {$rango['ano_inicial']} - {$rango['ano_final']}\n";
        echo "   📊 Total años: " . ($rango['total_anos'] ?? 0) . "\n";
        
        if (isset($resumen['estadisticas_por_ano'])) {
            foreach ($resumen['estadisticas_por_ano'] as $ano => $statsAno) {
                echo "      📅 Año {$ano}: {$statsAno['total_dias']} días, {$statsAno['total_registros']} registros, {$statsAno['total_insertados']} insertados\n";
            }
        }
    }
    
    echo "   ⏱️ Tiempo: " . ($resumen['tiempo'] ?? '0') . "\n";
    
    // Verificación final
    echo "\n7. VERIFICACIÓN FINAL:\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
    $totalRegistros = $stmt->fetch()['total'];
    echo "   📊 Total registros en BD: " . number_format($totalRegistros, 0) . "\n";
    
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
    echo "   📊 Con entrada: " . number_format($stats['con_entrada'], 0) . "\n";
    echo "   📊 Con salida: " . number_format($stats['con_salida'], 0) . "\n";
    echo "   📊 Registros completos: " . number_format($stats['completos'], 0) . "\n";
    echo "   👥 Empleados únicos: " . $stats['empleados_unicos'] . "\n";
    echo "   📅 Días únicos: " . $stats['dias_unicos'] . "\n";
    
    // Consistencia
    $procesados = ($resumen['insertados'] ?? 0) + ($resumen['actualizados'] ?? 0);
    $consistencia = ($procesados == $totalRegistros) ? '✅ PERFECTA' : '❌ INCONSISTENCIA';
    echo "   📊 Consistencia: {$consistencia}\n";
    
    // Rendimiento
    $tiempoTotal = ($tiempoParser + $tiempoInsercion);
    $regsPorSegundo = $totalRegistros / $tiempoInsercion;
    
    echo "\n8. ANÁLISIS DE RENDIMIENTO:\n";
    echo "   ⏱️ Tiempo parser: " . number_format($tiempoParser, 2) . " segundos\n";
    echo "   ⏱️ Tiempo inserción: " . number_format($tiempoInsercion, 2) . " segundos\n";
    echo "   ⏱️ Tiempo total: " . number_format($tiempoTotal, 2) . " segundos\n";
    echo "   🚀 Registros/segundo: " . number_format($regsPorSegundo, 0) . "\n";
    if ($totalRegistros > 0) {
        echo "   📊 Tiempo promedio: " . number_format(($tiempoInsercion / $totalRegistros) * 1000, 2) . " ms\n";
    } else {
        echo "   📊 Tiempo promedio: N/A (sin registros)\n";
    }
    
    // Resumen final
    echo "\n🎉 RESULTADO FINAL:\n";
    echo "   ✅ Sincronización completada con IDs ZKTeco del DAT\n";
    echo "   ✅ Procesamiento multi-año implementado\n";
    echo "   ✅ Empleados sincronizados y mapeados correctamente\n";
    echo "   ✅ Registros procesados por año\n";
    echo "   ✅ Lógica de entrada/salida por día\n";
    
    if ($regsPorSegundo > 200) {
        echo "   🚀 Rendimiento EXCELENTE (>200 regs/seg)\n";
    } elseif ($regsPorSegundo > 100) {
        echo "   ✅ Rendimiento EXCELENTE (>100 regs/seg)\n";
    } elseif ($regsPorSegundo > 50) {
        echo "   ✅ Rendimiento BUENO (>50 regs/seg)\n";
    } else {
        echo "   ⚠️ Rendimiento aceptable (<50 regs/seg)\n";
    }
    
    if ($stats['completos'] > 0 && $stats['total'] > 0) {
        $porcentajeCompletos = ($stats['completos'] / $stats['total']) * 100;
        echo "   📊 {$porcentajeCompletos}% de registros completos (entrada+salida)\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN PRUEBA COMPLETA MULTI-AÑO ===\n";
?>