-- Migración para tabla de dispositivos biométricos configurables por sede
-- Soporta hasta 100+ dispositivos con IPs individuales

CREATE TABLE IF NOT EXISTS dispositivos_biometricos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispositivo_id INT UNIQUE NOT NULL COMMENT 'ID único del dispositivo',
    nombre VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo del dispositivo',
    sede VARCHAR(100) NOT NULL COMMENT 'Nombre de la sede/sucursal',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Dirección IP del dispositivo (IPv4 o IPv6)',
    puerto INT DEFAULT 4370 COMMENT 'Puerto de conexión (ZKTeco usa 4370 por defecto)',
    tipo_dispositivo VARCHAR(50) DEFAULT 'ZKTeco' COMMENT 'Marca/tipo de dispositivo',
    modelo VARCHAR(50) COMMENT 'Modelo específico del dispositivo',
    firmware_version VARCHAR(20) COMMENT 'Versión del firmware',
    capacidades JSON COMMENT 'Capacidades del dispositivo: {"huella": true, "cara": true, "tarjeta": false}',
    activo BOOLEAN DEFAULT 1 COMMENT 'Estado del dispositivo (activo/inactivo)',
    fecha_instalacion DATE COMMENT 'Fecha de instalación del dispositivo',
    ultima_sincronizacion DATETIME COMMENT 'Última vez que se sincronizó con el servidor',
    configuracion_adicional JSON COMMENT 'Configuraciones adicionales específicas del dispositivo',
    notas TEXT COMMENT 'Notas adicionales sobre el dispositivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_sede (sede),
    INDEX idx_activo (activo),
    INDEX idx_ip_address (ip_address),
    
    CHECK (puerto > 0 AND puerto <= 65535)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo para 35 sedes
