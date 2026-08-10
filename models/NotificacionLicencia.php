<?php
require_once 'Database.php';

/**
 * Modelo para gestionar notificaciones automáticas de licencias médicas
 * Maneja el envío de recordatorios según plazos establecidos por HR
 */
class NotificacionLicencia {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Genera notificaciones para licencias médicas que requieren recordatorio
     * Reglas de notificación según política de Recursos Humanos:
     * - Justificaciones del día 01 al 15: notificar máximo día 20 del mes actual
     * - Justificaciones del día 16 al 31: notificar máximo día 5 del mes siguiente
     *
     * @return array Lista de notificaciones generadas con información de empleados
     */
    public function generarNotificaciones() {
        $notificaciones = [];

        // Obtener licencias médicas activas que necesitan notificación
        $licencias = $this->getLicenciasMedicasActivas();

        foreach ($licencias as $licencia) {
            $diasRestantes = $this->calcularDiasRestantes($licencia);

            // Solo notificar si faltan pocos días para el vencimiento
            if ($diasRestantes <= 3 && $diasRestantes > 0) {
                $notificacion = [
                    'empleado_id' => $licencia['empleado_id'],
                    'tipo' => 'recordatorio_licencia',
                    'mensaje' => "Recordatorio: Su licencia médica vence en {$diasRestantes} día(s). " .
                               "Fecha límite para justificar: " . date('d/m/Y', strtotime($licencia['fecha_limite_justificacion'])),
                    'fecha_envio' => date('Y-m-d H:i:s'),
                    'estado' => 'pendiente'
                ];

                // Crear notificación en BD
                $id = $this->crearNotificacion($notificacion);
                if ($id) {
                    $notificacion['id'] = $id;
                    $notificaciones[] = $notificacion;
                }
            }
        }

        return $notificaciones;
    }

    /**
     * Obtiene licencias médicas activas que requieren notificación
     *
     * @return array Lista de licencias médicas activas
     */
    public function getLicenciasMedicasActivas() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT
                a.*,
                e.nombre,
                e.apellido,
                e.email
            FROM ausencias a
            INNER JOIN empleados e ON a.empleado_id = e.id
            WHERE a.tipo = 'medica'
            AND a.fecha_fin >= CURDATE()
            AND a.fecha_limite_justificacion >= CURDATE()
            AND a.activo = 1
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Calcula días restantes hasta la fecha límite de justificación
     *
     * @param array $licencia Datos de la licencia
     * @return int Días restantes
     */
    public function calcularDiasRestantes($licencia) {
        $fechaLimite = strtotime($licencia['fecha_limite_justificacion']);
        $hoy = strtotime(date('Y-m-d'));

        // Calcular días hábiles restantes
        $diasRestantes = 0;
        $current = $hoy;

        while ($current <= $fechaLimite) {
            $diaSemana = date('N', $current);
            if ($diaSemana <= 5) { // Lunes a Viernes
                $diasRestantes++;
            }
            $current = strtotime('+1 day', $current);
        }

        return max(0, $diasRestantes - 1); // No contar el día actual
    }

    /**
     * Crea una nueva notificación en la base de datos
     *
     * @param array $datos Datos de la notificación
     * @return int|bool ID de la notificación creada o false si falla
     */
    public function crearNotificacion($datos) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO notificaciones_licencias
                (empleado_id, tipo, mensaje, fecha_envio, estado, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $datos['empleado_id'],
                $datos['tipo'],
                $datos['mensaje'],
                $datos['fecha_envio'],
                $datos['estado']
            ]);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca una notificación como enviada
     *
     * @param int $id ID de la notificación
     * @return bool True si se actualizó correctamente
     */
    public function marcarComoEnviada($id) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE notificaciones_licencias
            SET estado = 'enviada', updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Obtiene notificaciones pendientes de envío
     *
     * @return array Lista de notificaciones pendientes
     */
    public function getNotificacionesPendientes() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT
                n.*,
                e.nombre,
                e.apellido,
                e.email
            FROM notificaciones_licencias n
            INNER JOIN empleados e ON n.empleado_id = e.id
            WHERE n.estado = 'pendiente'
            ORDER BY n.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene historial de notificaciones de un empleado
     *
     * @param int $empleadoId ID del empleado
     * @return array Historial de notificaciones
     */
    public function getHistorialByEmpleado($empleadoId) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM notificaciones_licencias
            WHERE empleado_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$empleadoId]);
        return $stmt->fetchAll();
    }
}
?>
