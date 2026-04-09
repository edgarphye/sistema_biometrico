<?php
require_once __DIR__ . '/Database.php';

class Ciclo {

    private PDO $conn;
    
    // Mapeo de días para consistencia con la base de datos (Lunes, Martes...)
    private array $diasMap = [
        0 => 'Lunes',
        1 => 'Martes',
        2 => 'Miércoles',
        3 => 'Jueves',
        4 => 'Viernes',
        5 => 'Sábado',
        6 => 'Domingo'
    ];

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    /* =====================================================
       CICLOS
       ===================================================== */

    public function getAll(): array {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM ciclos
            ORDER BY nombre
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM ciclos
            WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $data): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO ciclos
            (nombre, fecha_inicio, num_ciclo, unidad_ciclo, activo, fecha_creacion)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");

        return $stmt->execute([
            $data['nombre'],
            $data['fecha_inicio'],
            $data['num_ciclo'],
            $data['unidad_ciclo']
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->conn->prepare("
            UPDATE ciclos
            SET nombre = ?, fecha_inicio = ?, num_ciclo = ?, unidad_ciclo = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['nombre'],
            $data['fecha_inicio'],
            $data['num_ciclo'],
            $data['unidad_ciclo'],
            $id
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->conn->prepare("
            UPDATE ciclos
            SET activo = 0
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /* =====================================================
       BLOQUES DE CICLO
       ===================================================== */

    public function getBloques(int $cicloId): array {
        $stmt = $this->conn->prepare("
            SELECT
                bc.id,
                bc.ciclo_id,
                bc.dia_semana,
                bc.hora_inicio,
                bc.hora_fin,
                bc.horario_id,
                h.nombre AS nombre_horario,
                h.color
            FROM bloques_ciclo bc
            INNER JOIN horarios_laborales h ON h.id = bc.horario_id
            WHERE bc.ciclo_id = ?
              AND bc.activo = 1
            ORDER BY bc.dia_semana, bc.hora_inicio
        ");
        $stmt->execute([$cicloId]);
        $bloques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convertir nombre de día (string) a índice (0-6) para el frontend si es necesario
        foreach ($bloques as &$bloque) {
            if (!is_numeric($bloque['dia_semana'])) {
                $idx = array_search($bloque['dia_semana'], $this->diasMap);
                if ($idx !== false) {
                    $bloque['dia_semana'] = $idx;
                }
            }
        }
        return $bloques;
    }

    /**
     * Verifica si existe solapamiento real de horas
     */
    public function existeSolapamiento(
        int $cicloId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin
    ): bool {

        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM bloques_ciclo
            WHERE ciclo_id = ?
              AND dia_semana = ?
              AND activo = 1
              AND (? < hora_fin AND ? > hora_inicio)
        ");

        $stmt->execute([
            $cicloId,
            $diaSemana,
            $horaInicio,
            $horaFin
        ]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Asigna UN horario del catálogo a VARIOS días (L–V)
     */
    public function addBloquesDesdeHorario(
        int $cicloId,
        int $horarioId,
        array $dias,
        bool $limpiarDia = false
    ): bool {

        $stmt = $this->conn->prepare("
            SELECT id, hora_entrada, hora_salida
            FROM horarios_laborales
            WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$horarioId]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$horario) {
            // No se puede proceder si el horario base no existe
            return false;
        }

        $this->conn->beginTransaction();

        try {
            foreach ($dias as $dia) {

                // Validar Lunes(0) a Domingo(6)
                if ($dia < 0 || $dia > 6) {
                    // Si un día es inválido, se omite. Podría lanzarse una excepción si se prefiere.
                    continue;
                }

                // Al asignar un horario a un día, se limpia cualquier horario previo (superposición a nivel de día).
                // Esto asegura que la nueva asignación reemplace completamente la anterior para el día seleccionado.
                $delete = $this->conn->prepare("
                    UPDATE bloques_ciclo
                    SET activo = 0
                    WHERE ciclo_id = ?
                      AND dia_semana = ?
                      AND activo = 1
                ");
                $delete->execute([$cicloId, $dia]);

                $insert = $this->conn->prepare("
                    INSERT INTO bloques_ciclo
                    (ciclo_id, dia_semana, hora_inicio, hora_fin, horario_id, activo, fecha_creacion)
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");

                $insert->execute([
                    $cicloId,
                    $dia,
                    $horario['hora_entrada'],
                    $horario['hora_salida'],
                    $horarioId
                ]);
            }

            $this->conn->commit();
            
            // Recalcular campos en empleado_horarios para este ciclo
            $this->actualizarCamposEmpleadoHorarios($cicloId);
            
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza los campos calculados en empleado_horarios después de modificar bloques
     */
    private function actualizarCamposEmpleadoHorarios(int $cicloId): bool {
        // Obtener empleados con este ciclo asignado
        $stmt = $this->conn->prepare("
            SELECT id FROM empleado_horarios 
            WHERE ciclo_id = ? AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
        ");
        $stmt->execute([$cicloId]);
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($asignaciones)) {
            return true;
        }
        
        // Calcular nuevos valores desde bloques_ciclo
        $stmt = $this->conn->prepare("
            SELECT 
                MIN(hora_inicio) as hora_entrada,
                MAX(hora_fin) as hora_salida,
                SUM(TIMESTAMPDIFF(SECOND, hora_inicio, hora_fin) / 3600) as total_horas,
                COUNT(DISTINCT CONCAT(hora_inicio, hora_fin)) as rangos_distintos
            FROM bloques_ciclo
            WHERE ciclo_id = ? AND activo = 1
        ");
        $stmt->execute([$cicloId]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$datos || !$datos['hora_entrada']) {
            return false;
        }
        
        // Determinar tipo de horario
        $tipo = ($datos['rangos_distintos'] == 1) ? 'FIJO' : 'COMBINADO';
        
        // Obtener detalle JSON
        $stmtDetalle = $this->conn->prepare("
            SELECT dia_semana as dia, hora_inicio, hora_fin, semana_numero as semana
            FROM bloques_ciclo
            WHERE ciclo_id = ? AND activo = 1
            ORDER BY dia_semana
        ");
        $stmtDetalle->execute([$cicloId]);
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
        
        // Actualizar cada asignación de empleado
        $stmtUpdate = $this->conn->prepare("
            UPDATE empleado_horarios 
            SET hora_entrada = ?,
                hora_salida = ?,
                total_horas_semanales = ?,
                tipo_horario = ?,
                detalle_json = ?
            WHERE ciclo_id = ? 
            AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
        ");
        
        return $stmtUpdate->execute([
            $datos['hora_entrada'],
            $datos['hora_salida'],
            $datos['total_horas'],
            $tipo,
            json_encode($detalle),
            $cicloId
        ]);
    }

    public function deleteBloque(int $bloqueId): bool {
        // Obtener el ciclo_id antes de eliminar
        $stmtGet = $this->conn->prepare("SELECT ciclo_id FROM bloques_ciclo WHERE id = ?");
        $stmtGet->execute([$bloqueId]);
        $bloque = $stmtGet->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $this->conn->prepare("
            UPDATE bloques_ciclo
            SET activo = 0
            WHERE id = ?
        ");
        $result = $stmt->execute([$bloqueId]);
        
        // Recalcular campos si se encontró el bloque
        if ($result && $bloque && $bloque['ciclo_id']) {
            $this->actualizarCamposEmpleadoHorarios($bloque['ciclo_id']);
        }
        
        return $result;
    }

    public function deleteAllBloques(int $cicloId): bool {
        $stmt = $this->conn->prepare("
            UPDATE bloques_ciclo
            SET activo = 0
            WHERE ciclo_id = ?
        ");
        $result = $stmt->execute([$cicloId]);
        
        // Recalcular campos
        if ($result) {
            $this->actualizarCamposEmpleadoHorarios($cicloId);
        }
        
        return $result;
    }

    public function getAllHorarios(): array {
        $stmt = $this->conn->prepare("
            SELECT * FROM horarios_laborales 
            WHERE activo = 1 
            ORDER BY nombre
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
