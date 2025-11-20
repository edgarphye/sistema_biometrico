<?php
require_once 'BiometricInterface.php';

/**
 * Gestor de dispositivos biométricos
 * Maneja la lógica de conexión y monitoreo de dispositivos
 */
class DeviceManager
{
    private $biometricImpl;
    private $connectedDevices = [];
    private $deviceStatus = [];
    private $lastStatusCheck = 0;
    private $statusCacheTime = 30; // Cache por 30 segundos

    public function __construct(BiometricInterface $biometricImpl)
    {
        $this->biometricImpl = $biometricImpl;
    }

    /**
     * Conecta un dispositivo específico
     */
    public function connectDevice(int $deviceId): array
    {
        try {
            $result = $this->biometricImpl->connectDevice($deviceId);
            $this->connectedDevices[$deviceId] = [
                'connected_at' => time(),
                'info' => $result
            ];
            $this->updateDeviceStatus($deviceId, 'conectado');
            return $result;
        } catch (Exception $e) {
            $this->updateDeviceStatus($deviceId, 'error');
            throw $e;
        }
    }

    /**
     * Desconecta un dispositivo
     */
    public function disconnectDevice(int $deviceId): bool
    {
        if (isset($this->connectedDevices[$deviceId])) {
            unset($this->connectedDevices[$deviceId]);
            $this->updateDeviceStatus($deviceId, 'desconectado');
            return true;
        }
        return false;
    }

    /**
     * Verifica si un dispositivo está conectado
     */
    public function isDeviceConnected(int $deviceId): bool
    {
        return isset($this->connectedDevices[$deviceId]);
    }

    /**
     * Obtiene información de un dispositivo conectado
     */
    public function getDeviceInfo(int $deviceId): ?array
    {
        return $this->connectedDevices[$deviceId]['info'] ?? null;
    }

    /**
     * Obtiene lista de dispositivos conectados
     */
    public function getConnectedDevices(): array
    {
        return array_keys($this->connectedDevices);
    }

    /**
     * Obtiene el estado de todos los dispositivos con cache
     */
    public function getAllDeviceStatus(): array
    {
        $now = time();

        // Usar cache si no ha expirado
        if ($now - $this->lastStatusCheck < $this->statusCacheTime && !empty($this->deviceStatus)) {
            return $this->deviceStatus;
        }

        try {
            $status = $this->biometricImpl->getDeviceStatus();
            $this->deviceStatus = $status;
            $this->lastStatusCheck = $now;

            // Actualizar estado de dispositivos conectados
            foreach ($status as $device) {
                $deviceId = $device['device_id'] ?? $device['dispositivo_id'];
                $deviceStatus = $device['status'];

                if ($deviceStatus === 'conectado' && !isset($this->connectedDevices[$deviceId])) {
                    $this->connectedDevices[$deviceId] = [
                        'connected_at' => $now,
                        'info' => $device
                    ];
                } elseif ($deviceStatus !== 'conectado' && isset($this->connectedDevices[$deviceId])) {
                    unset($this->connectedDevices[$deviceId]);
                }
            }

            return $status;
        } catch (Exception $e) {
            error_log("Error obteniendo estado de dispositivos: " . $e->getMessage());
            return $this->deviceStatus; // Retornar último estado conocido
        }
    }

    /**
     * Actualiza el estado de un dispositivo específico
     */
    private function updateDeviceStatus(int $deviceId, string $status): void
    {
        // Invalidar cache para forzar actualización
        $this->lastStatusCheck = 0;
    }

    /**
     * Obtiene estadísticas de dispositivos
     */
    public function getDeviceStats(): array
    {
        $allStatus = $this->getAllDeviceStatus();

        $stats = [
            'total' => count($allStatus),
            'conectados' => 0,
            'desconectados' => 0,
            'errores' => 0,
            'tiempo_promedio_conexion' => 0
        ];

        $totalUptime = 0;
        $connectedCount = 0;

        foreach ($allStatus as $device) {
            $status = $device['status'] ?? 'desconocido';

            switch ($status) {
                case 'conectado':
                    $stats['conectados']++;
                    if (isset($this->connectedDevices[$device['device_id'] ?? $device['dispositivo_id']])) {
                        $connectedCount++;
                        $totalUptime += time() - $this->connectedDevices[$device['device_id'] ?? $device['dispositivo_id']]['connected_at'];
                    }
                    break;
                case 'desconectado':
                    $stats['desconectados']++;
                    break;
                default:
                    $stats['errores']++;
                    break;
            }
        }

        if ($connectedCount > 0) {
            $stats['tiempo_promedio_conexion'] = round($totalUptime / $connectedCount);
        }

        return $stats;
    }

    /**
     * Fuerza actualización del estado de dispositivos
     */
    public function refreshDeviceStatus(): array
    {
        $this->lastStatusCheck = 0; // Invalidar cache
        return $this->getAllDeviceStatus();
    }

    /**
     * Verifica salud de dispositivos conectados
     */
    public function healthCheck(): array
    {
        $results = [];
        $connectedDevices = $this->getConnectedDevices();

        foreach ($connectedDevices as $deviceId) {
            try {
                // Intentar una operación simple para verificar que el dispositivo responde
                $this->biometricImpl->connectDevice($deviceId);
                $results[$deviceId] = ['status' => 'healthy', 'last_check' => time()];
            } catch (Exception $e) {
                $results[$deviceId] = [
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                    'last_check' => time()
                ];
            }
        }

        return $results;
    }
}
