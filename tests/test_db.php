<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Verificar empleados
    $result = $conn->query('SELECT * FROM empleados');
    $empleados = $result->fetchAll(PDO::FETCH_ASSOC);
    echo 'Empleados en BD: ' . count($empleados) . PHP_EOL;
    foreach ($empleados as $emp) {
        echo 'ID: ' . $emp['id'] . ' - ' . $emp['nombre'] . ' ' . $emp['apellido'] . PHP_EOL;
    }

    // Verificar asistencia
    $result = $conn->query('SELECT * FROM asistencia');
    $asistencias = $result->fetchAll(PDO::FETCH_ASSOC);
    echo PHP_EOL . 'Registros de asistencia: ' . count($asistencias) . PHP_EOL;

    // Verificar retardos
    $result = $conn->query('SELECT * FROM retardos');
    $retardos = $result->fetchAll(PDO::FETCH_ASSOC);
    echo 'Registros de retardos: ' . count($retardos) . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
