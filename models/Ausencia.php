<?php
require_once __DIR__ . '/Database.php';

class Ausencia {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO ausencias (
                empleado_id, fecha_inicio, fecha_fin, tipo, motivo, tipo_ausencia,
                fecha_limite_justificacion, requiere_evidencia, activo, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([
            $data['empleado_id'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['tipo'],
            $data['motivo'] ?? null,
            $data['tipo_ausencia'] ?? null,
            $data['fecha_limite_justificacion'] ?? null,
            $data['requiere_evidencia'] ?? 0,
            $data['activo'] ?? 1
        ]);

        return $result ? $this->db->getConnection()->lastInsertId() : false;
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null) {
        $query = "SELECT * FROM ausencias";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($fecha_inicio && $fecha_fin) {
            $query .= ($params ? " AND" : " WHERE") . " ((fecha_inicio BETWEEN ? AND ?) OR (fecha_fin BETWEEN ? AND ?))";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY fecha_inicio DESC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function justificarAusencia($id) {
        $stmt = $this->db->getConnection()->prepare("UPDATE ausencias SET activo = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTotalAusencias($empleado_id, $mes = null, $anio = null) {
        $query = "SELECT COUNT(*) as total, tipo FROM ausencias";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($mes && $anio) {
            $query .= ($params ? " AND" : " WHERE") . " ((MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?) OR (MONTH(fecha_fin) = ? AND YEAR(fecha_fin) = ?))";
            $params[] = $mes;
            $params[] = $anio;
            $params[] = $mes;
            $params[] = $anio;
        }

        $query .= " GROUP BY tipo";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Verifica si hay una ausencia activa (no justificada) para un empleado en una fecha específica
     * @param int $empleado_id ID del empleado
     * @param string $fecha Fecha en formato Y-m-d
     * @return array|null Datos de la ausencia activa o null si no hay
     */
    public function getAusenciaActiva($empleado_id, $fecha) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM ausencias
            WHERE empleado_id = ?
            AND ? BETWEEN fecha_inicio AND fecha_fin
            AND (justificada = 0 OR justificada IS NULL)
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ");
        $stmt->execute([$empleado_id, $fecha]);
        return $stmt->fetch();
    }
}
?>
