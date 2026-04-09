<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/EmpleadoController.php';

require_once __DIR__ . '/../models/Empleado.php';

$controller = new EmpleadoController();
$empleadoModel = new Empleado(); // Instanciar el modelo directamente
echo "=== REVISIÓN COMPLETA DE EMPLEADOS ===\n\n";

// Obtener lista de empleados
$empleados = $empleadoModel->getAll();

if (empty($empleados)) {
    echo "No hay empleados registrados en el sistema.\n";
    exit;
}

foreach ($empleados as $index => $emp) {
    echo ($index + 1) . ". EMPLEADO: {$emp['nombre']} {$emp['apellido']} (ID: {$emp['id']})\n";
    echo str_repeat("-", 60) . "\n";

    // Obtener datos completos del empleado
    ob_start();
    $controller->resumenCompleto($emp['id']);
    $jsonOutput = ob_get_clean();

    $data = json_decode($jsonOutput, true);

    if (!$data || isset($data['error'])) {
        echo "Error obteniendo datos del empleado\n\n";
        continue;
    }

    // Mostrar información del empleado
    echo "INFORMACIÓN PERSONAL:\n";
    echo "- Nombre: {$data['empleado']['nombre_completo']}\n";
    echo "- RFC: {$data['empleado']['rfc']}\n";
    echo "- Área: {$data['empleado']['area']}\n";
    echo "- Jerarquía: {$data['empleado']['jerarquia']}\n";
    echo "- Estado: " . ($data['empleado']['activo'] ? 'Activo' : 'Inactivo') . "\n\n";

    // Estadísticas generales
    echo "ESTADÍSTICAS GENERALES:\n";
    echo "- Total Asistencias: {$data['estadisticas_generales']['total_asistencias']}\n";
    echo "- Total Retardos: {$data['estadisticas_generales']['total_retardos']}\n";
    echo "- Total Comisiones: {$data['estadisticas_generales']['total_comisiones']}\n";
    echo "- Total Ausencias: {$data['estadisticas_generales']['total_ausencias']}\n\n";

    echo "ANÁLISIS DE RETARDOS (Según Reglas de Negocio):\n";
    echo "- Total Retardos: {$data['retardos']['total']}\n";
    echo "- Retardos Justificados: {$data['retardos']['justificados']}\n";
    echo "- Retardos Sin Justificar: {$data['retardos']['sin_justificar']}\n";
    echo "- Acumulados en Quincena Actual: {$data['retardos']['acumulados_quincena_actual']}\n\n";

    echo "CLASIFICACIÓN DE RETARDOS:\n";
    echo "- Tolerancia (≤10 min): {$data['retardos']['clasificacion_retardo']['tolerancia']}\n";
    echo "- Menor (>10-20 min): {$data['retardos']['clasificacion_retardo']['retardo_menor']}\n";
    echo "- Mayor (>20-30 min): {$data['retardos']['clasificacion_retardo']['retardo_mayor']}\n";
    echo "- Fuera de Rango (>30 min): {$data['retardos']['clasificacion_retardo']['fuera_rango']}\n\n";

    // Análisis de comisiones
    echo "ANÁLISIS DE COMISIONES:\n";
    echo "- Total Comisiones: {$data['comisiones']['total']}\n";
    echo "- Comisiones Justificadas: {$data['comisiones']['justificadas']}\n";
    echo "- Comisiones Pendientes: {$data['comisiones']['pendientes']}\n\n";

    // Análisis de ausencias
    echo "ANÁLISIS DE AUSENCIAS:\n";
    echo "- Total Ausencias: {$data['ausencias']['total']}\n";
    echo "- Ausencias Justificadas: {$data['ausencias']['justificadas']}\n";
    echo "- Ausencias Sin Justificar: {$data['ausencias']['sin_justificar']}\n\n";

    // Alertas
    echo "ALERTAS:\n";
    if ($data['alertas']['retardos_acumulados_alto']) {
        echo "⚠️  RETARDOS ACUMULADOS ALTOS: {$data['retardos']['acumulados_quincena_actual']} en quincena actual\n";
    }
    if ($data['alertas']['comisiones_pendientes']) {
        echo "⚠️  COMISIONES PENDIENTES: {$data['comisiones']['pendientes']}\n";
    }
    if ($data['alertas']['ausencias_sin_justificar']) {
        echo "⚠️  AUSENCIAS SIN JUSTIFICAR: {$data['ausencias']['sin_justificar']}\n";
    }

    if (!$data['alertas']['retardos_acumulados_alto'] && !$data['alertas']['comisiones_pendientes'] && !$data['alertas']['ausencias_sin_justificar']) {
        echo "✅ Sin alertas activas\n";
    }
    echo "\n";

    // Mostrar últimos retardos si existen
    if (!empty($data['retardos']['detalle'])) {
        echo "ÚLTIMOS RETARDOS:\n";
        foreach (array_slice($data['retardos']['detalle'], 0, 3) as $retardo) {
            $fecha = date('d/m/Y', strtotime($retardo['fecha']));
            $tipo = $retardo['tipo'] == 'menor' ? 'Menor' : 'Mayor';
            $justificado = $retardo['justificado'] ? 'Justificado' : 'Sin Justificar';
            echo "- $fecha: {$retardo['minutos_retardo']} min ($tipo) - $justificado\n";
        }
        echo "\n";
    }

    echo str_repeat("=", 80) . "\n\n";
}

echo "REVISIÓN COMPLETADA\n";
?>
