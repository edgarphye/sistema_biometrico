<?php
require_once 'config.php';

try {
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $db   = defined('DB_NAME') ? DB_NAME : 'sistema_biometrico';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : 'root';
    
    echo "Conectando a $db en $host...\n";
    
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $sql = "CREATE TABLE IF NOT EXISTS `zkteco_procesamiento_logs` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `dispositivo_id` INT(11) DEFAULT NULL,
      `fecha_ejecucion` DATETIME NOT NULL,
      `registros_procesados` INT(11) DEFAULT 0,
      `estado` ENUM('exito','error','advertencia','procesando') DEFAULT 'procesando',
      `detalles` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `sede` TEXT DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_dispositivo` (`dispositivo_id`),
      KEY `idx_fecha` (`fecha_ejecucion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "✅ Tabla 'zkteco_procesamiento_logs' verificada.\n";

    // Verificar si existe la columna archivo_nombre
    $stmt = $pdo->query("SHOW COLUMNS FROM `zkteco_procesamiento_logs` LIKE 'archivo_nombre'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `zkteco_procesamiento_logs` ADD COLUMN `archivo_nombre` VARCHAR(255) NULL AFTER `sede`");
        echo "✅ Columna 'archivo_nombre' agregada correctamente.\n";
    }

    // Verificar si existe la columna archivo_tamano
    $stmt = $pdo->query("SHOW COLUMNS FROM `zkteco_procesamiento_logs` LIKE 'archivo_tamano'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `zkteco_procesamiento_logs` ADD COLUMN `archivo_tamano` VARCHAR(50) NULL AFTER `archivo_nombre`");
        echo "✅ Columna 'archivo_tamano' agregada correctamente.\n";
    }

    // Verificar si existe la columna archivo_sha256
    $stmt = $pdo->query("SHOW COLUMNS FROM `zkteco_procesamiento_logs` LIKE 'archivo_sha256'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `zkteco_procesamiento_logs` ADD COLUMN `archivo_sha256` VARCHAR(64) NULL AFTER `archivo_tamano`");
        echo "✅ Columna 'archivo_sha256' agregada correctamente.\n";
    }

    // Verificar si existe la columna creado_at (compatibilidad)
    $stmt = $pdo->query("SHOW COLUMNS FROM `zkteco_procesamiento_logs` LIKE 'creado_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `zkteco_procesamiento_logs` ADD COLUMN `creado_at` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`");
        echo "✅ Columna 'creado_at' agregada correctamente.\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>