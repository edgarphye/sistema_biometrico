<?php
require_once __DIR__ . '/Database.php';

class HorarioClassifier {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Calcula el resumen de un horario asignado
     * @param int $asignacionId ID de la asignación en empleado_horarios
     * @return array Resumen con hora_entrada, hora_salida, total_horas, tipo, detalle
     */
    public function calcularResumenHorario($asignacionId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT eh.*, c.nombre as nombre_ciclo, h.nombre as nombre_horario, h.hora_entrada as hl_entrada, h.hora_salida as hl_salida
            FROM empleado_horarios eh
            LEFT JOIN ciclos c ON eh.ciclo_id = c.id
            LEFT JOIN horarios_laborales h ON eh.horario_id = h.id
            WHERE eh.id = ?
        ");
        $stmt->execute([$asignacionId]);
        $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$asignacion) {
            return null;
        }

        $resultado = [
            'tipo_asignacion' => !empty($asignacion['ciclo_id']) ? 'CICLO' : 'HORARIO',
            'nombre' => !empty($asignacion['ciclo_id']) 
                ? ($asignacion['nombre_ciclo'] ?? 'Ciclo #' . $asignacion['ciclo_id'])
                : ($asignacion['nombre_horario'] ?? 'Horario #' . $asignacion['horario_id']),
            'fecha_inicio' => $asignacion['fecha_inicio'],
            'fecha_fin' => $asignacion['fecha_fin'],
            'hora_entrada' => null,
            'hora_salida' => null,
            'total_horas_semanales' => 0,
            'tipo_horario' => null,
            'detalle' => []
        ];

        if (!empty($asignacion['ciclo_id'])) {
            // Es un ciclo - calcular desde bloques_ciclo
            $resultado = $this->calcularDesdeCiclo($asignacion['ciclo_id'], $resultado);
        } elseif (!empty($asignacion['horario_id'])) {
            // Es un horario fijo
            $resultado = $this->calcularDesdeHorarioFijo($asignacion, $resultado);
        }

        // Determinar estado de vigencia
        $hoy = date('Y-m-d');
        if ($asignacion['fecha_inicio'] > $hoy) {
            $resultado['estado'] = 'FUTURO';
        } elseif ($asignacion['fecha_fin'] && $asignacion['fecha_fin'] < $hoy) {
            $resultado['estado'] = 'FINALIZADO';
        } else {
            $resultado['estado'] = 'VIGENTE';
        }

