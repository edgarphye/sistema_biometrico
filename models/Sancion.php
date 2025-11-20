<?php
require_once 'Database.php';

class Sancion {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("\n            INSERT INTO sanciones (empleado_id, tipo, fecha_inicio, dias, motivo, creado_por)\n            VALUES (?, ?, ?, ?, ?, ?)\n        ");
        return $stmt->execute([
            $data['empleado_id'],
            $data['tipo'],
            $data['fecha_inicio'],
            $data['dias'] ?? 0,
            $data['motivo'] ?? null,
            $data['creado_por'] ?? null
        ]);
    }

    public function getByEmpleadoYear($empleado_id, $anio) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM sanciones WHERE empleado_id = ? AND YEAR(fecha_inicio) = ? ORDER BY fecha_inicio DESC");
        $stmt->execute([$empleado_id, $anio]);
        return $stmt->fetchAll();
    }

    public function countSuspensionesYear($empleado_id, $anio) {
        $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) as total FROM sanciones WHERE empleado_id = ? AND tipo = 'suspension' AND YEAR(fecha_inicio) = ?");
        $stmt->execute([$empleado_id, $anio]);
        $r = $stmt->fetch();
        return intval($r['total'] ?? 0);
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("SELECT s.*, e.nombre, e.apellido FROM sanciones s JOIN empleados e ON s.empleado_id = e.id ORDER BY s.fecha_creacion DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT s.*, e.nombre, e.apellido FROM sanciones s JOIN empleados e ON s.empleado_id = e.id WHERE s.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $stmt = $this->db->getConnection()->prepare("UPDATE sanciones SET empleado_id = ?, tipo = ?, fecha_inicio = ?, dias = ?, motivo = ?, creado_por = ?, modified_by = ?, fecha_modificacion = NOW() WHERE id = ?");
        return $stmt->execute([
            $data['empleado_id'],
            $data['tipo'],
            $data['fecha_inicio'],
            $data['dias'] ?? 0,
            $data['motivo'] ?? null,
            $data['creado_por'] ?? null,
            $data['modified_by'] ?? ($data['creado_por'] ?? null),
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("DELETE FROM sanciones WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>