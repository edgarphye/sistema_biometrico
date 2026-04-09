<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $sql = "
        CREATE TABLE IF NOT EXISTS `zkteco_procesamiento_logs` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `archivo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `archivo_tamano` int(11) NOT NULL,
          `archivo_sha256` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
          `creado_at` datetime NOT NULL,
          `formato_detectado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `registros_leidos` int(11) DEFAULT NULL,
          `registros_procesados` int(11) DEFAULT NULL,
          `errores_count` int(11) DEFAULT NULL,
          `exito` tinyint(1) DEFAULT NULL,
          `errores` text COLLATE utf8mb4_unicode_ci,
          `metadata` text COLLATE utf8mb4_unicode_ci,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `zk_empleado_mapeo` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `zk_empleado_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `empleado_id` int(11) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `zk_empleado_id` (`zk_empleado_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `zkteco_formatos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `dispositivo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
          `formato` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `conteo_campos` int(11) NOT NULL,
          `confianza` int(11) DEFAULT NULL,
          `mapa_tipos` text COLLATE utf8mb4_unicode_ci,
          `veces_usado` int(11) DEFAULT '1',
          `ultimo_uso` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);

    echo 'Missing tables created successfully.';

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
