<?php
require_once 'Database.php';

class HorarioLaboral {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Crear un nuevo horario laboral en el catálogo
     */
    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO horarios_laborales (nombre, hora_entrada, hora_salida, tolerancia_minutos, descripcion)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['hora_entrada'],
            $data['hora_salida'],
            $data['tolerancia_minutos'] ?? 10,
            $data['descripcion'] ?? null
        ]);
    }

    /**
     * Obtener todos los horarios activos
     */
    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM horarios_laborales WHERE activo = 1 ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener horario por ID
     */
    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM horarios_laborales WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Asignar horario a empleado para un día específico
     */
    public function asignarAEmpleado($empleado_id, $horario_id, $dia_semana) {
        // Verificar que no exista ya un horario para ese día
        $stmt = $this->db->getConnection()->prepare("
            SELECT id FROM horarios_empleados
            WHERE empleado_id = ? AND dia_semana = ? AND activo = 1
        ");
        $stmt->execute([$empleado_id, $dia_semana]);
        $existe = $stmt->fetch();

        if ($existe) {
            // Ya existe, no hacer nada
            return true;
        }

        // Asignar el nuevo
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO horarios_empleados (empleado_id, horario_id, dia_semana)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$empleado_id, $horario_id, $dia_semana]);
    }

    /**
     * Obtener horarios asignados a un empleado
     */
    public function getHorariosEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT he.*, hl.nombre, hl.hora_entrada, hl.hora_salida, hl.tolerancia_minutos
            FROM horarios_empleados he
            JOIN horarios_laborales hl ON he.horario_id = hl.id
            WHERE he.empleado_id = ? AND he.activo = 1 AND hl.activo = 1
            ORDER BY FIELD(he.dia_semana, 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo')
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener horario aplicable para un empleado en una fecha específica
     */
    public function getHorarioPorFecha($empleado_id, $fecha) {
        $dia_semana = strtolower(date('l', strtotime($fecha)));
        // Traducir al español
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
            SELECT hl.*, he.dia_semana
            FROM horarios_empleados he
            JOIN horarios_laborales hl ON he.horario_id = hl.id
            WHERE he.empleado_id = ? AND he.dia_semana = ? AND he.activo = 1 AND hl.activo = 1
        ");
        $stmt->execute([$empleado_id, $dia_semana_es]);
        return $stmt->fetch();
    }

    /**
     * Actualizar horario
     */
    public function update($id, $data) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE horarios_laborales SET
                nombre = ?,
                hora_entrada = ?,
                hora_salida = ?,
                tolerancia_minutos = ?,
                descripcion = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['hora_entrada'],
            $data['hora_salida'],
            $data['tolerancia_minutos'] ?? 10,
            $data['descripcion'] ?? null,
            $id
        ]);
    }

    /**
     * Desactivar horario
     */
    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE horarios_laborales SET activo = 0 WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Remover asignación de horario a empleado
     */
    public function removerDeEmpleado($empleado_id, $dia_semana) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE horarios_empleados SET activo = 0
            WHERE empleado_id = ? AND dia_semana = ?
        ");
        return $stmt->execute([$empleado_id, $dia_semana]);
    }
}
?>
