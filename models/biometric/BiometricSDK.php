<?php
require_once 'BiometricInterface.php';

/**
 * Clase base preparada para integración con SDK real de ZKTeco
 * Esta clase debe ser extendida con la implementación específica del SDK
 */
abstract class BiometricSDK implements BiometricInterface
{
    protected $config;
    protected $connectedDevices = [];
    protected $logger;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'api_url' => BIOMETRIC_API_URL ?? 'https://api.zkteco.com',
            'api_key' => BIOMETRIC_API_KEY ?? '',
            'timeout' => 30,
            'max_devices' => 10,
            'debug' => false
        ], $config);

        $this->initializeLogger();
        $this->initializeSDK();
    }

    /**
     * Inicializa el logger para debugging
     */
    protected function initializeLogger(): void
    {
        $this->logger = new class {
            public function info(string $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                error_log("[$timestamp] INFO BiometricSDK: $message$contextStr");
            }

            public function error(string $message, array $context = []): void {
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                error_log("[$timestamp] ERROR BiometricSDK: $message$contextStr");
            }

            public function debug(string $message, array $context = []): void {
                if ($this->config['debug'] ?? false) {
                    $timestamp = date('Y-m-d H:i:s');
                    $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                    error_log("[$timestamp] DEBUG BiometricSDK: $message$contextStr");
                }
            }
        };
    }

    /**
     * Inicializa el SDK - debe ser implementado por la clase hija
     */
    abstract protected function initializeSDK(): void;

    /**
     * Método helper para validar configuración
     */
    protected function validateConfig(): bool
    {
        $required = ['api_url', 'api_key'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $this->logger->error("Configuración faltante: $key");
                return false;
            }
        }
        return true;
    }

    /**
     * Método helper para hacer peticiones HTTP al API
     */
    protected function makeAPIRequest(string $endpoint, array $data = [], string $method = 'GET'): array
    {
        $url = rtrim($this->config['api_url'], '/') . '/' . ltrim($endpoint, '/');

        $options = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->config['api_key'],
                    'User-Agent: SistemaBiometrico/1.0'
                ],
                'timeout' => $this->config['timeout']
            ]
        ];

        if (!empty($data) && $method !== 'GET') {
            $options['http']['content'] = json_encode($data);
        } elseif (!empty($data) && $method === 'GET') {
            $url .= '?' . http_build_query($data);
        }

        $context = stream_context_create($options);

        $this->logger->debug("API Request: $method $url", $data);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $this->logger->error("API Request failed: " . ($error['message'] ?? 'Unknown error'));
            throw new Exception("Error de conexión con API ZKTeco: " . ($error['message'] ?? 'Unknown error'));
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error("Invalid JSON response: $response");
            throw new Exception("Respuesta inválida del API ZKTeco");
        }

        $this->logger->debug("API Response", $result);

        return $result;
    }

    /**
     * Método helper para validar ID de dispositivo
     */
    protected function validateDeviceId(int $deviceId): void
    {
        if ($deviceId < 1 || $deviceId > $this->config['max_devices']) {
            throw new InvalidArgumentException("ID de dispositivo inválido: $deviceId");
        }
    }

    /**
     * Método helper para manejar errores del SDK
     */
    protected function handleSDKError(string $operation, Exception $e): void
    {
        $this->logger->error("SDK Error in $operation: " . $e->getMessage());
        throw new Exception("Error en operación biométrica '$operation': " . $e->getMessage());
    }

    // Implementaciones base que deben ser sobrescritas

    public function connectDevice(int $deviceId): array
    {
        $this->validateDeviceId($deviceId);

        try {
            $this->logger->info("Conectando dispositivo", ['device_id' => $deviceId]);
            $result = $this->connectDeviceImplementation($deviceId);
            $this->connectedDevices[$deviceId] = true;
            $this->logger->info("Dispositivo conectado exitosamente", ['device_id' => $deviceId]);
            return $result;
        } catch (Exception $e) {
            $this->handleSDKError('connectDevice', $e);
        }
    }

    public function receiveBiometricData(int $deviceId): array
    {
        $this->validateDeviceId($deviceId);

        if (!isset($this->connectedDevices[$deviceId])) {
            throw new Exception("Dispositivo $deviceId no está conectado");
        }

        try {
            $this->logger->info("Recibiendo datos biométricos", ['device_id' => $deviceId]);
            $result = $this->receiveBiometricDataImplementation($deviceId);
            $this->logger->info("Datos biométricos recibidos", ['device_id' => $deviceId, 'type' => $result['type']]);
            return $result;
        } catch (Exception $e) {
            $this->handleSDKError('receiveBiometricData', $e);
        }
    }

    public function verifyIdentity(string $biometricData, string $type): ?array
    {
        if (!in_array($type, ['huella', 'cara'])) {
            throw new InvalidArgumentException("Tipo de verificación inválido: $type");
        }

        try {
            $this->logger->info("Verificando identidad", ['type' => $type]);
            $result = $this->verifyIdentityImplementation($biometricData, $type);

            if ($result) {
                $this->logger->info("Identidad verificada", [
                    'employee_id' => $result['id'],
                    'coincidencia' => $result['coincidencia']
                ]);
            } else {
                $this->logger->info("Identidad no verificada");
            }

            return $result;
        } catch (Exception $e) {
            $this->handleSDKError('verifyIdentity', $e);
        }
    }

    public function getDeviceStatus(): array
    {
        try {
            $this->logger->info("Obteniendo estado de dispositivos");
            $result = $this->getDeviceStatusImplementation();
            $this->logger->info("Estado de dispositivos obtenido", ['count' => count($result)]);
            return $result;
        } catch (Exception $e) {
            $this->handleSDKError('getDeviceStatus', $e);
        }
    }

    public function isAvailable(): bool
    {
        try {
            $available = $this->isAvailableImplementation();
            $this->logger->info("Verificación de disponibilidad", ['available' => $available]);
            return $available;
        } catch (Exception $e) {
            $this->logger->error("Error verificando disponibilidad: " . $e->getMessage());
            return false;
        }
    }

    public function getImplementationInfo(): array
    {
        return [
            'type' => 'sdk',
            'name' => static::class,
            'version' => $this->getSDKVersion(),
            'description' => $this->getSDKDescription(),
            'capabilities' => $this->getSDKCapabilities(),
            'max_devices' => $this->config['max_devices'],
            'api_url' => $this->config['api_url']
        ];
    }

    public function logEvent(int $deviceId, string $eventType, string $result, string $message, array $metadata = [], ?int $employeeId = null, ?string $biometricType = null): void
    {
        // Implementación por defecto - las subclases pueden sobrescribir
        $this->logger->info("Device Event", [
            'device_id' => $deviceId,
            'event_type' => $eventType,
            'result' => $result,
            'message' => $message,
            'employee_id' => $employeeId,
            'biometric_type' => $biometricType,
            'metadata' => $metadata
        ]);
    }

    public function getDeviceLogs(int $deviceId, int $limit = 50): array
    {
        // Implementación por defecto - debería ser sobrescrita por subclases específicas
        throw new Exception("getDeviceLogs debe ser implementado por la subclase del SDK");
    }

    public function getBiometricStats(?string $startDate = null, ?string $endDate = null): array
    {
        // Implementación por defecto - debería ser sobrescrita por subclases específicas
        throw new Exception("getBiometricStats debe ser implementado por la subclase del SDK");
    }

    // Métodos abstractos que deben ser implementados por SDK específico

    abstract protected function connectDeviceImplementation(int $deviceId): array;
    abstract protected function receiveBiometricDataImplementation(int $deviceId): array;
    abstract protected function verifyIdentityImplementation(string $biometricData, string $type): ?array;
    abstract protected function getDeviceStatusImplementation(): array;
    abstract protected function isAvailableImplementation(): bool;
    abstract protected function getSDKVersion(): string;
    abstract protected function getSDKDescription(): string;
    abstract protected function getSDKCapabilities(): array;
}
