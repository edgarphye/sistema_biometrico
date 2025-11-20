<?php

/**
 * Logger especializado para operaciones biométricas
 * Proporciona logging estructurado con niveles y contextos específicos
 */
class BiometricLogger
{
    private $logFile;
    private $minLevel;
    private $enabled;

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;

    private static $instance = null;
    private $levelNames = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];

    private function __construct()
    {
        $this->logFile = getenv('BIOMETRIC_LOG_FILE') ?: __DIR__ . '/../../../logs/biometric.log';
        $this->minLevel = intval(getenv('BIOMETRIC_LOG_LEVEL') ?: self::LEVEL_INFO);
        $this->enabled = getenv('BIOMETRIC_LOG_ENABLED') !== 'false';

        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log de debug
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log informativo
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log de advertencia
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log de error
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log crítico
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log de operación biométrica exitosa
     */
    public function biometricSuccess(string $operation, array $context = []): void
    {
        $this->info("Biometric operation successful: $operation", $context);
    }

    /**
     * Log de operación biométrica fallida
     */
    public function biometricFailure(string $operation, string $reason, array $context = []): void
    {
        $context['reason'] = $reason;
        $this->error("Biometric operation failed: $operation", $context);
    }

    /**
     * Log de conexión de dispositivo
     */
    public function deviceConnected(int $deviceId, array $context = []): void
    {
        $context['device_id'] = $deviceId;
        $this->info("Device connected", $context);
    }

    /**
     * Log de desconexión de dispositivo
     */
    public function deviceDisconnected(int $deviceId, array $context = []): void
    {
        $context['device_id'] = $deviceId;
        $this->warning("Device disconnected", $context);
    }

    /**
     * Log de verificación de identidad
     */
    public function identityVerification(string $type, bool $success, array $context = []): void
    {
        $context['verification_type'] = $type;
        $context['success'] = $success;

        if ($success) {
            $this->info("Identity verification successful", $context);
        } else {
            $this->warning("Identity verification failed", $context);
        }
    }

    /**
     * Log de rendimiento
     */
    public function performance(string $operation, float $duration, array $context = []): void
    {
        $context['duration_ms'] = round($duration * 1000, 2);
        $this->debug("Performance: $operation", $context);
    }

    /**
     * Log principal
     */
    private function log(int $level, string $message, array $context = []): void
    {
        if (!$this->enabled || $level < $this->minLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelName = $this->levelNames[$level] ?? 'UNKNOWN';

        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $levelName,
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        // Escribir al archivo
        if ($this->logFile) {
            @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        }

        // También enviar a error_log si es error o superior
        if ($level >= self::LEVEL_ERROR) {
            error_log("[$levelName] $message " . json_encode($context));
        }
    }

    /**
     * Obtiene estadísticas de logging
     */
    public function getStats(): array
    {
        if (!file_exists($this->logFile)) {
            return ['total_entries' => 0, 'file_size' => 0];
        }

        $fileSize = filesize($this->logFile);
        $lines = file($this->logFile);
        $totalEntries = count($lines);

        $levelCounts = [];
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if ($entry && isset($entry['level'])) {
                $levelCounts[$entry['level']] = ($levelCounts[$entry['level']] ?? 0) + 1;
            }
        }

        return [
            'total_entries' => $totalEntries,
            'file_size' => $fileSize,
            'file_size_human' => $this->formatBytes($fileSize),
            'level_counts' => $levelCounts,
            'last_modified' => date('Y-m-d H:i:s', filemtime($this->logFile))
        ];
    }

    /**
     * Limpia logs antiguos
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        if (!file_exists($this->logFile)) {
            return 0;
        }

        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $lines = file($this->logFile);
        $keptLines = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if ($entry && strtotime($entry['timestamp']) > $cutoffTime) {
                $keptLines[] = $line;
            }
        }

        $removedCount = count($lines) - count($keptLines);
        file_put_contents($this->logFile, implode('', $keptLines));

        return $removedCount;
    }

    /**
     * Formatea bytes a formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Busca entradas de log
     */
    public function search(array $filters = [], int $limit = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = array_reverse(file($this->logFile)); // Más recientes primero
        $results = [];

        foreach ($lines as $line) {
            if (count($results) >= $limit) {
                break;
            }

            $entry = json_decode($line, true);
            if (!$entry) {
                continue;
            }

            $matches = true;
            foreach ($filters as $key => $value) {
                if (!isset($entry[$key]) || $entry[$key] != $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $results[] = $entry;
            }
        }

        return $results;
    }
}
