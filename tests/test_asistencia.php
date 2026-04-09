<?php
require_once 'config.php';
require_once 'services/AsistenciaService.php';
require_once 'models/Biometrico.php';

$bio = new Biometrico();
$asistenciaService = new AsistenciaService();

// Simular datos biométricos
$datos = $bio->recibirDatosBiometricos(1);
echo 'Datos biométricos simulados: ' . json_encode($datos) . PHP_EOL;

// Verificar identidad
$empleado = $bio->verificarIdentidad($datos['data'], $datos['type']);
echo 'Empleado identificado: ' . ($empleado ? $empleado['nombre'] . ' ' . $empleado['apellido'] : 'NO') . PHP_EOL;

if ($empleado) {
    // Registrar entrada
    $result = $asistenciaService->registrarEntrada($empleado['id'], 1, $datos['type'], $datos['data'], $datos['quality_score'], $datos['metadata'], 0.5);
    echo 'Entrada registrada: ' . ($result ? 'SI' : 'NO') . PHP_EOL;

    // Calcular retardo
    $retardo = $asistenciaService->calcularRetardo('09:15:00', $empleado['id'], date('Y-m-d')); // Simular llegada a las 9:15
    echo 'Cálculo de retardo: ' . json_encode($retardo) . PHP_EOL;
}
?>
