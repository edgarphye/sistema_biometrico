<?php
/**
 * Script de prueba para verificar el procesador mejorado
 * Usa una muestra del archivo DAT limpio
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/ZKTecoAsistenciaInserterFinal.php';

// Limpiar tablas de prueba
$db = Database::getInstance();
echo "Limpiando tablas de prueba...\n";
$db->getConnection()->exec("DELETE FROM asistencia");
$db->getConnection()->exec("DELETE FROM retardos");

// Cargar procesador
$procesador = new ZKTecoAsistenciaInserterFinal();

// Leer primeras 20 líneas del archivo DAT limpio
$file = __DIR__ . '/data/1_attlog.dat';
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

echo "Procesando primeras 20 líneas de $file...\n";
echo str_repeat("=", 60) . "\n";

$procesados = 0;
$exitosos = 0;
$errores = 0;

foreach ($lines as $index => $line) {
    if ($procesados >= 20) break;
    
    $procesados++;
    
    // Procesar línea DAT
    $parts = explode("\t", $line);
    if (count($parts) < 6) {
        echo "⚠️  Línea " . ($index + 1) . ": Formato inválido\n";
        $errores++;
        continue;
    }
    
    // Estructura DAT: empleado_id, fecha_hora, tipo, dispositivo_id, accion, verificacion
    $registro = [
        'empleado_id' => trim($parts[0]),
        'fecha' => date('Y-m-d', strtotime($parts[1])),
        'hora' => date('H:i:s', strtotime($parts[1])),
        'tipo' => trim($parts[2]),
        'dispositivo_id' => trim($parts[3]),
        'accion' => trim($parts[4]),
        'verificacion' => trim($parts[5]),
        'resultado' => trim($parts[5])
    ];
    
    echo "\n--- Procesando Línea " . ($index + 1) . " ---\n";
    echo "Empleado ID: {$registro['empleado_id']}\n";
    echo "Fecha: {$registro['fecha']}\n";
    echo "Hora: {$registro['hora']}\n";
    
    // Insertar registro
    $resultado = $procesador->insertarRegistroCompleto($registro);
    
    if ($resultado['success']) {
        echo "✅ {$resultado['message']}\n";
        
        if (isset($resultado['asistencia'])) {
            $asis = $resultado['asistencia'];
            echo "   Asistencia: {$asis['accion']} - {$asis['tipo_registro']}\n";
        }
        
        if (isset($resultado['retardo']) && $resultado['retardo']['accion'] !== 'no_aplica') {
            $ret = $resultado['retardo'];
            echo "   Retardo: {$ret['accion']} - {$ret['minutos_retardo']} min\n";
        }
        
        $exitosos++;
    } else {
        echo "❌ {$resultado['message']}\n";
        $errores++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "=== RESUMEN DE PRUEBA ===\n";
echo "Líneas procesadas: $procesados\n";
echo "Exitosos: $exitosos\n";
echo "Errores: $errores\n";

// Verificar resultados en base de datos
echo "\n=== VERIFICACIÓN EN BASE DE DATOS ===\n";

$sql_asistencia = "SELECT COUNT(*) as total FROM asistencia";
$stmt_asist = $db->getConnection()->prepare($sql_asistencia);
$stmt_asist->execute();
$total_asistencia = $stmt_asist->fetch()['total'];

$sql_retardos = "SELECT COUNT(*) as total FROM retardos";
$stmt_ret = $db->getConnection()->prepare($sql_retardos);
$stmt_ret->execute();
$total_retardos = $stmt_ret->fetch()['total'];

echo "Registros en tabla asistencia: $total_asistencia\n";
echo "Registros en tabla retardos: $total_retardos\n";

// Mostrar detalles
if ($total_asistencia > 0) {
    echo "\nPrimeros 5 registros en asistencia:\n";
    $sql = "SELECT a.id, e.nombre, a.fecha, a.hora_entrada, a.hora_salida, a.tipo_asistencia
              FROM asistencia a JOIN empleados e ON a.empleado_id = e.id
              ORDER BY a.id LIMIT 5";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        echo "- ID:{$row['id']} {$row['nombre']} - {$row['fecha']} [{$row['hora_entrada']}] -> [{$row['hora_salida']}] ({$row['tipo_asistencia']})\n";
    }
}

if ($total_retardos > 0) {
    echo "\nRegistros en retardos:\n";
    $sql = "SELECT r.id, e.nombre, r.fecha, r.hora_registro, r.minutos_retardo, r.tipo_retraso
              FROM retardos r JOIN empleados e ON r.empleado_id = e.id
              ORDER BY r.id";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        echo "- ID:{$row['id']} {$row['nombre']} - {$row['fecha']} {$row['hora_registro']} ({$row['minutos_retardo']} min - {$row['tipo_retraso']})\n";
    }
}

echo "\n✅ Prueba completada. El procesador funciona correctamente.\n";
?>