<?php
/**
 * Bootstrap para PHPUnit Tests
 * Sistema Biométrico - Testing Framework Completo
 */

// Configuración de errores para testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/phpunit.log');

// Crear directorios necesarios si no existen
$testDirs = [
    __DIR__ . '/logs',
    __DIR__ . '/reports',
    __DIR__ . '/.phpunit.cache',
    __DIR__ . '/temp',
    __DIR__ . '/fixtures'
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Limpiar directorio temporal
$tempDir = __DIR__ . '/temp';
if (is_dir($tempDir)) {
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// Cargar configuración principal
require_once __DIR__ . '/../config.php';

// Cargar autoloader de Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Cargar clases principales del sistema
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../helpers/CacheManager.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

// Cargar clases de API
if (file_exists(__DIR__ . '/../api/ApiController.php')) {
    require_once __DIR__ . '/../api/ApiController.php';
}

// Configuración específica para testing
define('TESTING_ENVIRONMENT', true);
define('TEST_DB_NAME', 'sistema_biometrico_test');
define('TEST_BASE_URL', 'http://localhost:8000');

// Variables de entorno para testing
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'sistema_biometrico_test';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = 'root';
$_ENV['CACHE_DRIVER'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';
$_ENV['MAIL_DRIVER'] = 'array';
$_ENV['ENCRYPTION_KEY'] = 'test_encryption_key_32_characters!!';
$_ENV['BASE_URL'] = 'http://localhost:8000';
$_ENV['LOG_LEVEL'] = 'debug';
$_ENV['TIMEZONE'] = 'America/Mexico_City';

// Inicializar mock de logger
class MockLogger {
    public function emergency($message, array $context = []) {
        $this->log('EMERGENCY', $message, $context);
    }
    
    public function alert($message, array $context = []) {
        $this->log('ALERT', $message, $context);
    }
    
    public function critical($message, array $context = []) {
        $this->log('CRITICAL', $message, $context);
    }
    
    public function error($message, array $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, array $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function notice($message, array $context = []) {
        $this->log('NOTICE', $message, $context);
    }
    
    public function info($message, array $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function debug($message, array $context = []) {
        $this->log('DEBUG', $message, $context);
    }
    
    public function log($level, $message, array $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        error_log($logMessage, 3, __DIR__ . '/logs/phpunit.log');
    }
}

// Reemplazar logger global con mock
if (!isset($GLOBALS['logger'])) {
    $GLOBALS['logger'] = new MockLogger();
}

// Asegurar que no haya sesión activa para CLI tests
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Limpiar y resetear variables globales
$_POST = [];
$_GET = [];
$_REQUEST = [];
$_FILES = [];
$_COOKIE = [];
$_SESSION = [];

// Resetear headers
if (function_exists('headers_list')) {
    foreach (headers_list() as $header) {
        if (strpos($header, 'Set-Cookie') === 0) {
            header_remove('Set-Cookie');
        }
    }
}

// Configurar timezone
date_default_timezone_set('America/Mexico_City');

// Configurar límites de memoria para testing
ini_set('memory_limit', '512M');
set_time_limit(300); // 5 minutos para tests

// Funciones helper para testing
if (!function_exists('createTestDatabase')) {
    function createTestDatabase() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Crear base de datos de testing si no existe
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . TEST_DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Seleccionar base de datos de testing
            $pdo->exec("USE " . TEST_DB_NAME);
            
            // Ejecutar migraciones si existen
            $migrationFile = __DIR__ . '/../database/migrations.sql';
            if (file_exists($migrationFile)) {
                $sql = file_get_contents($migrationFile);
                $pdo->exec($sql);
            }
            
            return $pdo;
        } catch (Exception $e) {
            echo "Error creando base de datos de testing: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
}

if (!function_exists('cleanupTestDatabase')) {
    function cleanupTestDatabase() {
        try {
            $db = new Database();
            $pdo = $db->getConnection();
            
            // Limpiar todas las tablas
            $pdo->exec("USE " . TEST_DB_NAME);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            return true;
        } catch (Exception $e) {
            echo "Error limpiando base de datos de testing: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
}

if (!function_exists('getMockEmpleado')) {
    function getMockEmpleado($overrides = []) {
        return array_merge([
            'id' => rand(1, 1000),
            'nombre' => 'TestEmpleado' . rand(1, 100),
            'apellido' => 'TestApellido' . rand(1, 100),
            'rfc' => 'TEST' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT) . 'XX',
            'email' => 'test' . rand(1, 100) . '@test.com',
            'telefono' => '555' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'area' => 'Testing',
            'puesto' => 'Tester',
            'activo' => 1,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'foto_cara' => null,
            'foto_huella' => null
        ], $overrides);
    }
}

// Inicializar base de datos de testing
if (!defined('SKIP_DB_SETUP')) {
    // Usar configuración de testing real
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
    define('DB_NAME', 'sistema_biometrico_test');
    
    // createTestDatabase();
}

echo "Bootstrap de PHPUnit completado exitosamente" . PHP_EOL;
