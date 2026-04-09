<?php
/**
 * Script para verificar y corregir el mapeo de empleados ZKTeco
 * Ejecutar desde línea de comandos: php verificar_mapeo_828.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

echo "=== VERIFICACIÓN Y CORRECCIÓN DE MAPEO DE EMPLEADOS ===\n\n";

$db = Database::getInstance();
$pdo = $db->getConnection();

// 1. Verificar archivo .dat existente
$archivos_dat = glob(__DIR__ . '/data/*.dat');
echo "Archivos .dat encontrados en /data:\n";
foreach ($archivos_dat as $archivo) {
    $nombre = basename($archivo);
    $tamano = filesize($archivo);
    echo "  - $nombre (" . number_format($tamano) . " bytes)\n";
}

// Usar el archivo más reciente o el que coincida con el patrón
$archivo_a_usar = null;
foreach ($archivos_dat as $archivo) {
    $nombre = basename($archivo);
    // Preferir el archivo que no sea copia
    if (strpos($nombre, 'copia') === false) {
        $archivo_a_usar = $archivo;
        break;
    }
}

if (!$archivo_a_usar && count($archivos_dat) > 0) {
    $archivo_a_usar = $archivos_dat[0];
}

echo "\n>>> Archivo a procesar: " . basename($archivo_a_usar) . "\n\n";

// 2. Leer IDs únicos del archivo
$ids_en_archivo = [];
$handle = fopen($archivo_a_usar, 'r');
while (($linea = fgets($handle)) !== false) {
    $linea = trim($linea);
    if (empty($linea)) continue;
    
    // Extraer ID (primer campo, puede tener espacios)
    $campos = preg_split('/\s+/', $linea);
    if (!empty($campos[0]) && is_numeric(trim($campos[0]))) {
        $ids_en_archivo[] = (int)trim($campos[0]);
    }
}
fclose($handle);

$ids_en_archivo = array_unique($ids_en_archivo);
sort($ids_en_archivo);

echo "IDs únicos en archivo: " . count($ids_en_archivo) . "\n";
echo "Primeros 20: " . implode(', ', array_slice($ids_en_archivo, 0, 20)) . "\n";
echo "Últimos 20: " . implode(', ', array_slice($ids_en_archivo, -20)) . "\n\n";

// 3. Verificar mapeo para ID 828
$zk_id = 828;

echo "=== VERIFICANDO MAPEO PARA EMPLEADO ZK ID $zk_id ===\n";

// Verificar mapeo
$stmt = $pdo->prepare('SELECT * FROM zk_empleado_mapeo WHERE zk_empleado_id = ?');
$stmt->execute([$zk_id]);
$mapeo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($mapeo) {
    echo "✓ Mapeo encontrado:\n";
    echo "  - zk_empleado_id: " . $mapeo['zk_empleado_id'] . "\n";
    echo "  - empleado_id: " . $mapeo['empleado_id'] . "\n";
    echo "  - nombre: " . $mapeo['nombre_empleado'] . "\n";
} else {
    echo "✗ NO HAY MAPEO para ZK ID $zk_id - CREANDO...\n";
    
    // Verificar si existe empleado
    $stmt = $pdo->prepare('SELECT id, nombre, apellido FROM empleados WHERE id = ?');
    $stmt->execute([$zk_id]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($empleado) {
        $nombre = $empleado['nombre'] . ' ' . $empleado['apellido'];
        $stmt = $pdo->prepare('INSERT INTO zk_empleado_mapeo (zk_empleado_id, empleado_id, nombre_empleado, fecha_creacion) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$zk_id, $zk_id, $nombre]);
        echo "✓ Mapeo creado para empleado $zk_id\n";
    } else {
        echo "✗ NO existe empleado con ID $zk_id\n";
    }
}

// 4. Verificar datos de asistencia
echo "\n=== VERIFICANDO DATOS DE ASISTENCIA ===\n";

$stmt = $pdo->prepare('
    SELECT fecha, hora_entrada, hora_salida 
    FROM asistencia 
    WHERE empleado_id = ? 
    AND fecha BETWEEN "2026-02-01" AND "2026-02-28"
    ORDER BY fecha
');
$stmt->execute([$zk_id]);

$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total registros en febrero 2026: " . count($registros) . "\n\n";

echo "Fecha       | Entrada  | Salida\n";
echo str_repeat('-', 40) . "\n";
foreach ($registros as $reg) {
    $entrada = $reg['hora_entrada'] ? substr($reg['hora_entrada'], 0, 5) : '--:--';
    $salida = $reg['hora_salida'] ? substr($reg['hora_salida'], 0, 5) : '--:--';
    echo $reg['fecha'] . " | $entrada  | $salida\n";
}

// 5. Comparar con archivo .dat
echo "\n=== COMPARANDO CON ARCHIVO .DAT ===\n";

$handle = fopen($archivo_a_usar, 'r');
$registros_en_dat = [];
while (($linea = fgets($handle)) !== false) {
    $linea = trim($linea);
    if (empty($linea)) continue;
    
    $campos = preg_split('/\s+/', $linea);
    $id = (int)trim($campos[0]);
    
    if ($id == $zk_id && count($campos) >= 3) {
        $fecha_hora = trim($campos[1] . ' ' . $campos[2]);
        $registros_en_dat[] = $fecha_hora;
    }
}
fclose($handle);

echo "Registros en archivo .dat para ID $zk_id: " . count($registros_en_dat) . "\n";
foreach (array_slice($registros_en_dat, -10) as $reg) {
    echo "  $reg\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
