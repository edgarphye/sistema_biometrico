<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Retardo.php';
require_once __DIR__ . '/../models/Sancion.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/DispositivoBiometrico.php';
require_once __DIR__ . '/../models/HorarioLaboral.php';
require_once __DIR__ . '/../models/LogDispositivo.php';
require_once __DIR__ . '/../services/AsistenciaService.php';

echo "=== PRUEBA MANUAL: PSEUDOCÓDIGO DE RETARDOS ===\n\n";

// Inicializar servicios
$db = new Database();
$pdo = $db->getConnection();
$retardoModel = new Retardo();
$sancionModel = new Sancion();
$asistenciaService = new AsistenciaService();

// Crear empleado de prueba
echo "1. Creando empleado de prueba...\n";
$stmt = $pdo->prepare("INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['Juan', 'Pérez', 'TEST' . time(), 'TEST' . time() . 'HDFRRN01', 'Administración', 'Empleado']);
$empleado_id = $pdo->lastInsertId();
echo "   Empleado creado con ID: $empleado_id\n\n";

// Crear dispositivo de prueba
echo "2. Creando dispositivo de prueba...\n";
$stmt = $pdo->prepare("INSERT INTO dispositivos_biometricos (dispositivo_id, nombre, sede, activo) VALUES (?, ?, ?, ?)");
$stmt->execute(['TEST001', 'Dispositivo Test', 'Sede Central', 1]);
$dispositivo_id = $pdo->lastInsertId();
echo "   Dispositivo creado con ID: $dispositivo_id\n\n";

// Crear horario de prueba
echo "3. Creando horario de prueba...\n";
$stmt = $pdo->prepare("INSERT INTO horarios_laborales (nombre, hora_entrada, hora_salida, tolerancia_minutos, activo) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['Horario Test', '09:00:00', '18:00:00', 15, 1]);
$horario_id = $pdo->lastInsertId();

// Asignar horario al empleado
$stmt = $pdo->prepare("INSERT INTO empleado_horarios (empleado_id, horario_id, fecha_asignacion) VALUES (?, ?, ?)");
$stmt->execute([$empleado_id, $horario_id, '2023-01-01']);
echo "   Horario asignado\n\n";

// Test clasificación de retardos según pseudocódigo
echo "4. Probando clasificación de retardos según pseudocódigo...\n";
$test_cases = [
    ['hora' => '09:00:00', 'expected' => 'puntual', 'desc' => 'Llegada puntual'],
    ['hora' => '09:05:00', 'expected' => 'tolerancia', 'desc' => 'Retardo 5 min (tolerancia)'],
    ['hora' => '09:15:00', 'expected' => 'menor', 'desc' => 'Retardo 15 min (menor)'],
    ['hora' => '09:25:00', 'expected' => 'mayor', 'desc' => 'Retardo 25 min (mayor)'],
    ['hora' => '09:35:00', 'expected' => 'tolerancia', 'desc' => 'Retardo 35 min (tolerancia)']
];

foreach ($test_cases as $test) {
    $retardo = $asistenciaService->calcularRetardo($test['hora'], $empleado_id, '2023-01-01', $dispositivo_id);
    $status = $retardo['tipo'] === $test['expected'] ? '✓' : '✗';
    echo "   {$status} {$test['desc']}: esperado '{$test['expected']}', obtenido '{$retardo['tipo']}'\n";
}
echo "\n";

// Test acumulación quincenal
echo "5. Probando acumulación quincenal...\n";
$fechas_test = ['2023-01-02', '2023-01-05', '2023-01-10', '2023-01-16', '2023-01-20'];

foreach ($fechas_test as $fecha) {
    $retardoModel->registrarRetardo($empleado_id, $fecha, '09:15:00', 15, 'menor', $horario_id, $dispositivo_id);
    $acumulados = $retardoModel->getRetardosAcumuladosQuincena($empleado_id, $fecha);
    echo "   Fecha $fecha: $acumulados retardos acumulados\n";
}
echo "\n";

// Test registro de entrada con lógica completa
echo "6. Probando registro de entrada con lógica completa del pseudocódigo...\n";
$fecha_actual = date('Y-m-d');
$result = $asistenciaService->registrarEntrada($empleado_id, $dispositivo_id, 'huella', null, 95, null, 150);

if ($result) {
    echo "   ✓ Registro de entrada exitoso\n";

    // Verificar retardo registrado
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM retardos WHERE empleado_id = ? AND fecha = ?");
    $stmt->execute([$empleado_id, $fecha_actual]);
    $retardos_count = $stmt->fetch()['total'];
    echo "   ✓ Retardos registrados hoy: $retardos_count\n";

    // Verificar acumulación
    $acumulados = $retardoModel->getRetardosAcumuladosQuincena($empleado_id, $fecha_actual);
    echo "   ✓ Retardos acumulados en quincena: $acumulados\n";

    // Verificar sanciones
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sanciones WHERE empleado_id = ? AND YEAR(fecha_inicio) = ?");
    $stmt->execute([$empleado_id, date('Y')]);
    $sanciones_count = $stmt->fetch()['total'];
    echo "   ✓ Sanciones creadas este año: $sanciones_count\n";

} else {
    echo "   ✗ Error en registro de entrada\n";
}
echo "\n";

// Test funcionalidades existentes no afectadas
echo "7. Probando que funcionalidades existentes no se afectaron...\n";

// Test registro de salida
$result = $asistenciaService->registrarSalida($empleado_id, $dispositivo_id, 'huella', null, 95, null, 150);
echo "   " . ($result ? "✓" : "✗") . " Registro de salida funciona\n";

// Test obtener asistencia filtrada
$asistencia = $asistenciaService->getAsistenciaFiltrada(['empleado_id' => $empleado_id]);
echo "   " . (is_array($asistencia) ? "✓" : "✗") . " Obtener asistencia filtrada funciona\n";

// Test calcular horas
$horas = $asistenciaService->calcularHorasPorEmpleado(
    ['id' => $empleado_id, 'nombre' => 'Juan', 'apellido' => 'Pérez'],
    ['hora_entrada' => '09:00:00', 'hora_salida' => '18:00:00'],
    [],
    $fecha_actual
);
echo "   " . (is_array($horas) && isset($horas['horas_trabajadas']) ? "✓" : "✗") . " Calcular horas funciona\n";

echo "\n";

// Limpiar datos de prueba
echo "8. Limpiando datos de prueba...\n";
$pdo->exec("DELETE FROM retardos WHERE empleado_id = $empleado_id");
$pdo->exec("DELETE FROM sanciones WHERE empleado_id = $empleado_id");
$pdo->exec("DELETE FROM asistencia WHERE empleado_id = $empleado_id");
$pdo->exec("DELETE FROM empleado_horarios WHERE empleado_id = $empleado_id");
$pdo->exec("DELETE FROM empleados WHERE id = $empleado_id");
$pdo->exec("DELETE FROM dispositivos_biometricos WHERE id = $dispositivo_id");
$pdo->exec("DELETE FROM horarios_laborales WHERE id = $horario_id");
echo "   ✓ Limpieza completada\n\n";

echo "=== PRUEBA COMPLETADA ===\n";
?>
