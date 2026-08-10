<?php
// ZKTecoMB360.php - Clase para comunicación con el checador ZKTeco MB360

class ZKTecoMB360 {
    private $ip;
    private $port;
    private $timeout;
    private $socket;
    private $session_id;
    private $connected = false;
    
    // Comandos ZKTeco
    const CMD_CONNECT = 0x0050;
    const CMD_DISCONNECT = 0x0051;
    const CMD_ENABLE = 0x0052;
    const CMD_DISABLE = 0x0053;
    const CMD_GET_USER = 0x0059;
    const CMD_SET_USER = 0x005A;
    const CMD_DELETE_USER = 0x005B;
    const CMD_GET_FINGERPRINT = 0x0061;
    const CMD_SET_FINGERPRINT = 0x0062;
    const CMD_DELETE_FINGERPRINT = 0x0063;
    const CMD_ENROLL_FINGERPRINT = 0x0064;
    const CMD_GET_TIME = 0x0065;
    const CMD_SET_TIME = 0x0066;
    
    public function __construct($ip = '192.168.1.201', $port = 4370, $timeout = 5) {
        $this->ip = $ip;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->session_id = rand(1, 9999);
    }
    
    /**
     * Conectar al dispositivo ZKTeco MB360
     */
    public function connect() {
        try {
            $this->socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $this->timeout);
            
            if (!$this->socket) {
                throw new Exception("Error de conexión: $errstr ($errno)");
            }
            
            // Enviar comando de conexión
            $command = $this->buildCommand(self::CMD_CONNECT);
            fwrite($this->socket, $command);
            
            // Leer respuesta
            $response = fread($this->socket, 1024);
            
            if (strlen($response) < 8) {
                throw new Exception("Respuesta inválida del dispositivo");
            }
            
            $this->connected = true;
            $this->logOperation('conexion', 'exito', 'Conectado exitosamente');
            
            return true;
            
        } catch (Exception $e) {
            $this->logOperation('conexion', 'error', $e->getMessage());
            $this->connected = false;
            return false;
        }
    }
    
    /**
     * Desconectar del dispositivo
     */
    public function disconnect() {
        if (!$this->connected) {
            return true;
        }
        
        try {
            $command = $this->buildCommand(self::CMD_DISCONNECT);
            fwrite($this->socket, $command);
            
            fclose($this->socket);
            $this->connected = false;
            
            $this->logOperation('desconexion', 'exito', 'Desconectado exitosamente');
            
            return true;
            
        } catch (Exception $e) {
            $this->logOperation('desconexion', 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todos los usuarios del dispositivo
     */
    public function getUsers() {
        if (!$this->connected) {
            throw new Exception("No conectado al dispositivo");
        }
        
        try {
            $command = $this->buildCommand(self::CMD_GET_USER);
            fwrite($this->socket, $command);
            
            $response = fread($this->socket, 8192);
            $users = $this->parseUserResponse($response);
            
            $this->logOperation('lectura', 'exito', 'Usuarios leídos: ' . count($users));
            
            return $users;
            
        } catch (Exception $e) {
            $this->logOperation('lectura', 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener huellas dactilares de un usuario específico
     */
    public function getFingerprints($userId) {
        if (!$this->connected) {
            throw new Exception("No conectado al dispositivo");
        }
        
        try {
            $command = $this->buildCommand(self::CMD_GET_FINGERPRINT, $userId);
            fwrite($this->socket, $command);
            
            $response = fread($this->socket, 16384);
            $fingerprints = $this->parseFingerprintResponse($response);
            
            $this->logOperation('lectura', 'exito', "Huellas leídas para usuario $userId: " . count($fingerprints));
            
            return $fingerprints;
            
        } catch (Exception $e) {
            $this->logOperation('lectura', 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enrolar huella dactilar de un usuario
     */
    public function enrollFingerprint($userId, $fingerIndex = 0) {
        if (!$this->connected) {
            throw new Exception("No conectado al dispositivo");
        }
        
        try {
            // Iniciar proceso de enrolamiento
            $command = $this->buildCommand(self::CMD_ENROLL_FINGERPRINT, $userId, $fingerIndex);
            fwrite($this->socket, $command);
            
            // Esperar respuesta del proceso
            $response = fread($this->socket, 1024);
            
            if ($this->isSuccessResponse($response)) {
                $this->logOperation('enrolamiento', 'exito', "Enrolamiento iniciado para usuario $userId, dedo $fingerIndex");
                return true;
            } else {
                $error = $this->parseErrorResponse($response);
                $this->logOperation('enrolamiento', 'error', $error);
                return false;
            }
            
        } catch (Exception $e) {
            $this->logOperation('enrolamiento', 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sincronizar todos los usuarios y sus huellas con la base de datos
     */
    public function syncAllUsers() {
        if (!$this->connected) {
            throw new Exception("No conectado al dispositivo");
        }
        
        $sync_results = [
            'users_found' => 0,
            'users_synced' => 0,
            'fingerprints_synced' => 0,
            'errors' => []
        ];
        
        try {
            // Obtener todos los usuarios del dispositivo
            $device_users = $this->getUsers();
            $sync_results['users_found'] = count($device_users);
            
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../models/Database.php';
            
            $db = new Database();
            $conn = $db->getConnection();
            
            foreach ($device_users as $device_user) {
                try {
                    // Buscar empleado correspondiente en la base de datos
                    $stmt = $conn->prepare("SELECT id FROM empleados WHERE zkteo_id = ? OR id = ?");
                    $stmt->execute([$device_user['user_id'], $device_user['user_id']]);
                    $empleado = $stmt->fetch();
                    
                    if ($empleado) {
                        // Obtener huellas del usuario en el dispositivo
                        $fingerprints = $this->getFingerprints($device_user['user_id']);
                        
                        foreach ($fingerprints as $fingerprint) {
                            // Guardar huella en la base de datos
                            $stmt = $conn->prepare("
                                INSERT INTO huellas_empleados 
                                (empleado_id, zk_empleado_id, indice_huella, huella_template, 
                                 calidad_huella, dispositivo_serial, fecha_captura, estado)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                huella_template = VALUES(huella_template),
                                calidad_huella = VALUES(calidad_huella),
                                fecha_sincronizacion = NOW()
                            ");
                            
                            $result = $stmt->execute([
                                $empleado['id'],
                                $device_user['user_id'],
                                $fingerprint['finger_index'],
                                $fingerprint['template'],
                                $fingerprint['quality'],
                                $this->getDeviceSerial(),
                                $fingerprint['capture_time'],
                                'activo'
                            ]);
                            
                            if ($result) {
                                $sync_results['fingerprints_synced']++;
                            }
                        }
                        
                        $sync_results['users_synced']++;
                    }
                    
                } catch (Exception $e) {
                    $sync_results['errors'][] = "Error sincronizando usuario {$device_user['user_id']}: " . $e->getMessage();
                }
            }
            
            $this->logOperation('sincronizacion', 'exito', "Sincronización completada: {$sync_results['users_synced']} usuarios, {$sync_results['fingerprints_synced']} huellas");
            
            return $sync_results;
            
        } catch (Exception $e) {
            $this->logOperation('sincronizacion', 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Construir comando ZKTeco
     */
    private function buildCommand($command, $userId = 0, $fingerIndex = 0) {
        $packet = '';
        
        // Header (8 bytes)
        $packet .= pack('V', $this->session_id);  // Session ID (4 bytes)
        $packet .= pack('v', $command);            // Command code (2 bytes)
        
        // Calculate data size
        $dataSize = 0;
        $data = '';
        if ($userId > 0) {
            $data .= pack('V', $userId);           // User ID (4 bytes)
            $dataSize += 4;
            if ($fingerIndex >= 0) {                // Fix: >= 0 instead of > 0
                $data .= pack('C', $fingerIndex);  // Finger index (1 byte)
                $dataSize += 1;
            }
        }
        
        $packet .= pack('v', $dataSize);           // Command size (2 bytes)
        $packet .= $data;
        
        // Checksum (sum of all bytes before checksum)
        $checksum = 0;
        for ($i = 0; $i < strlen($packet); $i++) {
            $checksum += ord($packet[$i]);
        }
        $packet .= pack('v', $checksum & 0xFFFF);  // Checksum (2 bytes, mask to 16-bit)
        
        return $packet;
    }
    
    /**
     * Parsear respuesta de usuarios
     */
    private function parseUserResponse($response) {
        $users = [];
        
        if (strlen($response) < 8) {
            return $users;
        }
        
        // ZKTeco MB360 user record format:
        // - Header: 8 bytes (session_id, command, size)
        // - Each user record: ~36 bytes
        //   - User ID: 4 bytes
        //   - Name: 24 bytes (padded with zeros)
        //   - Password: 8 bytes (optional)
        //   - Flags: 1 byte (admin, enabled, etc.)
        
        $offset = 8; // Skip header
        
        while ($offset < strlen($response)) {
            if ($offset + 36 > strlen($response)) {
                break; // Not enough data for a complete record
            }
            
            // Extract user ID (little-endian 32-bit)
            $userId = unpack('V', substr($response, $offset, 4))[1];
            
            if ($userId > 0 && $userId < 100000) { // Valid user ID range
                // Extract name (24 bytes, null-terminated)
                $name = '';
                for ($i = 0; $i < 24; $i++) {
                    $char = ord($response[$offset + 4 + $i]);
                    if ($char == 0) break;
                    $name .= chr($char);
                }
                
                // Extract flags (1 byte)
                $flags = ord($response[$offset + 32]);
                $isAdmin = ($flags & 0x01) == 0x01;
                $isEnabled = ($flags & 0x02) != 0x02; // Bit 1 = disabled
                
                $users[] = [
                    'user_id' => $userId,
                    'name' => trim($name),
                    'is_admin' => $isAdmin,
                    'is_enabled' => $isEnabled,
                    'flags' => $flags
                ];
            }
            
            $offset += 36; // Move to next record
        }
        
        return $users;
    }
    
    /**
     * Parsear respuesta de huellas
     */
    private function parseFingerprintResponse($response) {
        $fingerprints = [];
        
        if (strlen($response) < 8) {
            return $fingerprints;
        }
        
        // ZKTeco fingerprint record format:
        // - Header: 8 bytes
        // - User ID: 4 bytes
        // - Finger index: 1 byte (0-9)
        // - Template size: 2 bytes
        // - Template data: variable
        // - Quality: 1 byte
        
        $offset = 8; // Skip header
        
        while ($offset < strlen($response)) {
            if ($offset + 7 > strlen($response)) {
                break; // Not enough data
            }
            
            // Extract user ID
            $userId = unpack('V', substr($response, $offset, 4))[1];
            
            if ($userId > 0 && $userId < 100000) {
                // Extract finger index
                $fingerIndex = ord($response[$offset + 4]);
                
                if ($fingerIndex >= 0 && $fingerIndex <= 9) {
                    // Extract template size
                    $templateSize = unpack('v', substr($response, $offset + 5, 2))[1];
                    
                    if ($templateSize > 0 && $templateSize < 2048 && 
                        $offset + 7 + $templateSize <= strlen($response)) {
                        
                        // Extract template
                        $template = substr($response, $offset + 7, $templateSize);
                        
                        // Extract quality (after template)
                        $qualityOffset = $offset + 7 + $templateSize;
                        $quality = ($qualityOffset < strlen($response)) ? 
                                   ord($response[$qualityOffset]) : 50;
                        
                        // Get capture time (use current time as fallback)
                        $captureTime = date('Y-m-d H:i:s');
                        
                        $fingerprints[] = [
                            'user_id' => $userId,
                            'finger_index' => $fingerIndex,
                            'template' => $template,
                            'template_size' => $templateSize,
                            'quality' => $quality,
                            'capture_time' => $captureTime
                        ];
                        
                        // Move past template + quality byte
                        $offset = $qualityOffset + 1;
                        continue;
                    }
                }
            }
            
            $offset++; // Move forward one byte and try again
        }
        
        return $fingerprints;
    }
    
    /**
     * Verificar si la respuesta es exitosa
     */
    private function isSuccessResponse($response) {
        return strlen($response) >= 8 && ord($response[6]) == 0x00;
    }
    
    /**
     * Parsear mensaje de error
     */
    private function parseErrorResponse($response) {
        if (strlen($response) >= 8) {
            $error_code = ord($response[6]);
            $error_messages = [
                0x01 => 'Comando inválido',
                0x02 => 'Parámetro inválido',
                0x03 => 'Usuario no encontrado',
                0x04 => 'Huella no encontrada',
                0x05 => 'Memoria llena',
                0x06 => 'Error de comunicación',
                0x07 => 'Dispositivo ocupado'
            ];
            
            return $error_messages[$error_code] ?? "Error desconocido (código: $error_code)";
        }
        
        return 'Error desconocido';
    }
    
    /**
     * Obtener número de serie del dispositivo
     */
    private function getDeviceSerial() {
        // Try to read serial from device if connected
        if ($this->connected && $this->socket) {
            try {
                // ZKTeco command to get device info
                $command = $this->buildCommand(0x0054); // CMD_GET_DEVICE_INFO
                fwrite($this->socket, $command);
                $response = fread($this->socket, 1024);
                
                if (strlen($response) >= 16) {
                    // Extract serial from response (bytes 8-15 typically)
                    $serial = '';
                    for ($i = 8; $i < 16 && $i < strlen($response); $i++) {
                        $char = ord($response[$i]);
                        if ($char == 0) break;
                        $serial .= chr($char);
                    }
                    
                    if (!empty($serial) && strlen($serial) >= 4) {
                        return $serial;
                    }
                }
            } catch (Exception $e) {
                // Fall back to database lookup or default
            }
        }
        
        // Try to get serial from database for this device IP
        try {
            require_once __DIR__ . '/../models/Database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT dispositivo_serial FROM logs_dispositivo_zk WHERE ip_origen = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$this->ip]);
            $row = $stmt->fetch();
            
            if ($row && !empty($row['dispositivo_serial'])) {
                return $row['dispositivo_serial'];
            }
        } catch (Exception $e) {
            // Fall through to default
        }
        
        // Default serial based on IP
        return 'MB360_' . str_replace('.', '_', $this->ip);
    }
    
    /**
     * Registrar operación en log
     */
    private function logOperation($operation, $status, $message, $errorCode = null) {
        try {
            require_once __DIR__ . '/../config.php';
            require_once __DIR__ . '/../models/Database.php';
            
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO logs_dispositivo_zk 
                (dispositivo_serial, tipo_operacion, estado, codigo_error, mensaje_error, ip_origen)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->getDeviceSerial(),
                $operation,
                $status,
                $errorCode,
                $message,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            
        } catch (Exception $e) {
            error_log("Error en log de operación ZKTeco: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar estado de conexión
     */
    public function isConnected() {
        return $this->connected;
    }
    
    /**
     * Obtener información del dispositivo
     */
    public function getDeviceInfo() {
        return [
            'ip' => $this->ip,
            'port' => $this->port,
            'model' => 'MB360',
            'brand' => 'ZKTeco',
            'serial' => $this->getDeviceSerial(),
            'connected' => $this->connected
        ];
    }
    
    /**
     * Probar conexión al dispositivo
     * @return array Resultado de la prueba
     */
    public function testConnection() {
        $result = [
            'exitoso' => false,
            'mensaje' => '',
            'dispositivo' => null,
            'tiempo_respuesta' => 0
        ];
        
        $inicio = microtime(true);
        
        try {
            // Try to connect
            $conectado = $this->connect();
            
            if ($conectado) {
                $tiempoRespuesta = round((microtime(true) - $inicio) * 1000, 2);
                
                $result['exitoso'] = true;
                $result['mensaje'] = "Conexión exitosa en {$tiempoRespuesta}ms";
                $result['dispositivo'] = $this->getDeviceInfo();
                $result['tiempo_respuesta'] = $tiempoRespuesta;
                
                // Try to get device info
                try {
                    $usuarios = $this->getUsers();
                    $result['usuarios_encontrados'] = count($usuarios);
                } catch (Exception $e) {
                    $result['usuarios_encontrados'] = 0;
                    $result['nota'] = "Conectado pero no se pudieron leer usuarios";
                }
                
                // Disconnect after test
                $this->disconnect();
            } else {
                $result['mensaje'] = "No se pudo conectar al dispositivo {$this->ip}:{$this->port}";
            }
            
        } catch (Exception $e) {
            $result['mensaje'] = "Error: " . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Obtener estado del dispositivo
     * @return array Estado detallado
     */
    public function getDeviceStatus() {
        $status = [
            'ip' => $this->ip,
            'port' => $this->port,
            'conectado' => $this->connected,
            'serial' => $this->getDeviceSerial(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($this->connected) {
            try {
                // Get user count
                $usuarios = $this->getUsers();
                $status['usuarios_registrados'] = count($usuarios);
                $status['estado'] = 'operativo';
            } catch (Exception $e) {
                $status['estado'] = 'error_lectura';
                $status['error'] = $e->getMessage();
            }
        } else {
            $status['estado'] = 'desconectado';
        }
        
        return $status;
    }
    
    /**
     * Verificar si el dispositivo está respondiendo (ping test)
     * @return bool True si responde
     */
    public function ping() {
        $fp = @fsockopen($this->ip, $this->port, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }
}
?>