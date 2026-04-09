<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

/**
 * Actualizar mapeo con TODOS los IDs reales del archivo DAT
 * Para que el sistema procese todos los 19,583 registros
 */

// Conectar a la base de datos
$db = Database::getInstance();

// Analizar archivo DAT y obtener TODOS los IDs únicos
$file = __DIR__ . '/data/1_attlog.dat';
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ids_unicos = [];
foreach ($lines as $line) {
    $parts = explode("\t", $line);
    if (!empty($parts[0])) {
        $id = trim($parts[0]);
        if (is_numeric($id)) {
            $ids_unicos[] = $id;
        }
    }
}

$ids_unicos = array_unique($ids_unicos);
sort($ids_unicos);

echo "Total IDs únicos en archivo DAT: " . count($ids_unicos) . "\n";
echo "Primeros 20 IDs: " . implode(', ', array_slice($ids_unicos, 0, 20)) . "...\n";

// Obtener todos los empleados disponibles
$sql = "SELECT id, nombre, apellido FROM empleados ORDER BY id";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Empleados disponibles en BD: " . count($empleados) . "\n";

// Crear mapeo para TODOS los IDs
echo "\n=== CREANDO MAPEO COMPLETO ===\n";

$mapeos_creados = 0;
$max_empleados = min(count($ids_unicos), count($empleados));

for ($i = 0; $i < $max_empleados; $i++) {
    $zk_id = $ids_unicos[$i];
    $empleado = $empleados[$i];
    $emp_formateado = 'EMP' . str_pad($empleado['id'], 4, '0', STR_PAD_LEFT);
    
    // Verificar si ya existe
    $sql_check = "SELECT id FROM zkteo_empleado_mapeo WHERE empleado_id = ?";
    $stmt_check = $db->getConnection()->prepare($sql_check);
    $stmt_check->execute([$empleado['id']]);
    $existente = $stmt_check->fetch();
    
    if ($existente) {
        // Actualizar mapeo existente
        $sql_update = "UPDATE zkteo_empleado_mapeo 
                      SET zkteo_id = ?, nombre_completo = ?, activo = TRUE
                      WHERE empleado_id = ?";
        
        $stmt_update = $db->getConnection()->prepare($sql_update);
        $stmt_update->execute([
            $zk_id,
            $empleado['nombre'] . ' ' . $empleado['apellido'],
            $empleado['id']
        ]);
        
        echo "✓ Actualizado: EMP{$empleado['id']} -> ZK_ID $zk_id ({$empleado['nombre']} {$empleado['apellido']})\n";
    } else {
        // Crear nuevo mapeo
        $sql_insert = "INSERT INTO zkteo_empleado_mapeo 
                      (empleado_id, zkteo_id, nombre_completo, activo) 
                      VALUES (?, ?, ?, TRUE)";
        
        $stmt_insert = $db->getConnection()->prepare($sql_insert);
        $stmt_insert->execute([
            $empleado['id'],
            $zk_id,
            $empleado['nombre'] . ' ' . $empleado['apellido']
        ]);
        
        echo "✓ Creado: EMP{$empleado['id']} -> ZK_ID $zk_id ({$empleado['nombre']} {$empleado['apellido']})\n";
    }
    
    $mapeos_creados++;
}

echo "\nTotal mapeos procesados: $mapeos_creados\n";

// Limpiar mapeos antiguos que ya no se usan
$sql_clean = "UPDATE zkteo_empleado_mapeo 
               SET activo = FALSE 
               WHERE empleado_id > ?";
$stmt_clean = $db->getConnection()->prepare($sql_clean);
$stmt_clean->execute([$max_empleados]);

echo "Mapeos antiguos desactivados: " . $stmt_clean->rowCount() . "\n";

echo "\n=== VERIFICACIÓN FINAL ===\n";
$sql_verify = "SELECT COUNT(*) as total, COUNT(CASE WHEN activo = TRUE THEN 1 END) as activos 
               FROM zkteo_empleado_mapeo";
$stmt_verify = $db->getConnection()->prepare($sql_verify);
$stmt_verify->execute();
$result = $stmt_verify->fetch();

echo "Total mapeos en BD: {$result['total']}\n";
echo "Mapeos activos: {$result['activos']}\n";

echo "\n¡TODO LISTO! El sistema ahora puede procesar todos los registros del archivo DAT.\n";
?>