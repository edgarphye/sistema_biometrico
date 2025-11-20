<?php

/**
 * Excepción base para errores biométricos
 */
class BiometricException extends Exception
{
    protected $errorCode;
    protected $context;

    public function __construct(string $message, string $errorCode = 'BIOMETRIC_ERROR', array $context = [], int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }
}

/**
 * Excepción para errores de dispositivo
 */
class DeviceException extends BiometricException
{
    public function __construct(string $message, int $deviceId = null, array $context = [])
    {
        $errorCode = 'DEVICE_ERROR';
        if ($deviceId !== null) {
            $context['device_id'] = $deviceId;
        }
        parent::__construct($message, $errorCode, $context);
    }
}

/**
 * Excepción para errores de conexión
 */
class ConnectionException extends DeviceException
{
    public function __construct(string $message, int $deviceId = null, array $context = [])
    {
        $errorCode = 'CONNECTION_ERROR';
        parent::__construct($message, $deviceId, $context);
    }
}

/**
 * Excepción para errores de verificación
 */
class VerificationException extends BiometricException
{
    public function __construct(string $message, string $type = null, array $context = [])
    {
        $errorCode = 'VERIFICATION_ERROR';
        if ($type !== null) {
            $context['verification_type'] = $type;
        }
        parent::__construct($message, $errorCode, $context);
    }
}

/**
 * Excepción para errores de calidad de datos
 */
class QualityException extends BiometricException
{
    public function __construct(string $message, int $quality = null, array $context = [])
    {
        $errorCode = 'QUALITY_ERROR';
        if ($quality !== null) {
            $context['quality_score'] = $quality;
        }
        parent::__construct($message, $errorCode, $context);
    }
}

/**
 * Excepción para errores de SDK
 */
class SDKException extends BiometricException
{
    public function __construct(string $message, string $sdkName = null, array $context = [])
    {
        $errorCode = 'SDK_ERROR';
        if ($sdkName !== null) {
            $context['sdk_name'] = $sdkName;
        }
        parent::__construct($message, $errorCode, $context);
    }
}
