<?php
namespace App\Helpers;

class DashboardHelper {
    public static function renderEstadoDispositivos($dispositivos) {
        $html = '';
        foreach ($dispositivos as $dispositivo) {
            $statusClass = $dispositivo['status'] === 'conectado' ? 'border-success' : 'border-danger';
            $iconClass = $dispositivo['status'] === 'conectado' ? 'text-success' : 'text-danger';
            $badgeClass = $dispositivo['status'] === 'conectado' ? 'bg-success' : 'bg-danger';

            $html .= "
            <div class='col-md-2 mb-3'>
                <div class='card {$statusClass}'>
                    <div class='card-body text-center'>
                        <i class='fas fa-fingerprint fa-2x {$iconClass}'></i>
                        <h6 class='card-title'>Disp. {$dispositivo['dispositivo_id']}</h6>
                        <span class='badge {$badgeClass}'>{$dispositivo['status']}</span>
                    </div>
                </div>
            </div>";
        }
        return $html;
    }

    public static function renderAlertas($comisionesVencidas, $retardosHoy) {
        $html = '';

        if (count($comisionesVencidas) > 0) {
            $html .= '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <strong>' . count($comisionesVencidas) . '</strong> comisiones vencidas requieren atención.</div>';
        }

        if (count($retardosHoy) > 0) {
            $html .= '<div class="alert alert-info"><i class="fas fa-clock"></i> <strong>' . count($retardosHoy) . '</strong> retardos registrados hoy.</div>';
        }

        if (empty($html)) {
            $html = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> No hay alertas pendientes.</div>';
        }

        return $html;
    }

    public static function renderActividadReciente($asistencias, $empleados) {
        $html = '';
        $empleadosMap = [];
        foreach ($empleados as $emp) {
            $empleadosMap[$emp['id']] = $emp['nombre'] . ' ' . $emp['apellido'];
        }

        $recentes = array_slice($asistencias, 0, 10); // Últimas 10 actividades

        foreach ($recentes as $asistencia) {
            $hora = date('H:i', strtotime($asistencia['timestamp']));
            $empleado = $empleadosMap[$asistencia['empleado_id']] ?? 'Desconocido';
            $tipo = $asistencia['tipo'] === 'entrada' ? 'Entrada' : 'Salida';
            $badgeClass = $asistencia['tipo'] === 'entrada' ? 'bg-success' : 'bg-warning';

            $html .= "
            <tr>
                <td>{$hora}</td>
                <td>{$empleado}</td>
                <td><span class='badge {$badgeClass}'>{$tipo}</span></td>
                <td>Dispositivo {$asistencia['dispositivo_id']}</td>
            </tr>";
        }

        if (empty($html)) {
            $html = '<tr><td colspan="4" class="text-center text-muted">No hay actividad reciente</td></tr>';
        }

        return $html;
    }

    public static function getEstadisticasMes($retardosMes, $ausenciasMes, $comisionesMes) {
        $menores = 0;
        $mayores = 0;

        foreach ($retardosMes as $retardo) {
            if ($retardo['tipo'] === 'menor') {
                $menores = $retardo['total'];
            } elseif ($retardo['tipo'] === 'mayor') {
                $mayores = $retardo['total'];
            }
        }

        $totalAusencias = array_sum(array_column($ausenciasMes, 'total'));

        return "$menores, $mayores, $totalAusencias, " . (is_array($comisionesMes) ? count($comisionesMes) : $comisionesMes);
    }
}
