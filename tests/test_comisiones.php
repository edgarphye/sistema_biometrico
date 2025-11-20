<?php
require_once 'config.php';
require_once 'models/Comision.php';

$comision = new Comision();

// Crear comisión
$data = [
    'empleado_id' => 3,
    'descripcion' => 'Bono de productividad',
    'monto' => 500.00, // Monto dentro del límite AEFCM
    'fecha_asignacion' => date('Y-m-d'),
    'fecha_vencimiento' => date('Y-m-d', strtotime('+5 days')), // Menos días para cumplir con AEFCM
    'tipo_comision' => 'otros'
];
$result = $comision->create($data);
echo 'Comisión creada: ' . ($result ? 'SI' : 'NO') . PHP_EOL;

// Obtener comisiones del empleado
$comisiones = $comision->getByEmpleado(3);
echo 'Comisiones del empleado: ' . count($comisiones) . PHP_EOL;

// Obtener comisiones vencidas
$vencidas = $comision->getVencidas(3);
echo 'Comisiones vencidas: ' . count($vencidas) . PHP_EOL;

// Obtener total de comisiones
$total = $comision->getTotalComisiones(3, date('m'), date('Y'));
echo 'Total comisiones este mes: $' . number_format($total, 2) . PHP_EOL;
?>
