<?php
/**
 * Script para verificar los cálculos de horas en ciclos
 * Usage: php verificar_calculos_ciclos.php [empleado_id]
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

$db = Database::getInstance()->getConnection();

$empleadoId = $argv[1] ?? 27;

echo "=== Verificando cálculos para empleado ID: $empleadoId ===\n\n";

// 1. Obtener ciclos asignados
$stmt = $db->prepare("
    SELECT eh.*, c.nombre as nombre_ciclo
    FROM empleado_horarios eh
    LEFT JOIN ciclos c ON eh.ciclo_id = c.id
    WHERE eh.empleado_id = ? AND eh.ciclo_id IS NOT NULL
    ORDER BY eh.fecha_inicio DESC
");
$stmt->execute([$empleadoId]);
$ciclos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Ciclos encontrados: " . count($ciclos) . "\n\n";

foreach ($ciclos as $ciclo) {
    $cicloId = $ciclo['id'];
    $nombreCiclo = $ciclo['nombre_ciclo'] ?? 'Ciclo #' . $ciclo['ciclo_id'];
    $fechaInicio = $ciclo['fecha_inicio'];
    $fechaFin = $ciclo['fecha_fin'] ?? date('Y-m-d');
    
    // Validar fechas
    if ($fechaFin < $fechaInicio) {
        $temp = $fechaInicio;
        $fechaInicio = $fechaFin;
        $fechaFin = $temp;
    }
    
    echo "--- Ciclo: $nombreCiclo ---\n";
    echo "Vigencia: $fechaInicio a $fechaFin\n";
    
    // Calcular meses
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $diff = $inicio->diff($fin);
    $meses = $diff->m + ($diff->y * 12);
    if ($diff->d > 0 && $meses == 0) $meses = 1;
    $meses = max(1, $meses);
    echo "Meses de vigencia: $meses\n";
    
    // Horas requeridas
    $horasSemanales = floatval($ciclo['total_horas_semanales'] ?? 0);
    $horasRequeridas = $horasSemanales * 4.33;
    echo "Horas requeridas/semana: $horasSemanales\n";
    echo "Horas requeridas/mes: " . number_format($horasRequeridas, 2) . "\n";
    
    // Horas trabajadas
    $stmtHoras = $db->prepare("
        SELECT SUM(TIMESTAMPDIFF(SECOND, hora_entrada, hora_salida)) / 3600 as horas
        FROM asistencia 
        WHERE empleado_id = ? 
        AND DATE(fecha) BETWEEN ? AND ?
        AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL
    ");
    $stmtHoras->execute([$empleadoId, $fechaInicio, $fechaFin]);
    $horasData = $stmtHoras->fetch(PDO::FETCH_ASSOC);
    $horasTotales = floatval($horasData['horas'] ?? 0);
    echo "Horas trabajadas (total): " . number_format($horasTotales, 2) . "\n";
    echo "Horas trabajadas/mes: " . number_format($horasTotales / $meses, 2) . "\n";
    
    // Horas de retardo
    $stmtRetardos = $db->prepare("
        SELECT SUM(minutos_retardo) as minutos
        FROM retardos 
        WHERE empleado_id = ? 
        AND DATE(fecha) BETWEEN ? AND ?
        AND justificado = 0
    ");
    $stmtRetardos->execute([$empleadoId, $fechaInicio, $fechaFin]);
    $retardoData = $stmtRetardos->fetch(PDO::FETCH_ASSOC);
    $minutosRetardo = floatval($retardoData['minutos'] ?? 0);
    $horasRetardo = $minutosRetardo / 60;
    echo "Minutos de retardo: $minutosRetardo\n";
    echo "Horas de retardo/mes: " . number_format($horasRetardo / $meses, 2) . "\n";
    
    // Horas efectivas
    $horasEfectivas = $horasTotales - $horasRetardo;
    echo "Horas efectivas/mes: " . number_format($horasEfectivas / $meses, 2) . "\n";
    
    // Diferencia
    $diferencia = ($horasEfectivas / $meses) - $horasRequeridas;
    echo "Diferencia vs requerido: " . number_format($diferencia, 2) . "h\n";
    
    echo "\n";
}

// También verificar retardos
echo "=== Resumen de Retardos ===\n";
$stmtR = $db->prepare("
    SELECT COUNT(*) as total, SUM(minutos_retardo) as minutos
    FROM retardos 
    WHERE empleado_id = ? AND justificado = 0
");
$stmtR->execute([$empleadoId]);
$retardos = $stmtR->fetch(PDO::FETCH_ASSOC);
echo "Total retardos (no justificados): " . $retardos['total'] . "\n";
echo "Total minutos de retardo: " . ($retardos['minutos'] ?? 0) . "\n";
