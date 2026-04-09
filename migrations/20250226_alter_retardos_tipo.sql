-- Migración para asegurar que la columna tipo_retraso acepte valores genéricos
-- Se cambia a VARCHAR(50) para permitir tipos personalizados del catálogo

ALTER TABLE retardos MODIFY COLUMN tipo_retraso VARCHAR(50) NOT NULL DEFAULT 'retardo_menor';

-- Fin de la migración