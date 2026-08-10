<?php
namespace App\Models;

use App\Models\Database;

class Asistencia {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getConnection() {
        return $this->db->getConnection();
    }

    public function registrarEntrada($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO asistencia
            (empleado_id, tipo, dispositivo_id, tipo_biometria, datos_biometricos, calidad_verificacion, metadata_dispositivo, tiempo_procesamiento)
            VALUES (?, 'entrada', ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos ? json_encode($datos_biometricos) : null,
            $calidad_verificacion,
            $metadata_dispositivo ? json_encode($metadata_dispositivo) : null,
            $tiempo_procesamiento
        ]);
    }

    public function registrarSalida($empleado_id, $dispositivo_id, $tipo_biometria = null, $datos_biometricos = null, $calidad_verificacion = null, $metadata_dispositivo = null, $tiempo_procesamiento = null) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO asistencia
            (empleado_id, tipo, dispositivo_id, tipo_biometria, datos_biometricos, calidad_verificacion, metadata_dispositivo, tiempo_procesamiento)
            VALUES (?, 'salida', ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $empleado_id,
            $dispositivo_id,
            $tipo_biometria,
            $datos_biometricos ? json_encode($datos_biometricos) : null,
            $calidad_verificacion,
            $metadata_dispositivo ? json_encode($metadata_dispositivo) : null,
            $tiempo_procesamiento
        ]);
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
        ";
        $params = [];

        if ($empleado_id !== null) {
            $query .= " WHERE a.empleado_id = ?";
            $params[] = $empleado_id;
        }

        if ($fecha_inicio && $fecha_fin) {
            $query .= ($params ? " AND" : " WHERE") . " DATE(a.timestamp) BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY a.timestamp DESC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByDispositivo($dispositivo_id, $fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE a.dispositivo_id = ?
        ";
        $params = [$dispositivo_id];

        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND DATE(a.timestamp) BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $query .= " ORDER BY a.timestamp DESC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT a.*, e.nombre, e.apellido, e.rfc
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            ORDER BY a.timestamp DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene estadísticas de asistencia por dispositivo
     */
    public function getEstadisticasPorDispositivo($fecha_inicio = null, $fecha_fin = null) {
        $query = "
            SELECT
                dispositivo_id,
                COUNT(*) as total_registros,
                SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN tipo = 'salida' THEN 1 ELSE 0 END) as salidas,
                SUM(CASE WHEN tipo_biometria = 'huella' THEN 1 ELSE 0 END) as verificaciones_huella,
                SUM(CASE WHEN tipo_biometria = 'cara' THEN 1 ELSE 0 END) as verificaciones_cara,
                AVG(calidad_verificacion) as calidad_promedio,
                AVG(tiempo_procesamiento) as tiempo_promedio_procesamiento
            FROM asistencia
            WHERE 1=1
        ";
        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND DATE(timestamp) BETWEEN ? AND ?";
            $params = [$fecha_inicio, $fecha_fin];
        }

        $query .= " GROUP BY dispositivo_id ORDER BY dispositivo_id";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene registros de asistencia con filtros avanzados
     */
    public function getAsistenciaFiltrada($filtros = []) {
        $query = "
            SELECT a.*, e.nombre, e.apellido, e.rfc, e.area
            FROM asistencia a
            JOIN empleados e ON a.empleado_id = e.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['dispositivo_id'])) {
            $query .= " AND a.dispositivo_id = ?";
            $params[] = $filtros['dispositivo_id'];
        }

        if (!empty($filtros['tipo_biometria'])) {
            $query .= " AND a.tipo_biometria = ?";
            $params[] = $filtros['tipo_biometria'];
        }

        if (!empty($filtros['tipo_asistencia'])) {
            $query .= " AND a.tipo = ?";
            $params[] = $filtros['tipo_asistencia'];
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query .= " AND DATE(a.timestamp) BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['empleado_id'])) {
            $query .= " AND a.empleado_id = ?";
            $params[] = $filtros['empleado_id'];
        }

        $query .= " ORDER BY a.timestamp DESC";

        if (!empty($filtros['limit'])) {
            $query .= " LIMIT " . (int)$filtros['limit'];
        }

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene empleados con asistencia en un período específico
     */
    public function getEmpleadosConAsistencia($fecha_inicio, $fecha_fin, $area = null) {
        $query = "
            SELECT DISTINCT
                e.id,
                e.nombre,
                e.apellido,
                e.area,
                DATE(a.timestamp) as fecha,
                MIN(CASE WHEN a.tipo = 'entrada' THEN TIME(a.timestamp) END) as hora_entrada,
                MAX(CASE WHEN a.tipo = 'salida' THEN TIME(a.timestamp) END) as hora_salida
            FROM empleados e
            JOIN asistencia a ON e.id = a.empleado_id
            WHERE DATE(a.timestamp) BETWEEN ? AND ?
        ";
        $params = [$fecha_inicio, $fecha_fin];

        if ($area) {
            $query .= " AND e.area = ?";
            $params[] = $area;
        }

        $query .= " GROUP BY e.id, e.nombre, e.apellido, e.area, DATE(a.timestamp)
                   ORDER BY DATE(a.timestamp) DESC, e.nombre ASC";

        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
