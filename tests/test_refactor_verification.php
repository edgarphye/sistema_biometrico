<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../services/AsistenciaService.php';
require_once __DIR__ . '/../services/ReporteService.php';

// Mock session for AEFCM rules
$_SESSION['user_id'] = 1;

echo "Iniciando pruebas de verificación de refactorización...\n";

// 1. Instanciar servicios
try {
    $asistenciaService = new AsistenciaService();
    $reporteService = new ReporteService();
    echo "[OK] Servicios instanciados correctamente.\n";
} catch (Exception $e) {
    echo "[ERROR] Fallo al instanciar servicios: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Probar calcularRetardo (Lógica de negocio movida)
echo "\nProbando calcularRetardo...\n";
$fecha = date('Y-m-d');
$hora_entrada_puntual = '08:55:00';
$hora_entrada_retardo_menor = '09:05:00'; // Asumiendo tolerancia 10 min
$hora_entrada_retardo_mayor = '09:30:00';

// Mock empleado ID (asegúrate de que exista un empleado con ID 1 o ajusta)
$empleado_id = 1; 

$resultadoPuntual = $asistenciaService->calcularRetardo($hora_entrada_puntual, $empleado_id, $fecha);
echo "Entrada Puntual ({$hora_entrada_puntual}): Tipo=" . $resultadoPuntual['tipo'] . " (Esperado: sin_retardo)\n";

$resultadoMenor = $asistenciaService->calcularRetardo($hora_entrada_retardo_menor, $empleado_id, $fecha);
echo "Entrada Retardo Menor ({$hora_entrada_retardo_menor}): Tipo=" . $resultadoMenor['tipo'] . " (Esperado: menor o sin_retardo dependiendo de tolerancia)\n";

$resultadoMayor = $asistenciaService->calcularRetardo($hora_entrada_retardo_mayor, $empleado_id, $fecha);
echo "Entrada Retardo Mayor ({$hora_entrada_retardo_mayor}): Tipo=" . $resultadoMayor['tipo'] . " (Esperado: mayor)\n";

// 3. Probar ReporteService (Existencia de métodos)
echo "\nProbando ReporteService...\n";
if (method_exists($reporteService, 'exportarExcel') && method_exists($reporteService, 'exportarPDF')) {
    echo "[OK] Métodos de exportación existen en ReporteService.\n";
} else {
    echo "[ERROR] Faltan métodos en ReporteService.\n";
}

// 4. Verificar AsistenciaController (Simulación básica)
echo "\nVerificando AsistenciaController...\n";
require_once __DIR__ . '/../controllers/AsistenciaController.php';
$controller = new AsistenciaController();
if ($controller) {
    echo "[OK] AsistenciaController instanciado correctamente.\n";
}

echo "\nPruebas de verificación completadas.\n";
