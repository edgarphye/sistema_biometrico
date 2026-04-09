<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php'; // Si usas Composer
require_once __DIR__ . '/BaseController.php';

class EmailController extends BaseController {
    private $mailer;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->mailer = new PHPMailer(true);

        // Configuración del servidor SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com'; // Cambiar según tu proveedor
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'tu-email@gmail.com'; // Configurar
        $this->mailer->Password = 'tu-contraseña-app'; // Configurar
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;

        $this->mailer->setFrom('tu-email@gmail.com', 'Sistema Biométrico');
        $this->mailer->isHTML(true);
    }

    public function enviarNotificacionRetardo($empleado, $retardo) {
        try {
            $this->mailer->addAddress($empleado['email'] ?? 'admin@sistema.com'); // Agregar campo email a empleados

            $this->mailer->Subject = 'Notificación de Retardo - Sistema Biométrico';
            $this->mailer->Body = $this->plantillaRetardo($empleado, $retardo);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'enviarNotificacionRetardo', 'empleado_id' => $empleado['id'] ?? null]);
            error_log("Error enviando email de retardo: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    public function enviarNotificacionComisionVencida($empleado, $comision) {
        try {
            $this->mailer->addAddress($empleado['email'] ?? 'admin@sistema.com');

            $this->mailer->Subject = 'Comisión Vencida - Sistema Biométrico';
            $this->mailer->Body = $this->plantillaComisionVencida($empleado, $comision);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'enviarNotificacionComisionVencida', 'empleado_id' => $empleado['id'] ?? null]);
            return false;
        }
    }

    public function enviarNotificacionAusencia($empleado, $ausencia) {
        try {
            $this->mailer->addAddress($empleado['email'] ?? 'admin@sistema.com');

            $this->mailer->Subject = 'Notificación de Ausencia - Sistema Biométrico';
            $this->mailer->Body = $this->plantillaAusencia($empleado, $ausencia);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'enviarNotificacionAusencia', 'empleado_id' => $empleado['id'] ?? null]);
            error_log("Error enviando email de ausencia: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    public function enviarReporteDiario($destinatarios, $reporte) {
        try {
            foreach ($destinatarios as $email) {
                $this->mailer->addAddress($email);
            }

            $this->mailer->Subject = 'Reporte Diario - Sistema Biométrico';
            $this->mailer->Body = $this->plantillaReporteDiario($reporte);

            $this->mailer->send();
            $this->mailer->clearAddresses();
            return true;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'enviarReporteDiario']);
            error_log("Error enviando reporte diario: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    private function plantillaRetardo($empleado, $retardo) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #dc3545; color: white; padding: 10px; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 10px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Notificación de Retardo</h2>
            </div>
            <div class='content'>
                <p>Estimado {$empleado['nombre']} {$empleado['apellido']},</p>
                <p>Se ha registrado un retardo en su asistencia:</p>
                <ul>
                    <li><strong>Fecha:</strong> {$retardo['fecha']}</li>
                    <li><strong>Minutos de retardo:</strong> {$retardo['minutos_retardo']}</li>
                    <li><strong>Tipo:</strong> " . ($retardo['tipo'] == 'menor' ? 'Retardo Menor' : 'Retardo Mayor') . "</li>
                </ul>
                <p>Por favor, tome las medidas necesarias para evitar futuros retardos.</p>
            </div>
            <div class='footer'>
                <p>Sistema Biométrico - Notificación automática</p>
            </div>
        </body>
        </html>
        ";
    }

    private function plantillaComisionVencida($empleado, $comision) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #ffc107; color: black; padding: 10px; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 10px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Comisión Vencida</h2>
            </div>
            <div class='content'>
                <p>Estimado {$empleado['nombre']} {$empleado['apellido']},</p>
                <p>Tiene una comisión pendiente de justificación:</p>
                <ul>
                    <li><strong>Descripción:</strong> {$comision['descripcion']}</li>
                    <li><strong>Monto:</strong> $" . number_format($comision['monto'], 2) . "</li>
                    <li><strong>Fecha de asignación:</strong> {$comision['fecha_asignacion']}</li>
                    <li><strong>Fecha de vencimiento:</strong> {$comision['fecha_vencimiento']}</li>
                </ul>
                <p>Por favor, justifique esta comisión antes de la fecha límite.</p>
            </div>
            <div class='footer'>
                <p>Sistema Biométrico - Notificación automática</p>
            </div>
        </body>
        </html>
        ";
    }

    private function plantillaAusencia($empleado, $ausencia) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #17a2b8; color: white; padding: 10px; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 10px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Notificación de Ausencia</h2>
            </div>
            <div class='content'>
                <p>Estimado {$empleado['nombre']} {$empleado['apellido']},</p>
                <p>Se ha registrado una ausencia:</p>
                <ul>
                    <li><strong>Tipo:</strong> {$ausencia['tipo']}</li>
                    <li><strong>Fecha inicio:</strong> {$ausencia['fecha_inicio']}</li>
                    <li><strong>Fecha fin:</strong> {$ausencia['fecha_fin']}</li>
                    <li><strong>Estado:</strong> " . ($ausencia['justificada'] ? 'Justificada' : 'Pendiente de justificación') . "</li>
                </ul>
            </div>
            <div class='footer'>
                <p>Sistema Biométrico - Notificación automática</p>
            </div>
        </body>
        </html>
        ";
    }

    private function plantillaReporteDiario($reporte) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background-color: #28a745; color: white; padding: 10px; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 10px; font-size: 12px; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Reporte Diario del Sistema Biométrico</h2>
            </div>
            <div class='content'>
                <h3>Resumen del día</h3>
                <ul>
                    <li><strong>Total de empleados:</strong> {$reporte['total_empleados']}</li>
                    <li><strong>Asistencias registradas:</strong> {$reporte['total_asistencias']}</li>
                    <li><strong>Retardos:</strong> {$reporte['total_retardos']}</li>
                    <li><strong>Ausencias:</strong> {$reporte['total_ausencias']}</li>
                </ul>

                <h3>Retardos del día</h3>
                <table>
                    <tr><th>Empleado</th><th>Minutos</th><th>Tipo</th></tr>
                    " . implode('', array_map(function($r) {
                        return "<tr><td>{$r['empleado']}</td><td>{$r['minutos']}</td><td>{$r['tipo']}</td></tr>";
                    }, $reporte['retardos_hoy'])) . "
                </table>
            </div>
            <div class='footer'>
                <p>Sistema Biométrico - Reporte automático</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Enviar notificación de licencia
     * @param array $notificacion Datos de la notificación
     * @return bool Éxito de envío
     */
    public function enviarNotificacionLicencia($notificacion) {
        try {
            $this->mailer->addAddress($notificacion['email'] ?? 'admin@sistema.com');
            $this->mailer->Subject = 'Solicitud de Licencia - Sistema Biométrico';
            $this->mailer->Body = $this->plantillaLicencia($notificacion);
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $this->logException($e, ['action' => 'enviarNotificacionLicencia']);
            error_log("Error enviando notificación de licencia: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Plantilla HTML para notificación de licencia
     * @param array $notificacion Datos de la notificación
     * @return string HTML
     */
    private function plantillaLicencia($notificacion) {
        $tipoLicencia = isset($notificacion['tipo']) ? $notificacion['tipo'] : 'desconocida';
        $tipoColor = ($tipoLicencia === 'enfermedad') ? '#28a745' : '#dc3545';
        $fechaInicio = isset($notificacion['fecha_inicio']) ? $notificacion['fecha_inicio'] : date('Y-m-d');
        $fechaFin = isset($notificacion['fecha_fin']) ? $notificacion['fecha_fin'] : date('Y-m-d', strtotime('+7 days'));
        
        ob_start();
        ?>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Solicitud de Licencia</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #9F2241, #235B4E); color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .content { padding: 30px; }
                .details { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .badge { display: inline-block; padding: 4px 8px; background: <?php echo $tipoColor; ?>; color: white; border-radius: 4px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Solicitud de Licencia</h1>
                    <p>Sistema Biométrico de Control de Asistencia</p>
                </div>
                
                <div class='content'>
                    <h2>Detalles de la Solicitud</h2>
                    
                    <div class='details'>
                        <p><strong>Empleado:</strong> <?php echo htmlspecialchars($notificacion['empleado_nombre'] . ' ' . $notificacion['empleado_apellido']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($notificacion['empleado_email']); ?></p>
                        <p><strong>RFC:</strong> <?php echo htmlspecialchars($notificacion['empleado_rfc']); ?></p>
                        <p><strong>Departamento:</strong> <?php echo htmlspecialchars($notificacion['departamento']); ?></p>
                        <p><strong>Puesto:</strong> <?php echo htmlspecialchars($notificacion['puesto']); ?></p></p>
                        
                        <hr style='margin: 20px 0; border: 1px solid #dee2e6;'>
                        
                        <p><span class='badge'><?php echo $tipoLicencia; ?></span> <strong>Tipo de Licencia:</strong> <?php echo htmlspecialchars($tipoLicencia); ?></p>
                        <p><strong>Fecha de Inicio:</strong> <?php echo $fechaInicio; ?></p>
                        <p><strong>Fecha de Fin:</strong> <?php echo $fechaFin; ?></p>
                        <p><strong>Motivo:</strong> <?php echo htmlspecialchars($notificacion['motivo'] ?? 'No especificado'); ?></p>
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($notificacion['descripcion'] ?? 'No hay descripción adicional'); ?></p>
                        
                        <hr style='margin: 20px 0; border: 1px solid #dee2e6;'>
                        
                        <p><strong>Solicitado el:</strong> <?php echo $notificacion['fecha_solicitud'] ?? date('Y-m-d H:i:s'); ?></p>
                        <p><strong>Estatus Actual:</strong> <span style='color: <?php echo $tipoColor; ?>; font-weight: bold;'>Pendiente de Aprobación</span></p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>Sistema Biométrico - Gestión de Licencias</strong></p>
                    <p>Esta es una notificación automática. Por favor, no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
?>
