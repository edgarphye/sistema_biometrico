<?php

/**
 * CKTeco Biometric SDK Integration
 * Integración real con dispositivos biométricos CKTeco
 */

class CKTecoBiometricSDK
{
    private $apiUrl;
    private $apiKey;
    private $deviceId;
    private $timeout = 30;
    private $devices = [];
    
    public function __construct($apiUrl = null, $apiKey = null)
    {
        $this->apiUrl = $apiUrl ?: BIOMETRIC_API_URL;
        $this->apiKey = $apiKey ?: BIOMETRIC_API_KEY;
    }
    
    /**
     * Conectar con dispositivo biométrico
     */
    public function connectToDevice($deviceId, $ip, $port = 4370)
    {
        try {
            $this->deviceId = $deviceId;
            
            // API Call para conectar
            $response = $this->makeRequest('/device/connect', [
                'device_id' => $deviceId,
                'ip' => $ip,
                'port' => $port
            ]);
            
            if ($response['success']) {
                $this->devices[$deviceId] = [
                    'ip' => $ip,
                    'port' => $port,
                    'connected' => true,
                    'session_id' => $response['data']['session_id'],
                    'last_activity' => time()
                ];
                
                return [
                    'success' => true,
                    'device_id' => $deviceId,
                    'message' => 'Dispositivo conectado exitosamente'
                ];
            } else {
                throw new Exception($response['error']['message'] ?? 'Error de conexión');
            }
            
        } catch (Exception $e) {
            error_log("CKTeco connection error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener información del dispositivo
     */
    public function getDeviceInfo($deviceId)
    {
        try {
            $response = $this->makeRequest('/device/info', [
                'device_id' => $deviceId
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco device info error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sincronizar usuarios con dispositivo
     */
    public function syncUsersToDevice($deviceId, $usuarios)
    {
        try {
            $usersData = [];
            
            foreach ($usuarios as $usuario) {
                $usersData[] = [
                    'user_id' => $usuario['id'],
                    'name' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                    'rfc' => $usuario['rfc'],
                    'password' => $this->generateDevicePassword($usuario['rfc']),
                    'privilege' => $this->getUserPrivilege($usuario)
                ];
            }
            
            $response = $this->makeRequest('/device/sync-users', [
                'device_id' => $deviceId,
                'users' => $usersData
            ]);
            
            if ($response['success']) {
                $this->logSyncOperation($deviceId, 'users_sync', $response);
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco sync users error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Registrar huella dactilar
     */
    public function registerFingerprint($deviceId, $userId, $fingerId = 0)
    {
        try {
            $response = $this->makeRequest('/device/register-fingerprint', [
                'device_id' => $deviceId,
                'user_id' => $userId,
                'finger_id' => $fingerId,
                'timeout' => 30
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco register fingerprint error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar huella dactilar
     */
    public function verifyFingerprint($deviceId, $template)
    {
        try {
            $response = $this->makeRequest('/device/verify-fingerprint', [
                'device_id' => $deviceId,
                'template' => $template,
                'timeout' => 10
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco verify fingerprint error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Registrar cara (reconocimiento facial)
     */
    public function registerFace($deviceId, $userId, $imageData)
    {
        try {
            // Preparar imagen para el dispositivo
            $processedImage = $this->processFaceImage($imageData);
            
            $response = $this->makeRequest('/device/register-face', [
                'device_id' => $deviceId,
                'user_id' => $userId,
                'face_data' => base64_encode($processedImage),
                'timeout' => 30
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco register face error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar cara
     */
    public function verifyFace($deviceId, $imageData)
    {
        try {
            $processedImage = $this->processFaceImage($imageData);
            
            $response = $this->makeRequest('/device/verify-face', [
                'device_id' => $deviceId,
                'face_data' => base64_encode($processedImage),
                'timeout' => 10
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco verify face error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener registros de asistencia del dispositivo
     */
    public function getAttendanceRecords($deviceId, $fechaInicio, $fechaFin)
    {
        try {
            $response = $this->makeRequest('/device/attendance-records', [
                'device_id' => $deviceId,
                'start_date' => $fechaInicio,
                'end_date' => $fechaFin
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco attendance records error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Realizar llamada API genérica
     */
    private function makeRequest($endpoint, $data = [])
    {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'X-API-Version: 1.0',
            'User-Agent: Sistema-Biometrico/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("CURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Generar contraseña para dispositivo
     */
    private function generateDevicePassword($rfc)
    {
        // Reglas de contraseña CKTeco:
        // - Mínimo 6 caracteres
        // - Máximo 8 caracteres
        // - Al menos 1 número
        // - Al menos 1 letra mayúscula
        
        $base = strtoupper(substr($rfc, 0, 4));
        $number = rand(10, 99);
        
        return $base . $number;
    }
    
    /**
     * Determinar nivel de privilegio de usuario
     */
    private function getUserPrivilege($usuario)
    {
        // Niveles de privilegio CKTeco
        // 0: Usuario común (solo registrar)
        // 1: Usuario avanzado (registrar y ver reports)
        // 2: Administrador (control total)
        
        if ($usuario['rol'] === 'admin') {
            return 2;
        } elseif ($usuario['rol'] === 'user') {
            return 1;
        } else {
            return 0;
        }
    }
    
    /**
     * Procesar imagen de cara
     */
    private function processFaceImage($imageData)
    {
        // Convertir a formato esperado por CKTeco
        // Generalmente 320x240 pixels, formato JPG
        
        if (is_string($imageData) && strpos($imageData, 'data:image') === 0) {
            // Es base64 data URI
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
        }
        
        // Redimensionar si es necesario
        $image = imagecreatefromstring($imageData);
        if ($image) {
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Redimensionar a 320x240 si es más grande
            if ($width > 320 || $height > 240) {
                $newWidth = 320;
                $newHeight = 240;
                
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                imagedestroy($image);
                $image = $resized;
            }
            
            // Convertir a JPEG con calidad 85%
            ob_start();
            imagejpeg($image, null, 85);
            $processedImage = ob_get_contents();
            ob_end_clean();
            
            imagedestroy($image);
            return $processedImage;
        }
        
        return $imageData;
    }
    
    /**
     * Registrar operaciones en log
     */
    private function logSyncOperation($deviceId, $operation, $response)
    {
        try {
            global $pdo;
            
            $stmt = $pdo->prepare("
                INSERT INTO device_logs (device_id, operation, response_data, status, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $deviceId,
                $operation,
                json_encode($response),
                $response['success'] ? 'success' : 'error'
            ]);
            
        } catch (Exception $e) {
            error_log("Error logging sync operation: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estado de todos los dispositivos
     */
    public function getDevicesStatus()
    {
        try {
            $response = $this->makeRequest('/devices/status');
            
            if ($response['success']) {
                // Actualizar estado local
                foreach ($response['data']['devices'] as $device) {
                    $this->devices[$device['id']]['status'] = $device['status'];
                    $this->devices[$device['id']]['last_ping'] = $device['last_ping'];
                }
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco devices status error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Limpiar datos de usuario del dispositivo
     */
    public function clearUserData($deviceId, $userId)
    {
        try {
            $response = $this->makeRequest('/device/clear-user', [
                'device_id' => $deviceId,
                'user_id' => $userId
            ]);
            
            if ($response['success']) {
                $this->logSyncOperation($deviceId, 'clear_user', $response);
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco clear user data error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Reiniciar dispositivo
     */
    public function restartDevice($deviceId)
    {
        try {
            $response = $this->makeRequest('/device/restart', [
                'device_id' => $deviceId
            ]);
            
            return $response;
            
        } catch (Exception $e) {
            error_log("CKTeco restart device error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}