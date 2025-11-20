<?php
require_once 'config.php';
require_once 'models/HorarioLaboral.php';
require_once 'models/EmpleadoHorarios.php';

$horarioModel = new HorarioLaboral();
$empleadoHorariosModel = new EmpleadoHorarios();

// Crear horario de prueba
echo "Creando horario de prueba...\n";
$horarioData = [
    'nombre' => 'Horario Matutino',
    'hora_entrada' => '09:00:00',
    'hora_salida' => '17:00:00',
    'tolerancia_minutos' => 10,
    'descripcion' => 'Horario estándar de oficina'
];

$horarioId = $horarioModel->create($horarioData);
echo "Horario creado con ID: $horarioId\n";

// Obtener todos los horarios
echo "\nObteniendo todos los horarios...\n";
$horarios = $horarioModel->getAll();
foreach ($horarios as $horario) {
    echo "- {$horario['nombre']}: {$horario['hora_entrada']} - {$horario['hora_salida']}\n";
}

// Asignar horario a empleado (asumiendo que existe el empleado con ID 1)
echo "\nAsignando horario a empleado...\n";
$result = $empleadoHorariosModel->asignarHorario(3, $horarioId, 'lunes');
echo "Asignación " . ($result ? "exitosa" : "fallida") . "\n";

// Obtener horarios del empleado
echo "\nObteniendo horarios del empleado...\n";
$horariosEmpleado = $empleadoHorariosModel->getHorarios(3);
foreach ($horariosEmpleado as $horario) {
    echo "- {$horario['dia_semana']}: {$horario['hora_entrada']} - {$horario['hora_salida']}\n";
}

// Obtener horario por fecha
echo "\nObteniendo horario para hoy...\n";
$fecha = date('Y-m-d');
$horarioFecha = $empleadoHorariosModel->getHorarioPorFecha(3, $fecha);
if ($horarioFecha) {
    echo "Horario para hoy: {$horarioFecha['hora_entrada']} - {$horarioFecha['hora_salida']}\n";
} else {
    echo "No hay horario asignado para hoy\n";
}

echo "\nPruebas completadas.\n";
?>
