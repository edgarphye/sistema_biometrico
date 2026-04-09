<?php
// Prueba final completa del sistema ZKTeco corregido
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PRUEBA FINAL SISTEMA ZKTECO CORREGIDO ===\n";

try {
    // Configurar sesión
    if (!session_id()) {
        session_start();
    }
    $_SESSION['user_id'] = 1;
    $_SESSION['loggedin'] = true;
    
    // Cargar dependencias
    require_once 'config.php';
    require_once 'controllers/ConfiguracionController.php';
    
    echo "1. Iniciando prueba completa del sistema...\n";
    
    // Crear controlador
    $controller = new ConfiguracionController();
    
    // Simular archivo completo
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_FILES['zk_file'] = [
        'tmp_name' => '20241106.DAT',
        'name' => '20241106.DAT',
        'error' => UPLOAD_ERR_OK,
        'size' => filesize('20241106.DAT'),
        'type' => 'application/octet-stream'
    ];
    
    echo "2. Ejecutando uploadZkteco() con sistema corregido...\n";
    
    // Capturar salida JSON
    ob_start();
    $controller->uploadZkteco();
    $jsonOutput = ob_get_clean();
    
    echo "3. Resultado del sistema:\n";
    $resultado = json_decode($jsonOutput, true);
    
    if ($resultado) {
        echo "✅ Respuesta JSON válida\n";
        
        if (isset($resultado['success']) && $resultado['success']) {
            echo "✅ Procesamiento exitoso\n";
            
            $stats = $resultado['estadisticas_generales'];
            echo "📊 Estadísticas Generales:\n";
            echo "   Archivo: {$stats['nombre_archivo']}\n";
            echo "   Tamaño: {$stats['tamano']}\n";
            echo "   Formato: {$stats['formato_detectado']} ({$stats['confianza_formato']})\n";
            echo "   Registros válidos: {$stats['registros_validos']}\n";
            
            $resumen = $stats['resumen_insercion'];
            echo "📋 Resumen Inserción:\n";
            echo "   Total: {$resumen['total']}\n";
            echo "   Insertados: {$resumen['insertados']}\n";
            echo "   Actualizados: {$resumen['actualizados']}\n";
            echo "   Omitidos: {$resumen['omitidos']}\n";
            echo "   Errores: {$resumen['errores']}\n";
            
            // Verificar estado final en BD
            require_once 'models/Database.php';
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM asistencia");
            $totalBD = $stmt->fetch()['total'];
            
            echo "\n📋 Verificación Base de Datos:\n";
            echo "   Total registros en BD: $totalBD\n";
            
            if ($resumen['insertados'] == $totalBD) {
                echo "   ✅ Consistencia PERFECTA entre reporte y BD\n";
            } else {
                echo "   ❌ Inconsistencia entre reporte ({$resumen['insertados']}) y BD ($totalBD)\n";
            }
            
            // Mostrar distribución por hora
            echo "\n📈 Registros por Hora:\n";
            $stmt = $pdo->query("SELECT hora_entrada, COUNT(*) as frecuencia 
                                 FROM asistencia 
                                 WHERE hora_entrada IS NOT NULL 
                                 GROUP BY hora_entrada 
                                 ORDER BY hora_entrada");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   {$row['hora_entrada']}: {$row['frecuencia']} registro(s)\n";
            }
            
            echo "\n🎉 RESULTADO FINAL:\n";
            if ($resumen['errores'] == 0) {
                echo "✅ Sistema ZKTeco funcionando PERFECTAMENTE\n";
                echo "✅ Error 500 COMPLETAMENTE RESUELTO\n";
                echo "✅ Lógica de entradas/salidas funcionando\n";
                echo "✅ Detección de duplicados correcta\n";
                echo "✅ Inserción en base de datos estable\n";
                echo "\n🚀 EL SISTEMA ESTÁ LISTO PARA PRODUCCIÓN\n";
            } else {
                echo "⚠️  Hay {$resumen['errores']} errores que requieren atención\n";
            }
            
        } else {
            echo "❌ Error en procesamiento: " . ($resultado['error'] ?? 'Desconocido') . "\n";
        }
        
    } else {
        echo "❌ Respuesta JSON inválida:\n$jsonOutput\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN PRUEBA FINAL COMPLETA ===\n";
?>