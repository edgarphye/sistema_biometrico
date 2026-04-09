<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

/**
 * Script para crear mapeo automático basado en los empleados que realmente existen en el archivo DAT
 */

// Conectar a la base de datos
$db = Database::getInstance();

// Analizar archivo DAT y obtener IDs reales
$file = __DIR__ . '/data/1_attlog_backup.dat';
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ids_encontrados = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $parts = preg_split('/\s+/', $line);
    if (!empty($parts)) {
        $id = trim($parts[0]);
        if (is_numeric($id)) {
            $ids_encontrados[] = $id;
        }
    }
}

$ids_unicos = array_unique($ids_encontrados);
echo "IDs únicos encontrados en archivo DAT: " . count($ids_unicos) . "\n";

// Obtener empleados existentes en la base de datos
$sql = "SELECT id, nombre, apellido FROM empleados ORDER BY id LIMIT 20";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute();
$empleados_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nEmpleados disponibles en la base de datos:\n";
echo str_repeat("=", 50) . "\n";
foreach ($empleados_db as $emp) {
    echo "ID: {$emp['id']}, Nombre: {$emp['nombre']} {$emp['apellido']}\n";
}

// Crear mapeo automático usando los primeros empleados
echo "\n=== CREANDO MAPEO AUTOMÁTICO ===\n";
$mapeo_creado = 0;
$ids_a_mapear = array_slice($ids_unicos, 0, count($empleados_db));

foreach ($ids_a_mapear as $index => $zk_id) {
    if (isset($empleados_db[$index])) {
        $empleado = $empleados_db[$index];
        $emp_formateado = 'EMP' . str_pad($empleado['id'], 4, '0', STR_PAD_LEFT);
        
        // Verificar si ya existe
        $sql_check = "SELECT id FROM zkteo_empleado_mapeo WHERE zkteo_id = ?";
        $stmt_check = $db->getConnection()->prepare($sql_check);
        $stmt_check->execute([$emp_formateado]);
        
        if (!$stmt_check->fetch()) {
            // Insertar nuevo mapeo
            $sql_insert = "INSERT INTO zkteo_empleado_mapeo 
                (empleado_id, zkteo_id, nombre_completo, activo) 
                VALUES (?, ?, ?, TRUE)";
            
            $stmt_insert = $db->getConnection()->prepare($sql_insert);
            $stmt_insert->execute([
                $empleado['id'],
                $emp_formateado,
                $empleado['nombre'] . ' ' . $empleado['apellido']
            ]);
            
            echo "✓ Mapeo creado: ZK_ID=$zk_id -> EMP{$emp['id']} ({$empleado['nombre']} {$empleado['apellido']})\n";
            $mapeo_creado++;
        }
    }
}

echo "\nTotal mapeos creados: $mapeo_creado\n";

// Ahora ejecutar script de limpieza con los nuevos mapeos
echo "\n=== EJECUTANDO LIMPIEZA DEL ARCHIVO ===\n";
require_once __DIR__ . '/limpiar_archivo_dat_v2.php';
?>