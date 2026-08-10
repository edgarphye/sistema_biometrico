-- Migración para agregar campos de seguimiento de modificaciones
-- Tabla retardos
ALTER TABLE retardos 
ADD COLUMN modificado_por INT NULL,
ADD COLUMN fecha_modificacion TIMESTAMP NULL;

-- Tabla asistencia - agregar columna tipo_justificacion_id si no existe
ALTER TABLE asistencia 
ADD COLUMN tipo_justificacion_id INT NULL,
ADD COLUMN motivo_justificacion TEXT NULL,
ADD COLUMN modificado_por INT NULL,
ADD COLUMN fecha_modificacion TIMESTAMP NULL;

-- Tabla dias_economicos - agregar seguimiento
ALTER TABLE dias_economicos 
ADD COLUMN modalidad VARCHAR(1) NULL,
ADD COLUMN modificado_por INT NULL,
ADD COLUMN fecha_modificacion TIMESTAMP NULL,
ADD COLUMN validado_por INT NULL,
ADD COLUMN fecha_validacion TIMESTAMP NULL;

-- Tabla ausencias - agregar seguimiento
ALTER TABLE ausencias 
ADD COLUMN modificado_por INT NULL,
ADD COLUMN fecha_modificacion TIMESTAMP NULL,
ADD COLUMN validado_por INT NULL,
ADD COLUMN fecha_validacion TIMESTAMP NULL;