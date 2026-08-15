-- Migración: Registro de entrega de oficios de notas malas
-- Fecha: 2026-08-11
-- Cambios: columnas de entrega en documentos_generados (módulo marca de entrega)

-- Marca binaria de entrega (0 = no entregado, 1 = entregado)
ALTER TABLE documentos_generados ADD COLUMN IF NOT EXISTS entregado TINYINT(1) NOT NULL DEFAULT 0;

-- Fecha/hora en que se entregó el oficio al empleado
ALTER TABLE documentos_generados ADD COLUMN IF NOT EXISTS fecha_entrega DATETIME NULL DEFAULT NULL;

-- Usuario del sistema que registró la entrega
ALTER TABLE documentos_generados ADD COLUMN IF NOT EXISTS entregado_por INT(11) NULL DEFAULT NULL;
