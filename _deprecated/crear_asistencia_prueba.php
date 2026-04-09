<?php
/**
 * Script para crear registros de asistencia de prueba
 * Usage: php crear_asistencia_prueba.php [empleado_id] [dias]
 */

// Cargar configuración
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/models/Database.php';

$db = Database::getInstance()->getConnection();

// Obtener un empleado válido
$stmt = $db->query("SELECT id, nombre, apellido FROM empleados WHERE activo = 1 LIMIT 1");
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empleado) {
    echo "No hay empleados activos en la base de datos.\n";
    exit;
}

// Obtener un empleado específico o el primero disponible
$stmtCheck = $db->prepare("SELECT id, nombre, apellido FROM empleados WHERE id = ?");
$stmtCheck->execute([$argv[1] ?? $empleado['id']]);
$emp = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    echo "El empleado con ID " . ($argv[1] ?? '') . " no existe. Usando: {$empleado['nombre']} {$empleado['apellido']} (ID: {$empleado['id']})\n";
    $empleadoId = $empleado['id'];
} else {
    $empleadoId = $emp['id'];
    echo "Usando empleado: {$emp['nombre']} {$emp['apellido']} (ID: {$empleadoId})\n";
}

$dias = $argv[2] ?? 60;

// Obtener la fecha actual
$fechaActual = new DateTime();

echo "Generando $dias días de asistencia...\n\n";

$conn = $db;

for ($i = $dias; $i >= 0; $i--) {
    $fecha = clone $fechaActual;
    $fecha->modify("-$i days");
    $fechaStr = $fecha->format('Y-m-d');
    
    // Solo registrar de lunes a viernes
    $diaSemana = (int)$fecha->format('N');
    if ($diaSemana > 5) continue;
    
    // Generar hora de entrada aleatoria entre 8:00 y 9:30
    $horaEntradaMin = rand(8, 9);
    $minutoEntrada = rand(0, 30);
    $horaEntrada = sprintf("%02d:%02d:00", $horaEntradaMin, $minutoEntrada);
    
    // Generar hora de salida aleatoria entre 16:00 y 17:30
    $horaSalidaMin = rand(16, 17);
    $minutoSalida = rand(0, 30);
    $horaSalida = sprintf("%02d:%02d:00", $horaSalidaMin, $minutoSalida);
    
    // Insertar registro de asistencia
    $stmt = $conn->prepare("
        INSERT INTO asistencia (empleado_id, fecha, hora_entrada, hora_salida, tipo_asistencia, dispositivo_id, created_at)
        VALUES (?, ?, ?, ?, 'normal', 1, NOW())
    ");
    
    try {
        $stmt->execute([$empleadoId, $fechaStr, $horaEntrada, $horaSalida]);
        echo "✓ $fechaStr: $horaEntrada - $horaSalida\n";
    } catch (Exception $e) {
        // Ignorar duplicados
    }
}

echo "\n¡Registros de asistencia creados exitosamente!\n";

// Mostrar resumen
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, 
           SUM(TIMESTAMPDIFF(SECOND, hora_entrada, hora_salida)) / 3600 as horas
    FROM asistencia 
    WHERE empleado_id = ? AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL
");
$stmt->execute([$empleadoId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nResumen:\n";
echo "- Total de registros: " . $result['total'] . "\n";
echo "- Horas trabajadas: " . round($result['horas'] ?? 0, 2) . " hrs\n";

// Obtener la fecha actual
$fechaActual = new DateTime();

// Generar registros de asistencia para los últimos N días
echo "Creando registros de asistencia de prueba para empleado ID: $empleadoId\n";
echo "Generando $dias días de asistencia...\n\n";

$conn = $db;

for ($i = $dias; $i >= 0; $i--) {
    $fecha = clone $fechaActual;
    $fecha->modify("-$i days");
    $fechaStr = $fecha->format('Y-m-d');
    
    // Solo registrar de lunes a viernes
    $diaSemana = (int)$fecha->format('N');
    if ($diaSemana > 5) continue;
    
    // Generar hora de entrada aleatoria entre 8:00 y 9:30
    $horaEntradaMin = rand(8, 9);
    $minutoEntrada = rand(0, 30);
    $horaEntrada = sprintf("%02d:%02d:00", $horaEntradaMin, $minutoEntrada);
    
    // Generar hora de salida aleatoria entre 16:00 y 17:30
    $horaSalidaMin = rand(16, 17);
    $minutoSalida = rand(0, 30);
    $horaSalida = sprintf("%02d:%02d:00", $horaSalidaMin, $minutoSalida);
    
    // Insertar registro de asistencia
    $stmt = $conn->prepare("
        INSERT INTO asistencia (empleado_id, fecha, hora_entrada, hora_salida, tipo_asistencia, dispositivo_id, created_at)
        VALUES (?, ?, ?, ?, 'normal', 1, NOW())
    ");
    
    try {
        $stmt->execute([$empleadoId, $fechaStr, $horaEntrada, $horaSalida]);
        echo "✓ $fechaStr: $horaEntrada - $horaSalida\n";
    } catch (Exception $e) {
        echo "✗ Error en $fechaStr: " . $e->getMessage() . "\n";
    }
}

echo "\n¡Registros de asistencia creados exitosamente!\n";

// Mostrar resumen
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, 
           SUM(TIMESTAMPDIFF(SECOND, hora_entrada, hora_salida)) / 3600 as horas
    FROM asistencia 
    WHERE empleado_id = ? AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL
");
$stmt->execute([$empleadoId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nResumen:\n";
echo "- Total de registros: " . $result['total'] . "\n";
echo "- Horas trabajadas: " . round($result['horas'], 2) . " hrs\n";
