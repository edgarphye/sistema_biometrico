<?php
require_once 'config.php';
require_once 'models/HorarioLaboral.php';
require_once 'models/Retardo.php';
require_once 'models/Asistencia.php';

$horarioModel = new HorarioLaboral();
$retardoModel = new Retardo();
$asistencia = new Asistencia();

// Crear horario de prueba
$horarioData = [
    'nombre' => 'Horario de Prueba',
    'hora_entrada' => '09:00:00',
    'hora_salida' => '18:00:00',
    'tolerancia_minutos' => 10,
    'descripcion' => 'Horario para pruebas'
];

echo "Creando horario de prueba...\n";
$horarioId = $horarioModel->create($horarioData);
if ($horarioId) {
    echo "✓ Horario creado con ID: $horarioId\n";
} else {
    echo "✗ Error creando horario\n";
}

// Asignar horario a empleado para martes (usando empleado ID 4 que existe)
echo "\nAsignando horario a empleado 4 para martes...\n";
if ($horarioModel->asignarAEmpleado(4, $horarioId, 'martes')) {
    echo "✓ Horario asignado\n";
} else {
    echo "Horario ya asignado o error asignando horario\n";
}

// Probar cálculo de retardo con horario específico (cambiar fecha a martes para que coincida)
echo "\nProbando cálculo de retardo...\n";
$hora_entrada = '09:10:00'; // 10 minutos tarde
$fecha_martes = date('Y-m-d', strtotime('next tuesday')); // Próximo martes
$horario = $horarioModel->getHorarioPorFecha(4, $fecha_martes);

if ($horario) {
    $retardo = $asistencia->calcularRetardo($hora_entrada, $horario['hora_entrada'], $horario['tolerancia_minutos']);
    echo "Retardo calculado con horario específico: " . json_encode($retardo) . "\n";
} else {
    echo "No se encontró horario para la fecha, usando horario por defecto\n";
    $retardo = $asistencia->calcularRetardo($hora_entrada); // Usar horario por defecto
    echo "Retardo calculado con horario por defecto: " . json_encode($retardo) . "\n";
}

// Registrar retardo
echo "\nRegistrando retardo...\n";
$result = $retardoModel->registrarRetardo(4, $fecha_martes, $retardo['minutos'], $retardo['tipo'], $horario ? $horario['id'] : null);
if ($result) {
    echo "✓ Retardo registrado\n";
} else {
    echo "✗ Error registrando retardo\n";
}

// Probar norma AEFCM (necesitaríamos dos retardos consecutivos)
echo "\nPara probar norma AEFCM, registre otro retardo menor en día hábil siguiente\n";

// Obtener horarios del empleado
echo "\nHorarios del empleado 4:\n";
$horariosEmpleado = $horarioModel->getHorariosEmpleado(4);
foreach ($horariosEmpleado as $h) {
    echo "- {$h['dia_semana']}: {$h['hora_entrada']} - {$h['hora_salida']} (Tolerancia: {$h['tolerancia_minutos']} min)\n";
}

echo "\nPruebas completadas.\n";
?>
