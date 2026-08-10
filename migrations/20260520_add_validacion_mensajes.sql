-- ============================================================
-- MIGRACIÓN: Sistema de Conversación Bidireccional en Validaciones
-- Fecha: 2026-05-20
-- ============================================================

-- 1. Tabla de mensajes para conversación jefe↔empleado
CREATE TABLE IF NOT EXISTS `validacion_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `validacion_id` int(11) NOT NULL,
  `remitente_tipo` enum('jefe','empleado','sistema') NOT NULL DEFAULT 'empleado',
  `remitente_id` int(11) NOT NULL COMMENT 'ID del usuario que envía',
  `remitente_nombre` varchar(255) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `tipo_mensaje` enum('info_request','info_response','decision','notificacion','sistema') NOT NULL DEFAULT 'info_response',
  `archivo_adjunto` varchar(500) DEFAULT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `leido_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_validacion_id` (`validacion_id`),
  KEY `idx_remitente` (`remitente_tipo`, `remitente_id`),
  KEY `idx_leido` (`leido`),
  CONSTRAINT `fk_mensaje_validacion` FOREIGN KEY (`validacion_id`) REFERENCES `validaciones_jefe` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Agregar columna para seguimiento de conversación en validaciones_jefe
ALTER TABLE `validaciones_jefe`
  ADD COLUMN IF NOT EXISTS `ultimo_mensaje_id` int(11) DEFAULT NULL AFTER `fecha_validacion`,
  ADD COLUMN IF NOT EXISTS `esperando_respuesta_de` enum('jefe','empleado') DEFAULT NULL AFTER `ultimo_mensaje_id`,
  ADD COLUMN IF NOT EXISTS `notificacion_leida_empleado` tinyint(1) NOT NULL DEFAULT 0 AFTER `esperando_respuesta_de`,
  ADD COLUMN IF NOT EXISTS `notificacion_leida_jefe` tinyint(1) NOT NULL DEFAULT 0 AFTER `notificacion_leida_empleado`,
  ADD INDEX IF NOT EXISTS `idx_esperando_respuesta` (`esperando_respuesta_de`);

-- 3. Tabla para almacenar adjuntos temporales antes de enviar
CREATE TABLE IF NOT EXISTS `validacion_adjuntos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensaje_id` int(11) DEFAULT NULL,
  `validacion_id` int(11) NOT NULL,
  `archivo_original` varchar(255) NOT NULL,
  `archivo_path` varchar(500) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamano` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mensaje` (`mensaje_id`),
  KEY `idx_validacion` (`validacion_id`),
  CONSTRAINT `fk_adjunto_validacion` FOREIGN KEY (`validacion_id`) REFERENCES `validaciones_jefe` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
