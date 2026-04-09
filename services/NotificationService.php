<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/Sancion.php';
require_once __DIR__ . '/../models/Empleado.php';
require_once __DIR__ . '/../controllers/EmailController.php';

class NotificationService {
    private $sancionModel;
    private $empleadoModel;
    private $emailController;

    public function __construct() {
        $this->sancionModel = new Sancion();
        $this->empleadoModel = new Empleado();
        $this->emailController = new EmailController();
    }

    /**
     * Verifica las sanciones acumuladas de un empleado y envía una notificación si se superan los umbrales.
     *
     * @param int $empleado_id
     * @return bool True si se envió una notificación, false en caso contrario.
     */
    public function verificarYNotificarSanciones($empleado_id) {
        $fecha_actual = date('Y-m-d');
        
        $sanciones_quincena = $this->sancionModel->getSancionesAcumuladasQuincena($empleado_id, $fecha_actual);
        $sanciones_mes = $this->sancionModel->getSancionesAcumuladasMes($empleado_id, $fecha_actual);

        $notificacion_enviada = false;
        $mensaje_notificacion = '';

        if ($sanciones_quincena > 2) {
            $mensaje_notificacion = "ha acumulado $sanciones_quincena 'notas malas' durante la quincena actual.";
        } elseif ($sanciones_mes > 1) {
            // Usamos elseif para no enviar dos correos por la misma sanción si ambas condiciones se cumplen a la vez
            $mensaje_notificacion = "ha acumulado $sanciones_mes 'notas malas' durante el mes actual.";
        }

        if (!empty($mensaje_notificacion)) {
            $empleado = $this->empleadoModel->getById($empleado_id);
            if ($empleado && !empty($empleado['email'])) {
                $this->enviarEmailNotificacionSancion($empleado, $mensaje_notificacion);
                $notificacion_enviada = true;
            }
        }
        
        return $notificacion_enviada;
    }

    /**
     * Envía el correo electrónico de notificación de sanción.
     *
     * @param array $empleado
     * @param string $mensaje
     */
    private function enviarEmailNotificacionSancion($empleado, $mensaje) {
        $asunto = 'Notificación de Acumulación de Sanciones';
        
        $cuerpo = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #d9534f; color: white; padding: 10px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px 0; }
                .footer { font-size: 0.9em; color: #777; text-align: center; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Alerta de Acumulación de Sanciones</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a {$empleado['nombre']} {$empleado['apellido']},</p>
                    <p>Le informamos que el sistema ha registrado que usted {$mensaje}</p>
                    <p>Este es un aviso para que pueda tomar las medidas correctivas necesarias y evitar futuras sanciones.</p>
                    <p>Si considera que esto es un error, por favor, póngase en contacto con el departamento de Recursos Humanos.</p>
                </div>
                <div class='footer'>
                    <p>Sistema Biométrico de Asistencia<br>Notificación generada automáticamente</p>
                </div>
            </div>
        </body>
        </html>";

        // Reutilizamos EmailController para el envío.
        // Es una forma rápida, aunque idealmente la configuración de PHPMailer estaría en un lugar más centralizado.
        try {
            // Accedemos a la propiedad privada $mailer vía Reflection ya que no podemos modificar EmailController.
            // Esto evita el error de visibilidad (PHP1416) al acceder a miembros privados.
            $reflection = new ReflectionClass($this->emailController);
            $mailerProperty = $reflection->getProperty('mailer');
            $mailerProperty->setAccessible(true);
            $mailer = $mailerProperty->getValue($this->emailController);

            $mailer->clearAddresses();
            $mailer->addAddress($empleado['email']);
            $mailer->Subject = $asunto;
            $mailer->Body = $cuerpo;

            $mailer->send();
        } catch (Exception $e) {
            $errorInfo = isset($mailer) ? $mailer->ErrorInfo : $e->getMessage();
            error_log("Error al enviar email de notificación de sanción a {$empleado['email']}: {$errorInfo}");
        }
    }
}
