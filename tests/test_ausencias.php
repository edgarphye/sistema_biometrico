<?php
require_once 'config.php';
require_once 'models/Ausencia.php';

$ausencia = new Ausencia();

// Crear ausencia
$data = [
    'empleado_id' => 3,
    'fecha_inicio' => date('Y-m-d'),
    'fecha_fin' => date('Y-m-d', strtotime('+3 days')),
    'tipo' => 'enfermedad'
];
$result = $ausencia->create($data);
echo 'Ausencia creada: ' . ($result ? 'SI' : 'NO') . PHP_EOL;

// Obtener ausencias del empleado
$ausencias = $ausencia->getByEmpleado(3);
echo 'Ausencias del empleado: ' . count($ausencias) . PHP_EOL;

// Obtener estadísticas de ausencias
$stats = $ausencia->getTotalAusencias(3, date('m'), date('Y'));
echo 'Estadísticas de ausencias este mes: ' . json_encode($stats) . PHP_EOL;
?>
