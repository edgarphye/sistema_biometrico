<?php
/**
 * Debug inserter ZKTeco
 */

require_once 'config.php';
require_once 'models/Database.php';
require_once 'models/ZKTecoUniversalParser.php';
require_once 'models/ZKTecoAsistenciaInserter.php';

$archivo = __DIR__ . '/data/UEED252700041_attlog_02_03_2026.dat';

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

echo "Mapeo cargado: " . count($mapeo) . "\n";
$resultadoParser = $parser->procesarArchivo($archivo);
echo "Registros del parser: " . count($resultadoParser['registros']) . "\n";

if (empty($resultadoParser['registros'])) {
    die("No hay registros para procesar\n");
}

// Tomar solo 10 registros para prueba
$registrosPrueba = array_slice($resultadoParser['registros'], 0, 10);

echo "\n=== Probando inserter con 10 registros ===\n";

$inserter = new ZKTecoAsistenciaInserter([
    'crear_empleados_automaticos' => false,
    'evitar_duplicados' => true
]);

$inserter->setMapeoEmpleados($mapeo);

$resultadoInsercion = $inserter->insertarRegistros($registrosPrueba);

echo "Resultado:\n";
print_r($resultadoInsercion);
