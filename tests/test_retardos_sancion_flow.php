<?php
// Test flow: insertar 5 retardos en el mes y verificar que se crea sanción de suspension
// Usage: php tests/test_retardos_sancion_flow.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Sancion.php';

$db = new Database();
$pdo = $db->getConnection();

function rr($m) { echo $m . PHP_EOL; }

$pdo->beginTransaction();
try {
    // Crear empleado
    $stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute(['Test2', 'Empleado', 'TSTRFC7654321', 'TSTCURP0987654321', 'IT', 'staff']);
    $empleado_id = $pdo->lastInsertId();

    $retModel = new Retardo();
    $sancModel = new Sancion();

    // Insertar 5 retardos (no justificados) en el mes actual
    $fecha = date('Y-m-01');
    for ($i = 0; $i < 5; $i++) {
        $d = date('Y-m-d', strtotime($fecha . " +$i days"));
        $retModel->registrarRetardo($empleado_id, $d, 15, 'menor');
    }

    // Ejecutar creación de suspensión (la función crea_suspension si hay >=5 notas)
    $created = $retModel->crearSuspensionSiCorresponde($empleado_id, date('n'), date('Y'), null);

    rr('Suspension creada: ' . ($created ? 'YES' : 'NO'));

    // Buscar sanciones para empleado en el año
    $s = $sancModel->getByEmpleadoYear($empleado_id, date('Y'));
    rr('Sanciones encontradas: ' . count($s));

    $pdo->rollBack();
    rr('Rollback executed.');
} catch (Exception $e) {
    $pdo->rollBack();
    rr('Test error: ' . $e->getMessage());
    exit(1);
}

?>