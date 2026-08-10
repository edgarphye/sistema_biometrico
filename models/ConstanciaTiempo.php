<?php
require_once __DIR__ . '/Database.php';

class ConstanciaTiempo {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO constancias_tiempo (
                empleado_id, tipo_justificacion_id, fecha_inicio, fecha_fin, dias_solicitados,
                tipo_constancia, folio_constancia, motivo, institucion_emisora, estatus, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $data['empleado_id'],
            $data['tipo_justificacion_id'] ?? null,
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['dias_solicitados'],
            $data['tipo_constancia'],
            $data['folio_constancia'] ?? '',
            $data['motivo'] ?? '',
            $data['institucion_emisora'] ?? '',
            $data['estatus'] ?? 'pendiente'
        ]);

        return $result ? $pdo->lastInsertId() : false;
    }

    public function getByEmpleado($empleado_id, $limit = 20) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM constancias_tiempo 
            WHERE empleado_id = ? 
            ORDER BY created_at DESC 
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM constancias_tiempo WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateEstatus($id, $estatus) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE constancias_tiempo SET estatus = ?, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$estatus, $id]);
    }

    public function getAll($estatus = null) {
        $query = "SELECT * FROM constancias_tiempo";
        $params = [];
        
        if ($estatus) {
            $query .= " WHERE estatus = ?";
            $params[] = $estatus;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>