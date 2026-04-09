-- Corrige typo en enum asistencia.tipo_asistencia: por_defnir -> por_definir

-- 1) Permitir ambos valores temporalmente
ALTER TABLE asistencia
MODIFY COLUMN tipo_asistencia ENUM(
  'normal',
  'por_defnir',
  'por_definir',
  'con_retardo',
  'con_ausencia',
  'sin_registro'
) NULL;

-- 2) Migrar datos existentes al valor correcto
UPDATE asistencia
SET tipo_asistencia = 'por_definir'
WHERE tipo_asistencia = 'por_defnir';

-- 3) Dejar solo el valor correcto en el enum
ALTER TABLE asistencia
MODIFY COLUMN tipo_asistencia ENUM(
  'normal',
  'por_definir',
  'con_retardo',
  'con_ausencia',
  'sin_registro'
) NULL;
