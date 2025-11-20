-- Migration: add modified_by and fecha_modificacion to sanciones
ALTER TABLE sanciones
    ADD COLUMN IF NOT EXISTS modified_by INT NULL,
    ADD COLUMN IF NOT EXISTS fecha_modificacion TIMESTAMP NULL;

-- Add index on modified_by
CREATE INDEX IF NOT EXISTS idx_sanciones_modified_by ON sanciones(modified_by);
