# Configuración de Base de Datos - Sistema Biométrico
# Importar este archivo para configurar la base de datos inicial

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS `sistema_biometrico` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sistema_biometrico`;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','gerente','empleado','rh') NOT NULL DEFAULT 'empleado',
  `empleado_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_empleado_id` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de empleados
CREATE TABLE IF NOT EXISTS `empleados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `curp` varchar(18) DEFAULT NULL,
  `area` varchar(100) NOT NULL,
  `jerarquia` enum('Empleado','Supervisor','Gerente','Director') NOT NULL DEFAULT 'Empleado',
  `sexo` enum('M','F','O') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `entidad_federativa` varchar(2) DEFAULT NULL,
  `foto_cara` varchar(255) DEFAULT NULL,
  `huella_dactilar` text DEFAULT NULL,
  `huella_template_id` varchar(100) DEFAULT NULL,
  `dispositivo_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfc` (`rfc`),
  UNIQUE KEY `curp` (`curp`),
  KEY `idx_area` (`area`),
  KEY `idx_jerarquia` (`jerarquia`),
  KEY `idx_activo` (`activo`),
  KEY `fk_dispositivo_id` (`dispositivo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de dispositivos biométricos
CREATE TABLE IF NOT EXISTS `dispositivos_biometricos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispositivo_id` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `puerto` int(11) NOT NULL DEFAULT 4370,
  `modelo` varchar(50) DEFAULT NULL,
  `ubicacion` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_sincronismo` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispositivo_id` (`dispositivo_id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de asistencia
CREATE TABLE IF NOT EXISTS `asistencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `dispositivo_id` int(11) DEFAULT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_biometria` varchar(20) DEFAULT NULL,
  `datos_biometricos` text DEFAULT NULL,
  `calidad_verificacion` int(11) DEFAULT NULL,
  `tiempo_procesamiento` decimal(10,3) DEFAULT NULL,
  `metadata_dispositivo` json DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `timestamp`),
  KEY `idx_dispositivo` (`dispositivo_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fecha` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de horarios_laborales
CREATE TABLE IF NOT EXISTS `horarios_laborales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `hora_entrada` time NOT NULL DEFAULT '09:00:00',
  `hora_salida` time NOT NULL DEFAULT '17:00:00',
  `tolerancia_minutos` int(11) NOT NULL DEFAULT 10,
  `descripcion` text DEFAULT NULL,
  `sede` varchar(100) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `debe_marcar_entrada` tinyint(1) NOT NULL DEFAULT 1,
  `debe_marcar_salida` tinyint(1) NOT NULL DEFAULT 1,
  `cuenta_dia_trabajo` tinyint(1) NOT NULL DEFAULT 1,
  `cuenta_minutos` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`),
  KEY `idx_sede` (`sede`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de retardos
CREATE TABLE IF NOT EXISTS `retardos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `minutos_retardo` int(11) NOT NULL,
  `tipo` enum('menor','mayor') NOT NULL DEFAULT 'menor',
  `justificado` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_justificacion_id` int(11) DEFAULT NULL,
  `motivo_justificacion` text DEFAULT NULL,
  `aprobado_por` int(11) DEFAULT NULL,
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `soporte` varchar(255) DEFAULT NULL,
  `horario_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `fecha`),
  KEY `idx_justificado` (`justificado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de comisiones
CREATE TABLE IF NOT EXISTS `comisiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_asignacion` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `tipo_comision` enum('viaticos','gastos_representacion','transporte','hospedaje','alimentacion','otros') NOT NULL DEFAULT 'otros',
  `requiere_aprobacion` tinyint(1) NOT NULL DEFAULT 1,
  `aprobado_por` int(11) DEFAULT NULL,
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `motivo_aprobacion` text DEFAULT NULL,
  `justificada` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `fecha_asignacion`),
  KEY `idx_aprobacion` (`aprobado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sanciones
CREATE TABLE IF NOT EXISTS `sanciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `tipo` enum('suspension','amonestacion','nota_mala','descuento') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `dias` int(11) NOT NULL DEFAULT 1,
  `motivo` text NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `fecha_inicio`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de ausencias
CREATE TABLE IF NOT EXISTS `ausencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `tipo` enum('medica','personal','vacaciones','otro') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` text DEFAULT NULL,
  `justificada` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_limite_justificacion` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fechas` (`empleado_id`, `fecha_inicio`, `fecha_fin`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_justificada` (`justificada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales

-- Insertar dispositivo biométrico por defecto
INSERT IGNORE INTO `dispositivos_biometricos` (`dispositivo_id`, `nombre`, `ip`, `puerto`, `modelo`, `ubicacion`) VALUES
('ZK0001', 'Dispositivo Principal', '192.168.1.100', 4370, 'ZKTeco MB360', 'Entrada Principal'),
('ZK0002', 'Dispositivo Secundario', '192.168.1.101', 4370, 'ZKTeco MB360', 'Salida Principal');

-- Insertar horarios por defecto
INSERT IGNORE INTO `horarios_laborales` (`nombre`, `hora_entrada`, `hora_salida`, `tolerancia_minutos`, `descripcion`) VALUES
('Estándar 8:00-17:00', '08:00:00', '17:00:00', 10, 'Horario laboral estándar de 8 horas'),
('Estándar 9:00-18:00', '09:00:00', '18:00:00', 10, 'Horario laboral estándar corrido'),
('Matutino 7:00-15:00', '07:00:00', '15:00:00', 10, 'Horario matutino temprano'),
('Vespertino 14:00-22:00', '14:00:00', '22:00:00', 10, 'Horario vespertino tardío'),
('Nocturno 22:00-06:00', '22:00:00', '06:00:00', 10, 'Horario nocturno');

-- Crear usuario administrador por defecto
-- Usuario: admin
-- Contraseña: admin123
INSERT IGNORE INTO `usuarios` (`username`, `email`, `password`, `rol`) VALUES
('admin', 'admin@sistema.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Crear usuario de recursos humanos por defecto
-- Usuario: rh
-- Contraseña: rh123
INSERT IGNORE INTO `usuarios` (`username`, `email`, `password`, `rol`) VALUES
('rh', 'rh@sistema.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'rh');

-- Insertar empleados de ejemplo
INSERT IGNORE INTO `empleados` (`nombre`, `apellido`, `rfc`, `curp`, `area`, `jerarquia`, `sexo`, `fecha_nacimiento`, `entidad_federativa`) VALUES
('Juan', 'Pérez García', 'PEGJ800101HDFXXX01', 'PEGJ800101HDFXXX01', 'Sistemas', 'Empleado', 'F', '1980-01-01', 'DF'),
('María', 'López Martínez', 'LOMM850215MDFXXX02', 'LOMM850215MDFXXX02', 'Recursos Humanos', 'Supervisor', 'M', '1985-02-15', 'DF'),
('Pedro', 'Sánchez Rodríguez', 'SARJ901230MDFXXX03', 'SARJ901230MDFXXX03', 'Contabilidad', 'Empleado', 'M', '1990-12-30', 'DF'),
('Ana', 'García Hernández', 'GAHA880715MDFXXX04', 'GAHA880715MDFXXX04', 'Ventas', 'Gerente', 'F', '1988-07-15', 'DF');

-- Asignar dispositivos a empleados
UPDATE `empleados` SET `dispositivo_id` = 1 WHERE `id` IN (1, 2, 3);
UPDATE `empleados` SET `dispositivo_id` = 2 WHERE `id` = 4;

-- Crear índices adicionales para mejor rendimiento
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_tipo ON asistencia(empleado_id, tipo);
CREATE INDEX IF NOT EXISTS idx_retardos_empleado_fecha ON retardos(empleado_id, fecha);
CREATE INDEX IF NOT EXISTS idx_comisiones_empleado_tipo ON comisiones(empleado_id, tipo_comision);
CREATE INDEX IF NOT EXISTS idx_sanciones_empleado_tipo ON sanciones(empleado_id, tipo);

-- Restricciones de claves foráneas
ALTER TABLE `usuarios` 
ADD CONSTRAINT `fk_usuario_empleado` 
FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `empleados` 
ADD CONSTRAINT `fk_empleado_dispositivo` 
FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_biometricos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `asistencia` 
ADD CONSTRAINT `fk_asistencia_empleado` 
FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `asistencia` 
ADD CONSTRAINT `fk_asistencia_dispositivo` 
FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_biometricos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `retardos` 
ADD CONSTRAINT `fk_retardo_empleado` 
FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `retardos` 
ADD CONSTRAINT `fk_retardo_horario` 
FOREIGN KEY (`horario_id`) REFERENCES `horarios_laborales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `comisiones` 
ADD CONSTRAINT `fk_comision_empleado` 
FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `comisiones` 
ADD CONSTRAINT `fk_comision_aprobador` 
FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `sanciones` 
ADD CONSTRAINT `fk_sancion_empleado` 
FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `sanciones` 
ADD CONSTRAINT `fk_sancion_creador` 
FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `sanciones` 
ADD CONSTRAINT `fk_sancion_modificador` 
FOREIGN KEY (`modified_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Restricciones de claves foráneas para tabla ausencias
ALTER TABLE `ausencias` 
ADD CONSTRAINT `fk_ausencia_empleado` 
FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;