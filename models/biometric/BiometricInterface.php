<?php
/**
 * Interfaz para operaciones biométricas
 * Permite cambiar fácilmente entre simulación y SDK real
 */
interface BiometricInterface
{
    /**
     * Conecta con un dispositivo biométrico específico
     * @param int $deviceId ID del dispositivo
     * @return array Información del dispositivo conectado
     */
    public function connectDevice(int $deviceId): array;

    /**
     * Recibe datos biométricos del dispositivo
     * @param int $deviceId ID del dispositivo
     * @return array Datos biométricos capturados
     */
    public function receiveBiometricData(int $deviceId): array;

    /**
     * Verifica identidad usando datos biométricos
     * @param string $biometricData Datos biométricos (JSON)
     * @param string $type Tipo de verificación ('huella' o 'cara')
     * @return array|null Información del empleado verificado o null si no coincide
     */
    public function verifyIdentity(string $biometricData, string $type): ?array;

    /**
     * Obtiene estado de todos los dispositivos
     * @return array Lista de dispositivos con su estado
     */
    public function getDeviceStatus(): array;

    /**
     * Verifica si la implementación está disponible
     * @return bool True si el SDK está disponible y funcional
     */
    public function isAvailable(): bool;

    /**
     * Obtiene información de la implementación
     * @return array Información sobre la implementación actual
     */
    public function getImplementationInfo(): array;

    /**
     * Registra un evento en el log del dispositivo
     * @param int $deviceId ID del dispositivo
     * @param string $eventType Tipo de evento
     * @param string $result Resultado del evento
     * @param string $message Mensaje descriptivo
     * @param array $metadata Metadatos adicionales
     * @param int|null $employeeId ID del empleado (opcional)
     * @param string|null $biometricType Tipo de biometría (opcional)
     */
    public function logEvent(int $deviceId, string $eventType, string $result, string $message, array $metadata = [], ?int $employeeId = null, ?string $biometricType = null): void;

    /**
     * Obtiene logs de un dispositivo específico
     * @param int $deviceId ID del dispositivo
     * @param int $limit Número máximo de registros
     * @return array Lista de logs del dispositivo
     */
    public function getDeviceLogs(int $deviceId, int $limit = 50): array;

    /**
     * Obtiene estadísticas biométricas
     * @param string|null $startDate Fecha de inicio (YYYY-MM-DD)
     * @param string|null $endDate Fecha de fin (YYYY-MM-DD)
     * @return array Estadísticas biométricas
     */
    public function getBiometricStats(?string $startDate = null, ?string $endDate = null): array;

    /**
     * Configura un dispositivo biométrico
     * @param int $deviceId ID del dispositivo
     * @param array $config Configuración del dispositivo
     * @return bool True si la configuración fue exitosa
     */
    public function configureDevice(int $deviceId, array $config): bool;

    /**
     * Registra la huella dactilar de un empleado en el dispositivo
     * @param int $deviceId ID del dispositivo
     * @param int $employeeId ID del empleado
     * @param string $fingerprintData Datos de la huella
     * @return bool True si el registro fue exitoso
     */
    public function registerEmployeeFingerprint(int $deviceId, int $employeeId, string $fingerprintData): bool;

    /**
     * Captura una huella dactilar del dispositivo
     * @param int $deviceId ID del dispositivo
     * @return string|null Datos de la huella capturada o null si falla
     */
    public function captureFingerprint(int $deviceId): ?string;

    /**
     * Elimina un empleado del dispositivo
     * @param int $deviceId ID del dispositivo
     * @param int $employeeId ID del empleado
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteEmployeeFromDevice(int $deviceId, int $employeeId): bool;

    /**
     * Obtiene la configuración actual de un dispositivo
     * @param int $deviceId ID del dispositivo
     * @return array Configuración del dispositivo
     */
    public function getDeviceConfig(int $deviceId): array;

    /**
     * Sincroniza empleados con el dispositivo
     * @param int $deviceId ID del dispositivo
     * @param array $employees Lista de empleados a sincronizar
     * @return bool True si la sincronización fue exitosa
     */
    public function syncEmployees(int $deviceId, array $employees): bool;

    /**
     * Actualiza los datos de un empleado en el dispositivo biométrico
     * (nombre, password, privilegio) sin re-enrolar la huella.
     * Busca al empleado por su ID registrado (zkteco_id) en el dispositivo.
     * @param int $deviceId ID del dispositivo
     * @param int $employeeId ID del empleado en BD
     * @return bool True si la actualización fue exitosa
     */
    public function updateEmployeeOnDevice(int $deviceId, int $employeeId): bool;

    /**
     * Descarga todos los registros de asistencia desde el dispositivo biométrico,
     * los procesa y los inserta en la tabla asistencia.
     * @param int $deviceId ID del dispositivo
     * @return array{imported: int, duplicates: int, errors: int, total: int}
     */
    public function downloadAttendanceFromDevice(int $deviceId): array;
}
