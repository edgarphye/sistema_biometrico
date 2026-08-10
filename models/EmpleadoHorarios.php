<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Empleado.php';

/**
 * Modelo EmpleadoHorarios - Métodos específicos para gestión de horarios por empleado
 */
class EmpleadoHorarios {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Obtener horarios asignados a un empleado
     */
    public function getHorarios($empleado_id) {
        require_once __DIR__ . '/HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->getHorariosEmpleado($empleado_id);
    }

    /**
     * Asignar horario a empleado
     */
    public function asignarHorario($empleado_id, $horario_id, $dia_semana) {
        require_once __DIR__ . '/HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->asignarHorarioEmpleado($empleado_id, $horario_id, $dia_semana);
    }

    /**
     * Remover horario de empleado
     */
    public function removerHorario($empleado_id, $dia_semana) {
        require_once __DIR__ . '/HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->removerDeEmpleado($empleado_id, $dia_semana);
    }

    /**
     * Asignar un ciclo rotativo a un empleado
     * Si el empleado ya tiene un ciclo activo (sin fecha_fin), lo cierra automáticamente
     */
    public function asignarCicloEmpleado($empleado_id, $ciclo_id, $fecha_inicio, $fecha_fin = null) {
        // Cerrar SOLO el ciclo activo más reciente del empleado (que tenga fecha_fin NULL)
        $stmt = $this->db->getConnection()->prepare("
            UPDATE empleado_horarios 
            SET fecha_fin = DATE_SUB(?, INTERVAL 1 DAY) 
            WHERE empleado_id = ? 
              AND ciclo_id IS NOT NULL 
              AND fecha_fin IS NULL
            ORDER BY fecha_inicio DESC 
            LIMIT 1
        ");
        $stmt->execute([$fecha_inicio, $empleado_id]);
        $ciclosCerrados = $stmt->rowCount();
        
        error_log("Ciclos cerrados al asignar nuevo ciclo: $ciclosCerrados para empleado $empleado_id");

        // Crear nueva asignación en la tabla de historial (con fecha_fin NULL = abierta)
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO empleado_horarios (empleado_id, ciclo_id, fecha_inicio, fecha_fin)
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$empleado_id, $ciclo_id, $fecha_inicio, $fecha_fin]);
        
        // Calcular y actualizar los campos derivados
        if ($result) {
            $asignacionId = $this->db->getConnection()->lastInsertId();
            $this->actualizarCamposCalculados($asignacionId);
        }
        
        return $result;
    }

    /**
     * Actualiza los campos calculados para una asignación
     */
    private function actualizarCamposCalculados($asignacionId) {
        require_once __DIR__ . '/HorarioClassifier.php';
        $classifier = new HorarioClassifier();
        return $classifier->actualizarCamposCalculados($asignacionId);
    }

    /**
     * Actualizar una asignación existente
     */
    public function updateAsignacion($id, $tipo, $item_id, $fecha_inicio, $fecha_fin) {
        $ciclo_id = ($tipo === 'ciclo') ? $item_id : null;
        $horario_id = ($tipo === 'horario') ? $item_id : null;

        $stmt = $this->db->getConnection()->prepare("
            UPDATE empleado_horarios
            SET ciclo_id = ?, horario_id = ?, fecha_inicio = ?, fecha_fin = ?
            WHERE id = ?
        ");
        return $stmt->execute([$ciclo_id, $horario_id, $fecha_inicio, $fecha_fin, $id]);
    }

    public function deleteAsignacion($id) {
        $stmt = $this->db->getConnection()->prepare("DELETE FROM empleado_horarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Obtener horario aplicable para un empleado en una fecha específica
     */
    public function getHorarioPorFecha($empleado_id, $fecha) {
        require_once __DIR__ . '/HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->getHorarioPorFecha($empleado_id, $fecha);
    }

    /**
     * Obtener empleados con sus horarios para una fecha específica
     */
    public function getEmpleadosConHorarios($fecha = null) {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }

        $dia_semana = strtolower(date('l', strtotime($fecha)));
        $dias = [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => 'domingo'
        ];
        $dia_semana_es = $dias[$dia_semana] ?? 'lunes';

        $stmt = $this->db->getConnection()->prepare("
            SELECT e.*, hl.nombre as horario_nombre, hl.hora_entrada, hl.hora_salida, hl.tolerancia_minutos
            FROM empleados e
            LEFT JOIN horarios_empleados he ON e.id = he.empleado_id AND he.dia_semana = ? AND he.activo = 1
            LEFT JOIN horarios_laborales hl ON he.horario_id = hl.id AND hl.activo = 1
            WHERE e.activo = 1
            ORDER BY e.nombre, e.apellido
        ");
        $stmt->execute([$dia_semana_es]);
        return $stmt->fetchAll();
    }
}
?>
