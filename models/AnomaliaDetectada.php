<?php
require_once __DIR__ . '/Database.php';

class AnomaliaDetectada {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    public function registrar($data) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO anomalias_detectadas 
            (empleado_id, tipo_anomalia, descripcion, fecha_deteccion, hora_deteccion, datos_json, evaluada, falsa_alarma)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['empleado_id'],
            $data['tipo_anomalia'],
            $data['descripcion'],
            $data['fecha_deteccion'] ?? date('Y-m-d'),
            $data['hora_deteccion'] ?? date('H:i:s'),
            $data['datos_json'] ?? null,
            $data['evaluada'] ?? 0,
            $data['falsa_alarma'] ?? 0
        ]);
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM anomalias_detectadas WHERE empleado_id = ?";
        $params = [$empleado_id];

        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND fecha_deteccion BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY fecha_deteccion DESC, hora_deteccion DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNoEvaluadas($fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT a.*, e.nombre, e.apellido, e.area 
                FROM anomalias_detectadas a 
                LEFT JOIN empleados e ON a.empleado_id = e.id 
                WHERE a.evaluada = 0";
        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND a.fecha_deteccion BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY a.fecha_deteccion DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstadisticas($fecha_inicio, $fecha_fin) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                tipo_anomalia,
                COUNT(*) as total,
                SUM(CASE WHEN evaluada = 1 THEN 1 ELSE 0 END) as evaluadas,
                SUM(CASE WHEN falsa_alarma = 1 THEN 1 ELSE 0 END) as falsas_alarmas
            FROM anomalias_detectadas
            WHERE fecha_deteccion BETWEEN ? AND ?
            GROUP BY tipo_anomalia
        ");
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarEvaluada($id, $falsa_alarma = false) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            UPDATE anomalias_detectadas 
            SET evaluada = 1, falsa_alarma = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$falsa_alarma ? 1 : 0, $id]);
    }

    public function eliminar($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM anomalias_detectadas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
