-- Migration: Add audit columns to sanciones table
-- Date: 2025-11-27
-- Purpose: Add modified_by and fecha_modificacion columns for audit trail

ALTER TABLE sanciones
    ADD COLUMN IF NOT EXISTS modified_by INT NULL COMMENT 'ID del usuario que modificó el registro',
    ADD COLUMN IF NOT EXISTS fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última fecha de modificación',
    ADD FOREIGN KEY (modified_by) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Create index for audit queries
CREATE INDEX IF NOT EXISTS idx_sanciones_modified_by ON sanciones(modified_by);
CREATE INDEX IF NOT EXISTS idx_sanciones_fecha_modificacion ON sanciones(fecha_modificacion);

-- Add comment to sanciones table
ALTER TABLE sanciones COMMENT='Registro de sanciones con soporte de auditoría';
