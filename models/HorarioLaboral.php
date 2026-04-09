<?php
require_once 'Database.php';
require_once __DIR__ . '/../helpers/RequestValidator.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class HorarioLaboral {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear un nuevo horario laboral en el catálogo
     */
    public function create($data) {
        // Validar datos de entrada
        $errors = $this->validateHorarioData($data);
        if (!empty($errors)) {
            throw new Exception("Datos de horario inválidos: " . implode(', ', $errors));
        }
        
        // Sanitizar datos
        $sanitized = RequestValidator::sanitizeAndValidate($data);
        
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO horarios_laborales (
                nombre, hora_entrada, hora_salida, tolerancia_minutos, descripcion, sede,
                color, inicio_marcaje_entrada, fin_marcaje_entrada, inicio_marcaje_salida, fin_marcaje_salida,
                debe_marcar_entrada, debe_marcar_salida, cuenta_dia_trabajo, cuenta_minutos
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            SecurityHelper::escape($sanitized['nombre']),
            $sanitized['hora_entrada'],
            $sanitized['hora_salida'],
            SecurityHelper::sanitizeInt($sanitized['tolerancia_minutos'] ?? 10, 0, 60),
            SecurityHelper::escape($sanitized['descripcion'] ?? null),
            SecurityHelper::escape($sanitized['sede'] ?? null),
            $sanitized['color'] ?? '#007bff',
            $sanitized['inicio_marcaje_entrada'] ?? null,
            $sanitized['fin_marcaje_entrada'] ?? null,
            $sanitized['inicio_marcaje_salida'] ?? null,
            $sanitized['fin_marcaje_salida'] ?? null,
            isset($sanitized['debe_marcar_entrada']) ? 1 : 0,
            isset($sanitized['debe_marcar_salida']) ? 1 : 0,
            $sanitized['cuenta_dia_trabajo'] ?? 1,
            $sanitized['cuenta_minutos'] ?? 0
        ]);
    }
    
    /**
     * Validar datos de horario
     */
    private function validateHorarioData($data) {
        $errors = [];
        
        if (empty($data['nombre']) || strlen(trim($data['nombre'])) < 2) {
            $errors['nombre'] = 'El nombre del horario es obligatorio';
        }
        
        if (empty($data['hora_entrada']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['hora_entrada'])) {
            $errors['hora_entrada'] = 'La hora de entrada no es válida (formato HH:MM)';
        }
        
        if (empty($data['hora_salida']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['hora_salida'])) {
            $errors['hora_salida'] = 'La hora de salida no es válida (formato HH:MM)';
        }
        
        // Validar que la salida sea después de la entrada
        if (!empty($data['hora_entrada']) && !empty($data['hora_salida'])) {
            $entrada = DateTime::createFromFormat('H:i', $data['hora_entrada']);
            $salida = DateTime::createFromFormat('H:i', $data['hora_salida']);
            
            if ($entrada && $salida && $salida <= $entrada) {
                $errors['hora_salida'] = 'La hora de salida debe ser posterior a la hora de entrada';
            }
        }
        
        return $errors;
    }

    /**
     * Obtener todos los horarios activos
     */
    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM horarios_laborales
            WHERE activo = 1
            ORDER BY nombre ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener horario por ID
     */
    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM horarios_laborales
            WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Desactivar horario (alias para delete)
     */
    public function deactivate($id) {
        return $this->delete($id);
    }

    /**
     * Asignar horario a empleado para un día específico
     */
    public function asignarHorarioEmpleado($empleado_id, $horario_id, $dia_semana) {
        // Primero verificar si ya existe una asignación para este empleado y día
        $stmt = $this->db->getConnection()->prepare("
            SELECT id FROM horarios_empleados
            WHERE empleado_id = ? AND dia_semana = ? AND activo = 1
        ");
        $stmt->execute([$empleado_id, $dia_semana]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Actualizar asignación existente
            $stmt = $this->db->getConnection()->prepare("
                UPDATE horarios_empleados SET horario_id = ? WHERE id = ?
            ");
            return $stmt->execute([$horario_id, $existing['id']]);
        } else {
            // Crear nueva asignación
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO horarios_empleados (empleado_id, horario_id, dia_semana)
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$empleado_id, $horario_id, $dia_semana]);
        }
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
            ORDER BY FIELD(he.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener historial de horarios (todos los registros)
     */
    public function getHistorialHorariosEmpleado($empleado_id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT he.*, hl.nombre as nombre_horario, hl.hora_entrada, hl.hora_salida, hl.tolerancia_minutos, hl.activo as horario_activo
            FROM horarios_empleados he
            LEFT JOIN horarios_laborales hl ON he.horario_id = hl.id
            WHERE he.empleado_id = ?
            ORDER BY he.fecha_asignacion DESC
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    // ... (otros métodos hasta update) ...

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
                descripcion = ?,
                sede = ?,
                color = ?,
                inicio_marcaje_entrada = ?,
                fin_marcaje_entrada = ?,
                inicio_marcaje_salida = ?,
                fin_marcaje_salida = ?,
                debe_marcar_entrada = ?,
                debe_marcar_salida = ?,
                cuenta_dia_trabajo = ?,
                cuenta_minutos = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['hora_entrada'],
            $data['hora_salida'],
            $data['tolerancia_minutos'] ?? 10,
            $data['descripcion'] ?? null,
            $data['sede'] ?? null,
            $data['color'] ?? '#007bff',
            $data['inicio_marcaje_entrada'] ?? null,
            $data['fin_marcaje_entrada'] ?? null,
            $data['inicio_marcaje_salida'] ?? null,
            $data['fin_marcaje_salida'] ?? null,
            isset($data['debe_marcar_entrada']) ? 1 : 0,
            isset($data['debe_marcar_salida']) ? 1 : 0,
            $data['cuenta_dia_trabajo'] ?? 1,
            $data['cuenta_minutos'] ?? 0,
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
    
    /**
     * Obtener horario aplicable para un empleado en una fecha específica
     * @param int $empleado_id ID del empleado
     * @param string $fecha Fecha en formato Y-m-d
     * @param int|null $sede ID de la sede (opcional)
     * @return array|null Horario aplicable o null si no hay
     */
    public function getHorarioPorFecha($empleado_id, $fecha) {
        $conn = $this->db->getConnection();
        
        // 1. Buscar la asignación activa para el empleado en la fecha dada en `empleado_horarios`
        $stmt = $conn->prepare("
            SELECT eh.horario_id, eh.ciclo_id, eh.fecha_inicio, c.nombre as ciclo_nombre
            FROM empleado_horarios eh
            LEFT JOIN ciclos c ON eh.ciclo_id = c.id
            WHERE eh.empleado_id = ?
            AND ? >= eh.fecha_inicio AND ? <= IFNULL(eh.fecha_fin, '9999-12-31')
            ORDER BY eh.fecha_inicio DESC
            LIMIT 1
        ");
        $stmt->execute([$empleado_id, $fecha, $fecha]);
        $asignacion = $stmt->fetch();

        if ($asignacion) {
            // Caso 1: Asignación de ciclo
            if (!empty($asignacion['ciclo_id'])) {
                $ciclo_id = $asignacion['ciclo_id'];
                // Determinar dia_semana (0=Lunes, ..., 6=Domingo)
                $timestamp = strtotime($fecha);
                $diaSemanaDb = (date('w', $timestamp) == 0) ? 6 : date('w', $timestamp) - 1;

                // Primero buscar por horario_id en bloques_ciclo (enlace a horarios_laborales)
                $stmtBloque = $conn->prepare("
                    SELECT hl.*
                    FROM bloques_ciclo bc
                    JOIN horarios_laborales hl ON bc.horario_id = hl.id
                    WHERE bc.ciclo_id = ? AND bc.dia_semana = ? AND bc.activo = 1 AND hl.activo = 1
                    LIMIT 1
                ");
                $stmtBloque->execute([$ciclo_id, $diaSemanaDb]);
                $horario = $stmtBloque->fetch();
                
                // Si no encuentra por horario_id, usar hora_inicio/hora_fin directos de bloques_ciclo
                if (!$horario) {
                    $stmtBloqueDirecto = $conn->prepare("
                        SELECT bc.hora_inicio, bc.hora_fin, bc.ciclo_id, c.nombre as ciclo_nombre
                        FROM bloques_ciclo bc
                        LEFT JOIN ciclos c ON bc.ciclo_id = c.id
                        WHERE bc.ciclo_id = ? AND bc.dia_semana = ? AND bc.activo = 1
                        LIMIT 1
                    ");
                    $stmtBloqueDirecto->execute([$ciclo_id, $diaSemanaDb]);
                    $bloque = $stmtBloqueDirecto->fetch();
                    
                    if ($bloque) {
                        return [
                            'id' => null,
                            'nombre' => 'Horario Ciclo: ' . ($bloque['ciclo_nombre'] ?? $ciclo_id),
                            'hora_entrada' => $bloque['hora_inicio'],
                            'hora_salida' => $bloque['hora_fin'],
                            'tolerancia_minutos' => 10,
                            'ciclo_id' => $ciclo_id,
                            'ciclo_nombre' => $bloque['ciclo_nombre'] ?? null,
                            'dia_semana' => $diaSemanaDb,
                            'fecha_inicio_asignacion' => $asignacion['fecha_inicio'],
                            'es_horario_ciclo' => true
                        ];
                    }
                }
                
                if ($horario) {
                    $horario['ciclo_id'] = $ciclo_id;
                    $horario['ciclo_nombre'] = $asignacion['ciclo_nombre'] ?? null;
                    $horario['dia_semana'] = $diaSemanaDb;
                    $horario['fecha_inicio_asignacion'] = $asignacion['fecha_inicio'];
                    $horario['es_horario_ciclo'] = true;
                    return $horario;
                }
            }
            // Caso 2: Asignación de horario fijo
            elseif (!empty($asignacion['horario_id'])) {
                $stmtHorario = $conn->prepare(
                    "SELECT * FROM horarios_laborales WHERE id = ? AND activo = 1"
                );
                $stmtHorario->execute([$asignacion['horario_id']]);
                $horario = $stmtHorario->fetch();
                if ($horario) {
                    $horario['fecha_inicio_asignacion'] = $asignacion['fecha_inicio'];
                    $horario['es_horario_ciclo'] = false;
                    return $horario;
                }
            }
        }

        // Fallback a horario por defecto si no se encuentra nada
        $stmtDefault = $conn->prepare(
            "SELECT * FROM horarios_laborales WHERE activo = 1 AND nombre LIKE '%Default%' LIMIT 1"
        );
        $stmtDefault->execute();
        $default = $stmtDefault->fetch();
        if ($default) {
            $default['es_horario_ciclo'] = false;
            $default['es_fallback'] = true;
        }
        return $default;
    }

    /**
     * Clasifica el retardo según las reglas de negocio estrictas:
     * - 0 a 10 min: Tolerancia (no se registra)
     * - 11 a 20 min: Retardo Menor
     * - 21 a 30 min: Retardo Mayor
     * - 31+ min: Falta
     * 
     * @param int $minutos Minutos de diferencia contra el horario oficial
     * @return array ['tipo' => string, 'es_retardo' => bool]
     */
    public function clasificarRetardo($minutos) {
        $minutos = (int)$minutos;
        
        // 0-10 min: Tolerancia (no se registra)
        if ($minutos >= 0 && $minutos <= 10) {
            return ['tipo' => 'tolerancia', 'es_retardo' => false];
        }
        
        // 11-20 min: Retardo Menor
        if ($minutos >= 11 && $minutos <= 20) {
            return ['tipo' => 'retardo_menor', 'es_retardo' => true];
        }
        
        // 21-30 min: Retardo Mayor
        if ($minutos >= 21 && $minutos <= 30) {
            return ['tipo' => 'retardo_mayor', 'es_retardo' => true];
        }
        
        // 31+ min: Falta
        return ['tipo' => 'falta', 'es_retardo' => true];
    }
}
?>
