<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Database.php';

echo "Iniciando verificación y creación de tablas ZKTeco...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // 1. Tabla zkteco_procesamiento_logs
    echo "Verificando tabla 'zkteco_procesamiento_logs'...\n";
    $sql = "
        CREATE TABLE IF NOT EXISTS zkteco_procesamiento_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            archivo_nombre VARCHAR(255) NOT NULL,
            archivo_tamano BIGINT NOT NULL,
            archivo_sha256 VARCHAR(64) NOT NULL,
            formato_detectado VARCHAR(50) NULL,
            registros_leidos INT DEFAULT 0,
            registros_procesados INT DEFAULT 0,
            errores_count INT DEFAULT 0,
            exito TINYINT(1) DEFAULT 0,
            errores TEXT NULL,
            metadata JSON NULL,
            creado_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (creado_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "✅ Tabla 'zkteco_procesamiento_logs' verificada.\n";

    // 2. Tabla zkteo_empleado_mapeo
    echo "Verificando tabla 'zkteo_empleado_mapeo'...\n";
    $sql = "
        CREATE TABLE IF NOT EXISTS zkteo_empleado_mapeo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            zkteo_id VARCHAR(50) NOT NULL COMMENT 'ID original del dispositivo ZKTeco',
            empleado_id INT NULL COMMENT 'ID del empleado en sistema',
            numero_empleado VARCHAR(50) NULL COMMENT 'Número de empleado en sistema',
            nombre_empleado VARCHAR(200) NULL COMMENT 'Nombre completo del empleado',
            rfc VARCHAR(20) NULL COMMENT 'RFC del empleado',
            area VARCHAR(100) NULL COMMENT 'Área del empleado',
            metodo_creacion VARCHAR(50) NULL COMMENT 'manual, automatico, similitud, temporal',
            notas TEXT NULL,
            fecha_mapeo TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del mapeo',
            fecha_modificacion TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE COMMENT 'Mapeo activo',
            INDEX (zkteo_id),
            INDEX (empleado_id),
            INDEX (numero_empleado),
            INDEX (activo),
            UNIQUE KEY uk_zkteo_empleado (zkteo_id, empleado_id, activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Tabla de mapeo entre IDs ZKTeco y empleados del sistema';
    ";
    $pdo->exec($sql);
    echo "✅ Tabla 'zkteo_empleado_mapeo' verificada.\n";

    // 3. Tabla zkteco_formatos
    echo "Verificando tabla 'zkteco_formatos'...\n";
    $sql = "
        CREATE TABLE IF NOT EXISTS zkteco_formatos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo VARCHAR(50) NOT NULL DEFAULT 'importacion_manual',
            formato VARCHAR(50) NOT NULL,
            conteo_campos INT NOT NULL,
            confianza INT DEFAULT 0,
            mapa_tipos JSON NULL,
            veces_usado INT DEFAULT 1,
            ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP,
            creado_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_formato (dispositivo, formato, conteo_campos)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "✅ Tabla 'zkteco_formatos' verificada.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>