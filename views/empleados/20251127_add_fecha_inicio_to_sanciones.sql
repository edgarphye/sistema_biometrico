-- Agrega la columna fecha_inicio a la tabla sanciones para corregir el error de ordenamiento
ALTER TABLE sanciones ADD COLUMN fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP;