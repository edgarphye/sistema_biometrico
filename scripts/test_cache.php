<?php
/**
 * Test del Cache Manager
 * Verifica que el sistema de cache funcione correctamente
 */

require_once 'config.php';
require_once 'helpers/CacheManager.php';

echo "=== TEST DEL CACHE MANAGER ===" . PHP_EOL;
echo "Timestamp: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "========================================" . PHP_EOL;

try {
    $cache = CacheManager::getInstance();
    
    // Test 1: Verificar disponibilidad
    echo "Test 1: Verificando disponibilidad..." . PHP_EOL;
    $isAvailable = $cache->isAvailable();
    echo $isAvailable ? "✅ Cache disponible" : "⚠️ Cache no disponible (usando fallback)" . PHP_EOL;
    
    // Test 2: Almacenar y obtener valor simple
    echo PHP_EOL . "Test 2: Almacenando y obteniendo valor..." . PHP_EOL;
    $testKey = 'test_simple';
    $testValue = ['id' => 123, 'nombre' => 'Test Usuario', 'timestamp' => time()];
    
    $setResult = $cache->set($testKey, $testValue, 300); // 5 minutos
    echo $setResult ? "✅ Valor almacenado" : "❌ Error almacenando valor" . PHP_EOL;
    
    $retrievedValue = $cache->get($testKey);
    if ($retrievedValue === $testValue) {
        echo "✅ Valor recuperado correctamente" . PHP_EOL;
    } else {
        echo "❌ Error recuperando valor" . PHP_EOL;
    }
    
    // Test 3: Almacenar template biométrico
    echo PHP_EOL . "Test 3: Almacenando template biométrico..." . PHP_EOL;
    $empleadoId = 1;
    $tipoBiometrico = 'huella';
    $templateData = [
        'template' => 'data_base64...',
        'quality' => 85,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $templateSet = $cache->setBiometricTemplate($empleadoId, $tipoBiometrico, $templateData, 3600);
    echo $templateSet ? "✅ Template biométrico almacenado" : "❌ Error almacenando template" . PHP_EOL;
    
    $retrievedTemplate = $cache->getBiometricTemplate($empleadoId, $tipoBiometrico);
    if ($retrievedTemplate === $templateData) {
        echo "✅ Template biométrico recuperado correctamente" . PHP_EOL;
    } else {
        echo "❌ Error recuperando template biométrico" . PHP_EOL;
    }
    
    // Test 4: Cache de query
    echo PHP_EOL . "Test 4: Cacheando query de base de datos..." . PHP_EOL;
    $query = "SELECT * FROM empleados WHERE id = ?";
    $params = [1];
    $queryResult = ['id' => 1, 'nombre' => 'Empleado Test', 'activo' => 1];
    
    $queryCacheResult = $cache->cacheQuery($query, $params, $queryResult, 300);
    echo $queryCacheResult ? "✅ Query cacheado" : "❌ Error cacheando query" . PHP_EOL;
    
    $retrievedQuery = $cache->getCachedQuery($query, $params);
    if ($retrievedQuery === $queryResult) {
        echo "✅ Query cacheado recuperado correctamente" . PHP_EOL;
    } else {
        echo "❌ Error recuperando query cacheado" . PHP_EOL;
    }
    
    // Test 5: Invalidación de cache de empleado
    echo PHP_EOL . "Test 5: Invalidando cache de empleado..." . PHP_EOL;
    $invalidateResult = $cache->invalidateEmployeeCache($empleadoId);
    echo "✅ Cache de empleado invalidado: {$invalidateResult} claves" . PHP_EOL;
    
    // Verificar que el template ya no existe
    $templateAfterInvalidate = $cache->getBiometricTemplate($empleadoId, $tipoBiometrico);
    echo $templateAfterInvalidate === null ? "✅ Template correctamente invalidado" : "❌ Template aún existe" . PHP_EOL;
    
    // Test 6: Verificar existencia de clave
    echo PHP_EOL . "Test 6: Verificando existencia de claves..." . PHP_EOL;
    $cache->set('test_exists', 'valor_test', 300);
    $exists = $cache->exists('test_exists');
    echo $exists ? "✅ Clave existe" : "❌ Clave no existe" . PHP_EOL;
    
    $notExists = $cache->exists('test_not_exists');
    echo !$notExists ? "✅ Clave no existe (correcto)" : "❌ Clave existe (incorrecto)" . PHP_EOL;
    
    // Test 7: Estadísticas del cache
    echo PHP_EOL . "Test 7: Obteniendo estadísticas del cache..." . PHP_EOL;
    $stats = $cache->getStats();
    
    echo "📊 Estadísticas del Cache:" . PHP_EOL;
    echo "   Hits: {$stats['hits']}" . PHP_EOL;
    echo "   Misses: {$stats['misses']}" . PHP_EOL;
    echo "   Sets: {$stats['sets']}" . PHP_EOL;
    echo "   Deletes: {$stats['deletes']}" . PHP_EOL;
    echo "   Hit Rate: {$stats['hit_rate']}%" . PHP_EOL;
    echo "   Total Requests: {$stats['total_requests']}" . PHP_EOL;
    echo "   Available: " . ($stats['is_available'] ? "Sí" : "No") . PHP_EOL;
    echo "   Redis Connected: " . ($stats['redis_connected'] ? "Sí" : "No") . PHP_EOL;
    
    if (isset($stats['fallback_count'])) {
        echo "   Fallback Cache Items: {$stats['fallback_count']}" . PHP_EOL;
    }
    
    // Test 8: Warm-up del cache
    echo PHP_EOL . "Test 8: Warm-up del cache..." . PHP_EOL;
    $warmUpData = [
        'config:sistema' => [
            'data' => ['modo' => 'producción', 'debug' => false],
            'ttl' => 3600
        ],
        'estadisticas:generales' => [
            'data' => ['empleados' => 100, 'dispositivos' => 35],
            'ttl' => 1800
        ]
    ];
    
    $warmUpResult = $cache->warmUp($warmUpData);
    echo "✅ Warm-up completado: {$warmUpResult} claves" . PHP_EOL;
    
    // Test 9: Performance Test
    echo PHP_EOL . "Test 9: Test de rendimiento..." . PHP_EOL;
    $startTime = microtime(true);
    
    // Realizar 100 operaciones de cache
    for ($i = 0; $i < 100; $i++) {
        $key = "perf_test_{$i}";
        $value = ['iteration' => $i, 'data' => str_repeat('x', 100)];
        
        $cache->set($key, $value, 300);
        $retrieved = $cache->get($key);
        
        if ($retrieved !== $value) {
            echo "❌ Error en iteración {$i}" . PHP_EOL;
            break;
        }
    }
    
    $endTime = microtime(true);
    $totalTime = ($endTime - $startTime) * 1000; // Convertir a milisegundos
    $avgTime = $totalTime / 100;
    
    echo "✅ 100 operaciones completadas en {$totalTime}ms" . PHP_EOL;
    echo "   Tiempo promedio por operación: {$avgTime}ms" . PHP_EOL;
    echo "   Operaciones por segundo: " . round(1000 / $avgTime, 2) . PHP_EOL;
    
    // Performance Rating
    if ($avgTime < 1) {
        echo "🚀 Performance: Excelente (<1ms por operación)" . PHP_EOL;
    } elseif ($avgTime < 5) {
        echo "✅ Performance: Bueno (<5ms por operación)" . PHP_EOL;
    } elseif ($avgTime < 10) {
        echo "⚠️ Performance: Regular (<10ms por operación)" . PHP_EOL;
    } else {
        echo "❌ Performance: Lento (>=10ms por operación)" . PHP_EOL;
    }
    
    // Estadísticas finales
    echo PHP_EOL . "=== ESTADÍSTICAS FINALES ===" . PHP_EOL;
    $finalStats = $cache->getStats();
    echo "Total Hits: {$finalStats['hits']}" . PHP_EOL;
    echo "Total Misses: {$finalStats['misses']}" . PHP_EOL;
    echo "Hit Rate Final: {$finalStats['hit_rate']}%" . PHP_EOL;
    echo "Total Operations: {$finalStats['total_requests']}" . PHP_EOL;
    
    if ($finalStats['hit_rate'] > 80) {
        echo "🎉 EXCELENTE: Hit rate superior al 80%" . PHP_EOL;
    } elseif ($finalStats['hit_rate'] > 60) {
        echo "✅ BUENO: Hit rate superior al 60%" . PHP_EOL;
    } elseif ($finalStats['hit_rate'] > 40) {
        echo "⚠️ ACEPTABLE: Hit rate superior al 40%" . PHP_EOL;
    } else {
        echo "❌ POBRE: Hit rate inferior al 40%" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== TEST COMPLETADO ===" . PHP_EOL;
    echo "Timestamp: " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "Resultado: " . ($avgTime < 10 && $finalStats['hit_rate'] > 40 ? "EXITOSO" : "MEJORABLE") . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ ERROR EN TEST: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL;
?>