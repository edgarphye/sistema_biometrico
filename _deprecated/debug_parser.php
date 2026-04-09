<?php
/**
 * Debug parser ZKTeco
 */

require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/ZKTecoUniversalParser.php';

$archivo = __DIR__ . '/data/UEED252700041_attlog_02_03_2026.dat';

if (!file_exists($archivo)) {
    die("Archivo no encontrado: $archivo\n");
}

$parser = new ZKTecoUniversalParser([
    'mantener_fecha_original' => false,
    'procesar_fines_semana' => false,
    'ajuste_horario_nocturno' => true
]);

// Cargar mapeo
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT zk_empleado_id, empleado_id FROM zk_empleado_mapeo");
$mapeo = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mapeo[$row['zk_empleado_id']] = $row['empleado_id'];
}
$parser->setMapeoEmpleados($mapeo);

echo "Mapeo cargado: " . count($mapeo) . " registros\n";
echo "Primeros 5 mapeos:\n";
print_r(array_slice($mapeo, 0, 5));

$resultado = $parser->procesarArchivo($archivo);

echo "\n=== RESULTADO ===\n";
echo "Éxito: " . ($resultado['exito'] ? 'SI' : 'NO') . "\n";
echo "Registros: " . count($resultado['registros']) . "\n";
echo "Estadísticas:\n";
print_r($resultado['estadisticas']);

if (!empty($resultado['registros'])) {
    echo "\n=== PRIMEROS 5 REGISTROS PROCESADOS ===\n";
    for ($i = 0; $i < min(5, count($resultado['registros'])); $i++) {
        $r = $resultado['registros'][$i];
        echo "Empleado ID: {$r['empleado_id']} | Fecha: {$r['fecha']} | Hora: {$r['hora']} | Tipo: {$r['tipo_registro']}\n";
    }
}
