<?php
require_once 'config.php';
require_once 'models/Database.php';

echo "🔍 Verificando integridad de foreign key...\n";

$db = new Database();
$pdo = $db->getConnection();

// Verificar empleados que existen en mapeo pero no en tabla empleados
$stmt = $pdo->prepare("
    SELECT zm.empleado_id, zm.zk_empleado_id, e.nombre as nombre_empleado
    FROM zk_empleado_mapeo zm
    LEFT JOIN empleados e ON zm.empleado_id = e.id
    WHERE e.id IS NULL
");
$stmt->execute();
$empleadosFaltantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($empleadosFaltantes)) {
    echo "   ❌ Empleados en mapeo que no existen en tabla empleados:\n";
    foreach ($empleadosFaltantes as $emp) {
        echo "      - Empleado ID {$emp['empleado_id']} (ZK-ID: {$emp['zk_empleado_id']})\n";
    }
} else {
    echo "   ✅ Todos los empleados en mapeo existen en tabla empleados\n";
}

// Verificar los primeros 10 empleados en mapeo
$stmt = $pdo->prepare("
    SELECT zm.empleado_id, zm.zk_empleado_id, e.nombre as nombre_empleado
    FROM zk_empleado_mapeo zm
    LEFT JOIN empleados e ON zm.empleado_id = e.id
    LIMIT 10
");
$stmt->execute();
$primerosEmpleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   📋 Primeros 10 empleados en mapeo:\n";
foreach ($primerosEmpleados as $emp) {
    $estado = $emp['nombre_empleado'] ? '✅' : '❌';
    echo "      {$estado} ID: {$emp['empleado_id']} (ZK: {$emp['zk_empleado_id']}) - " . ($emp['nombre_empleado'] ?? 'NO EXISTE') . "\n";
}
?>