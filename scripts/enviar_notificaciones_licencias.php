<?php
/**
 * Script para enviar notificaciones automáticas de licencias médicas
 *
 * Reglas de notificación:
 * - Justificaciones del día 01 al 15: notificar máximo día 20
 * - Justificaciones del día 16 al 31: notificar máximo día 5 del mes siguiente
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/NotificacionLicencia.php';
require_once __DIR__ . '/../controllers/EmailController.php';

class NotificacionesLicenciasScript {
    private $notificacionModel;
    private $emailController;

    public function __construct() {
        $this->notificacionModel = new NotificacionLicencia();
        $this->emailController = new EmailController();
    }

    /**
     * Ejecutar el script de notificaciones
     */
    public function ejecutar() {
        echo "=== Script de Notificaciones de Licencias Médicas ===\n";
        echo "Fecha de ejecución: " . date('Y-m-d H:i:s') . "\n\n";

        try {
            // Generar notificaciones
            $notificacionesGeneradas = $this->notificacionModel->generarNotificaciones();

            if (empty($notificacionesGeneradas)) {
                echo "No hay notificaciones pendientes para enviar.\n";
                return;
            }

            echo "Notificaciones generadas: " . count($notificacionesGeneradas) . "\n";

            // Enviar notificaciones por email
            $enviadas = 0;
            $errores = 0;

            foreach ($notificacionesGeneradas as $notificacion) {
                try {
                    $this->emailController->enviarNotificacionLicencia($notificacion);
                    $enviadas++;
                    echo "✓ Notificación enviada para empleado ID: {$notificacion['empleado_id']}\n";
                } catch (Exception $e) {
                    $errores++;
                    echo "✗ Error enviando notificación para empleado ID: {$notificacion['empleado_id']} - {$e->getMessage()}\n";
                }
            }

            echo "\n=== Resumen ===\n";
            echo "Notificaciones enviadas: $enviadas\n";
            echo "Errores: $errores\n";
            echo "Total procesadas: " . ($enviadas + $errores) . "\n";

        } catch (Exception $e) {
            echo "Error ejecutando el script: " . $e->getMessage() . "\n";
        }
    }
}

// Ejecutar el script
$script = new NotificacionesLicenciasScript();
$script->ejecutar();
?>
