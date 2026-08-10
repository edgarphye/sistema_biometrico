<?php
// Configuración de timezone - Forzar zona horaria de México
date_default_timezone_set('America/Mexico_City');

// Configuración de sesiones - usar directorio escribible
ini_set('session.save_path', '/tmp');
ini_set('session.cookie_path', '/');

// Configuración de la base de datos
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'root'); // Por defecto sin contraseña
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'sistema_biometrico');

// Opciones PDO para compatibilidad con MySQL 8.0+
if (!defined('DB_OPTIONS')) define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
]);

// Configuración de la aplicación
if (!defined('APP_NAME')) define('APP_NAME', getenv('APP_NAME') ?: 'Sistema Biométrico');
if (!defined('APP_VERSION')) define('APP_VERSION', getenv('APP_VERSION') ?: '2.0');

//BASE_URL - Configuración fija para el servidor
if (!defined('BASE_URL')) {
    // Detectar automáticamente el path base para soportar diferentes entornos
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $basePath = dirname($scriptName);
    $basePath = str_replace('\\', '/', $basePath);
    $basePath = ($basePath === '/') ? '' : rtrim($basePath, '/');
    
    define('BASE_URL', $basePath);
}

// Crear directorio de logs si no existe
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Configurar manejo de errores PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/php_errors_' . date('Y-m-d') . '.log';
    
    $tipos = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $tipo = $tipos[$errno] ?? 'UNKNOWN';
    $mensaje = "[$tipo] $errstr en $errfile línea $errline";
    
    $logEntry = sprintf(
        "[%s] %s\nURI: %s\n%s\n",
        date('Y-m-d H:i:s'),
        $mensaje,
        $_SERVER['REQUEST_URI'] ?? 'N/A',
        str_repeat('-', 80)
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return true;
});

// Manejar excepciones no capturadas
set_exception_handler(function($exception) {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/php_errors_' . date('Y-m-d') . '.log';
    
    $logEntry = sprintf(
        "[%s] [EXCEPTION] %s\nMensaje: %s\nURI: %s\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($exception),
        $exception->getMessage(),
        $_SERVER['REQUEST_URI'] ?? 'N/A',
        $exception->getTraceAsString(),
        str_repeat('-', 80)
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
});

// Capturar errores fatales en shutdown
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $logDir = __DIR__ . '/logs';
        $logFile = $logDir . '/php_errors_' . date('Y-m-d') . '.log';
        
        $tipos = [
            E_ERROR => 'FATAL_ERROR',
            E_PARSE => 'PARSE_ERROR',
            E_CORE_ERROR => 'CORE_ERROR',
            E_COMPILE_ERROR => 'COMPILE_ERROR'
        ];
        
        $tipo = $tipos[$error['type']] ?? 'UNKNOWN_FATAL';
        $mensaje = "[$tipo] {$error['message']} en {$error['file']} línea {$error['line']}";
        
        $logEntry = sprintf(
            "[%s] %s\nURI: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $mensaje,
            $_SERVER['REQUEST_URI'] ?? 'N/A',
            str_repeat('-', 80)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
});

// Configuración de biometría (CKTeco)
// Detectar automáticamente el host para compatibilidad localhost/127.0.0.1
$defaultBiometricUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ':8080/api';
if (!defined('BIOMETRIC_API_URL')) define('BIOMETRIC_API_URL', getenv('BIOMETRIC_API_URL') ?: $defaultBiometricUrl); // URL adaptable al host actual
if (!defined('BIOMETRIC_API_KEY')) define('BIOMETRIC_API_KEY', getenv('BIOMETRIC_API_KEY') ?: 'dev_key_12345'); // Clave de desarrollo
if (!defined('BIOMETRIC_SIMULATION')) define('BIOMETRIC_SIMULATION', getenv('BIOMETRIC_SIMULATION') ?: 'true'); // Modo simulación activado
if (!defined('BIOMETRIC_MODE')) define('BIOMETRIC_MODE', getenv('BIOMETRIC_MODE') ?: 'sdk'); // 'sdk' (dispositivos reales) o 'simulation'

// Configuración de tolerancia
if (!defined('TOLERANCE_MINUTES')) define('TOLERANCE_MINUTES', intval(getenv('TOLERANCE_MINUTES') ?: 10));

// Configuración de seguridad
// Se recomienda establecer ENCRYPTION_KEY en las variables de entorno del servidor.
$envKey = getenv('ENCRYPTION_KEY');
if (!defined('ENCRYPTION_KEY')) {
    if ($envKey !== false && $envKey !== '') {
        define('ENCRYPTION_KEY', $envKey);
    } else {
        // Clave de cifrado por defecto para desarrollo
        define('ENCRYPTION_KEY', 'sistema_biometrico_zkteco_2025_key_secure_32bytes');
        // En producción, configure: ENCRYPTION_KEY=tu_clave_segura_32_caracteres
    }
}

// Configuración de logging
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
if (!defined('LOG_FILE')) define('LOG_FILE', __DIR__ . '/logs/biometric_system.log');
if (!defined('LOG_MAX_SIZE')) define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
if (!defined('LOG_ROTATE_COUNT')) define('LOG_ROTATE_COUNT', 5);

// Configuración biométrica avanzada
if (!defined('BIOMETRIC_TIMEOUT')) define('BIOMETRIC_TIMEOUT', 30); // Timeout en segundos para operaciones biométricas
if (!defined('BIOMETRIC_QUALITY_THRESHOLD')) define('BIOMETRIC_QUALITY_THRESHOLD', 75); // Umbral mínimo de calidad para verificación
if (!defined('BIOMETRIC_MAX_ATTEMPTS')) define('BIOMETRIC_MAX_ATTEMPTS', 3); // Máximo número de intentos por verificación
if (!defined('BIOMETRIC_CACHE_TTL')) define('BIOMETRIC_CACHE_TTL', 3600); // TTL para cache de templates biométricos (1 hora)
?>
