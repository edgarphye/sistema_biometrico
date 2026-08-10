<?php
/**
 * Script para recalcular tipo_asistencia basándose en el ciclo del empleado
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/models/Database.php';

echo "=== RECALCULANDO TIPO_ASISTENCIA BASADO EN CICLOS ===\n\n";

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Obtener registros que necesitan recalcularse
    // Solo aquellos con hora_entrada y que no sean justificaciones especiales
    $sql = "
        SELECT a.id, a.empleado_id, a.fecha, a.hora_entrada, a.hora_salida, a.tipo_asistencia
        FROM asistencia a
        WHERE a.fecha >= '2026-01-01'
          AND a.hora_entrada IS NOT NULL
          AND a.tipo_asistencia NOT IN (
              'licencia_medica', 'dia_economico', 'comision_entrada', 'comision_salida', 
              'comision_todo_dia', 'vacaciones', 'cuidados_maternos', 'constancia_tiempo', 
              'permiso_fallecimiento', 'CLIDDA', 'EYR'
          )
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($registros);
    echo "Registros a procesar: {$total}\n\n";
    
    $actualizados = 0;
    $errores = 0;
    
    foreach ($registros as $reg) {
        $fecha = $reg['fecha'];
        $diaSemana = date('N', strtotime($fecha));
        $empleadoId = $reg['empleado_id'];
        $horaEntrada = $reg['hora_entrada'];
        
        // Obtener horario del ciclo del empleado
        $sqlHorario = "
            SELECT bc.hora_inicio, hl.tolerancia_minutos as tolerancia
            FROM empleados_ciclos ec
            JOIN ciclos c ON ec.ciclo_id = c.id
            JOIN bloques_ciclo bc ON c.id = bc.ciclo_id
            JOIN horarios_laborales hl ON bc.horario_id = hl.id
            WHERE ec.empleado_id = ?
              AND ec.activo = 1
              AND c.activo = 1
              AND bc.activo = 1
              AND bc.dia_semana = ?
              AND ec.fecha_inicio <= ?
            ORDER BY ec.fecha_inicio DESC
            LIMIT 1
        ";
        
        $stmtHorario = $conn->prepare($sqlHorario);
        $stmtHorario->execute([$empleadoId, $diaSemana, $fecha]);
        $horario = $stmtHorario->fetch(PDO::FETCH_ASSOC);
        
        $horaProgramada = $horario['hora_inicio'] ?? '09:00:00';
        $tolerancia = $horario['tolerancia'] ?? 10;
        
        // Calcular minutos de retardo
        $timestampProgramado = strtotime($horaProgramada);
        $timestampEntrada = strtotime($horaEntrada);
        
        $minutosRetraso = 0;
        if ($timestampEntrada > $timestampProgramado) {
            $minutosRetraso = floor(($timestampEntrada - $timestampProgramado) / 60);
        }
        
        // Determinar tipo
        if ($minutosRetraso <= $tolerancia) {
            $nuevoTipo = 'normal';
        } elseif ($minutosRetraso >= 11 && $minutosRetraso <= 20) {
            $nuevoTipo = 'retardo_menor';
        } elseif ($minutosRetraso >= 21 && $minutosRetraso <= 30) {
            $nuevoTipo = 'retardo_mayor';
        } else {
            $nuevoTipo = 'falta';
        }
        
        // Actualizar si cambió
        if ($nuevoTipo !== $reg['tipo_asistencia']) {
            $updateSql = "UPDATE asistencia SET tipo_asistencia = ? WHERE id = ?";
            $stmtUpdate = $conn->prepare($updateSql);
            $stmtUpdate->execute([$nuevoTipo, $reg['id']]);
            $actualizados++;
        }
    }
    
    echo "=== RESUMEN ===\n";
    echo "Total procesados: {$total}\n";
    echo "Actualizados: {$actualizados}\n";
    echo "Errores: {$errores}\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}