<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/biometric/BiometricFactory.php';

/**
 * Modelo Biometrico - Fachada para operaciones biométricas
 * Utiliza el patrón Factory para cambiar entre simulación y SDK real
 */
class Biometrico {
    private $db;
    private $biometricImpl;

    public function __construct() {
        $this->db = new Database();
        $factory = BiometricFactory::getInstance();
        $this->biometricImpl = $factory->createBiometric();
    }

    /**
     * Conecta con un dispositivo biométrico
     * @param int $dispositivo_id ID del dispositivo
     * @return array Información del dispositivo conectado
     */
    public function conectarDispositivo($dispositivo_id) {
        return $this->biometricImpl->connectDevice($dispositivo_id);
    }

    /**
     * Recibe datos biométricos del dispositivo
     * @param int $dispositivo_id ID del dispositivo
     * @return array Datos biométricos capturados
     */
    public function recibirDatosBiometricos($dispositivo_id) {
        return $this->biometricImpl->receiveBiometricData($dispositivo_id);
    }

    /**
     * Verifica identidad usando datos biométricos
     * @param string $datos_biometricos Datos biométricos (JSON)
     * @param string $tipo Tipo de verificación ('huella' o 'cara')
     * @return array|null Información del empleado verificado o null
     */
    public function verificarIdentidad($datos_biometricos, $tipo) {
        return $this->biometricImpl->verifyIdentity($datos_biometricos, $tipo);
    }

    /**
     * Obtiene estado de todos los dispositivos
     * @return array Lista de dispositivos con su estado
     */
    public function getEstadoDispositivos() {
        return $this->biometricImpl->getDeviceStatus();
    }

    /**
     * Verifica si la implementación biométrica está disponible
     * @return bool True si está disponible
     */
    public function isAvailable() {
        return $this->biometricImpl->isAvailable();
    }

    /**
     * Obtiene información sobre la implementación actual
     * @return array Información de la implementación
     */
    public function getImplementationInfo() {
        return $this->biometricImpl->getImplementationInfo();
    }

    /**
     * Cambia la implementación biométrica (útil para testing)
     * @param BiometricInterface $implementation Nueva implementación
     */
    public function setImplementation($implementation) {
        if (!$implementation instanceof BiometricInterface) {
            throw new InvalidArgumentException("La implementación debe implementar BiometricInterface");
        }
        $this->biometricImpl = $implementation;
    }

    // Métodos legacy para compatibilidad hacia atrás

    /**
     * Registra un fallo de verificación biométrica
     */
    public function logVerificationFailure($dispositivoId, $datosBiometricos) {
        $this->biometricImpl->logEvent(
            $dispositivoId,
            'verificacion',
            'fallido',
            'Verificación biométrica fallida',
            [
                'tipo_biometria' => $datosBiometricos['type'],
                'calidad' => $datosBiometricos['quality_score'],
                'motivo' => 'Empleado no identificado'
            ]
        );
    }

    /**
     * Registra un error del dispositivo
     */
    public function logDeviceError($dispositivoId, $operacion, $mensajeError) {
        $this->biometricImpl->logEvent(
            $dispositivoId,
            'error',
            'error',
            "Error en $operacion: $mensajeError",
            [
                'operacion' => $operacion,
                'error_message' => $mensajeError
            ]
        );
    }

    /**
     * Obtiene logs de un dispositivo específico
     */
    public function getDeviceLogs($dispositivoId, $limit = 50) {
        return $this->biometricImpl->getDeviceLogs($dispositivoId, $limit);
    }

    /**
     * Obtiene estadísticas biométricas por dispositivo
     */
    public function getBiometricStats($fechaInicio = null, $fechaFin = null) {
        return $this->biometricImpl->getBiometricStats($fechaInicio, $fechaFin);
    }

    /**
     * Crear datos biométricos simulados para empleados existentes
     * @deprecated Usar la implementación biométrica específica
     */
    public function crearDatosBiometricosSimulados() {
        // Este método se mantiene por compatibilidad pero delega a la implementación
        // La implementación de simulación lo maneja internamente
        return true;
    }

    /**
     * Registra la huella dactilar de un empleado en el dispositivo
     * @param int $deviceId ID del dispositivo
     * @param int $employeeId ID del empleado
     * @param string $fingerprintData Datos de la huella
     * @return bool True si el registro fue exitoso
     */
    public function registerEmployeeFingerprint($deviceId, $employeeId, $fingerprintData) {
        return $this->biometricImpl->registerEmployeeFingerprint($deviceId, $employeeId, $fingerprintData);
    }

    /**
     * Captura una huella dactilar del dispositivo
     * @param int $deviceId ID del dispositivo
     * @return string|null Datos de la huella capturada o null si falla
     */
    public function captureFingerprint($deviceId) {
        return $this->biometricImpl->captureFingerprint($deviceId);
    }

    /**
     * Elimina un empleado del dispositivo
     * @param int $deviceId ID del dispositivo
     * @param int $employeeId ID del empleado
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteEmployeeFromDevice($deviceId, $employeeId) {
        return $this->biometricImpl->deleteEmployeeFromDevice($deviceId, $employeeId);
    }

    /**
     * Obtiene la configuración actual de un dispositivo
     * @param int $deviceId ID del dispositivo
     * @return array Configuración del dispositivo
     */
    public function getDeviceConfig($deviceId) {
        return $this->biometricImpl->getDeviceConfig($deviceId);
    }

    /**
     * Sincroniza empleados con el dispositivo
     * @param int $deviceId ID del dispositivo
     * @param array $employees Lista de empleados a sincronizar
     * @return bool True si la sincronización fue exitosa
     */
    public function syncEmployees($deviceId, $employees) {
        return $this->biometricImpl->syncEmployees($deviceId, $employees);
    }

    /**
     * Obtiene empleados activos para sincronización
     * @return array Lista de empleados activos
     */
    public function getEmpleadosActivos() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT id, nombre, apellido, rfc, curp, huella_dactilar, foto_cara
            FROM empleados
            WHERE activo = 1
            ORDER BY nombre, apellido
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        // Descifrar huellas antes de devolver
        require_once __DIR__ . '/../helpers/Encryption.php';
        foreach ($rows as &$r) {
            if (!empty($r['huella_dactilar'])) {
                $r['huella_dactilar'] = Encryption::decrypt($r['huella_dactilar']);
            }
        }
        return $rows;
    }
}
