-- Agrega columna dedicada para ID biométrico del empleado (ID reportado por ZKTeco)
ALTER TABLE empleados
    ADD COLUMN IF NOT EXISTS zkteo_id VARCHAR(50) NULL AFTER id;

-- Índice para acelerar correlación de registros del .dat con empleados
CREATE INDEX idx_empleados_zkteo_id ON empleados(zkteo_id);
