<?php
require_once 'BiometricInterface.php';

/**
 * Implementación de simulación para desarrollo y pruebas
 * Reemplaza la lógica actual del modelo Biometrico.php
 */
class BiometricSimulation implements BiometricInterface
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function connectDevice(int $deviceId): array
    {
        // Simular conexión con latencia realista
        usleep(rand(100000, 500000)); // 0.1-0.5 segundos

        return [
            'status' => 'conectado',
            'dispositivo_id' => $deviceId,
            'type' => 'CKTeco',
            'firmware_version' => 'v1.0.' . rand(1, 9),
            'connection_time' => date('Y-m-d H:i:s')
        ];
    }

    public function receiveBiometricData(int $deviceId): array
    {
        // Simular recepción de datos con latencia
        usleep(rand(200000, 800000)); // 0.2-0.8 segundos

        $types = ['huella', 'cara'];
        $type = $types[array_rand($types)];

        $biometricData = $this->generateBiometricData($type);

        return [
            'dispositivo_id' => $deviceId,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => json_encode($biometricData),
            'metadata' => $this->generateMetadata(),
            'quality_score' => rand(70, 100)
        ];
    }

    public function verifyIdentity(string $biometricData, string $type): ?array
    {
        // Simular proceso de verificación con latencia
        usleep(rand(300000, 1000000)); // 0.3-1 segundo

        // Obtener empleados con datos biométricos simulados
        $stmt = $this->db->getConnection()->prepare("
            SELECT id, nombre, apellido, rfc, curp, huella_dactilar, foto_cara
            FROM empleados
            WHERE activo = 1
            ORDER BY RAND()
            LIMIT 10
        ");
        $stmt->execute();
        $empleados = $stmt->fetchAll();

        // Asegurar que, si las huellas están cifradas en la BD, sean descifradas
        require_once __DIR__ . '/../../helpers/Encryption.php';
        foreach ($empleados as &$empleado) {
            if (!empty($empleado['huella_dactilar'])) {
                $empleado['huella_dactilar'] = Encryption::decrypt($empleado['huella_dactilar']);
            }
        }

        if (empty($empleados)) {
            // Crear datos simulados si no existen
            $this->createSimulatedBiometricData();
            $stmt->execute();
            $empleados = $stmt->fetchAll();
        }

        // Simular verificación con mayor realismo - AUMENTAR PROBABILIDAD DE ÉXITO
        foreach ($empleados as $empleado) {
            $coincidencia = rand(0, 100);

            // Mayor probabilidad si el empleado tiene datos del tipo correcto
            if (($type === 'huella' && $empleado['huella_dactilar']) ||
                ($type === 'cara' && $empleado['foto_cara'])) {
                $coincidencia += rand(30, 50); // Aumentado de 20-40 a 30-50
            }

            // Aplicar umbral de verificación MÁS BAJO para simulación
            if ($coincidencia >= 70) { // Bajado de 85 a 70 para mayor éxito
                return [
                    'id' => $empleado['id'],
                    'nombre' => $empleado['nombre'],
                    'apellido' => $empleado['apellido'],
                    'rfc' => $empleado['rfc'],
                    'coincidencia' => min($coincidencia, 100) . '%',
                    'verification_time' => date('Y-m-d H:i:s'),
                    'confidence_level' => $this->calculateConfidenceLevel($coincidencia)
                ];
            }
        }

        // Si no hay coincidencia, devolver un empleado aleatorio con baja confianza (para simulación)
        if (!empty($empleados) && rand(1, 3) === 1) { // 33% de probabilidad de "falsa coincidencia"
            $empleado = $empleados[array_rand($empleados)];
            $coincidencia = rand(71, 84); // Coincidencia baja pero aceptable

            return [
                'id' => $empleado['id'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'rfc' => $empleado['rfc'],
                'coincidencia' => $coincidencia . '%',
                'verification_time' => date('Y-m-d H:i:s'),
                'confidence_level' => $this->calculateConfidenceLevel($coincidencia)
            ];
        }

        return null;
    }

    public function getDeviceStatus(): array
    {
        $devices = [];
        for ($i = 1; $i <= 10; $i++) {
            $device = $this->connectDevice($i);
            $devices[] = [
                'device_id' => $device['dispositivo_id'],
                'dispositivo_id' => $device['dispositivo_id'],
                'status' => $device['status'],
                'type' => $device['type'],
                'firmware_version' => $device['firmware_version']
            ];
        }
        return $devices;
    }

    public function isAvailable(): bool
    {
        return true; // La simulación siempre está disponible
    }

    public function getImplementationInfo(): array
    {
        return [
            'type' => 'simulation',
            'name' => 'BiometricSimulation',
            'version' => '1.0.0',
            'description' => 'Implementación de simulación para desarrollo',
            'capabilities' => ['huella', 'cara'],
            'max_devices' => 10
        ];
    }

    public function logEvent(int $deviceId, string $eventType, string $result, string $message, array $metadata = [], ?int $employeeId = null, ?string $biometricType = null): void
    {
        // En simulación, solo registramos en el log de PHP si está habilitado
        if (defined('LOG_LEVEL') && in_array(LOG_LEVEL, ['DEBUG', 'INFO'])) {
            $logMessage = sprintf(
                "[SIMULATION] Device %d - %s (%s): %s",
                $deviceId,
                $eventType,
                $result,
                $message
            );

            if ($employeeId) {
                $logMessage .= " - Employee ID: $employeeId";
            }

            if ($biometricType) {
                $logMessage .= " - Biometric Type: $biometricType";
            }

            error_log($logMessage);

            if (!empty($metadata)) {
                error_log("[SIMULATION] Metadata: " . json_encode($metadata));
            }
        }
    }

    public function getDeviceLogs(int $deviceId, int $limit = 50): array
    {
        // En simulación, generamos logs ficticios basados en actividad reciente
        $logs = [];
        $tiposEvento = ['conexion', 'verificacion', 'mantenimiento'];
        $resultados = ['exitoso', 'fallido', 'error'];

        for ($i = 0; $i < min($limit, 20); $i++) {
            $logs[] = [
                'id' => rand(1000, 9999),
                'dispositivo_id' => $deviceId,
                'tipo_evento' => $tiposEvento[array_rand($tiposEvento)],
                'timestamp' => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
                'empleado_id' => rand(1, 10),
                'tipo_biometria' => ['huella', 'cara'][array_rand(['huella', 'cara'])],
                'resultado' => $resultados[array_rand($resultados)],
                'mensaje' => 'Log simulado para dispositivo ' . $deviceId,
                'metadata' => json_encode(['simulated' => true])
            ];
        }

        return $logs;
    }

    public function getBiometricStats(?string $startDate = null, ?string $endDate = null): array
    {
        // Generar estadísticas simuladas
        $stats = [];
        for ($deviceId = 1; $deviceId <= 10; $deviceId++) {
            $stats[] = [
                'dispositivo_id' => $deviceId,
                'total_verificaciones' => rand(50, 200),
                'exitosas' => rand(40, 180),
                'fallidas' => rand(5, 20),
                'errores' => rand(0, 5),
                'huellas_verificadas' => rand(20, 100),
                'caras_verificadas' => rand(20, 100),
                'calidad_promedio' => rand(75, 95),
                'tiempo_promedio_procesamiento' => rand(500, 1500) / 1000, // en segundos
                'fecha_inicio' => $startDate ?? date('Y-m-d', strtotime('-7 days')),
                'fecha_fin' => $endDate ?? date('Y-m-d')
            ];
        }

        return $stats;
    }

    /**
     * Genera datos biométricos simulados
     */
    private function generateBiometricData(string $type): array
    {
        if ($type === 'huella') {
            return [
                'template' => base64_encode(random_bytes(512)),
                'quality' => rand(70, 100),
                'finger_index' => rand(0, 9),
                'minutiae_points' => rand(20, 50),
                'ridge_count' => rand(100, 200)
            ];
        } else { // cara
            return [
                'face_template' => base64_encode(random_bytes(1024)),
                'confidence' => rand(85, 98),
                'landmarks' => json_encode($this->generateFaceLandmarks()),
                'pose' => [
                    'yaw' => rand(-15, 15),
                    'pitch' => rand(-10, 10),
                    'roll' => rand(-5, 5)
                ],
                'quality' => rand(80, 100)
            ];
        }
    }

    /**
     * Genera landmarks faciales simulados
     */
    private function generateFaceLandmarks(): array
    {
        return [
            'eyes' => [rand(100, 200), rand(100, 150)],
            'nose' => [rand(150, 250), rand(150, 200)],
            'mouth' => [rand(150, 250), rand(200, 250)],
            'chin' => [rand(150, 250), rand(250, 300)],
            'eyebrows' => [rand(100, 200), rand(80, 120)]
        ];
    }

    /**
     * Genera metadata del dispositivo
     */
    private function generateMetadata(): array
    {
        return [
            'temperature' => rand(20, 30) . '°C',
            'humidity' => rand(40, 70) . '%',
            'ambient_light' => rand(100, 1000) . ' lux',
            'device_uptime' => rand(3600, 86400), // 1 hora a 24 horas
            'last_calibration' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
        ];
    }

    /**
     * Calcula nivel de confianza basado en coincidencia
     */
    private function calculateConfidenceLevel(int $coincidencia): string
    {
        if ($coincidencia >= 95) return 'Muy Alto';
        if ($coincidencia >= 90) return 'Alto';
        if ($coincidencia >= 85) return 'Medio';
        return 'Bajo';
    }

    /**
     * Configura un dispositivo biométrico
     */
    public function configureDevice(int $deviceId, array $config): bool
    {
        // Simular configuración con latencia
        usleep(rand(500000, 2000000)); // 0.5-2 segundos

        // En simulación, siempre retorna éxito
        return true;
    }

    /**
     * Registra la huella dactilar de un empleado en el dispositivo
     */
    public function registerEmployeeFingerprint(int $deviceId, int $employeeId, string $fingerprintData): bool
    {
        // Simular registro con latencia
        usleep(rand(1000000, 3000000)); // 1-3 segundos

        // Actualizar datos del empleado en la base de datos
        $stmt = $this->db->getConnection()->prepare("
            UPDATE empleados SET huella_dactilar = ? WHERE id = ?
        ");
        // Cifrar la huella antes de guardar para mantener consistencia con el resto del sistema
        require_once __DIR__ . '/../../helpers/Encryption.php';
        $fingerprintEncrypted = Encryption::encrypt($fingerprintData);
        $result = $stmt->execute([$fingerprintEncrypted, $employeeId]);

        if ($result) {
            $this->logEvent(
                $deviceId,
                'registro',
                'exitoso',
                'Huella dactilar registrada exitosamente',
                ['employee_id' => $employeeId],
                $employeeId,
                'huella'
            );
        }

        return $result;
    }

    /**
     * Captura una huella dactilar del dispositivo
     */
    public function captureFingerprint(int $deviceId): ?string
    {
        // Simular captura con latencia
        usleep(rand(2000000, 5000000)); // 2-5 segundos

        // Simular posibilidad de fallo (10% de probabilidad)
        if (rand(1, 10) === 1) {
            $this->logEvent(
                $deviceId,
                'verificacion',
                'fallido',
                'Error al capturar huella dactilar',
                ['error' => 'simulated_capture_failure']
            );
            return null;
        }

        // Generar datos de huella simulados
        $fingerprintData = $this->generateBiometricData('huella');
        $fingerprintJson = json_encode($fingerprintData);

        $this->logEvent(
            $deviceId,
            'verificacion',
            'exitoso',
            'Huella dactilar capturada exitosamente',
            ['quality' => $fingerprintData['quality']]
        );

        return $fingerprintJson;
    }

    /**
     * Elimina un empleado del dispositivo
     */
    public function deleteEmployeeFromDevice(int $deviceId, int $employeeId): bool
    {
        // Simular eliminación con latencia
        usleep(rand(500000, 1500000)); // 0.5-1.5 segundos

        $this->logEvent(
            $deviceId,
            'mantenimiento',
            'exitoso',
            'Empleado eliminado del dispositivo',
            ['employee_id' => $employeeId],
            $employeeId
        );

        return true;
    }

    /**
     * Obtiene la configuración actual de un dispositivo
     */
    public function getDeviceConfig(int $deviceId): array
    {
        // Intentar cargar configuración desde base de datos
        try {
            require_once __DIR__ . '/../DispositivoBiometrico.php';
            $dispositivoModel = new DispositivoBiometrico();
            $config = $dispositivoModel->getConnectionConfig($deviceId);
            
            if ($config) {
                return [
                    'device_id' => $config['dispositivo_id'],
                    'nombre' => $config['nombre'],
                    'sede' => $config['sede'],
                    'type' => $config['tipo_dispositivo'],
                    'ip_address' => $config['ip_address'],
                    'port' => $config['puerto'],
                    'modelo' => $config['modelo'],
                    'firmware_version' => 'v1.0.' . rand(1, 9),
                    'max_users' => 1000,
                    'biometric_types' => $config['capacidades'] ?? ['huella' => true, 'cara' => false],
                    'auto_sync' => true,
                    'log_level' => 'INFO'
                ];
            }
        } catch (Exception $e) {
            error_log('Error cargando config de dispositivo: ' . $e->getMessage());
        }
        
        // Fallback a configuración simulada
        return [
            'device_id' => $deviceId,
            'type' => 'CKTeco',
            'firmware_version' => 'v1.0.' . rand(1, 9),
            'ip_address' => '192.168.' . $deviceId . '.100',
            'port' => 4370,
            'timeout' => 30,
            'max_users' => 1000,
            'biometric_types' => ['huella', 'cara'],
            'auto_sync' => true,
            'log_level' => 'INFO'
        ];
    }

    /**
     * Sincroniza empleados con el dispositivo
     */
    public function syncEmployees(int $deviceId, array $employees): bool
    {
        // Simular sincronización con latencia
        usleep(rand(1000000, 5000000)); // 1-5 segundos

        $syncCount = count($employees);

        $this->logEvent(
            $deviceId,
            'mantenimiento',
            'exitoso',
            "Sincronización completada: {$syncCount} empleados",
            ['sync_count' => $syncCount]
        );

        return true;
    }

    /**
     * Crea datos biométricos simulados para empleados existentes
     */
    private function createSimulatedBiometricData(): void
    {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE empleados SET
                huella_dactilar = ?,
                foto_cara = ?
            WHERE activo = 1 AND (huella_dactilar IS NULL OR foto_cara IS NULL)
            LIMIT 3
        ");

        $huellaSimulada = base64_encode(random_bytes(512));
        $caraSimulada = base64_encode(random_bytes(1024));

        // Cifrar la huella simulada antes de guardar
        require_once __DIR__ . '/../../helpers/Encryption.php';
        $huellaSimuladaEnc = Encryption::encrypt($huellaSimulada);

        $stmt->execute([$huellaSimuladaEnc, $caraSimulada]);
    }
}
