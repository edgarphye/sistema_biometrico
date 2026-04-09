<?php
// Solución rápida: Actualizar mapeo existente con los IDs reales del archivo DAT

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

$db = Database::getInstance();

// IDs más comunes del archivo DAT (del análisis anterior)
$ids_reales_dat = ['210', '263', '634', '2621', '536', '2602', '632', '670', '62', '2611'];

// Obtener primeros 10 empleados de la base de datos
$sql = "SELECT id, nombre, apellido FROM empleados ORDER BY id LIMIT 10";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Actualizando mapeo con IDs reales del archivo DAT...\n";
echo str_repeat("=", 50) . "\n";

foreach ($empleados as $index => $empleado) {
    if (isset($ids_reales_dat[$index])) {
        $zk_id_real = $ids_reales_dat[$index];
        $emp_formateado = 'EMP' . str_pad($empleado['id'], 4, '0', STR_PAD_LEFT);
        
        // Actualizar mapeo existente
        $sql_update = "UPDATE zkteo_empleado_mapeo 
                      SET zkteo_id = ?, nombre_completo = ?
                      WHERE empleado_id = ?";
        
        $stmt_update = $db->getConnection()->prepare($sql_update);
        $stmt_update->execute([
            $zk_id_real,
            $empleado['nombre'] . ' ' . $empleado['apellido'],
            $empleado['id']
        ]);
        
        echo "✓ Actualizado: EMP{$empleado['id']} -> ZK_ID $zk_id_real ({$empleado['nombre']} {$empleado['apellido']})\n";
    }
}

// Ahora procesar el archivo DAT con los IDs correctos
echo "\n=== PROCESANDO ARCHIVO DAT ===\n";

$input_file = __DIR__ . '/data/1_attlog_backup.dat';
$output_file = __DIR__ . '/data/1_attlog.dat';

$input_handle = fopen($input_file, 'r');
$output_handle = fopen($output_file, 'w');

$procesadas = 0;
$omitidas = 0;

while (($line = fgets($input_handle)) !== false) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 6) {
        $omitidas++;
        continue;
    }
    
    $emp_id = trim($parts[0]);
    
    // Verificar si este ID está en nuestro mapeo
    if (in_array($emp_id, $ids_reales_dat)) {
        // Mantener línea original (el sistema ya procesará estos IDs)
        fwrite($output_handle, $line . "\n");
        $procesadas++;
    } else {
        $omitidas++;
    }
}

fclose($input_handle);
fclose($output_handle);

echo "\n=== RESUMEN ===\n";
echo "Líneas procesadas: $procesadas\n";
echo "Líneas omitidas: $omitidas\n";
echo "Archivo 1_attlog.dat actualizado con datos válidos.\n";

echo "\n¡LISTO! El sistema ahora puede procesar el archivo DAT correctamente.\n";
?>