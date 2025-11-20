<?php
require_once 'config.php';
require_once 'models/Retardo.php';
require_once 'models/Asistencia.php';

$retardoModel = new Retardo();
$asistencia = new Asistencia();

// Simular cálculo de retardo
$hora_entrada = '09:45:00'; // Llegada tarde
$fecha = date('Y-m-d');
$empleado_id = 3; // Usar empleado existente
$retardo = $asistencia->calcularRetardo($hora_entrada, $empleado_id, $fecha);
echo 'Retardo calculado para ' . $hora_entrada . ': ' . json_encode($retardo) . PHP_EOL;

// Registrar retardo
$result = $retardoModel->registrarRetardo($empleado_id, date('Y-m-d'), $retardo['minutos'], $retardo['tipo'], $retardo['horario_id']);
echo 'Retardo registrado: ' . ($result ? 'SI' : 'NO') . PHP_EOL;

// Obtener retardos del empleado
$retardos = $retardoModel->getByEmpleado($empleado_id);
echo 'Total retardos del empleado: ' . count($retardos) . PHP_EOL;

// Obtener estadísticas de retardos
$stats = $retardoModel->getTotalRetardos($empleado_id, date('m'), date('Y'));
echo 'Estadísticas de retardos este mes: ' . json_encode($stats) . PHP_EOL;
?>
