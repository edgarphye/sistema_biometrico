-- Tabla para almacenar huellas dactilares de empleados del checador ZKTeco MB360
CREATE TABLE IF NOT EXISTS `huellas_empleados` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `empleado_id` int(11) NOT NULL,
    `zk_empleado_id` int(11) NOT NULL COMMENT 'ID del empleado en el dispositivo ZKTeco',
    `indice_huella` int(2) NOT NULL DEFAULT 0 COMMENT 'Índice de la huella (0-9, hasta 10 huellas por empleado)',
    `huella_template` blob NOT NULL COMMENT 'Plantilla de huella dactilar (formato ZKTeco)',
    `calidad_huella` tinyint(3) DEFAULT NULL COMMENT 'Calidad de la huella (0-100)',
    `tipo_huella` enum('dedo_indice_derecho','dedo_indice_izquierdo','dedo_pulgar_derecho','dedo_pulgar_izquierdo','dedo_medio_derecho','dedo_medio_izquierdo','dedo_anular_derecho','dedo_anular_izquierdo','dedo_menique_derecho','dedo_menique_izquierdo') DEFAULT NULL,
    `dispositivo_id` int(11) DEFAULT NULL COMMENT 'ID del dispositivo donde se capturó',
    `dispositivo_serial` varchar(50) DEFAULT NULL COMMENT 'Número de serie del dispositivo',
    `fecha_captura` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_sincronizacion` timestamp NULL DEFAULT NULL COMMENT 'Fecha de sincronización con el dispositivo',
    `estado` enum('activo','inactivo','error','sincronizando') DEFAULT 'activo',
    `intento_captura` int(11) DEFAULT 0 COMMENT 'Número de intentos de captura',
    `datos_adicionales` json DEFAULT NULL COMMENT 'Datos adicionales en formato JSON',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_empleado_huella` (`empleado_id`, `indice_huella`),
    UNIQUE KEY `unique_zk_empleado_huella` (`zk_empleado_id`, `indice_huella`),
    KEY `idx_empleado_id` (`empleado_id`),
    KEY `idx_zk_empleado_id` (`zk_empleado_id`),
    KEY `idx_dispositivo_id` (`dispositivo_id`),
    KEY `idx_estado` (`estado`),
    KEY `idx_fecha_captura` (`fecha_captura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla para almacenar huellas dactilares de empleados en dispositivos ZKTeco';

-- Tabla para controlar el proceso de enrolamiento de huellas
CREATE TABLE IF NOT EXISTS `proceso_enrolamiento` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `empleado_id` int(11) NOT NULL,
    `zk_empleado_id` int(11) NOT NULL,
    `dispositivo_id` int(11) NOT NULL,
    `estado_proceso` enum('iniciado','capturando','completado','error','cancelado') DEFAULT 'iniciado',
    `paso_actual` int(11) DEFAULT 1 COMMENT 'Paso actual del proceso de enrolamiento',
    `total_pasos` int(11) DEFAULT 3 COMMENT 'Total de pasos para el enrolamiento',
    `huellas_capturadas` int(11) DEFAULT 0 COMMENT 'Número de huellas capturadas',
    `mensaje_estado` text DEFAULT NULL,
    `error_detalle` text DEFAULT NULL,
    `fecha_inicio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_completado` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_empleado_id` (`empleado_id`),
    KEY `idx_zk_empleado_id` (`zk_empleado_id`),
    KEY `idx_dispositivo_id` (`dispositivo_id`),
    KEY `idx_estado_proceso` (`estado_proceso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control del proceso de enrolamiento de huellas';

-- Tabla para logs de comunicación con dispositivos ZKTeco
CREATE TABLE IF NOT EXISTS `logs_dispositivo_zk` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `dispositivo_id` int(11) DEFAULT NULL,
    `dispositivo_serial` varchar(50) DEFAULT NULL,
    `tipo_operacion` enum('conexion','desconexion','enrolamiento','sincronizacion','lectura','escritura','error') NOT NULL,
    `empleado_id` int(11) DEFAULT NULL,
    `zk_empleado_id` int(11) DEFAULT NULL,
    `comando` varchar(100) DEFAULT NULL,
    `respuesta` text DEFAULT NULL,
    `estado` enum('exito','error','timeout') NOT NULL,
    `codigo_error` int(11) DEFAULT NULL,
    `mensaje_error` text DEFAULT NULL,
    `datos_enviados` blob DEFAULT NULL,
    `datos_recibidos` blob DEFAULT NULL,
    `tiempo_procesamiento` decimal(8,3) DEFAULT NULL COMMENT 'Tiempo en segundos',
    `fecha_operacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_origen` varchar(45) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dispositivo_id` (`dispositivo_id`),
    KEY `idx_tipo_operacion` (`tipo_operacion`),
    KEY `idx_estado` (`estado`),
    KEY `idx_fecha_operacion` (`fecha_operacion`),
    KEY `idx_empleado_id` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de comunicación con dispositivos ZKTeco';

-- Tabla de huellas ya está lista para usar