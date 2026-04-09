<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE TABLAS - Sistema Biométrico\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Tablas definidas en database.sql
    $tablasEsperadas = [
        'empleados' => 'Información de empleados',
        'asistencia' => 'Registros de entrada/salida',
        'horarios_laborales' => 'Catálogo de horarios de trabajo',
        'horarios_empleados' => 'Asignación de horarios por empleado',
        'tipos_justificacion' => 'Tipos de justificación para ausencias',
        'retardos' => 'Registro de retardos',
        'sanciones' => 'Suspensiones y amonestaciones',
        'comisiones' => 'Comisiones por cobrar',
        'ausencias' => 'Ausencias registradas',
        'usuarios' => 'Usuarios del sistema',
        'dispositivos_biometricos' => 'Configuración de dispositivos',
        'logs_dispositivos' => 'Logs de eventos de dispositivos',
        'zkteco_procesamiento_logs' => 'Logs de carga de archivos ZKTeco',
        'zkteo_empleado_mapeo' => 'Mapeo de IDs ZKTeco a Empleados',
        'zkteco_formatos' => 'Formatos de archivos detectados'
    ];
    
    // Obtener tablas existentes en la BD
    $stmt = $pdo->query("SHOW TABLES");
    $tablasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 TABLAS EN BASE DE DATOS\n";
    echo str_repeat("─", 63) . "\n\n";
    
    $estadisticas = [
        'total_esperadas' => count($tablasEsperadas),
        'total_existentes' => count($tablasExistentes),
        'coinciden' => 0,
        'faltan' => 0,
        'extras' => 0
    ];
    
    // Verificar cada tabla esperada
    foreach ($tablasEsperadas as $tabla => $descripcion) {
        $existe = in_array($tabla, $tablasExistentes);
        $estadisticas['coinciden'] += $existe ? 1 : 0;
        $estadisticas['faltan'] += $existe ? 0 : 1;
        
        $icono = $existe ? '✓' : '✗';
        $estado = $existe ? 'OK' : 'FALTA';
        $color = $existe ? '' : ' ⚠️';
        
        printf("%-30s [%s] %s%s\n", $tabla, $estado, $descripcion, $color);
        
        // Si existe, mostrar cantidad de registros
        if ($existe) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
                printf("   → Registros: %d\n", $count);
            } catch (Exception $e) {
                printf("   → Error contando: %s\n", $e->getMessage());
            }
        }
    }
    
    echo "\n";
    
    // Verificar tablas extras (que existen pero no están en database.sql)
    $tablasExtras = array_diff($tablasExistentes, array_keys($tablasEsperadas));
    
    if (!empty($tablasExtras)) {
        echo "📌 TABLAS ADICIONALES (no en database.sql):\n";
        echo str_repeat("─", 63) . "\n";
        foreach ($tablasExtras as $tabla) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
            printf("   ℹ️  %-30s → %d registros\n", $tabla, $count);
            $estadisticas['extras']++;
        }
        echo "\n";
    }
    
    // Resumen
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📈 RESUMEN\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    printf("Tablas esperadas (database.sql):  %d\n", $estadisticas['total_esperadas']);
    printf("Tablas existentes en BD:          %d\n", $estadisticas['total_existentes']);
    printf("✓ Tablas correctas:                %d\n", $estadisticas['coinciden']);
    
    if ($estadisticas['faltan'] > 0) {
        printf("✗ Tablas faltantes:                %d ⚠️\n", $estadisticas['faltan']);
    }
    
    if ($estadisticas['extras'] > 0) {
        printf("ℹ️  Tablas adicionales:              %d\n", $estadisticas['extras']);
    }
    
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Estado general
    if ($estadisticas['faltan'] == 0) {
        echo "✅ Estado: COMPLETO - Todas las tablas requeridas existen\n";
    } else {
        echo "⚠️  Estado: INCOMPLETO - Faltan {$estadisticas['faltan']} tabla(s)\n";
        echo "\nPara crear las tablas faltantes, ejecuta:\n";
        echo "   php -f database.sql\n";
        echo "   o importa database.sql manualmente desde phpMyAdmin\n";
    }
    
    // Información adicional sobre tablas importantes
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📋 DETALLES DE TABLAS CRÍTICAS\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    $tablasCriticas = ['empleados', 'asistencia', 'retardos', 'sanciones', 'dispositivos_biometricos'];
    
    foreach ($tablasCriticas as $tabla) {
        if (in_array($tabla, $tablasExistentes)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
            $columns = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
            
            printf("\n📄 %s (%d registros, %d columnas)\n", strtoupper($tabla), $count, count($columns));
            echo "   Columnas: " . implode(', ', array_slice($columns, 0, 8));
            if (count($columns) > 8) {
                echo "... (+" . (count($columns) - 8) . " más)";
            }
            echo "\n";
        }
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "\n❌ Error conectando a la base de datos:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}
?>
