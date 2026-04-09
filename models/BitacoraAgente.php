<?php
require_once __DIR__ . '/Database.php';

class BitacoraAgente {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    public function registrar($data) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO bitacora_agente 
            (fecha, hora, empleado_id, tipo_incidencia, incidencia_id, incidencia_tipo, clasificacion, detalle, regla_aplicada, decision, justificada, validada_por_jefe, evidencia_json, recomendaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['fecha'] ?? date('Y-m-d'),
            $data['hora'] ?? date('H:i:s'),
            $data['empleado_id'] ?? null,
            $data['tipo_incidencia'],
            $data['incidencia_id'] ?? null,
            $data['incidencia_tipo'] ?? null,
            $data['clasificacion'],
            $data['detalle'] ?? null,
            $data['regla_aplicada'] ?? null,
            $data['decision'],
            $data['justificada'] ?? null,
            $data['validada_por_jefe'] ?? null,
            $data['evidencia_json'] ?? null,
            $data['recomendaciones'] ?? null
        ]);
    }

    public function getByEmpleado($empleado_id, $fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM bitacora_agente WHERE empleado_id = ?";
        $params = [$empleado_id];

        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND fecha BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY fecha DESC, hora DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByFechas($fecha_inicio, $fecha_fin, $clasificacion = null, $limit = 1000) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT b.*, e.nombre, e.apellido, e.area, e.puesto 
                FROM bitacora_agente b 
                LEFT JOIN empleados e ON b.empleado_id = e.id 
                WHERE b.fecha BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];

        if ($clasificacion) {
            $sql .= " AND b.clasificacion = ?";
            $params[] = $clasificacion;
        }

        $sql .= " ORDER BY b.fecha DESC, b.hora DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNoValidadas($fecha_inicio = null, $fecha_fin = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT b.*, e.nombre, e.apellido, e.area, e.puesto, e.jefe_directo_id
                FROM bitacora_agente b 
                LEFT JOIN empleados e ON b.empleado_id = e.id 
                WHERE (b.validada_por_jefe IS NULL OR b.validada_por_jefe = 0)
                AND b.tipo_incidencia IN ('retardo', 'falta', 'comision')";

        $params = [];
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND b.fecha BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }

        $sql .= " ORDER BY b.fecha DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstadisticas($fecha_inicio, $fecha_fin) {
        $pdo = $this->db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                clasificacion,
                COUNT(*) as total,
                SUM(CASE WHEN justificada = 1 THEN 1 ELSE 0 END) as justificados,
                SUM(CASE WHEN justificada = 0 THEN 1 ELSE 0 END) as no_justificados,
                SUM(CASE WHEN validada_por_jefe = 1 THEN 1 ELSE 0 END) as validados
            FROM bitacora_agente
            WHERE fecha BETWEEN ? AND ?
            GROUP BY clasificacion
        ");
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIncidencia($incidencia_id, $tipo) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM bitacora_agente 
            WHERE incidencia_id = ? AND incidencia_tipo = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$incidencia_id, $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarValidacion($id, $validada, $observaciones = null) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            UPDATE bitacora_agente 
            SET validada_por_jefe = ?, detalle = CONCAT(COALESCE(detalle, ''), '\nValidación: ', ?) 
            WHERE id = ?
        ");
        return $stmt->execute([$validada ? 1 : 0, $observaciones ?? ($validada ? 'Validada' : 'Rechazada'), $id]);
    }
}
