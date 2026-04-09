<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Reparando tabla zkteco_procesamiento_logs (v2)...\n";
    
    // 1. Eliminar tabla si existe para asegurar estructura limpia
    $pdo->exec("DROP TABLE IF EXISTS `zkteco_procesamiento_logs`");
    echo "Tabla anterior eliminada.\n";
    
    // 2. Crear tabla con TODAS las columnas necesarias
    $sql = "CREATE TABLE `zkteco_procesamiento_logs` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `dispositivo_id` INT(11) DEFAULT NULL,
      `fecha_ejecucion` DATETIME NOT NULL,
      `registros_procesados` INT(11) DEFAULT 0,
      `estado` ENUM('exito','error','advertencia','procesando') DEFAULT 'procesando',
      `detalles` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `sede` TEXT DEFAULT NULL,
      `archivo_nombre` VARCHAR(255) NULL,
      `archivo_tamano` VARCHAR(50) NULL,
      `archivo_sha256` VARCHAR(64) NULL,
      `creado_at` TIMESTAMP NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_dispositivo` (`dispositivo_id`),
      KEY `idx_fecha` (`fecha_ejecucion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "✅ Tabla creada con estructura correcta.\n";
    
    // 3. Insertar registro de prueba
    $stmt = $pdo->prepare("INSERT INTO zkteco_procesamiento_logs (fecha_ejecucion, registros_procesados, estado, detalles, sede, archivo_nombre, archivo_tamano, archivo_sha256) VALUES (NOW(), 0, 'exito', 'Sistema inicializado correctamente', 'Principal', 'system_init.log', '0 KB', NULL)");
    $stmt->execute();
    echo "✅ Registro inicial insertado.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>