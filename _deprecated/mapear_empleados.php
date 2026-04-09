<?php
/**
 * Script para mostrar IDs de empleados del archivo ZKTeco
 * y crear mapeo manual con empleados del sistema
 */

require_once 'config.php';
require_once 'models/Database.php';

echo "=== Mapeo de Empleados ZKTeco ===\n\n";

$db = new Database();
$conn = $db->getConnection();

// Obtener empleados del sistema
echo "=== EMPLEADOS DEL SISTEMA ===\n";
$stmt = $conn->query("SELECT id, nombre, apellido, rfc, area FROM empleados ORDER BY id");
$empleados = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $empleados[$row['id']] = $row;
    echo "ID: {$row['id']} | {$row['nombre']} {$row['apellido']} | RFC: {$row['rfc']} | Área: {$row['area']}\n";
}

echo "\n=== MAPPINGOS EXISTENTES ===\n";
$stmt = $conn->query("SELECT zk_empleado_id, empleado_id, nombre_empleado FROM zk_empleado_mapeo LIMIT 20");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ZK_ID: {$row['zk_empleado_id']} -> Sistema ID: {$row['empleado_id']} ({$row['nombre_empleado']})\n";
}

// Buscar archivo más reciente en uploads
$uploadDir = __DIR__ . '/uploads/';
$files = glob($uploadDir . '*.dat');
if (empty($files)) {
    $uploadDir = __DIR__ . '/storage/uploads/';
    $files = glob($uploadDir . '*.dat');
}
if (empty($files)) {
    $uploadDir = __DIR__ . '/data/';
    $files = glob($uploadDir . '*.dat');
}

if (!empty($files)) {
    // Ordenar por fecha de modificación
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestFile = $files[0];
    echo "\n=== ÚLTIMO ARCHIVO: " . basename($latestFile) . " ===\n";
    
    // Leer archivo
    $contenido = file_get_contents($latestFile);
    $lineas = explode("\n", $contenido);
    
    // Extraer IDs únicos
    $zkIds = [];
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // Intentar diferentes formatos
        // Formato: UEED252700041    1     2026-02-03 08:06:22
        if (preg_match('/^([A-Z0-9]+)\s+\d+\s+\d{4}-\d{2}-\d{2}/', $linea, $matches)) {
            $zkIds[$matches[1]] = true;
        }
        // Otro formato común
        elseif (preg_match('/^(\d{6,})\s+\d+\s+/', $linea, $matches)) {
            $zkIds[$matches[1]] = true;
        }
    }
    
    if (!empty($zkIds)) {
        echo "\n=== IDs ZK ENCONTRADOS (" . count($zkIds) . ") ===\n";
        $zkIds = array_keys($zkIds);
        sort($zkIds);
        
        // Mostrar en columnas
        $cols = 4;
        $total = count($zkIds);
        for ($i = 0; $i < $total; $i += $cols) {
            $row = [];
            for ($j = 0; $j < $cols; $j++) {
                if (isset($zkIds[$i + $j])) {
                    $row[] = str_pad($zkIds[$i + $j], 20);
                }
            }
            echo implode(" | ", $row) . "\n";
        }
        
        echo "\n=== CREAR MAPPINGOS ===\n";
        echo "Para crear un mapeo, ejecuta:\n";
        echo "INSERT INTO zk_empleado_mapeo (zk_empleado_id, empleado_id, nombre_empleado, fecha_creacion) VALUES ('ZK_ID', ID_SISTEMA, 'Nombre', NOW());\n";
    } else {
        echo "No se pudieron extraer IDs del archivo. Formato no reconocido.\n";
        echo "Primeras 10 líneas:\n";
        for ($i = 0; $i < min(10, count($lineas)); $i++) {
            echo "$lineas[$i]\n";
        }
    }
} else {
    echo "\nNo se encontró archivo .dat en uploads/\n";
}

// Función para crear mapeo
function crearMapeo($zkId, $empleadoId, $nombre) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO zk_empleado_mapeo (zk_empleado_id, empleado_id, nombre_empleado, fecha_creacion)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE empleado_id = VALUES(empleado_id), nombre_empleado = VALUES(nombre_empleado)
    ");
    
    try {
        $stmt->execute([$zkId, $empleadoId, $nombre]);
        echo "✓ Mapeo creado: $zkId -> $empleadoId ($nombre)\n";
        return true;
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Si hay parámetros por GET o CLI, crear mapeo
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $zkId = $argv[1];
    $empleadoId = $argv[2] ?? null;
    
    if (!$empleadoId) {
        echo "Uso: php mapear_empleados.php <zk_empleado_id> <empleado_id_sistema>\n";
        exit(1);
    }
    
    $nombre = $empleados[$empleadoId]['nombre'] . ' ' . $empleados[$empleadoId]['apellido'];
    crearMapeo($zkId, $empleadoId, $nombre);
}

echo "\n=== FIN ===\n";
