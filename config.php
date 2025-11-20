<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Cambia esto si tienes contraseña
define('DB_NAME', 'sistema_biometrico');

// Opciones PDO para compatibilidad con MySQL 8.0+
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Configuración de la aplicación
define('APP_NAME', getenv('APP_NAME') ?: 'Sistema Biométrico');
define('APP_VERSION', getenv('APP_VERSION') ?: '1.0');
define('BASE_URL', getenv('BASE_URL') ?: '/sistema_biometrico');

// Configuración de biometría (CKTeco)
define('BIOMETRIC_API_URL', getenv('BIOMETRIC_API_URL') ?: 'https://api.zkteco.com'); // URL de ejemplo, ajustar según documentación real
define('BIOMETRIC_API_KEY', getenv('BIOMETRIC_API_KEY') ?: '');

// Configuración de tolerancia
define('TOLERANCE_MINUTES', intval(getenv('TOLERANCE_MINUTES') ?: 10));

// Configuración de seguridad
// Se recomienda establecer ENCRYPTION_KEY en las variables de entorno del servidor.
$envKey = getenv('ENCRYPTION_KEY');
if ($envKey !== false && $envKey !== '') {
    define('ENCRYPTION_KEY', $envKey);
} else {
    // Mantener vacío para deshabilitar cifrado hasta que se configure la clave
    define('ENCRYPTION_KEY', '');
    error_log('[config] ADVERTENCIA: ENCRYPTION_KEY no está definida. Configure ENCRYPTION_KEY en las variables de entorno para habilitar cifrado de datos biométricos.');
}

// Configuración de logging
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/logs/biometric_system.log');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_ROTATE_COUNT', 5);

// Configuración biométrica avanzada
define('BIOMETRIC_TIMEOUT', 30); // Timeout en segundos para operaciones biométricas
define('BIOMETRIC_QUALITY_THRESHOLD', 75); // Umbral mínimo de calidad para verificación
define('BIOMETRIC_MAX_ATTEMPTS', 3); // Máximo número de intentos por verificación
define('BIOMETRIC_CACHE_TTL', 3600); // TTL para cache de templates biométricos (1 hora)
?>
