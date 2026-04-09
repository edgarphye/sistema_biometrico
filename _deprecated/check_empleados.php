<?php
require_once 'config.php';
require_once 'models/Database.php';
$db = new Database();
$stmt = $db->getConnection()->query('SELECT id, nombre, apellido, activo FROM empleados');
$empleados = $stmt->fetchAll();
echo 'Empleados en BD:' . PHP_EOL;
foreach ($empleados as $emp) {
    echo $emp['id'] . ': ' . $emp['nombre'] . ' ' . $emp['apellido'] . ' - Activo: ' . ($emp['activo'] ? 'SI' : 'NO') . PHP_EOL;
}
?>
