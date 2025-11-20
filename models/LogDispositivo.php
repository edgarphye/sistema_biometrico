<?php
require_once 'Database.php';

/**
 * Modelo para logs de dispositivos biométricos
 * Maneja auditoría detallada de eventos biométricos
 */
class LogDispositivo {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Registra un evento en el log de dispositivos
     */
    public function logEvent($dispositivoId, $tipoEvento, $resultado, $mensaje = '', $metadata = [], $empleadoId = null, $tipoBiometria = null) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO logs_dispositivos
            (dispositivo_id, tipo_evento, empleado_id, tipo_biometria, resultado, mensaje, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $dispositivoId,
            $tipoEvento,
            $empleadoId,
            $tipoBiometria,
            $resultado,
            $mensaje,
            json_encode($metadata)
        ]);
    }

    /**
     * Obtiene logs por dispositivo
     */
    public function getByDispositivo($dispositivoId, $limit = 100, $offset = 0) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT ld.*, e.nombre, e.apellido
            FROM logs_dispositivos ld
            LEFT JOIN empleados e ON ld.empleado_id = e.id
            WHERE ld.dispositivo_id = ?
            ORDER BY ld.timestamp DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ");
        $stmt->execute([$dispositivoId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene logs por empleado
     */
    public function getByEmpleado($empleadoId, $limit = 50) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT ld.*, e.nombre, e.apellido
            FROM logs_dispositivos ld
            LEFT JOIN empleados e ON ld.empleado_id = e.id
            WHERE ld.empleado_id = ?
            ORDER BY ld.timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$empleadoId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene estadísticas de dispositivos
     */
    public function getEstadisticasDispositivos() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT
                dispositivo_id,
                COUNT(*) as total_eventos,
                SUM(CASE WHEN resultado = 'exitoso' THEN 1 ELSE 0 END) as exitos,
                SUM(CASE WHEN resultado = 'fallido' THEN 1 ELSE 0 END) as fallos,
                SUM(CASE WHEN resultado = 'error' THEN 1 ELSE 0 END) as errores,
                SUM(CASE WHEN tipo_evento = 'verificacion' THEN 1 ELSE 0 END) as verificaciones,
                MAX(timestamp) as ultimo_evento
            FROM logs_dispositivos
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY dispositivo_id
            ORDER BY dispositivo_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene logs recientes para dashboard
     */
    public function getLogsRecientes($limit = 20) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT ld.*, e.nombre, e.apellido
            FROM logs_dispositivos ld
            LEFT JOIN empleados e ON ld.empleado_id = e.id
            ORDER BY ld.timestamp DESC
            LIMIT " . (int)$limit . "
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Limpia logs antiguos (más de 90 días)
     */
    public function limpiarLogsAntiguos() {
        $stmt = $this->db->getConnection()->prepare("
            DELETE FROM logs_dispositivos
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        return $stmt->execute();
    }
}
?>
