-- Agrega la columna fecha_asignacion a la tabla comisiones para corregir el error de ordenamiento
ALTER TABLE comisiones ADD COLUMN fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP;