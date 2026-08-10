-- Migración para actualizar la columna tipo_retraso con los valores extendidos del catálogo
-- Se cambia de ENUM('menor','mayor') al conjunto completo de clasificaciones

ALTER TABLE retardos MODIFY COLUMN tipo_retraso ENUM('normal','retardo_menor','retardo_mayor','falta','comision_entrada','comision_salida','comision_todo_dia','dia_economico','ausencia') NOT NULL;

-- Fin de la migración