-- Ajustar según las sedes reales
INSERT INTO dispositivos_biometricos (dispositivo_id, nombre, sede, ip_address, puerto, capacidades, activo, fecha_instalacion) VALUES
(1, 'Biométrico Sede Central', 'Central', '192.168.1.100', 4370, '{"huella": true, "cara": true}', 1, '2024-01-15'),
(2, 'Biométrico Zona Norte', 'Norte', '192.168.2.100', 4370, '{"huella": true, "cara": true}', 1, '2024-01-20'),
(3, 'Biométrico Zona Sur', 'Sur', '192.168.3.100', 4370, '{"huella": true, "cara": true}', 1, '2024-01-25'),
(4, 'Biométrico Zona Este', 'Este', '192.168.4.100', 4370, '{"huella": true, "cara": false}', 1, '2024-02-01'),
(5, 'Biométrico Zona Oeste', 'Oeste', '192.168.5.100', 4370, '{"huella": true, "cara": true}', 1, '2024-02-05'),
(6, 'Biométrico Sucursal A1', 'Sucursal A1', '192.168.6.100', 4370, '{"huella": true, "cara": false}', 1, '2024-02-10'),
(7, 'Biométrico Sucursal A2', 'Sucursal A2', '192.168.7.100', 4370, '{"huella": true, "cara": true}', 1, '2024-02-15'),
(8, 'Biométrico Sucursal B1', 'Sucursal B1', '192.168.8.100', 4370, '{"huella": true, "cara": false}', 1, '2024-02-20'),
(9, 'Biométrico Sucursal B2', 'Sucursal B2', '192.168.9.100', 4370, '{"huella": true, "cara": true}', 1, '2024-02-25'),
(10, 'Biométrico Sucursal C1', 'Sucursal C1', '192.168.10.100', 4370, '{"huella": true, "cara": true}', 1, '2024-03-01'),
(11, 'Biométrico Sucursal C2', 'Sucursal C2', '192.168.11.100', 4370, '{"huella": true, "cara": false}', 1, '2024-03-05'),
(12, 'Biométrico Sucursal D1', 'Sucursal D1', '192.168.12.100', 4370, '{"huella": true, "cara": true}', 1, '2024-03-10'),
(13, 'Biométrico Sucursal D2', 'Sucursal D2', '192.168.13.100', 4370, '{"huella": true, "cara": false}', 1, '2024-03-15'),
(14, 'Biométrico Sucursal E1', 'Sucursal E1', '192.168.14.100', 4370, '{"huella": true, "cara": true}', 1, '2024-03-20'),
(15, 'Biométrico Sucursal E2', 'Sucursal E2', '192.168.15.100', 4370, '{"huella": true, "cara": true}', 1, '2024-03-25'),
(16, 'Biométrico Sucursal F1', 'Sucursal F1', '192.168.16.100', 4370, '{"huella": true, "cara": false}', 1, '2024-04-01'),
(17, 'Biométrico Sucursal F2', 'Sucursal F2', '192.168.17.100', 4370, '{"huella": true, "cara": true}', 1, '2024-04-05'),
(18, 'Biométrico Sucursal G1', 'Sucursal G1', '192.168.18.100', 4370, '{"huella": true, "cara": false}', 1, '2024-04-10'),
(19, 'Biométrico Sucursal G2', 'Sucursal G2', '192.168.19.100', 4370, '{"huella": true, "cara": true}', 1, '2024-04-15'),
(20, 'Biométrico Sucursal H1', 'Sucursal H1', '192.168.20.100', 4370, '{"huella": true, "cara": true}', 1, '2024-04-20'),
(21, 'Biométrico Sucursal H2', 'Sucursal H2', '192.168.21.100', 4370, '{"huella": true, "cara": false}', 1, '2024-04-25'),
(22, 'Biométrico Sucursal I1', 'Sucursal I1', '192.168.22.100', 4370, '{"huella": true, "cara": true}', 1, '2024-05-01'),
(23, 'Biométrico Sucursal I2', 'Sucursal I2', '192.168.23.100', 4370, '{"huella": true, "cara": false}', 1, '2024-05-05'),
(24, 'Biométrico Sucursal J1', 'Sucursal J1', '192.168.24.100', 4370, '{"huella": true, "cara": true}', 1, '2024-05-10'),
(25, 'Biométrico Sucursal J2', 'Sucursal J2', '192.168.25.100', 4370, '{"huella": true, "cara": true}', 1, '2024-05-15'),
(26, 'Biométrico Sucursal K1', 'Sucursal K1', '192.168.26.100', 4370, '{"huella": true, "cara": false}', 1, '2024-05-20'),
(27, 'Biométrico Sucursal K2', 'Sucursal K2', '192.168.27.100', 4370, '{"huella": true, "cara": true}', 1, '2024-05-25'),
(28, 'Biométrico Sucursal L1', 'Sucursal L1', '192.168.28.100', 4370, '{"huella": true, "cara": false}', 1, '2024-06-01'),
(29, 'Biométrico Sucursal L2', 'Sucursal L2', '192.168.29.100', 4370, '{"huella": true, "cara": true}', 1, '2024-06-05'),
(30, 'Biométrico Sucursal M1', 'Sucursal M1', '192.168.30.100', 4370, '{"huella": true, "cara": true}', 1, '2024-06-10'),
(31, 'Biométrico Sucursal M2', 'Sucursal M2', '192.168.31.100', 4370, '{"huella": true, "cara": false}', 1, '2024-06-15'),
(32, 'Biométrico Sucursal N1', 'Sucursal N1', '192.168.32.100', 4370, '{"huella": true, "cara": true}', 1, '2024-06-20'),
(33, 'Biométrico Sucursal N2', 'Sucursal N2', '192.168.33.100', 4370, '{"huella": true, "cara": false}', 1, '2024-06-25'),
(34, 'Biométrico Sucursal O1', 'Sucursal O1', '192.168.34.100', 4370, '{"huella": true, "cara": true}', 1, '2024-07-01'),
(35, 'Biométrico Sucursal O2', 'Sucursal O2', '192.168.35.100', 4370, '{"huella": true, "cara": true}', 1, '2024-07-05');
