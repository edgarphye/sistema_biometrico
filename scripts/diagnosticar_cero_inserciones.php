<?php
/**
 * Script de diagnóstico simplificado
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

$db = new Database();
$conn = $db->getConnection();
echo "Conexion BD OK\n";

$start = microtime(true);
$stmt = $conn->query("SELECT COUNT(*) FROM asistencia");
$cnt = $stmt->fetchColumn();
echo "Registros en asistencia: $cnt (" . round(microtime(true) - $start, 2) . "s)\n";
flush();

// 2. Contar empleados activos
echo "Consultando empleados activos...\n";
$stmt = $conn->query("SELECT COUNT(*) FROM empleados WHERE activo = 1");
echo "Empleados activos: " . $stmt->fetchColumn() . "\n";
flush();

// 3. Contar mapeos
echo "Consultando mapeos...\n";
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM zkteco_empleado_mapeo");
    echo "Mapeos ZK-Emp: " . $stmt->fetchColumn() . "\n";
    flush();
} catch (Exception $e) {
    echo "ERROR mapeos: " . $e->getMessage() . "\n";
}

// 4. IDs únicos en archivo DAT (desde cache)
$cacheFile = __DIR__ . '/../data/registros_parseados_cache.json';
if (file_exists($cacheFile)) {
    $data = json_decode(file_get_contents($cacheFile), true);
    $zkIds = array_unique(array_column($data['registros'], 'empleado_id'));
    echo "IDs únicos en archivo DAT: " . count($zkIds) . "\n";
    
    // Cuántos de esos IDs tienen mapeo
    $in = implode(',', $zkIds);
    $stmt = $conn->query("SELECT COUNT(*) FROM zkteco_empleado_mapeo WHERE zkteo_id IN ($in)");
    echo "Con mapeo en BD: " . $stmt->fetchColumn() . "\n";
    
    // Cuántos de esos mapeos tienen empleados existentes
    $stmt = $conn->query("SELECT COUNT(DISTINCT m.empleado_id) 
        FROM zkteco_empleado_mapeo m 
        JOIN empleados e ON m.empleado_id = e.id 
        WHERE m.zkteo_id IN ($in)");
    echo "Con empleado existente: " . $stmt->fetchColumn() . "\n";
}

// 5. Verificar fechas en asistencia
$stmt = $conn->query("SELECT MIN(fecha), MAX(fecha) FROM asistencia");
$row = $stmt->fetch();
echo "Rango fechas asistencia: {$row[0]} a {$row[1]}\n";

// 6. Top 5 empleados con más registros
$stmt = $conn->query("SELECT empleado_id, COUNT(*) as cnt FROM asistencia GROUP BY empleado_id ORDER BY cnt DESC LIMIT 5");
echo "\nTop 5 empleados con mas registros:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Emp {$row['empleado_id']}: {$row['cnt']} registros\n";
}
