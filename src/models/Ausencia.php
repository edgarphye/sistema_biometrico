<?php
namespace App\Models;

use App\Models\Database;

class Ausencia {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO ausencias (empleado_id, fecha_inicio, fecha_fin, tipo)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['empleado_id'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['tipo']
        ]);
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
        $stmt = $this->db->getConnection()->prepare("UPDATE ausencias SET justificada = 1 WHERE id = ?");
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
}
