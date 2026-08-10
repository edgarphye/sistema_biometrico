<?php
require_once 'BiometricInterface.php';
require_once 'BiometricSimulation.php';
require_once 'BiometricSDK.php';
require_once 'ZKTecoSDK.php';

/**
 * Factory para crear instancias de implementaciones biométricas
 * Permite cambiar fácilmente entre simulación y SDK real
 */
class BiometricFactory
{
    private static $instance = null;
    private $config;

    private function __construct()
    {
        $this->config = [
            'mode' => getenv('BIOMETRIC_MODE') ?: (defined('BIOMETRIC_MODE') ? BIOMETRIC_MODE : 'sdk'), // 'simulation' o 'sdk'
            'sdk_class' => getenv('BIOMETRIC_SDK_CLASS') ?: 'ZKTecoSDK',
            'debug' => getenv('BIOMETRIC_DEBUG') ?: false
        ];
    }

    /**
     * Obtiene la instancia singleton del factory
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Crea una instancia de la implementación biométrica configurada
     */
    public function createBiometric(array $config = []): BiometricInterface
    {
        $mergedConfig = array_merge($this->config, $config);

        switch ($mergedConfig['mode']) {
            case 'simulation':
                return new BiometricSimulation();

            case 'sdk':
                return $this->createSDKInstance($mergedConfig);

            default:
                throw new InvalidArgumentException("Modo biométrico desconocido: {$mergedConfig['mode']}");
        }
    }

    /**
     * Crea instancia del SDK específico
     */
    private function createSDKInstance(array $config): BiometricInterface
    {
        $sdkClass = $config['sdk_class'];

        if (!class_exists($sdkClass)) {
            throw new RuntimeException("Clase SDK no encontrada: $sdkClass");
        }

        if (!is_subclass_of($sdkClass, BiometricSDK::class)) {
            throw new RuntimeException("La clase $sdkClass debe extender BiometricSDK");
        }

        return new $sdkClass($config);
    }

    /**
     * Verifica si el modo SDK está disponible
     */
    public function isSDKAvailable(): bool
    {
        if ($this->config['mode'] !== 'sdk') {
            return false;
        }

        try {
            $instance = $this->createBiometric();
            return $instance->isAvailable();
        } catch (Exception $e) {
            error_log("Error verificando disponibilidad del SDK: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene información sobre la implementación actual
     */
    public function getImplementationInfo(): array
    {
        try {
            $instance = $this->createBiometric();
            return $instance->getImplementationInfo();
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'name' => 'Unknown',
                'version' => '0.0.0',
                'description' => 'Error creando instancia: ' . $e->getMessage(),
                'capabilities' => [],
                'max_devices' => 0
            ];
        }
    }

    /**
     * Cambia el modo de implementación
     */
    public function setMode(string $mode): void
    {
        if (!in_array($mode, ['simulation', 'sdk'])) {
            throw new InvalidArgumentException("Modo inválido: $mode");
        }

        $this->config['mode'] = $mode;
        putenv("BIOMETRIC_MODE=$mode");
    }

    /**
     * Configura la clase SDK a usar
     */
    public function setSDKClass(string $sdkClass): void
    {
        $this->config['sdk_class'] = $sdkClass;
        putenv("BIOMETRIC_SDK_CLASS=$sdkClass");
    }

    /**
     * Habilita/desabilita modo debug
     */
    public function setDebug(bool $debug): void
    {
        $this->config['debug'] = $debug;
        putenv("BIOMETRIC_DEBUG=" . ($debug ? '1' : '0'));
    }
}
