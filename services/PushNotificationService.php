<?php
require_once __DIR__ . '/Database.php';

class PushNotificationService {
    private static $instance = null;
    private $pdo;
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    
    private function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        // Configurar server key desde variable de entorno o config
        $this->serverKey = getenv('FCM_SERVER_KEY') ?: 'YOUR_FCM_SERVER_KEY';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Registrar token de dispositivo
     */
    public function registrarToken($empleadoId, $token, $tipoDispositivo = 'android') {
        try {
            // Verificar si el token ya existe
            $stmt = $this->pdo->prepare("SELECT id FROM device_tokens WHERE token = ?");
            $stmt->execute([$token]);
            
            if ($stmt->fetch()) {
                // Actualizar último login
                $stmt = $this->pdo->prepare("UPDATE device_tokens SET ultimo_login = NOW() WHERE token = ?");
                $stmt->execute([$token]);
            } else {
                // Insertar nuevo token
                $stmt = $this->pdo->prepare("
                    INSERT INTO device_tokens (empleado_id, token, tipo_dispositivo, ultimo_login, created_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$empleadoId, $token, $tipoDispositivo]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error al registrar token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar token de dispositivo
     */
    public function eliminarToken($token) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM device_tokens WHERE token = ?");
            $stmt->execute([$token]);
            return true;
        } catch (Exception $e) {
            error_log("Error al eliminar token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener tokens de un empleado
     */
    public function obtenerTokensEmpleado($empleadoId) {
        $stmt = $this->pdo->prepare("
            SELECT token, tipo_dispositivo 
            FROM device_tokens 
            WHERE empleado_id = ?
        ");
        $stmt->execute([$empleadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Enviar notificación push a un empleado
     */
    public function enviarNotificacion($empleadoId, $titulo, $mensaje, $data = []) {
        $tokens = $this->obtenerTokensEmpleado($empleadoId);
        
        if (empty($tokens)) {
            error_log("No hay tokens registrados para empleado $empleadoId");
            return false;
        }
        
        $deviceTokens = array_column($tokens, 'token');
        
        return $this->enviarNotificacionMultiples($deviceTokens, $titulo, $mensaje, $data);
    }
    
    /**
     * Enviar notificación a múltiples tokens
     */
    public function enviarNotificacionMultiples($tokens, $titulo, $mensaje, $data = []) {
        if (empty($tokens)) {
            return false;
        }
        
        // Preparar payload para FCM
        $notification = [
            'title' => $titulo,
            'body' => $mensaje,
            'icon' => 'ic_notification',
            'sound' => 'default'
        ];
        
        // Agregar datos adicionales
        $payload = [
            'notification' => $notification,
            'data' => array_merge($data, [
                'click_action' => 'OPEN_APP',
                'timestamp' => date('c')
            ]),
            'registration_ids' => $tokens,
            'priority' => 'high',
            'ttl' => 3600
        ];
        
        // Enviar a FCM
        return $this->enviarFCM($payload);
    }
    
    /**
     * Enviar a Firebase Cloud Messaging
     */
    private function enviarFCM($payload) {
        if ($this->serverKey === 'YOUR_FCM_SERVER_KEY') {
            // Modo desarrollo - solo guardar en log
            error_log("FCM (desarrollo): " . json_encode($payload));
            return true;
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->fcmUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $this->serverKey,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            // Limpiar tokens invalidados
            if (isset($result['results'])) {
                foreach ($result['results'] as $index => $r) {
                    if (isset($r['error']) && in_array($r['error'], ['NotRegistered', 'InvalidRegistration'])) {
                        // Eliminar token invalido
                        if (isset($payload['registration_ids'][$index])) {
                            $this->eliminarToken($payload['registration_ids'][$index]);
                        }
                    }
                }
            }
            
            return true;
        }
        
        error_log("FCM Error: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Notificar validación de incidencia
     */
    public function notificarValidacion($empleadoId, $estado, $tipoIncidencia, $mensaje) {
        $titulos = [
            'aprobado' => 'Incidencia Aprobada',
            'rechazado' => 'Incidencia Rechazada',
            'requiere_info' => 'Información Requerida'
        ];
        
        return $this->enviarNotificacion(
            $empleadoId,
            $titulos[$estado] ?? 'Notificación',
            $mensaje,
            [
                'tipo' => 'validacion',
                'estado' => $estado,
                'incidencia' => $tipoIncidencia
            ]
        );
    }
    
    /**
     * Notificar nota mala generada
     */
    public function notificarNotaMala($empleadoId, $tipo, $descripcion) {
        return $this->enviarNotificacion(
            $empleadoId,
            'Nota Mala Registrada',
            "Se ha registrado una nota mala por: $descripcion",
            [
                'tipo' => 'nota_mala',
                'nota_tipo' => $tipo
            ]
        );
    }
    
    /**
     * Notificar sanción aplicada
     */
    public function notificarSancion($empleadoId, $tipoSancion, $descripcion) {
        return $this->enviarNotificacion(
            $empleadoId,
            'Sanción Aplicada',
            "Se ha aplicado sanción: $descripcion",
            [
                'tipo' => 'sancion',
                'sancion_tipo' => $tipoSancion
            ]
        );
    }
    
    /**
     * Notificar alerta de suspensión/despido
     */
    public function notificarAlertaCritica($empleadoId, $tipo, $mensaje) {
        return $this->enviarNotificacion(
            $empleadoId,
            '⚠️ Alerta Importante',
            $mensaje,
            [
                'tipo' => 'alerta_critica',
                'alerta_tipo' => $tipo,
                'urgente' => 'true'
            ]
        );
    }
}
