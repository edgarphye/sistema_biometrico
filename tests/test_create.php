<?php
require_once 'config.php';
require_once 'models/Empleado.php';

$emp = new Empleado();
$data = [
    'nombre' => 'Maria',
    'apellido' => 'Garcia',
    'rfc' => 'MAGA850102' . rand(10, 99), // RFC más corto
    'curp' => 'MAGA850102HDFRZN0' . rand(0, 9), // CURP más corto
    'area' => 'Recursos Humanos',
    'jerarquia' => 'Supervisor'
];
$result = $emp->create($data);
echo 'Empleado creado: ' . ($result ? 'SI' : 'NO') . PHP_EOL;
?>