        return $resultado;
    }

    /**
     * Calcula los datos desde un ciclo
     */
    private function calcularDesdeCiclo($cicloId, $resultado) {
        $conn = $this->db->getConnection();

        // Obtener todos los bloques activos del ciclo
        $stmt = $conn->prepare("
            SELECT dia_semana, hora_inicio, hora_fin, semana_numero
            FROM bloques_ciclo
            WHERE ciclo_id = ? AND activo = 1
            ORDER BY semana_numero, dia_semana
        ");
        $stmt->execute([$cicloId]);
        $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($bloques)) {
            return $resultado;
        }

        // Calcular hora entrada mínima y hora salida máxima
        $horasEntrada = array_column($bloques, 'hora_inicio');
        $horasSalida = array_column($bloques, 'hora_fin');
        
        $resultado['hora_entrada'] = min($horasEntrada);
        $resultado['hora_salida'] = max($horasSalida);

        // Calcular total de horas semanales
        $totalHoras = 0;
        $diasConHorario = [];
        
        foreach ($bloques as $bloque) {
            $entrada = strtotime($bloque['hora_inicio']);
            $salida = strtotime($bloque['hora_fin']);
            
            // Manejar cruce de medianoche
            if ($salida < $entrada) {
                $salida = strtotime('+1 day', $salida);
            }
            
            $horas = ($salida - $entrada) / 3600;
            $totalHoras += $horas;

            $diaKey = $bloque['semana_numero'] . '-' . $bloque['dia_semana'];
            if (!isset($diasConHorario[$diaKey])) {
                $diasConHorario[$diaKey] = [
                    'semana' => $bloque['semana_numero'],
                    'dia' => $bloque['dia_semana'],
                    'hora_inicio' => $bloque['hora_inicio'],
                    'hora_fin' => $bloque['hora_fin']
                ];
            }
        }
        
        $resultado['total_horas_semanales'] = round($totalHoras, 2);
        $resultado['detalle'] = array_values($diasConHorario);

        // Determinar tipo de horario basándose en la configuración del ciclo
        $semanasDistintas = count(array_unique(array_column($bloques, 'semana_numero')));
        
        // Obtener los rangos de horario únicos para días laborables (0-4 = lunes a viernes)
        $rangosUnicos = [];
        foreach ($bloques as $b) {
            if ($b['dia_semana'] >= 0 && $b['dia_semana'] <= 4) {
                $rangosUnicos[$b['dia_semana']] = $b['hora_inicio'] . '-' . $b['hora_fin'];
            }
        }
        $rangosUnicosCount = count(array_unique($rangosUnicos));
        
        // Lógica de clasificación:
        // - ROTATIVO: si hay más de 1 semana en el ciclo (patrón semanal diferente)
        // - FIJO: si solo hay 1 semana Y todos los días laborables tienen el mismo horario
        // - COMBINADO: si hay más de un rango de horario diferente entre los días laborables
        if ($semanasDistintas > 1) {
            $resultado['tipo_horario'] = 'Ciclo Rotativo';
            $resultado['tipo_horario_codigo'] = 'ROTATIVO';
        } elseif ($rangosUnicosCount == 1) {
            // Solo una semana y todos los días laborables tienen el mismo horario
            $resultado['tipo_horario'] = 'Horario Fijo';
            $resultado['tipo_horario_codigo'] = 'FIJO';
        } else {
            // Una semana pero con horarios diferentes entre días laborables
            $resultado['tipo_horario'] = 'Horario Combinado';
            $resultado['tipo_horario_codigo'] = 'COMBINADO';
        }

        return $resultado;
    }

    /**
     * Calcula los datos desde un horario fijo
     */
    private function calcularDesdeHorarioFijo($asignacion, $resultado) {
        $entrada = strtotime($asignacion['hl_entrada']);
        $salida = strtotime($asignacion['hl_salida']);
        
        // Manejar cruce de medianoche
        if ($salida < $entrada) {
            $salida = strtotime('+1 day', $salida);
        }
        
        $horas = ($salida - $entrada) / 3600;
        
        $resultado['hora_entrada'] = $asignacion['hl_entrada'];
        $resultado['hora_salida'] = $asignacion['hl_salida'];
        $resultado['total_horas_semanales'] = round($horas, 2);
        $resultado['tipo_horario'] = 'Horario Fijo';
        $resultado['tipo_horario_codigo'] = 'FIJO';
        $resultado['detalle'] = [
            ['semana' => 1, 'dia' => 'todos', 'hora_inicio' => $asignacion['hl_entrada'], 'hora_fin' => $asignacion['hl_salida']]
        ];

        return $resultado;
    }

    /**
     * Actualiza los campos calculados en la tabla empleado_horarios
     * @param int $asignacionId ID de la asignación
     * @return bool Éxito de la operación
     */
    public function actualizarCamposCalculados($asignacionId) {
        $resumen = $this->calcularResumenHorario($asignacionId);
        
        if (!$resumen) {
            return false;
        }

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            UPDATE empleado_horarios 
            SET hora_entrada = ?,
                hora_salida = ?,
                total_horas_semanales = ?,
                tipo_horario = ?,
                detalle_json = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $resumen['hora_entrada'],
            $resumen['hora_salida'],
            $resumen['total_horas_semanales'],
            $resumen['tipo_horario_codigo'],
            json_encode($resumen['detalle']),
            $asignacionId
        ]);
    }

    /**
     * Obtiene el historial completo de horarios de un empleado con todos los datos calculados
     * @param int $empleadoId ID del empleado
     * @return array Historial de horarios
     */
    public function getHistorialCompleto($empleadoId) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT eh.*, 
                   c.nombre as nombre_ciclo, 
                   h.nombre as nombre_horario,
                   h.hora_entrada as hl_entrada,
                   h.hora_salida as hl_salida,
                   eh.detalle_json
            FROM empleado_horarios eh
            LEFT JOIN ciclos c ON eh.ciclo_id = c.id
            LEFT JOIN horarios_laborales h ON eh.horario_id = h.id
            WHERE eh.empleado_id = ?
            ORDER BY eh.fecha_inicio DESC
        ");
        $stmt->execute([$empleadoId]);
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        $hoy = date('Y-m-d');
        
        foreach ($asignaciones as $asignacion) {
            // Si ya tiene campos calculados, usarlos
            if ($asignacion['hora_entrada'] && $asignacion['tipo_horario']) {
                $item = [
                    'id' => $asignacion['id'],
                    'empleado_id' => $asignacion['empleado_id'],
                    'ciclo_id' => $asignacion['ciclo_id'],
                    'horario_id' => $asignacion['horario_id'],
                    'tipo_asignacion' => !empty($asignacion['ciclo_id']) ? 'CICLO' : 'HORARIO',
                    'nombre' => !empty($asignacion['ciclo_id']) 
                        ? $asignacion['nombre_ciclo'] 
                        : $asignacion['nombre_horario'],
                    'fecha_inicio' => $asignacion['fecha_inicio'],
                    'fecha_fin' => $asignacion['fecha_fin'],
                    'hora_entrada' => $asignacion['hora_entrada'],
                    'hora_salida' => $asignacion['hora_salida'],
                    'total_horas_semanales' => $asignacion['total_horas_semanales'],
                    'tipo_horario' => $this->mapearTipo($asignacion['tipo_horario']),
                    'detalle' => is_array($asignacion['detalle_json']) 
                        ? $asignacion['detalle_json'] 
                        : json_decode($asignacion['detalle_json'] ?? '[]', true)
                ];
            } else {
                // Recalcular dinámicamente
                $item = $this->calcularResumenHorario($asignacion['id']);
                // Asegurar que tenga los IDs
                $item['ciclo_id'] = $asignacion['ciclo_id'];
                $item['horario_id'] = $asignacion['horario_id'];
            }

            // Determinar estado
            if ($asignacion['fecha_inicio'] > $hoy) {
                $item['estado'] = 'FUTURO';
            } elseif ($asignacion['fecha_fin'] && $asignacion['fecha_fin'] < $hoy) {
                $item['estado'] = 'FINALIZADO';
            } else {
                $item['estado'] = 'VIGENTE';
            }

            $resultado[] = $item;
        }

        return $resultado;
    }

    /**
     * Mapea el código de tipo a nombre legible
     */
    private function mapearTipo($codigo) {
        $map = [
            'FIJO' => 'Horario Fijo',
            'COMBINADO' => 'Horario Combinado',
            'ROTATIVO' => 'Ciclo Rotativo'
        ];
        return $map[$codigo] ?? $codigo;
    }

    /**
     * Obtiene el color del badge según el tipo de horario
     */
    public static function getBadgeColor($tipoCodigo) {
        $colors = [
            'FIJO' => 'bg-primary',
            'COMBINADO' => 'bg-warning text-dark',
            'ROTATIVO' => 'bg-purple',
            'HORARIO FIJO' => 'bg-primary',
            'HORARIO COMBINADO' => 'bg-warning text-dark',
            'CICLO ROTATIVO' => 'bg-purple'
        ];
        
        $key = strtoupper(trim($tipoCodigo ?? ''));
        return $colors[$key] ?? 'bg-secondary';
    }
}
