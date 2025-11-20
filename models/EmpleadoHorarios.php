<?php
require_once 'Database.php';

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
        require_once 'HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->getHorariosEmpleado($empleado_id);
    }

    /**
     * Asignar horario a empleado
     */
    public function asignarHorario($empleado_id, $horario_id, $dia_semana) {
        require_once 'HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->asignarAEmpleado($empleado_id, $horario_id, $dia_semana);
    }

    /**
     * Remover horario de empleado
     */
    public function removerHorario($empleado_id, $dia_semana) {
        require_once 'HorarioLaboral.php';
        $horarioModel = new HorarioLaboral();
        return $horarioModel->removerDeEmpleado($empleado_id, $dia_semana);
    }

    /**
     * Obtener horario aplicable para un empleado en una fecha específica
     */
    public function getHorarioPorFecha($empleado_id, $fecha) {
        require_once 'HorarioLaboral.php';
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
